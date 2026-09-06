[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$Site
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$RootDirectory = Split-Path -Parent $PSScriptRoot
$SiteManager = Join-Path $PSScriptRoot "site-manager.mjs"
$Profiles = @(& node $SiteManager list-json | ConvertFrom-Json)
$Sites = @($Profiles | ForEach-Object { [string]$_.id })
$Domains = @{}
foreach ($profile in $Profiles) { $Domains[[string]$profile.id] = [string]$profile.domain }

if ($Site -ne "all" -and $Site -notin $Sites) {
    throw "Website is not in the local registry. Use wordpress_sites or wordpress_site_add first."
}

$StoreDirectory = Join-Path ([Environment]::GetFolderPath("LocalApplicationData")) "G3AR4UB\WordPressDrafts"
$StorePath = Join-Path $StoreDirectory "credentials.dpapi"
$Entropy = [Text.Encoding]::UTF8.GetBytes("g3ar4ub-wordpress-drafts-v4.1")

Add-Type -AssemblyName System.Security

function Read-CredentialStore {
    $store = @{}
    if (-not (Test-Path -LiteralPath $StorePath)) { return $store }
    $encrypted = [IO.File]::ReadAllBytes($StorePath)
    $plainBytes = [Security.Cryptography.ProtectedData]::Unprotect(
        $encrypted, $Entropy, [Security.Cryptography.DataProtectionScope]::CurrentUser
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

function Read-PlainTextSecret([string]$Prompt) {
    $secureValue = Read-Host $Prompt -AsSecureString
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureValue)
    try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

$store = Read-CredentialStore
$selectedSites = if ($Site -eq "all") { $Sites } else { @($Site) }
foreach ($siteId in $selectedSites) {
    $domain = $Domains[$siteId]
    Write-Host ""
    Write-Host "Configure https://$domain"
    $username = Read-Host "WordPress username for the AI Draft Writer account"
    if ([string]::IsNullOrWhiteSpace($username)) { throw "Username cannot be empty." }

    $appPassword = Read-PlainTextSecret "WordPress Application Password"
    if ([string]::IsNullOrWhiteSpace($appPassword)) { throw "Application Password cannot be empty." }

    $store[$siteId] = @{ username = $username; app_password = $appPassword }
    $appPassword = $null
    Write-Host "Credential prepared for $domain."
}

Save-CredentialStore $store
Write-Host ""
Write-Host "Done. Windows DPAPI protects credentials for the current user."
Write-Host "No password was written to the plugin, site registry, or source code."

