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
 * Security health-check model for com_jsecdash.
 *
 * Runs a series of lightweight environment and configuration checks and
 * produces a list of results together with an overall security score.
 *
 * @since  1.0.0
 */
class HealthcheckModel extends BaseDatabaseModel
{
    public const STATUS_PASS = 'pass';
    public const STATUS_WARN = 'warn';
    public const STATUS_FAIL = 'fail';

    /**
     * Runs every check and returns the structured results.
     *
     * @return  array  ['checks' => [...], 'score' => int, 'counts' => [...]]
     *
     * @since   1.0.0
     */
    public function getReport(): array
    {
        $checks = array_merge(
            $this->checkPhp(),
            $this->checkJoomlaConfig(),
            $this->checkFilesystem(),
            $this->checkPlugin()
        );

        $score = $this->calculateScore($checks);

        $counts = [
            self::STATUS_PASS => 0,
            self::STATUS_WARN => 0,
            self::STATUS_FAIL => 0,
        ];

        foreach ($checks as $check) {
            $counts[$check['status']]++;
        }

        return [
            'checks' => $checks,
            'score'  => $score,
            'counts' => $counts,
        ];
    }

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    private function checkPhp(): array
    {
        $checks = [];

        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');

        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_PHP_VERSION',
            $phpOk ? self::STATUS_PASS : self::STATUS_FAIL,
            PHP_VERSION,
            'COM_JSECDASH_HEALTH_PHP_VERSION_REC'
        );

        $displayErrors = strtolower((string) ini_get('display_errors'));
        $deOff         = \in_array($displayErrors, ['', '0', 'off', 'false'], true);

        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_DISPLAY_ERRORS',
            $deOff ? self::STATUS_PASS : self::STATUS_WARN,
            $deOff ? 'Off' : 'On',
            'COM_JSECDASH_HEALTH_DISPLAY_ERRORS_REC'
        );

        $allowUrlFopen = (bool) ini_get('allow_url_fopen');
        $checks[]      = $this->result(
            'COM_JSECDASH_HEALTH_ALLOW_URL_FOPEN',
            $allowUrlFopen ? self::STATUS_WARN : self::STATUS_PASS,
            $allowUrlFopen ? 'On' : 'Off',
            'COM_JSECDASH_HEALTH_ALLOW_URL_FOPEN_REC'
        );

        return $checks;
    }

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    private function checkJoomlaConfig(): array
    {
        $app    = Factory::getApplication();
        $checks = [];

        $debug    = (int) $app->get('debug', 0);
        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_DEBUG',
            $debug ? self::STATUS_FAIL : self::STATUS_PASS,
            $debug ? 'On' : 'Off',
            'COM_JSECDASH_HEALTH_DEBUG_REC'
        );

        $errorReporting = (string) $app->get('error_reporting', 'default');
        $erOk           = \in_array($errorReporting, ['none', 'default'], true);
        $checks[]       = $this->result(
            'COM_JSECDASH_HEALTH_ERROR_REPORTING',
            $erOk ? self::STATUS_PASS : self::STATUS_WARN,
            $errorReporting,
            'COM_JSECDASH_HEALTH_ERROR_REPORTING_REC'
        );

        $sef      = (int) $app->get('sef', 0);
        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_SEF',
            $sef ? self::STATUS_PASS : self::STATUS_WARN,
            $sef ? 'On' : 'Off',
            'COM_JSECDASH_HEALTH_SEF_REC'
        );

        $forceSsl = (int) $app->get('force_ssl', 0);
        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_FORCE_SSL',
            $forceSsl >= 2 ? self::STATUS_PASS : self::STATUS_WARN,
            $forceSsl >= 2 ? 'On' : 'Off',
            'COM_JSECDASH_HEALTH_FORCE_SSL_REC'
        );

        return $checks;
    }

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    private function checkFilesystem(): array
    {
        $checks = [];

        $configFile     = JPATH_CONFIGURATION . '/configuration.php';
        $configWritable = is_writable($configFile);
        $checks[]       = $this->result(
            'COM_JSECDASH_HEALTH_CONFIG_WRITABLE',
            $configWritable ? self::STATUS_WARN : self::STATUS_PASS,
            $configWritable ? 'Writable' : 'Read-only',
            'COM_JSECDASH_HEALTH_CONFIG_WRITABLE_REC'
        );

        $htaccess = is_file(JPATH_ROOT . '/.htaccess');
        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_HTACCESS',
            $htaccess ? self::STATUS_PASS : self::STATUS_WARN,
            $htaccess ? 'Present' : 'Missing',
            'COM_JSECDASH_HEALTH_HTACCESS_REC'
        );

        return $checks;
    }

    /**
     * @return  array
     *
     * @since   1.0.0
     */
    private function checkPlugin(): array
    {
        $checks  = [];
        $enabled = PluginHelper::isEnabled('system', 'jsecdash');

        $checks[] = $this->result(
            'COM_JSECDASH_HEALTH_PLUGIN_ENABLED',
            $enabled ? self::STATUS_PASS : self::STATUS_FAIL,
            $enabled ? 'Enabled' : 'Disabled',
            'COM_JSECDASH_HEALTH_PLUGIN_ENABLED_REC'
        );

        if ($enabled) {
            $plugin = PluginHelper::getPlugin('system', 'jsecdash');
            $params = new Registry($plugin->params ?? '');

            $ipBlock  = (int) $params->get('enable_ip_block', 1);
            $checks[] = $this->result(
                'COM_JSECDASH_HEALTH_IP_BLOCK',
                $ipBlock ? self::STATUS_PASS : self::STATUS_WARN,
                $ipBlock ? 'On' : 'Off',
                'COM_JSECDASH_HEALTH_IP_BLOCK_REC'
            );

            $secret   = trim((string) $params->get('admin_secret_key', ''));
            $checks[] = $this->result(
                'COM_JSECDASH_HEALTH_ADMIN_SECRET',
                $secret !== '' ? self::STATUS_PASS : self::STATUS_WARN,
                $secret !== '' ? 'Configured' : 'Not set',
                'COM_JSECDASH_HEALTH_ADMIN_SECRET_REC'
            );
        }

        return $checks;
    }

    /**
     * Builds a single check result row.
     *
     * @param   string  $labelKey       Language key for the check name.
     * @param   string  $status         One of the STATUS_* constants.
     * @param   string  $value          The detected value.
     * @param   string  $recommendation Language key for the recommendation.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function result(string $labelKey, string $status, string $value, string $recommendation): array
    {
        return [
            'label'          => $labelKey,
            'status'         => $status,
            'value'          => $value,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Computes a 0-100 score where a passing check counts fully, a warning
     * counts half and a failing check counts zero.
     *
     * @param   array  $checks  The list of check results.
     *
     * @return  integer
     *
     * @since   1.0.0
     */
    private function calculateScore(array $checks): int
    {
        $total = \count($checks);

        if ($total === 0) {
            return 100;
        }

        $points = 0.0;

        foreach ($checks as $check) {
            if ($check['status'] === self::STATUS_PASS) {
                $points += 1;
            } elseif ($check['status'] === self::STATUS_WARN) {
                $points += 0.5;
            }
        }

        return (int) round(($points / $total) * 100);
    }
}
