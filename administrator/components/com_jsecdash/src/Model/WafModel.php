<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Application Firewall log model for com_jsecdash.
 *
 * Reads the events recorded by the system plugin's firewall engine so they can
 * be reviewed, summarised and cleared from inside the dashboard. Every query is
 * defensive about the log table being absent (e.g. before the plugin's install
 * SQL has run) so the view never fatals.
 *
 * @since  1.0.0
 */
class WafModel extends BaseDatabaseModel
{
    /**
     * Returns the configured firewall mode (off|detect|block) read from the
     * system plugin parameters.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getMode(): string
    {
        if (!PluginHelper::isEnabled('system', 'jsecdash')) {
            return 'off';
        }

        $plugin = PluginHelper::getPlugin('system', 'jsecdash');
        $params = new Registry($plugin->params ?? '');

        return (string) $params->get('waf_mode', 'detect');
    }

    /**
     * Returns headline figures for the last 24 hours.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getSummary(): array
    {
        $default = ['total' => 0, 'blocked' => 0, 'detected' => 0, 'ips' => 0];

        $db     = $this->getDatabase();
        $dayAgo = Factory::getDate('-1 day')->toSql();

        try {
            $query = $db->getQuery(true)
                ->select(
                    [
                        'COUNT(*) AS ' . $db->quoteName('total'),
                        'SUM(CASE WHEN ' . $db->quoteName('action') . ' = ' . $db->quote('blocked') . ' THEN 1 ELSE 0 END) AS ' . $db->quoteName('blocked'),
                        'SUM(CASE WHEN ' . $db->quoteName('action') . ' = ' . $db->quote('detected') . ' THEN 1 ELSE 0 END) AS ' . $db->quoteName('detected'),
                        'COUNT(DISTINCT ' . $db->quoteName('ip') . ') AS ' . $db->quoteName('ips'),
                    ]
                )
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('created') . ' >= :dayAgo')
                ->bind(':dayAgo', $dayAgo);

            $row = $db->setQuery($query)->loadAssoc();
        } catch (\Throwable $e) {
            return $default;
        }

        if (!$row) {
            return $default;
        }

        return [
            'total'    => (int) $row['total'],
            'blocked'  => (int) $row['blocked'],
            'detected' => (int) $row['detected'],
            'ips'      => (int) $row['ips'],
        ];
    }

    /**
     * Returns the number of events per category over the last 7 days.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getCategoryBreakdown(): array
    {
        $db      = $this->getDatabase();
        $weekAgo = Factory::getDate('-7 day')->toSql();

        try {
            $query = $db->getQuery(true)
                ->select([$db->quoteName('category'), 'COUNT(*) AS ' . $db->quoteName('total')])
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('created') . ' >= :weekAgo')
                ->bind(':weekAgo', $weekAgo)
                ->group($db->quoteName('category'))
                ->order($db->quoteName('total') . ' DESC');

            return $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Returns the most frequently triggered rules over the last 7 days.
     *
     * @param   integer  $limit  Maximum number of rules to return.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getTopRules(int $limit = 10): array
    {
        $db      = $this->getDatabase();
        $weekAgo = Factory::getDate('-7 day')->toSql();

        try {
            $query = $db->getQuery(true)
                ->select(
                    [
                        $db->quoteName('rule_id'),
                        $db->quoteName('category'),
                        'COUNT(*) AS ' . $db->quoteName('total'),
                    ]
                )
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('created') . ' >= :weekAgo')
                ->bind(':weekAgo', $weekAgo)
                ->group([$db->quoteName('rule_id'), $db->quoteName('category')])
                ->order($db->quoteName('total') . ' DESC')
                ->setLimit($limit);

            return $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Returns the most recent firewall events.
     *
     * @param   integer  $limit  Maximum number of rows to return.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getItems(int $limit = 100): array
    {
        $db = $this->getDatabase();

        try {
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->order($db->quoteName('id') . ' DESC')
                ->setLimit($limit);

            return $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Deletes firewall log entries. When $days is greater than zero only entries
     * older than that many days are removed; otherwise the whole log is cleared.
     *
     * @param   integer  $days  Age threshold in days, or 0 to clear everything.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function clearLog(int $days = 0): bool
    {
        $db = $this->getDatabase();

        try {
            $query = $db->getQuery(true)->delete($db->quoteName('#__jsecdash_waf_log'));

            if ($days > 0) {
                $threshold = Factory::getDate('-' . $days . ' day')->toSql();
                $query->where($db->quoteName('created') . ' < :threshold')->bind(':threshold', $threshold);
            }

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
