#Requires -Version 5.1
<#
.SYNOPSIS
  Fresh install / deploy setup for the appointment system (Windows).
  Idempotent: safe to re-run. Fails fast on any error.

.USAGE
  powershell -ExecutionPolicy Bypass -File scripts/setup.ps1 [-SkipTests]
#>
[CmdletBinding()]
param(
    [switch]$SkipTests
)

$ErrorActionPreference = 'Stop'

function Require-Command($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        throw "Missing prerequisite: '$name' is not on PATH."
    }
}

Require-Command php
Require-Command composer
Require-Command node
Require-Command npm

Write-Output "php:      $(php -r 'echo PHP_VERSION;')"
Write-Output "composer: $(composer --version --no-ansi)"
Write-Output "node:     $(node -v)"

if (-not (Test-Path -LiteralPath 'artisan')) {
    throw 'Run this script from the project root (folder containing artisan).'
}

composer install --no-interaction --prefer-dist

if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    Write-Output 'Created .env from .env.example — fill in DB_* / REVERB_* / PAYSTACK_* next.'
}

$envKey = (Get-Content -LiteralPath '.env' | Select-String '^APP_KEY=' | Out-String)
if ($envKey -match '^APP_KEY=\s*$') {
    php artisan key:generate --force
}

php artisan migrate --force
npm install --ignore-scripts
npm run build

php artisan route:list --path=/ --no-ansi | Select-Object -First 5

if (-not $SkipTests) {
    php artisan test --compact
}

Write-Output 'Setup complete. Start local dev with: composer run dev'
