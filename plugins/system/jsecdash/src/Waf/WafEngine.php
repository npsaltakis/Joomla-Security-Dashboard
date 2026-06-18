<?php

/**
 * @package     Joomla.Security.Dashboard
 * @subpackage  System.jsecdash
 */

namespace Joomla\Plugin\System\Jsecdash\Waf;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Signature based Web Application Firewall engine.
 *
 * Inspects the incoming request (query string, POST body, cookies, request URI
 * and User-Agent) against a set of categorised regular-expression rules and
 * returns the first match. The engine is intentionally self-contained and
 * stateless so it stays cheap enough to run on every request.
 *
 * @since  1.0.0
 */
final class WafEngine
{
    /**
     * Maximum number of characters of any single value that are inspected.
     * Larger values are truncated to keep the regular-expression cost bounded.
     */
    private const MAX_VALUE_LENGTH = 8192;

    /**
     * Built-in rule set grouped by category. Each rule is [id, regex].
     */
    private const RULES = [
        'sqli' => [
            ['JS-SQLI-1', '/\bunion\b\s+(?:all\s+)?\bselect\b/i'],
            ['JS-SQLI-2', '/\b(?:or|and)\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+/i'],
            ['JS-SQLI-3', '/\binformation_schema\b/i'],
            ['JS-SQLI-4', '/\b(?:sleep|benchmark|pg_sleep)\s*\(/i'],
            ['JS-SQLI-5', '/\b(?:load_file|updatexml|extractvalue)\s*\(/i'],
            ['JS-SQLI-6', '/;\s*(?:drop|alter|truncate|rename)\s+table\b/i'],
            ['JS-SQLI-7', '/\bselect\b[\s\S]{1,200}?\bfrom\b[\s\S]{1,80}?\b(?:information_schema|mysql|users?)\b/i'],
        ],
        'xss' => [
            ['JS-XSS-1', '/<script[\s\S]*?>/i'],
            ['JS-XSS-2', '/javascript\s*:/i'],
            ['JS-XSS-3', '/\bon(?:error|load|click|mouseover|focus|submit|toggle)\s*=/i'],
            ['JS-XSS-4', '/<iframe[\s\S]*?>/i'],
            ['JS-XSS-5', '/document\.cookie/i'],
            ['JS-XSS-6', '/<svg[\s\S]*?on\w+\s*=/i'],
        ],
        'lfi' => [
            ['JS-LFI-1', '/(?:\.\.\/|\.\.\\\\){2,}/'],
            ['JS-LFI-2', '/\/etc\/(?:passwd|shadow|hosts)\b/i'],
            ['JS-LFI-3', '/(?:boot\.ini|win\.ini)\b/i'],
            ['JS-LFI-4', '/php:\/\/(?:input|filter|data)/i'],
            ['JS-LFI-5', '/=\s*(?:https?|ftp|php|data):\/\//i'],
        ],
        'cmdi' => [
            ['JS-CMDI-1', '/[;&|`]\s*(?:cat|ls|id|whoami|uname|wget|curl|nc|bash|sh|python|perl|powershell|cmd)\b/i'],
            ['JS-CMDI-2', '/\b(?:system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i'],
            ['JS-CMDI-3', '/\$\([\s\S]{1,80}?\)/'],
        ],
        'scanner' => [
            ['JS-SCAN-1', '/\b(?:sqlmap|nikto|nmap|masscan|nessus|acunetix|netsparker|wpscan|dirbuster|gobuster|havij|fimap|arachni)\b/i'],
        ],
        'exploit' => [
            ['JS-EXP-1', '#/\.(?:env|git|svn|hg)(?:/|$)#i'],
            ['JS-EXP-2', '#/(?:wp-login\.php|wp-admin|xmlrpc\.php)#i'],
            ['JS-EXP-3', '#/(?:phpmyadmin|pma|adminer)#i'],
            ['JS-EXP-4', '#eval-stdin\.php#i'],
            ['JS-EXP-5', '#/(?:vendor/phpunit|/cgi-bin/)#i'],
        ],
    ];

    /**
     * Categories the engine is allowed to evaluate.
     *
     * @var  string[]
     */
    private array $categories;

    /**
     * Extra administrator-supplied rules, [id, regex] tuples in the 'custom' category.
     *
     * @var  array<int, array{0:string,1:string}>
     */
    private array $customRules;

