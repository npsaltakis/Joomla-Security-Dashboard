<#
.SYNOPSIS
    Builds installable Joomla ZIP packages for the Joomla Security Dashboard.

.DESCRIPTION
    Produces three artifacts in the dist/ folder:
      * com_jsecdash.zip          - the component (manifest at root, files under admin/)
      * plg_system_jsecdash.zip   - the system plugin
      * pkg_jsecdash-<ver>.zip     - the package that installs/updates both at once

    The package ZIP is what you attach to a GitHub Release (named
    pkg_jsecdash-<version>.zip to match updates/pkg_jsecdash.xml).

.EXAMPLE
    pwsh ./build/build.ps1
#>

$ErrorActionPreference = 'Stop'

$root  = Split-Path -Parent $PSScriptRoot
$dist  = Join-Path $root 'dist'
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("jsecdash_build_" + [guid]::NewGuid().ToString('N'))

$compSrc = Join-Path $root 'administrator/components/com_jsecdash'
$plgSrc  = Join-Path $root 'plugins/system/jsecdash'
$pkgXml  = Join-Path $root 'pkg_jsecdash.xml'

# Read the package version from the package manifest.
$version = ([regex]::Match((Get-Content $pkgXml -Raw), '<version>([^<]+)</version>')).Groups[1].Value
if (-not $version) { throw "Could not read <version> from pkg_jsecdash.xml" }

Write-Host "Building Joomla Security Dashboard $version" -ForegroundColor Cyan

# Fresh output folders.
New-Item -ItemType Directory -Force -Path $dist  | Out-Null
New-Item -ItemType Directory -Force -Path $stage | Out-Null

try {
    # ---- Component (files under admin/, manifest also at ZIP root) ----
    $cStage = Join-Path $stage 'com_jsecdash'
    $cAdmin = Join-Path $cStage 'admin'
    New-Item -ItemType Directory -Force -Path $cAdmin | Out-Null

    Copy-Item (Join-Path $compSrc 'jsecdash.xml') (Join-Path $cStage 'jsecdash.xml')
    foreach ($item in 'access.xml','config.xml','jsecdash.xml','services','src','tmpl','language') {
        Copy-Item (Join-Path $compSrc $item) (Join-Path $cAdmin $item) -Recurse
    }
    $compZip = Join-Path $dist 'com_jsecdash.zip'
    Compress-Archive -Path (Join-Path $cStage '*') -DestinationPath $compZip -Force
    Write-Host "  + com_jsecdash.zip" -ForegroundColor Green

    # ---- System plugin (manifest + folders at ZIP root) ----
    $pStage = Join-Path $stage 'plg_system_jsecdash'
    New-Item -ItemType Directory -Force -Path $pStage | Out-Null

    Copy-Item (Join-Path $plgSrc 'jsecdash.xml') (Join-Path $pStage 'jsecdash.xml')
    foreach ($item in 'services','src','sql','language') {
        Copy-Item (Join-Path $plgSrc $item) (Join-Path $pStage $item) -Recurse
    }
    $plgZip = Join-Path $dist 'plg_system_jsecdash.zip'
    Compress-Archive -Path (Join-Path $pStage '*') -DestinationPath $plgZip -Force
    Write-Host "  + plg_system_jsecdash.zip" -ForegroundColor Green

    # ---- Package (manifest at root + inner ZIPs under packages/) ----
    $kStage = Join-Path $stage 'pkg'
    $kPacks = Join-Path $kStage 'packages'
    New-Item -ItemType Directory -Force -Path $kPacks | Out-Null

    Copy-Item $pkgXml (Join-Path $kStage 'pkg_jsecdash.xml')
    Copy-Item $compZip $kPacks
    Copy-Item $plgZip  $kPacks
    $pkgZip = Join-Path $dist ("pkg_jsecdash-$version.zip")
    Compress-Archive -Path (Join-Path $kStage '*') -DestinationPath $pkgZip -Force
    Write-Host "  + pkg_jsecdash-$version.zip" -ForegroundColor Green

    Write-Host "`nDone. Artifacts in: $dist" -ForegroundColor Cyan
    Get-ChildItem $dist -Filter '*.zip' | Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB,1)}}
}
finally {
    Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
}
