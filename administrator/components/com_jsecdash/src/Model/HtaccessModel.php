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
 * .htaccess generator model for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtaccessModel extends BaseDatabaseModel
{
    private const MARKER_START = '# BEGIN JSecDash';

    private const MARKER_END = '# END JSecDash';

    /**
     * @return  string
     *
     * @since   1.0.0
     */
    public function getCurrentContent(): string
    {
        $path = JPATH_ROOT . '/.htaccess';

        if (is_file($path)) {
            return (string) file_get_contents($path);
        }

        return '';
    }

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    public function getBackups(): array
    {
        $files = Folder::files(JPATH_ROOT, '^\.htaccess\.jsecdash-bak-.*$', false, true);

        if (!$files) {
            return [];
        }

        rsort($files);

        return array_map('basename', $files);
    }

    /**
     * Builds the managed rule block from the given options and writes it into .htaccess,
     * preserving any content outside the managed markers. A timestamped backup of the
     * previous file is created first.
     *
     * @param   array  $options  Generator options (boolean flags + custom_rules string).
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function generate(array $options): bool
    {
        $path = JPATH_ROOT . '/.htaccess';

        $existing = is_file($path) ? (string) file_get_contents($path) : '';

        if ($existing !== '') {
            $this->backup($existing);
        }

        $managedBlock = $this->buildBlock($options);
        $stripped     = $this->stripManagedBlock($existing);
        $newContent   = rtrim($stripped) . "\n\n" . $managedBlock . "\n";

        return (bool) File::write($path, ltrim($newContent));
    }

    /**
     * Restores the most recent backup over the current .htaccess file.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function restoreLatest(): bool
    {
        $backups = $this->getBackups();

        if (empty($backups)) {
            return false;
        }

        $latest = JPATH_ROOT . '/' . $backups[0];
        $target = JPATH_ROOT . '/.htaccess';

        try {
            return File::copy($latest, $target);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @param   string  $existing  The current .htaccess content to back up.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function backup(string $existing): void
    {
        $stamp = Factory::getDate()->format('Ymd-His');
        File::write(JPATH_ROOT . '/.htaccess.jsecdash-bak-' . $stamp, $existing);
    }

    /**
     * @param   string  $content  Full .htaccess content.
     *
     * @return  string  Content with the managed block removed.
     *
     * @since   1.0.0
     */
    private function stripManagedBlock(string $content): string
    {
        $pattern = '/' . preg_quote(self::MARKER_START, '/') . '.*?' . preg_quote(self::MARKER_END, '/') . '/s';

        return (string) preg_replace($pattern, '', $content);
    }

    /**
     * @param   array  $options  Generator options.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function buildBlock(array $options): string
    {
        $lines = [self::MARKER_START];

        if (!empty($options['disable_dir_listing'])) {
            $lines[] = 'Options -Indexes';
        }

        if (!empty($options['protect_config'])) {
            $lines[] = '<Files configuration.php>';
            $lines[] = '    Require all denied';
            $lines[] = '</Files>';
        }

        if (!empty($options['protect_htaccess'])) {
            $lines[] = '<Files .htaccess>';
            $lines[] = '    Require all denied';
            $lines[] = '</Files>';
        }

        if (!empty($options['block_xmlrpc'])) {
            $lines[] = '<Files xmlrpc.php>';
            $lines[] = '    Require all denied';
            $lines[] = '</Files>';
        }

        if (!empty($options['block_sensitive_files'])) {
            $lines[] = '<FilesMatch "(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json|\.sql|\.log|\.ini)$">';
            $lines[] = '    Require all denied';
            $lines[] = '</FilesMatch>';
        }

        if (!empty($options['custom_rules'])) {
            $lines[] = trim((string) $options['custom_rules']);
        }

        $lines[] = self::MARKER_END;

        return implode("\n", $lines);
    }
}
