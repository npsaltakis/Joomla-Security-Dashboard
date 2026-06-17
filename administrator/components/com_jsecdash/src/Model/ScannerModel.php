<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * File integrity scanner model for com_jsecdash.
 *
 * Tracks PHP/JS files under the site's extension folders (components, modules,
 * plugins, templates, language) so that unexpected additions, modifications or
 * deletions can be detected between a saved baseline and the current state.
 * Core library and vendor folders are intentionally excluded to keep scans fast.
 *
 * @since  1.0.0
 */
class ScannerModel extends BaseDatabaseModel
{
    private const META_KEY = '::meta::baseline_time';

    private const SCAN_ROOTS = [
        'components',
        'modules',
        'plugins',
        'templates',
        'language',
        'administrator/components',
        'administrator/modules',
        'administrator/templates',
        'administrator/language',
    ];

    private const SCAN_EXTENSIONS = ['php', 'js'];

    private const EXCLUDED_DIRS = ['cache', 'tmp', 'logs', '.git', 'node_modules'];

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    public function getBaselineInfo(): array
    {
        $db = $this->getDatabase();

        $metaKey = self::META_KEY;

        $metaQuery = $db->getQuery(true)
            ->select($db->quoteName('hash'))
            ->from($db->quoteName('#__jsecdash_filehashes'))
            ->where($db->quoteName('filepath') . ' = :meta')
            ->bind(':meta', $metaKey);
        $baselineTime = $db->setQuery($metaQuery)->loadResult();

        $countQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jsecdash_filehashes'))
            ->where($db->quoteName('filepath') . ' != :meta2')
            ->bind(':meta2', $metaKey);
        $fileCount = (int) $db->setQuery($countQuery)->loadResult();

        return [
            'baseline_time' => $baselineTime ?: null,
            'file_count'    => $fileCount,
        ];
    }

