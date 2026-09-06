[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$RootDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$PluginDirectory = Join-Path $RootDirectory "plugins\g3ar4ub-wordpress-drafts"
$ScriptsDirectory = Join-Path $PluginDirectory "scripts"

Get-Command codex -ErrorAction Stop | Out-Null
Get-Command node -ErrorAction Stop | Out-Null

$NodeMajor = [int](& node -p "Number(process.versions.node.split('.')[0])")
if ($NodeMajor -lt 18) {
    throw "WordPress Connector requires Node.js 18 or newer. Current version: $(& node --version)"
}

& codex login status *> $null
if ($LASTEXITCODE -ne 0) {
    throw "Codex is not signed in. Run 'codex login', choose Sign in with ChatGPT, then run this installer again."
}

Write-Host "Registering the WordPress MCP server..."
& (Join-Path $ScriptsDirectory "Register-Mcp-Windows.ps1")

Write-Host "Adding the local marketplace and plugin to Codex..."
& codex plugin marketplace add $RootDirectory
if ($LASTEXITCODE -ne 0) {
    Write-Host "The marketplace may already be registered; continuing with plugin installation."
}

& codex plugin add g3ar4ub-wordpress-drafts@g3ar4ub-local
if ($LASTEXITCODE -ne 0) { throw "Could not install g3ar4ub-wordpress-drafts." }

Write-Host ""
Write-Host "Available website IDs:"
& node (Join-Path $ScriptsDirectory "site-manager.mjs") list-ids
$SiteId = Read-Host "Enter a SITE_ID to configure with Windows DPAPI, or press Enter to skip"
if (-not [string]::IsNullOrWhiteSpace($SiteId)) {
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $ScriptsDirectory "Configure-Secrets-Windows.ps1") -Site $SiteId
    if ($LASTEXITCODE -ne 0) { throw "Could not store the WordPress credential." }
}

Write-Host ""
Write-Host "WordPress Connector v5.0 Dynamic Sites is ready on Windows."
Write-Host "Existing credentials were preserved. Open a new Codex session and call wordpress_sites."
Write-Host "To configure a credential: powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$ScriptsDirectory\Configure-Secrets-Windows.ps1`" -Site SITE_ID"
