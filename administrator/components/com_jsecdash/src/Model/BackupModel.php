<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Database backup model for com_jsecdash.
 *
 * Produces a plain SQL dump of every table that belongs to this Joomla
 * installation (matched by table prefix) and stores it in a protected folder.
 *
 * @since  1.0.0
 */
class BackupModel extends BaseDatabaseModel
{
    /**
     * Returns the absolute path of the backups folder.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getBackupDir(): string
    {
        return JPATH_ROOT . '/administrator/components/com_jsecdash/backups';
    }

    /**
     * Ensures the backups folder exists and is protected from web access.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function ensureBackupDir(): void
    {
        $dir = $this->getBackupDir();

        if (!is_dir($dir)) {
            Folder::create($dir);
        }

        if (!is_file($dir . '/.htaccess')) {
            File::write($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }

        if (!is_file($dir . '/index.html')) {
            File::write($dir . '/index.html', '<!DOCTYPE html><title></title>');
        }
    }

    /**
     * Creates an SQL dump of all prefixed tables.
     *
     * @return  string|false  The backup file name on success, or false on failure.
     *
     * @since   1.0.0
     */
    public function createBackup()
    {
        if (\function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $this->ensureBackupDir();

        $db     = $this->getDatabase();
        $prefix = $db->getPrefix();
        $tables = $db->getTableList();

        if (empty($tables)) {
            return false;
        }

        $fileName = 'backup-' . Factory::getDate()->format('Ymd-His') . '.sql';
        $fullPath = $this->getBackupDir() . '/' . $fileName;

        $handle = @fopen($fullPath, 'w');

        if ($handle === false) {
            return false;
        }

        fwrite(
            $handle,
            "-- Joomla Security Dashboard database backup\n"
            . '-- Generated: ' . Factory::getDate()->format('Y-m-d H:i:s') . "\n"
            . '-- Prefix: ' . $prefix . "\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n"
        );

        foreach ($tables as $table) {
            // Only back up tables belonging to this Joomla installation.
            if ($prefix !== '' && strpos($table, $prefix) !== 0) {
                continue;
            }

            $this->dumpTable($handle, $table);
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return $fileName;
    }

    /**
     * Writes the structure and data for a single table to the open file handle.
     *
     * @param   resource  $handle  The open file handle.
     * @param   string    $table   The table name.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function dumpTable($handle, string $table): void
    {
        $db     = $this->getDatabase();
        $quoted = $db->quoteName($table);

        fwrite($handle, "\n-- --------------------------------------------------\n");
        fwrite($handle, '-- Table: ' . $table . "\n");
        fwrite($handle, "-- --------------------------------------------------\n");
        fwrite($handle, 'DROP TABLE IF EXISTS ' . $quoted . ";\n");

        $create = $db->setQuery('SHOW CREATE TABLE ' . $quoted)->loadRow();

        if (isset($create[1])) {
            fwrite($handle, $create[1] . ";\n\n");
        }

        $offset    = 0;
        $chunkSize = 500;

        while (true) {
            $rows = $db->setQuery('SELECT * FROM ' . $quoted, $offset, $chunkSize)->loadAssocList();

            if (empty($rows)) {
                break;
            }

            $columns    = array_keys($rows[0]);
            $columnList = implode(', ', array_map([$db, 'quoteName'], $columns));

            foreach ($rows as $row) {
                $values = [];

                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : $db->quote($value);
                }

                fwrite(
                    $handle,
                    'INSERT INTO ' . $quoted . ' (' . $columnList . ') VALUES (' . implode(', ', $values) . ");\n"
                );
            }

            $offset += $chunkSize;

            if (\count($rows) < $chunkSize) {
                break;
            }
        }

        fwrite($handle, "\n");
    }

    /**
     * Returns the list of existing backup files, newest first.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getBackups(): array
    {
        $dir = $this->getBackupDir();

        if (!is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (Folder::files($dir, '\.sql$', false, true) as $full) {
            $items[] = [
                'file' => basename($full),
                'size' => @filesize($full) ?: 0,
                'time' => Factory::getDate('@' . (@filemtime($full) ?: time()))->toSql(),
            ];
        }

        usort($items, static fn ($a, $b) => strcmp($b['file'], $a['file']));

        return $items;
    }

    /**
     * Returns the absolute path of a backup file after validating the name.
     *
     * @param   string  $name  The backup file name.
     *
     * @return  string|false
     *
     * @since   1.0.0
     */
    public function getBackupPath(string $name)
    {
        $name = basename($name);

        if (!preg_match('/^backup-[\w-]+\.sql$/', $name)) {
            return false;
        }

        $path = $this->getBackupDir() . '/' . $name;

        return is_file($path) ? $path : false;
    }

    /**
     * Deletes a backup file.
     *
     * @param   string  $name  The backup file name.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function deleteBackup(string $name): bool
    {
        $path = $this->getBackupPath($name);

        if ($path === false) {
            return false;
        }

        try {
            File::delete($path);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
