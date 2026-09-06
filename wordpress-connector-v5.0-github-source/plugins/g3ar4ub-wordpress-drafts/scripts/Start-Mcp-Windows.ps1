[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$RootDirectory = Split-Path -Parent $PSScriptRoot
$ServerPath = Join-Path $RootDirectory "server\index.mjs"
$SiteManager = Join-Path $PSScriptRoot "site-manager.mjs"
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

try {
    $nodeCommand = Get-Command node -ErrorAction Stop
    $nodeMajor = [int](& $nodeCommand.Path -p "Number(process.versions.node.split('.')[0])")
    if ($nodeMajor -lt 18) { throw "AI WordPress MCP requires Node.js 18 or newer." }

    $Profiles = @(& $nodeCommand.Path $SiteManager list-json | ConvertFrom-Json)
    $store = Read-CredentialStore
    foreach ($profile in $Profiles) {
        $siteId = [string]$profile.id
        if ($store.ContainsKey($siteId)) {
            $sanitizedId = $siteId.ToUpperInvariant() -replace '[^A-Z0-9]', '_'
            $prefix = "AIWP_$sanitizedId"
            Set-Item -Path "Env:$($prefix)_USERNAME" -Value $store[$siteId].username
            Set-Item -Path "Env:$($prefix)_APP_PASSWORD" -Value $store[$siteId].app_password
        }
    }

    & $nodeCommand.Path $ServerPath
    exit $LASTEXITCODE
}
catch {
    [Console]::Error.WriteLine("AI WordPress MCP: $($_.Exception.Message)")
    exit 1
}
