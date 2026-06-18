# Release Checklist

Steps to publish a new version of the Joomla Security Dashboard so that existing
installs receive it through Joomla's built-in extension updater.

> The update site reads `updates/pkg_jsecdash.xml` from the **`main`** branch via
> `raw.githubusercontent.com`. Nothing is offered to users until that file is
> pushed to `main`.

## 1. Bump the version (must match in all three manifests)

Set the same `<version>` in:

- `pkg_jsecdash.xml`
- `administrator/components/com_jsecdash/jsecdash.xml`
- `plugins/system/jsecdash/jsecdash.xml`

## 2. Update the changelog

Add a new section at the top of `CHANGELOG.md` (date + Added/Changed/Fixed).

## 3. Add database migrations (only if the schema changed)

If this release changes tables, add a file named after the new version:

```
plugins/system/jsecdash/sql/updates/mysql/<version>.sql
```

Joomla runs every `*.sql` whose version is higher than the stored schema version,
in order. No DB change → skip this step.

## 4. Add the update entry

Prepend a new `<update>` block to `updates/pkg_jsecdash.xml` (newest first).
Copy an existing block and change:

- `<version>` → the new version
- `<infourl>` title and URL → `.../releases/tag/v<version>`
- `<downloadurl>` → `.../releases/download/v<version>/pkg_jsecdash-<version>.zip`

Keep `<targetplatform name="joomla" version="[56]\.[0-9]+"/>` (Joomla 5.x / 6.x)
and `<php_minimum>8.1</php_minimum>` unless requirements actually change.

## 5. Build the packages

```
pwsh ./build/build.ps1
```

Produces in `dist/`:

- `com_jsecdash.zip`
- `plg_system_jsecdash.zip`
- `pkg_jsecdash-<version>.zip`  ← this is the file you attach to the release

## 6. Publish the GitHub release

- Create a release tagged **`v<version>`** (the leading `v` matters — the URLs use it).
- Attach `dist/pkg_jsecdash-<version>.zip`. The asset name must exactly match the
  `<downloadurl>` in step 4, or the update will appear but fail to download.

## 7. Push to `main`

Commit and push the version bumps, changelog, any new SQL, and the updated
`updates/pkg_jsecdash.xml` to `main`. Only now will existing installs see the update.

## 8. Verify

In a test Joomla site: **System → Update → Extensions → Find Updates**. The new
version should appear and update cleanly (no uninstall, settings and data preserved).
