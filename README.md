# Joomla Security Dashboard (`pkg_jsecdash`)

A security suite for **Joomla 5 and Joomla 6**, delivered as a single installable package containing:

- **`com_jsecdash`** — administrator component (dashboard UI): security health check, IP
  blocks, **Web Application Firewall**, `.htaccess` generator, file integrity scanner,
  audit log and database backup.
- **`plg_system_jsecdash`** — system plugin (the enforcement engine): login lockout,
  IP blocking, admin-secret URL, and the WAF request inspection / logging.

## Requirements

- Joomla **5.x or 6.x**
- PHP **8.1+**

## Repository layout

The repo mirrors the Joomla site tree so files are easy to read and drop into a dev site:

```
administrator/components/com_jsecdash/   # component source
plugins/system/jsecdash/                 # system plugin source (incl. sql/updates)
pkg_jsecdash.xml                          # package manifest
updates/pkg_jsecdash.xml                  # Joomla update-server manifest (served from GitHub)
build/build.ps1                           # builds installable ZIPs into dist/
```

## Building installable ZIPs

```powershell
pwsh ./build/build.ps1
```

Produces in `dist/`:

| File | Purpose |
| --- | --- |
| `com_jsecdash.zip` | component only |
| `plg_system_jsecdash.zip` | plugin only |
| `pkg_jsecdash-<version>.zip` | **the package** — installs/updates both at once |

Install the package ZIP via **System → Install → Extensions** in Joomla.

## Updates from within Joomla

Updates are delivered through the Joomla update system:

1. The package manifest (`pkg_jsecdash.xml`) declares an `<updateservers>` entry pointing at
   `updates/pkg_jsecdash.xml` (served raw from this GitHub repo).
2. For each release: bump the `<version>` in `pkg_jsecdash.xml`, both extension manifests and
   add a matching `<update>` block in `updates/pkg_jsecdash.xml`.
3. Build the package, create a **GitHub Release** tagged `v<version>` and attach
   `pkg_jsecdash-<version>.zip`.
4. Joomla then offers the update under **System → Update → Extensions**.

### Database schema changes on update

Schema changes ship as incremental files under
`plugins/system/jsecdash/sql/updates/mysql/<version>.sql` and are applied automatically by
Joomla on update (the plugin manifest declares `<update><schemas>`). All statements use
`CREATE TABLE IF NOT EXISTS` so they are safe to re-run.

> **1.0.1** adds the `#__jsecdash_waf_log` table (WAF event log).

## Links

- Repository: <https://github.com/npsaltakis/Joomla-Security-Dashboard>
- Update server: `updates/pkg_jsecdash.xml` (served raw from `main`)
