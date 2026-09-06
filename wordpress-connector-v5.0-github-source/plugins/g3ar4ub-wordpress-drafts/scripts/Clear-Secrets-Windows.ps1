[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$Site
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$SiteManager = Join-Path $PSScriptRoot "site-manager.mjs"
$Profiles = @(& node $SiteManager list-json | ConvertFrom-Json)
$Sites = @($Profiles | ForEach-Object { [string]$_.id })
if ($Site -ne "all" -and $Site -notmatch "^[a-z0-9][a-z0-9-]{1,39}$") {
    throw "Site must be a valid connector ID or all."
}

$StoreDirectory = Join-Path ([Environment]::GetFolderPath("LocalApplicationData")) "G3AR4UB\WordPressDrafts"
$StorePath = Join-Path $StoreDirectory "credentials.dpapi"
$Entropy = [Text.Encoding]::UTF8.GetBytes("g3ar4ub-wordpress-drafts-v4.1")

Add-Type -AssemblyName System.Security

function Read-CredentialStore {
    $store = @{}
    if (-not (Test-Path -LiteralPath $StorePath)) { return $store }
    $plainBytes = [Security.Cryptography.ProtectedData]::Unprotect(
        [IO.File]::ReadAllBytes($StorePath), $Entropy, [Security.Cryptography.DataProtectionScope]::CurrentUser
    )
    try {
        $source = ConvertFrom-Json -InputObject ([Text.Encoding]::UTF8.GetString($plainBytes))
        if ($null -ne $source) {
            foreach ($property in $source.PSObject.Properties) {
                $store[$property.Name] = @{
                    username = [string]$property.Value.username
                    app_password = [string]$property.Value.app_password
                }
            }
        }
    }
    finally {
        if ($plainBytes.Length -gt 0) { [Array]::Clear($plainBytes, 0, $plainBytes.Length) }
    }
    return $store
}

function Save-CredentialStore([hashtable]$Store) {
    [IO.Directory]::CreateDirectory($StoreDirectory) | Out-Null
    $plainBytes = [Text.Encoding]::UTF8.GetBytes((ConvertTo-Json -InputObject $Store -Depth 5 -Compress))
    try {
        $encrypted = [Security.Cryptography.ProtectedData]::Protect(
            $plainBytes, $Entropy, [Security.Cryptography.DataProtectionScope]::CurrentUser
        )
        [IO.File]::WriteAllBytes($StorePath, $encrypted)
    }
    finally {
        if ($plainBytes.Length -gt 0) { [Array]::Clear($plainBytes, 0, $plainBytes.Length) }
    }
}

$store = Read-CredentialStore
$selectedSites = if ($Site -eq "all") { $Sites } else { @($Site) }
foreach ($siteId in $selectedSites) {
    if ($store.Remove($siteId)) { Write-Host "Local credential removed: $siteId" }
}
Save-CredentialStore $store

if ($Site -eq "all") {
    $codexCommand = Get-Command codex -ErrorAction SilentlyContinue
    if ($null -ne $codexCommand) {
        & $codexCommand.Path mcp get g3ar4ub_wordpress *> $null
        if ($LASTEXITCODE -eq 0) { & $codexCommand.Path mcp remove g3ar4ub_wordpress | Out-Null }
    }
    Write-Host "Local MCP registration removed."
}

Write-Host "Revoke the matching WordPress Application Password to disconnect completely."