    /**
     * @param   string[]  $enabledCategories  Categories to evaluate.
     * @param   string[]  $customRegexes      Raw regular-expression strings (without delimiters).
     *
     * @since   1.0.0
     */
    public function __construct(array $enabledCategories, array $customRegexes = [])
    {
        $this->categories = $enabledCategories;

        $this->customRules = [];
        $index             = 1;

        foreach ($customRegexes as $regex) {
            $regex = trim((string) $regex);

            if ($regex === '') {
                continue;
            }

            // Wrap in delimiters and validate; invalid expressions are skipped silently.
            $pattern = '#' . str_replace('#', '\#', $regex) . '#i';

            if (@preg_match($pattern, '') === false) {
                continue;
            }

            $this->customRules[] = ['JS-CUSTOM-' . $index++, $pattern];
        }
    }

    /**
     * Inspects a request and returns the first matching rule.
     *
     * @param   array   $params   Flattened request parameters as [key => value].
     * @param   string  $uri      The raw request URI.
     * @param   string  $ua       The User-Agent header.
     * @param   array   $headers  Selected request headers as [name => value].
     *
     * @return  array|null  ['rule_id', 'category', 'field', 'snippet'] or null when clean.
     *
     * @since   1.0.0
     */
    public function inspect(array $params, string $uri, string $ua, array $headers = []): ?array
    {
        $uris = [$uri];
        $decoded = rawurldecode($uri);

        if ($decoded !== $uri) {
            $uris[] = $decoded;
        }

        foreach ($this->getActiveRules() as $category => $rules) {
            $targets = $this->targetsFor($category);

            foreach ($rules as [$id, $pattern]) {
                if (\in_array('params', $targets, true)) {
                    foreach ($params as $key => $value) {
                        if ($this->matches($pattern, $value)) {
                            return $this->hit($id, $category, $key, $value);
                        }
                    }
                }

                if (\in_array('headers', $targets, true)) {
                    foreach ($headers as $name => $value) {
                        if ($this->matches($pattern, $value)) {
                            return $this->hit($id, $category, $name, $value);
                        }
                    }
                }

                if (\in_array('uri', $targets, true)) {
                    foreach ($uris as $candidate) {
                        if ($this->matches($pattern, $candidate)) {
                            return $this->hit($id, $category, 'REQUEST_URI', $candidate);
                        }
                    }
                }

                if (\in_array('ua', $targets, true) && $this->matches($pattern, $ua)) {
                    return $this->hit($id, $category, 'User-Agent', $ua);
                }
            }
        }

        return null;
    }

    /**
     * Flattens a (possibly nested) request array into a single-level [key => string] map.
     *
     * @param   array   $data    The source array (e.g. $_GET).
     * @param   string  $prefix  Internal key prefix used during recursion.
     *
     * @return  array<string, string>
     *
     * @since   1.0.0
     */
    public static function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            $compositeKey = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';

            if (\is_array($value)) {
                $flat += self::flatten($value, $compositeKey);
            } elseif (\is_scalar($value)) {
                $flat[$compositeKey] = (string) $value;
            }
        }

        return $flat;
    }

    /**
     * @return  array<string, array<int, array{0:string,1:string}>>
     *
     * @since   1.0.0
     */
    private function getActiveRules(): array
    {
        $active = [];

        foreach (self::RULES as $category => $rules) {
            if (\in_array($category, $this->categories, true)) {
                $active[$category] = $rules;
            }
        }

        if (!empty($this->customRules)) {
            $active['custom'] = $this->customRules;
        }

        return $active;
    }

    /**
     * @param   string  $category  Rule category.
     *
     * @return  string[]  The request parts this category should be tested against.
     *
     * @since   1.0.0
     */
    private function targetsFor(string $category): array
    {
        return match ($category) {
            'scanner' => ['ua'],
            'exploit' => ['uri'],
            'cmdi'    => ['params', 'uri', 'headers'],
            default   => ['params', 'uri', 'headers'],
        };
    }

    /**
     * @param   string  $pattern  The compiled regular expression.
     * @param   string  $value    The haystack.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function matches(string $pattern, string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (\strlen($value) > self::MAX_VALUE_LENGTH) {
            $value = substr($value, 0, self::MAX_VALUE_LENGTH);
        }

        return (bool) @preg_match($pattern, $value);
    }

    /**
     * @param   string  $id        The rule id.
     * @param   string  $category  The rule category.
     * @param   string  $field     The field/header that matched.
     * @param   string  $value     The offending value.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function hit(string $id, string $category, string $field, string $value): array
    {
        return [
            'rule_id'  => $id,
            'category' => $category,
            'field'    => $field,
            'snippet'  => substr($value, 0, 255),
        ];
    }
}
