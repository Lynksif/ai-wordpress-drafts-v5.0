[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$RootDirectory = Split-Path -Parent $PSScriptRoot
$StartScript = Join-Path $PSScriptRoot "Start-Mcp-Windows.ps1"
$McpName = "g3ar4ub_wordpress"

$codexCommand = Get-Command codex -ErrorAction Stop
$nodeCommand = Get-Command node -ErrorAction Stop
$nodeMajor = [int](& $nodeCommand.Path -p "Number(process.versions.node.split('.')[0])")
if ($nodeMajor -lt 18) {
    throw "AI WordPress MCP requires Node.js 18 or newer."
}

& $codexCommand.Path mcp get $McpName *> $null
if ($LASTEXITCODE -eq 0) {
    & $codexCommand.Path mcp remove $McpName | Out-Null
}

& $codexCommand.Path mcp add $McpName -- powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File $StartScript
if ($LASTEXITCODE -ne 0) {
    throw "Could not register MCP $McpName."
}

Write-Host "Registered MCP $McpName."
Write-Host "The MCP server reads credentials from the current user's Windows DPAPI store."
Write-Host "The MCP has 7 protected built-in profiles and supports custom profile add/remove from Codex."
Write-Host "Open a new Codex session to load the MCP server and skill."
