<?php

/**
 * @package     Joomla.Security.Dashboard
 * @subpackage  System.jsecdash
 */

namespace Joomla\Plugin\System\Jsecdash\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Event\User\LoginEvent;
use Joomla\CMS\Event\User\LoginFailureEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\Jsecdash\Waf\WafEngine;
use Joomla\Utilities\IpHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla Security Dashboard system plugin.
 *
 * Enforces IP blocks on every request and tracks failed login attempts,
 * automatically locking out an IP address that exceeds the configured
 * attempt threshold within the configured time window.
 *
 * @since  1.0.0
 */
final class Jsecdash extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * @return array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise'   => 'onAfterInitialise',
            'onUserLoginFailure'  => 'onUserLoginFailure',
            'onUserLogin'         => 'onUserLogin',
        ];
    }

    /**
     * Blocks the request early if the visitor's IP address is currently blocked.
     *
     * @param   AfterInitialiseEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterInitialise(AfterInitialiseEvent $event): void
    {
        $ip = IpHelper::getIp();

        // Admin secret URL protection runs first so it can hide the back-end entirely.
        $this->enforceAdminSecret($ip);

        // Whitelisted IPs bypass both the IP block list and the web application firewall.
        if (!empty($ip) && $this->isWhitelisted($ip)) {
            return;
        }

        // Enforce the IP block list.
        if ($this->params->get('enable_ip_block', 1) && !empty($ip) && $this->isBlocked($ip)) {
            $this->denyRequest('Access denied.');
        }

        // Run the web application firewall on the request payload.
        $this->runWaf($ip);
    }

    /**
     * Inspects the current request with the WAF engine and, depending on the
     * configured mode, logs and/or blocks malicious requests. Repeat offenders
     * are escalated to a full IP block once the configured threshold is reached.
     *
     * @param   string  $ip  The visitor's IP address.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function runWaf(string $ip): void
    {
        $mode = (string) $this->params->get('waf_mode', 'detect');

        if ($mode === 'off') {
            return;
        }

        // Optionally trust logged-in administrators to avoid false positives on
        // legitimate back-end content (articles, custom HTML, SQL snippets, ...).
        if ((int) $this->params->get('waf_trust_admins', 1) && $this->isTrustedAdmin()) {
            return;
        }

        $categories = [];

        foreach (['sqli', 'xss', 'lfi', 'cmdi', 'scanner', 'exploit'] as $category) {
            if ((int) $this->params->get('waf_cat_' . $category, 1)) {
                $categories[] = $category;
            }
        }

        $custom = preg_split('/[\r\n]+/', (string) $this->params->get('waf_custom_rules', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (empty($categories) && empty($custom)) {
            return;
        }

        $engine = new WafEngine($categories, $custom);

        $params = WafEngine::flatten($_GET)
            + WafEngine::flatten($_POST)
            + WafEngine::flatten($_COOKIE)
            + $this->jsonBodyParams();

        $uri     = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ua      = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $headers = $this->inspectableHeaders();

        $hit = $engine->inspect($params, $uri, $ua, $headers);

        if ($hit === null) {
            return;
        }

        $action = $mode === 'block' ? 'blocked' : 'detected';

        $this->logWafHit($ip, $hit, $uri, $action);

        if ($mode === 'block') {
            $this->escalate($ip);

            $this->denyRequest('Request blocked by security policy.');
        }
    }

    /**
     * Determines whether the current visitor is a logged-in user that is allowed
     * to reach the administrator application, in which case the WAF can be skipped
     * to avoid false positives on legitimate back-end content.
     *
     * @return  boolean
     *
     * @since   1.0.4
     */
    private function isTrustedAdmin(): bool
    {
        try {
            $user = $this->getApplication()->getIdentity();

            return $user !== null && !$user->guest && $user->authorise('core.login.admin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Returns the subset of request headers the WAF should inspect, keyed by a
     * human-readable header name. Empty headers are omitted.
     *
     * @return  array<string, string>
     *
     * @since   1.0.4
     */
    private function inspectableHeaders(): array
    {
        $map = [
            'HTTP_REFERER'          => 'Referer',
            'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
            'HTTP_X_REAL_IP'        => 'X-Real-IP',
            'HTTP_X_FORWARDED_HOST' => 'X-Forwarded-Host',
            'HTTP_ORIGIN'           => 'Origin',
        ];

        $headers = [];

        foreach ($map as $server => $name) {
            if (!empty($_SERVER[$server])) {
                $headers[$name] = (string) $_SERVER[$server];
            }
        }

        return $headers;
    }

    /**
     * Decodes a JSON request body (REST/AJAX calls) into a flat parameter map so
     * the WAF can inspect it. Returns an empty array for non-JSON or oversized bodies.
     *
     * @return  array<string, string>
     *
     * @since   1.0.4
     */
    private function jsonBodyParams(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (strpos($contentType, 'json') === false) {
            return [];
        }

        $raw = file_get_contents('php://input');

        // Skip empty or oversized bodies to keep the inspection cost bounded.
        if ($raw === false || $raw === '' || \strlen($raw) > 262144) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return \is_array($decoded) ? WafEngine::flatten($decoded) : [];
    }

    /**
     * Records a WAF detection in the log table. Failures are swallowed so a
     * logging problem can never break the request being processed.
     *
     * @param   string  $ip      The offending IP address.
     * @param   array   $hit     The matched rule details.
     * @param   string  $uri     The request URI.
     * @param   string  $action  Either 'detected' or 'blocked'.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function logWafHit(string $ip, array $hit, string $uri, string $action): void
    {
        try {
            $db  = $this->getDatabase();
            $now = Factory::getDate();

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__jsecdash_waf_log'))
                ->columns([
                    $db->quoteName('ip'),
                    $db->quoteName('rule_id'),
                    $db->quoteName('category'),
                    $db->quoteName('matched_field'),
                    $db->quoteName('payload'),
                    $db->quoteName('uri'),
                    $db->quoteName('action'),
                    $db->quoteName('created'),
                ])
                ->values(
                    implode(
                        ',',
                        [
                            $db->quote($ip),
                            $db->quote($hit['rule_id']),
                            $db->quote($hit['category']),
                            $db->quote(substr($hit['field'], 0, 100)),
                            $db->quote($hit['snippet']),
                            $db->quote(substr($uri, 0, 500)),
                            $db->quote($action),
                            $db->quote($now->toSql()),
                        ]
                    )
                );

            $db->setQuery($query)->execute();

            // Occasionally prune old rows so the log table stays bounded.
            if (random_int(1, 100) === 1) {
                $this->purgeOldWafLogs();
            }
        } catch (\Throwable $e) {
            // Never let a logging failure interrupt request handling.
        }
    }

    /**
     * Deletes WAF log rows older than the configured retention period. A
     * retention of 0 days disables pruning. Best-effort; failures are ignored.
     *
     * @return  void
     *
     * @since   1.0.4
     */
    private function purgeOldWafLogs(): void
    {
        $days = (int) $this->params->get('waf_log_retention_days', 30);

        if ($days <= 0) {
            return;
        }

        try {
            $db     = $this->getDatabase();
            $cutoff = Factory::getDate()->sub(new \DateInterval('P' . $days . 'D'))->toSql();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('created') . ' < :cutoff')
                ->bind(':cutoff', $cutoff);

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            // Best-effort cleanup.
        }
    }

    /**
     * Promotes an IP address to a full, time-limited block once it has tripped
     * the WAF more than the configured number of times within the escalation
     * window. A threshold of 0 disables escalation.
     *
     * @param   string  $ip  The offending IP address.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function escalate(string $ip): void
    {
        $threshold = (int) $this->params->get('waf_escalate_threshold', 10);

        if ($threshold <= 0 || empty($ip)) {
            return;
        }

        try {
            $db          = $this->getDatabase();
            $windowMin   = (int) $this->params->get('waf_escalate_window', 60);
            $windowStart = Factory::getDate()->sub(new \DateInterval('PT' . max(1, $windowMin) . 'M'))->toSql();

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__jsecdash_waf_log'))
                ->where($db->quoteName('ip') . ' = :ip')
                ->where($db->quoteName('created') . ' >= :windowStart')
                ->bind(':ip', $ip)
                ->bind(':windowStart', $windowStart);

            if ((int) $db->setQuery($query)->loadResult() >= $threshold) {
                $this->autoBlock($ip, 'Automatic block: repeated web application firewall violations');
            }
        } catch (\Throwable $e) {
            // Escalation is best-effort; never break the request.
        }
    }

    /**
     * Sends a plain 403 response and terminates the application.
     *
     * @param   string  $message  The response body.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function denyRequest(string $message): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo $message;
        $this->getApplication()->close();
    }

    /**
     * Hides the administrator back-end behind a secret URL key. When a secret
     * key is configured, anonymous visitors must include it as a query string
     * parameter (e.g. /administrator/?mysecret) before the login screen is
     * shown. Already authenticated users and whitelisted IPs are never locked
     * out, which prevents accidental self-lockout.
     *
     * @param   string  $ip  The visitor's IP address.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function enforceAdminSecret(string $ip): void
    {
        $secret = trim((string) $this->params->get('admin_secret_key', ''));

        if ($secret === '') {
            return;
        }

        $app = $this->getApplication();

        if (!method_exists($app, 'isClient') || !$app->isClient('administrator')) {
            return;
        }

        $session = $app->getSession();

        // Already unlocked during this session.
        if ($session->get('jsecdash.admin_unlocked', false)) {
            return;
        }

        // Whitelisted IPs bypass the secret key.
        if (!empty($ip) && $this->isWhitelisted($ip)) {
            return;
        }

        // Already logged-in users keep their access (avoids self-lockout).
        $user = $app->getIdentity();

        if ($user && !$user->guest) {
            $session->set('jsecdash.admin_unlocked', true);

            return;
        }

        // Correct secret supplied as a query parameter.
        if ($app->getInput()->exists($secret)) {
            $session->set('jsecdash.admin_unlocked', true);

            return;
        }

        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Page not found.';
        $app->close();
    }

    /**
     * Records a failed login attempt and locks out the IP address once the
     * configured attempt threshold is exceeded within the attempt window.
     *
     * @param   LoginFailureEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserLoginFailure(LoginFailureEvent $event): void
    {
        $response = $event->getAuthenticationResponse();

        if (($response['status'] ?? null) !== Authentication::STATUS_FAILURE) {
            return;
        }

        $ip = IpHelper::getIp();

        if (empty($ip) || $this->isWhitelisted($ip)) {
            return;
        }

        $db  = $this->getDatabase();
        $now = \Joomla\CMS\Factory::getDate();

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__jsecdash_attempts'))
            ->columns([$db->quoteName('ip'), $db->quoteName('username'), $db->quoteName('attempt_time')])
            ->values(
                implode(
                    ',',
                    [
                        $db->quote($ip),
                        $db->quote((string) ($response['username'] ?? '')),
                        $db->quote($now->toSql()),
                    ]
                )
            );
        $db->setQuery($query)->execute();

        $windowMinutes = (int) $this->params->get('attempt_window', 15);
        $maxAttempts   = (int) $this->params->get('max_attempts', 5);
        $windowStart   = $now->sub(new \DateInterval('PT' . $windowMinutes . 'M'))->toSql();

        $countQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jsecdash_attempts'))
            ->where($db->quoteName('ip') . ' = :ip')
            ->where($db->quoteName('attempt_time') . ' >= :windowStart')
            ->bind(':ip', $ip)
            ->bind(':windowStart', $windowStart);

        $recentAttempts = (int) $db->setQuery($countQuery)->loadResult();

        if ($maxAttempts > 0 && $recentAttempts >= $maxAttempts) {
            $this->autoBlock($ip);
        }
    }

    /**
     * Clears tracked failed attempts for an IP after a successful login.
     *
     * @param   LoginEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserLogin(LoginEvent $event): void
    {
        $ip = IpHelper::getIp();

        if (empty($ip)) {
            return;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__jsecdash_attempts'))
            ->where($db->quoteName('ip') . ' = :ip')
            ->bind(':ip', $ip);

        $db->setQuery($query)->execute();
    }

    /**
     * Creates (or refreshes) an automatic, time-limited block for an IP address.
     *
     * @param   string  $ip      The IP address to block.
     * @param   string  $reason  Human-readable reason stored with the block.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function autoBlock(string $ip, string $reason = 'Automatic lockout: too many failed login attempts'): void
    {
        $db             = $this->getDatabase();
        $lockoutMinutes = (int) $this->params->get('lockout_minutes', 30);
        $now            = \Joomla\CMS\Factory::getDate();

        // Capture "now" before add(); Joomla's Date::add() mutates the object
        // in place, so reading $now after it would return the expiry time.
        $created        = $now->toSql();
        $expires        = $now->add(new \DateInterval('PT' . $lockoutMinutes . 'M'))->toSql();

        $existing = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__jsecdash_blocks'))
            ->where($db->quoteName('ip') . ' = :ip')
            ->bind(':ip', $ip);

        $id = $db->setQuery($existing)->loadResult();

        if ($id) {
            $update = $db->getQuery(true)
                ->update($db->quoteName('#__jsecdash_blocks'))
                ->set($db->quoteName('expires') . ' = :expires')
                ->set($db->quoteName('reason') . ' = ' . $db->quote($reason))
                ->set($db->quoteName('auto') . ' = 1')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':expires', $expires)
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($update)->execute();

            $this->sendBlockAlert($ip);

            return;
        }

        $insert = $db->getQuery(true)
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
                        $db->quote($created),
                        $db->quote($expires),
                        1,
                    ]
                )
            );

        $db->setQuery($insert)->execute();

        $this->sendBlockAlert($ip);
    }

    /**
     * Sends an email notification to the configured address when an IP address
     * is automatically blocked. Mail failures are swallowed so they can never
     * break the request being processed.
     *
     * @param   string  $ip  The IP address that was blocked.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function sendBlockAlert(string $ip): void
    {
        if (!$this->params->get('enable_email_alert', 0)) {
            return;
        }

        $app   = $this->getApplication();
        $email = trim((string) $this->params->get('alert_email', ''));

        if ($email === '') {
            $email = (string) $app->get('mailfrom');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $sitename = (string) $app->get('sitename');
            $when     = Factory::getDate()->format('Y-m-d H:i:s');

            $mailer = Factory::getMailer();
            $mailer->addRecipient($email);
            $mailer->setSubject('[' . $sitename . '] Security alert: IP ' . $ip . ' blocked');
            $mailer->setBody(
                "The Joomla Security Dashboard automatically blocked an IP address after too "
                . "many failed login attempts.\n\n"
                . 'IP address: ' . $ip . "\n"
                . 'Time:       ' . $when . "\n"
                . 'Site:       ' . $sitename . "\n"
            );
            $mailer->Send();
        } catch (\Throwable $e) {
            // Never allow a mail failure to interrupt the login flow.
        }
    }

    /**
     * Determines whether an IP address is currently blocked (manual or automatic, not yet expired).
     *
     * @param   string  $ip  The IP address to check.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function isBlocked(string $ip): bool
    {
        $db  = $this->getDatabase();
        $now = Factory::getDate()->toSql();

        // Load every active block pattern, then match against the visitor's IP.
        // Patterns may be exact IPs, CIDR ranges (1.2.3.0/24) or hyphenated
        // ranges, all of which IpHelper::IPinList understands.
        $query = $db->getQuery(true)
            ->select($db->quoteName('ip'))
            ->from($db->quoteName('#__jsecdash_blocks'))
            ->where('(' . $db->quoteName('expires') . ' IS NULL OR ' . $db->quoteName('expires') . ' > :now)')
            ->bind(':now', $now);

        $patterns = $db->setQuery($query)->loadColumn();

        if (empty($patterns)) {
            return false;
        }

        // Fast path for the common exact-match case.
        if (\in_array($ip, $patterns, true)) {
            return true;
        }

        return IpHelper::IPinList($ip, array_map('trim', $patterns));
    }

    /**
     * Determines whether an IP address is in the configured whitelist.
     *
     * @param   string  $ip  The IP address to check.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function isWhitelisted(string $ip): bool
    {
        $whitelist = trim((string) $this->params->get('whitelist_ips', ''));

        if ($whitelist === '') {
            return false;
        }

        $list = preg_split('/[\r\n,]+/', $whitelist, -1, PREG_SPLIT_NO_EMPTY);

        return IpHelper::IPinList($ip, array_map('trim', $list));
    }
}
