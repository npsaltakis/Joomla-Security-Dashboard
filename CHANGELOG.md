# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/) and the project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.4] - 2026-06-18

### Added
- WAF now inspects selected request headers (Referer, X-Forwarded-For, X-Real-IP,
  X-Forwarded-Host, Origin) and JSON request bodies, not just query/POST/cookies.
- Command-injection rules are now also evaluated against the request URI and headers.
- New option **Trust Logged-in Administrators** (default on) to skip firewall
  inspection for back-end users and avoid false positives on legitimate content.
- New option **Log Retention (days)** (default 30) with automatic pruning of old
  firewall log entries.

### Fixed
- Automatic IP blocks stored the wrong `created` timestamp (equal to the expiry)
  because `Joomla\CMS\Date::add()` mutates the date object in place.
- Block responses now guard against already-sent headers and send
  `X-Content-Type-Options: nosniff`.

## [1.0.3] - 2026-06-18

### Fixed
- Update server manifest now matches Joomla 5.x / 6.x correctly. The
  `targetplatform` version regex escaped the dot (`[56]\.[0-9]+`) instead of
  treating it as a wildcard.

### Changed
- Build script (`build/build.ps1`) is now tracked in the repository and a
  release checklist was added at `docs/RELEASING.md`.

## [1.0.2] - 2026-06-17

### Added
- File Integrity Scanner now runs as a chunked AJAX job with a live progress bar
  showing the percentage and the file currently being hashed. This also avoids PHP
  execution-timeout limits on large sites.

### Changed
- Baseline and scan are driven by new `scanner.start` / `scanner.step` JSON endpoints
  instead of a single blocking request.

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

[1.0.4]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.4
[1.0.3]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.3
[1.0.2]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.2
[1.0.1]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.1
[1.0.0]: https://github.com/npsaltakis/Joomla-Security-Dashboard/releases/tag/v1.0.0
