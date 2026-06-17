<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Audit log model for com_jsecdash.
 *
 * Reads the core User Actions Log table (#__action_logs) so administrators can
 * review recent security-relevant activity from inside the dashboard.
 *
 * @since  1.0.0
 */
class AuditlogModel extends BaseDatabaseModel
{
    /**
     * Returns the most recent action-log entries together with the acting
     * user's name.
     *
     * @param   integer  $limit  Maximum number of rows to return.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getItems(int $limit = 50): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.message_language_key'),
                    $db->quoteName('a.message'),
                    $db->quoteName('a.log_date'),
                    $db->quoteName('a.extension'),
                    $db->quoteName('a.user_id'),
                    $db->quoteName('a.ip_address'),
                    $db->quoteName('u.name', 'user_name'),
                ]
            )
            ->from($db->quoteName('#__action_logs', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
            ->order($db->quoteName('a.id') . ' DESC')
            ->setLimit($limit);

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Indicates whether the core User Actions Log plugin is enabled, so the
     * view can hint the administrator to switch it on if no data is recorded.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function isLoggingEnabled(): bool
    {
        return PluginHelper::isEnabled('actionlog', 'joomla');
    }

    /**
     * Returns the number of recorded action-log entries.
     *
     * @return  integer
     *
     * @since   1.0.0
     */
    public function getTotal(): int
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__action_logs'));

        return (int) $db->setQuery($query)->loadResult();
    }
}
