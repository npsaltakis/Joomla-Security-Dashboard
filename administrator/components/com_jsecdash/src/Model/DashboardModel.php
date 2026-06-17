<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dashboard overview model for com_jsecdash.
 *
 * @since  1.0.0
 */
class DashboardModel extends BaseDatabaseModel
{
    /**
     * @return  array
     *
     * @since   1.0.0
     */
    public function getStats(): array
    {
        $db  = $this->getDatabase();
        $now = Factory::getDate()->toSql();

        $activeBlocksQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jsecdash_blocks'))
            ->where('(' . $db->quoteName('expires') . ' IS NULL OR ' . $db->quoteName('expires') . ' > :now1)')
            ->bind(':now1', $now);
        $activeBlocks = (int) $db->setQuery($activeBlocksQuery)->loadResult();

        $dayAgo = Factory::getDate('-1 day')->toSql();

        $recentAttemptsQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jsecdash_attempts'))
            ->where($db->quoteName('attempt_time') . ' >= :dayAgo')
            ->bind(':dayAgo', $dayAgo);
        $recentAttempts = (int) $db->setQuery($recentAttemptsQuery)->loadResult();

        $filesTrackedQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jsecdash_filehashes'))
            ->where($db->quoteName('filepath') . ' != ' . $db->quote('::meta::baseline_time'));
        $filesTracked = (int) $db->setQuery($filesTrackedQuery)->loadResult();

        $baselineQuery = $db->getQuery(true)
            ->select($db->quoteName('hash'))
            ->from($db->quoteName('#__jsecdash_filehashes'))
            ->where($db->quoteName('filepath') . ' = ' . $db->quote('::meta::baseline_time'));
        $lastBaseline = $db->setQuery($baselineQuery)->loadResult();

        $topIpsQuery = $db->getQuery(true)
            ->select([$db->quoteName('ip'), 'COUNT(*) AS ' . $db->quoteName('total')])
            ->from($db->quoteName('#__jsecdash_attempts'))
            ->where($db->quoteName('attempt_time') . ' >= :dayAgo2')
            ->bind(':dayAgo2', $dayAgo)
            ->group($db->quoteName('ip'))
            ->order($db->quoteName('total') . ' DESC')
            ->setLimit(5);
        $topIps = $db->setQuery($topIpsQuery)->loadObjectList();

        $wafHits = 0;

        try {
            $wafQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('created') . ' >= :dayAgo3')
                ->bind(':dayAgo3', $dayAgo);
            $wafHits = (int) $db->setQuery($wafQuery)->loadResult();
        } catch (\Throwable $e) {
            // The firewall log table may not exist yet; treat as zero hits.
        }

        return [
            'active_blocks'   => $activeBlocks,
            'recent_attempts' => $recentAttempts,
            'files_tracked'   => $filesTracked,
            'last_baseline'   => $lastBaseline ?: null,
            'top_ips'         => $topIps ?: [],
            'waf_hits'        => $wafHits,
        ];
    }
}