    /**
     * Recomputes hashes for all tracked files and stores them as the new baseline.
     *
     * @return  integer  Number of files hashed.
     *
     * @since   1.0.0
     */
    public function baseline(): int
    {
        if (\function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $files = $this->collectFiles();
        $db    = $this->getDatabase();

        $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__jsecdash_filehashes')))->execute();

        $now = Factory::getDate();

        foreach (array_chunk($files, 200, true) as $chunk) {
            $query = $db->getQuery(true)->insert($db->quoteName('#__jsecdash_filehashes'))
                ->columns([
                    $db->quoteName('filepath'),
                    $db->quoteName('hash'),
                    $db->quoteName('filesize'),
                    $db->quoteName('modified'),
                ]);

            foreach ($chunk as $relativePath => $info) {
                $query->values(
                    implode(
                        ',',
                        [
                            $db->quote($relativePath),
                            $db->quote($info['hash']),
                            (int) $info['size'],
                            $db->quote($info['modified']),
                        ]
                    )
                );
            }

            $db->setQuery($query)->execute();
        }

        $metaQuery = $db->getQuery(true)->insert($db->quoteName('#__jsecdash_filehashes'))
            ->columns([
                $db->quoteName('filepath'),
                $db->quoteName('hash'),
                $db->quoteName('filesize'),
                $db->quoteName('modified'),
            ])
            ->values(
                implode(
                    ',',
                    [
                        $db->quote(self::META_KEY),
                        $db->quote($now->toSql()),
                        0,
                        $db->quote($now->toSql()),
                    ]
                )
            );
        $db->setQuery($metaQuery)->execute();

        return \count($files);
    }

    /**
     * Compares the current state of tracked files against the saved baseline.
     *
     * @return  array  ['added' => [...], 'modified' => [...], 'deleted' => [...]]
     *
     * @since   1.0.0
     */
    public function scan(): array
    {
        if (\function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $current = $this->collectFiles();

        $db      = $this->getDatabase();
        $metaKey = self::META_KEY;
        $query   = $db->getQuery(true)
            ->select([$db->quoteName('filepath'), $db->quoteName('hash')])
            ->from($db->quoteName('#__jsecdash_filehashes'))
            ->where($db->quoteName('filepath') . ' != :meta')
            ->bind(':meta', $metaKey);
        $rows = $db->setQuery($query)->loadObjectList();

        $baseline = [];

        foreach ($rows as $row) {
            $baseline[$row->filepath] = $row->hash;
        }

        $added    = [];
        $modified = [];
        $deleted  = [];

        foreach ($current as $path => $info) {
            if (!isset($baseline[$path])) {
                $added[] = $path;
            } elseif ($baseline[$path] !== $info['hash']) {
                $modified[] = $path;
            }
        }

        $currentPaths = array_keys($current);

        foreach (array_keys($baseline) as $path) {
            if (!\in_array($path, $currentPaths, true)) {
                $deleted[] = $path;
            }
        }

        sort($added);
        sort($modified);
        sort($deleted);

        return [
            'added'    => $added,
            'modified' => $modified,
            'deleted'  => $deleted,
            'total'    => \count($current),
        ];
    }

    /**
     * Walks the configured scan roots and returns a [relativePath => ['hash' => ..., 'size' => ..., 'modified' => ...]] map.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function collectFiles(): array
    {
        $files = [];

        foreach (self::SCAN_ROOTS as $root) {
            $absoluteRoot = JPATH_ROOT . '/' . $root;

            if (!is_dir($absoluteRoot)) {
                continue;
            }

            $excludedDirs = $this->getExcludedDirs();
            $extensions   = $this->getScanExtensions();

            $filter = new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($absoluteRoot, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $current) use ($excludedDirs) {
                    if ($current->isDir()) {
                        return !\in_array($current->getFilename(), $excludedDirs, true);
                    }

                    return true;
                }
            );

            $iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    continue;
                }

                $extension = strtolower($fileInfo->getExtension());

                if (!\in_array($extension, $extensions, true)) {
                    continue;
                }

                $realPath = $fileInfo->getPathname();
                $relative = ltrim(str_replace('\\', '/', str_replace(JPATH_ROOT, '', $realPath)), '/');

                $hash = hash_file('sha256', $realPath);

                if ($hash === false) {
                    continue;
                }

                $files[$relative] = [
                    'hash'     => $hash,
                    'size'     => $fileInfo->getSize(),
                    'modified' => Factory::getDate('@' . $fileInfo->getMTime())->toSql(),
                ];
            }
        }

        return $files;
    }

    /**
     * Returns the absolute path of the quarantine folder.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function getQuarantineDir(): string
    {
        return JPATH_ROOT . '/administrator/components/com_jsecdash/quarantine';
    }

    /**
     * Ensures the quarantine folder exists and is protected from web access.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function ensureQuarantineDir(): void
    {
        $dir = $this->getQuarantineDir();

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
     * Moves a suspicious file into the quarantine folder. The original path is
     * encoded into the quarantined file name so it can be restored later.
     *
     * @param   string  $relativePath  Path of the file relative to the site root.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function quarantineFile(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '') {
            return false;
        }

        $source = realpath(JPATH_ROOT . '/' . $relativePath);
        $root   = realpath(JPATH_ROOT);

        // The file must exist and live inside the site root.
        if ($source === false || $root === false || !is_file($source) || strpos($source, $root) !== 0) {
            return false;
        }

        // Never quarantine the dashboard's own files.
        if (strpos(str_replace('\\', '/', $source), 'administrator/components/com_jsecdash') !== false) {
            return false;
        }

        $this->ensureQuarantineDir();

        $token = Factory::getDate()->format('Ymd-His')
            . '__' . rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=')
            . '.quarantine';

        $dest = $this->getQuarantineDir() . '/' . $token;

        try {
            File::move($source, $dest);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the list of quarantined files together with their original paths.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getQuarantinedFiles(): array
    {
        $dir = $this->getQuarantineDir();

        if (!is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (Folder::files($dir, '\.quarantine$', false, true) as $full) {
            $name     = basename($full);
            $encoded  = substr($name, strpos($name, '__') + 2, -\strlen('.quarantine'));
            $original = base64_decode(strtr($encoded, '-_', '+/')) ?: $name;

            $items[] = [
                'file'     => $name,
                'original' => $original,
                'size'     => @filesize($full) ?: 0,
                'time'     => Factory::getDate('@' . (@filemtime($full) ?: time()))->toSql(),
            ];
        }

        return $items;
    }

    /**
     * Restores a quarantined file back to its original location.
     *
     * @param   string  $name  The quarantined file name.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function restoreQuarantined(string $name): bool
    {
        $name = basename($name);

        if (!preg_match('/\.quarantine$/', $name)) {
            return false;
        }

        $source = $this->getQuarantineDir() . '/' . $name;

        if (!is_file($source)) {
            return false;
        }

        $encoded  = substr($name, strpos($name, '__') + 2, -\strlen('.quarantine'));
        $original = base64_decode(strtr($encoded, '-_', '+/'));

        if (!$original) {
            return false;
        }

        $original = ltrim(str_replace('\\', '/', $original), '/');
        $dest     = JPATH_ROOT . '/' . $original;
        $root     = realpath(JPATH_ROOT);
        $destDir  = realpath(\dirname($dest));

        if ($root === false || $destDir === false || strpos($destDir, $root) !== 0) {
            return false;
        }

        try {
            File::move($source, $dest);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Permanently deletes a quarantined file.
     *
     * @param   string  $name  The quarantined file name.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function deleteQuarantined(string $name): bool
    {
        $name = basename($name);

        if (!preg_match('/\.quarantine$/', $name)) {
            return false;
        }

        $source = $this->getQuarantineDir() . '/' . $name;

        if (!is_file($source)) {
            return false;
        }

        try {
            File::delete($source);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the list of file extensions to scan, read from the component
     * options with a sensible fallback to the built-in defaults.
     *
     * @return  string[]
     *
     * @since   1.0.0
     */
    private function getScanExtensions(): array
    {
        $configured = (string) ComponentHelper::getParams('com_jsecdash')->get('scan_extensions', '');
        $list       = array_filter(array_map('trim', explode(',', strtolower($configured))));

        return $list ?: self::SCAN_EXTENSIONS;
    }

    /**
     * Returns the list of directory names to skip while scanning, read from the
     * component options with a sensible fallback to the built-in defaults.
     *
     * @return  string[]
     *
     * @since   1.0.0
     */
    private function getExcludedDirs(): array
    {
        $configured = (string) ComponentHelper::getParams('com_jsecdash')->get('excluded_dirs', '');
        $list       = array_filter(array_map('trim', explode(',', $configured)));

        return $list ?: self::EXCLUDED_DIRS;
    }
}
