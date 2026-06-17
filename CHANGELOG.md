# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and the project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.1] - 2026-06-17

### Added
- **Web Application Firewall** (system plugin engine + component UI): inspects request
  parameters, URI and User-Agent for SQL injection, XSS, file inclusion / path traversal,
  command injection, bad bots/scanners and known exploit paths.
- WAF modes: Off / Detection-only / Block, per-category toggles and custom regex rules.
- Escalation of repeat WAF offenders to the IP block list.
- New `#__jsecdash_waf_log` table with an incremental schema update so existing installs
  receive it automatically on update.
- WAF dashboard view: 24h summary, category breakdown, top triggered rules and event log.
- Package (`pkg_jsecdash`), build script and GitHub Actions release workflow.
- Declared support for Joomla 5 and Joomla 6.

## [1.0.0] - 2026-06-16

### Added
- Initial release: security dashboard, health check, IP blocking (manual & automatic
  login-lockout), `.htaccess` generator, file integrity scanner with quarantine,
  audit log and database backup.
- System plugin with login-failure tracking, IP enforcement and admin-secret URL hardening.

[1.0.1]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.1
[1.0.0]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.0
