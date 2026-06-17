<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * IP blocks model for com_jsecdash.
 *
 * @since  1.0.0
 */
class BlocksModel extends BaseDatabaseModel
{
    /**
     * @return  array
     *
     * @since   1.0.0
     */
    public function getItems(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__jsecdash_blocks'))
            ->order($db->quoteName('created') . ' DESC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Returns the most active offending IPs based on failed login attempts in the last 24 hours.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getRecentAttempts(): array
    {
        $db     = $this->getDatabase();
        $dayAgo = Factory::getDate('-1 day')->toSql();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('ip'),
                'COUNT(*) AS ' . $db->quoteName('total'),
                'MAX(' . $db->quoteName('attempt_time') . ') AS ' . $db->quoteName('last_attempt'),
            ])
            ->from($db->quoteName('#__jsecdash_attempts'))
            ->where($db->quoteName('attempt_time') . ' >= :dayAgo')
            ->bind(':dayAgo', $dayAgo)
            ->group($db->quoteName('ip'))
            ->order($db->quoteName('total') . ' DESC')
            ->setLimit(25);

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Adds (or refreshes) a manual block for an IP address.
     *
     * @param   string   $ip              The IP address to block.
     * @param   string   $reason          Reason for the block.
     * @param   integer  $durationMinutes Minutes until the block expires; 0 means permanent.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function addBlock(string $ip, string $reason, int $durationMinutes = 0): bool
    {
        $ip = trim($ip);

        if (!self::isValidIpPattern($ip)) {
            return false;
        }

        $db      = $this->getDatabase();
        $now     = Factory::getDate();
        $expires = $durationMinutes > 0 ? $now->add(new \DateInterval('PT' . $durationMinutes . 'M'))->toSql() : null;

        $existingQuery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__jsecdash_blocks'))
            ->where($db->quoteName('ip') . ' = :ip')
            ->bind(':ip', $ip);
        $id = $db->setQuery($existingQuery)->loadResult();

        if ($id) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__jsecdash_blocks'))
                ->set($db->quoteName('reason') . ' = :reason')
                ->set($db->quoteName('auto') . ' = 0')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':reason', $reason)
                ->bind(':id', $id, ParameterType::INTEGER);

            if ($expires === null) {
                $query->set($db->quoteName('expires') . ' = NULL');
            } else {
                $query->set($db->quoteName('expires') . ' = :expires')->bind(':expires', $expires);
            }

            $db->setQuery($query)->execute();

            return true;
        }

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__jsecdash_blocks'))
            ->columns([
                $db->quoteName('ip'),
                $db->quoteName('reason'),
                $db->quoteName('created'),
                $db->quoteName('expires'),
                $db->quoteName('auto'),
            ])
            ->values(
                implode(
                    ',',
                    [
                        $db->quote($ip),
                        $db->quote($reason),
                        $db->quote($now->toSql()),
                        $expires === null ? 'NULL' : $db->quote($expires),
                        0,
                    ]
                )
            );

        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Validates that a string is a single IP address, a CIDR range
     * (e.g. 192.168.0.0/24) or a hyphenated range (e.g. 10.0.0.1-10.0.0.50).
     *
     * @param   string  $pattern  The pattern to validate.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public static function isValidIpPattern(string $pattern): bool
    {
        $pattern = trim($pattern);

        if ($pattern === '') {
            return false;
        }

        // Plain IP address (v4 or v6).
        if (filter_var($pattern, FILTER_VALIDATE_IP)) {
            return true;
        }

        // CIDR notation: address/prefix.
        if (strpos($pattern, '/') !== false) {
            [$address, $prefix] = explode('/', $pattern, 2);

            return filter_var($address, FILTER_VALIDATE_IP) !== false
                && ctype_digit($prefix)
                && (int) $prefix >= 0
                && (int) $prefix <= 128;
        }

        // Hyphenated range: start-end.
        if (strpos($pattern, '-') !== false) {
            [$start, $end] = explode('-', $pattern, 2);

            return filter_var(trim($start), FILTER_VALIDATE_IP) !== false
                && filter_var(trim($end), FILTER_VALIDATE_IP) !== false;
        }

        return false;
    }

    /**
     * Deletes blocks by id.
     *
     * @param   array  $ids  Array of block ids to delete.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function deleteBlocks(array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__jsecdash_blocks'))
            ->whereIn($db->quoteName('id'), $ids);

        $db->setQuery($query)->execute();
    }
}
