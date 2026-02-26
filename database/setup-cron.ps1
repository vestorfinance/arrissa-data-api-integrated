#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Smart cron setup for Arrissa Data API — event sync every minute.

.DESCRIPTION
    Auto-detects your PHP installation (WAMP, XAMPP, Laragon, raw PHP,
    or anything on your system PATH), then registers a Windows Scheduled
    Task that runs the cron-sync-events.php script at boot and every minute.

    Supports:  WAMP64 | WAMP32 | XAMPP | Laragon | raw PHP install | PATH php
    Run once as Administrator — survives reboots automatically.

.EXAMPLE
    .\setup-cron.ps1
    .\setup-cron.ps1 -PhpExe "D:\MyPHP\php.exe"   # override auto-detection
    .\setup-cron.ps1 -Uninstall                     # remove the scheduled task
#>

param(
    [string]$PhpExe   = '',    # Override: full path to php.exe
    [switch]$Uninstall         # Remove the scheduled task instead of creating it
)

$TaskName   = 'ArrissaEventsCron'
$ScriptPath = Join-Path $PSScriptRoot 'cron-sync-events.php'

# ─── Uninstall ────────────────────────────────────────────────────────────────
if ($Uninstall) {
    if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "[OK] Scheduled task '$TaskName' removed." -ForegroundColor Green
    } else {
        Write-Host "[INFO] Task '$TaskName' was not found — nothing to remove." -ForegroundColor Yellow
    }
    exit 0
}

# ─── Validate cron script exists ─────────────────────────────────────────────
if (-not (Test-Path $ScriptPath)) {
    Write-Host "[ERROR] Cannot find cron script at: $ScriptPath" -ForegroundColor Red
    Write-Host "        Make sure you are running this script from the 'database' folder." -ForegroundColor Red
    exit 1
}

# ─── Auto-detect PHP ─────────────────────────────────────────────────────────
function Find-PhpExe {
    # Candidate search paths — ordered by preference
    $candidates = @(
        # ── WAMP64 (multiple Apache/PHP versions) ──
        'C:\wamp64\bin\php',
        'D:\wamp64\bin\php',
        # ── WAMP32 ──
        'C:\wamp\bin\php',
        'D:\wamp\bin\php',
        # ── XAMPP ──
        'C:\xampp\php',
        'D:\xampp\php',
        'C:\xampp64\php',
        # ── Laragon ──
        'C:\laragon\bin\php',
        'D:\laragon\bin\php',
        # ── Common raw PHP installs ──
        'C:\PHP',
        'C:\PHP8',
        'C:\PHP82',
        'C:\PHP81',
        'C:\PHP80',
        'D:\PHP',
        # ── Chocolatey / Scoop / WinGet ──
        "$env:ProgramFiles\PHP",
        "$env:ProgramFiles\php",
        "$env:LOCALAPPDATA\scoop\apps\php\current",
        "$env:ProgramData\chocolatey\bin"
    )

    # 1. Walk WAMP/XAMPP-style versioned sub-folders (e.g. php8.2.26)
    foreach ($base in $candidates) {
        if (Test-Path $base) {
            # Direct php.exe in folder
            $direct = Join-Path $base 'php.exe'
            if (Test-Path $direct) { return $direct }

            # Sub-folders (WAMP puts php8.x.y sub-dirs)
            $sub = Get-ChildItem -Path $base -Directory -ErrorAction SilentlyContinue |
                   Sort-Object Name -Descending |   # prefer latest version
                   Select-Object -First 5
            foreach ($d in $sub) {
                $exe = Join-Path $d.FullName 'php.exe'
                if (Test-Path $exe) { return $exe }
            }
        }
    }

    # 2. Fallback: whatever 'php' is on the system PATH
    $pathPhp = Get-Command php -ErrorAction SilentlyContinue
    if ($pathPhp) { return $pathPhp.Source }

    return $null
}

if ($PhpExe -ne '') {
    # User provided path — verify it
    if (-not (Test-Path $PhpExe)) {
        Write-Host "[ERROR] Provided PHP path not found: $PhpExe" -ForegroundColor Red
        exit 1
    }
    Write-Host "[OVERRIDE] Using provided PHP: $PhpExe" -ForegroundColor Cyan
} else {
    Write-Host "[DETECT] Searching for PHP installation..." -ForegroundColor Cyan
    $PhpExe = Find-PhpExe

    if (-not $PhpExe) {
        Write-Host ""
        Write-Host "[ERROR] Could not find php.exe automatically." -ForegroundColor Red
        Write-Host "        Please re-run with the -PhpExe parameter:" -ForegroundColor Yellow
        Write-Host "        .\setup-cron.ps1 -PhpExe 'C:\your\path\to\php.exe'" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "[FOUND]  PHP: $PhpExe" -ForegroundColor Green
}

# ─── Verify PHP works ─────────────────────────────────────────────────────────
$phpVersion = & "$PhpExe" -r "echo PHP_VERSION;" 2>&1
if ($LASTEXITCODE -ne 0 -or -not $phpVersion) {
    Write-Host "[ERROR] PHP at '$PhpExe' does not appear to work (exit code $LASTEXITCODE)" -ForegroundColor Red
    exit 1
}
Write-Host "[OK]     PHP version: $phpVersion" -ForegroundColor Green
Write-Host "[OK]     Cron script: $ScriptPath" -ForegroundColor Green

# ─── Register Scheduled Task ──────────────────────────────────────────────────
Write-Host ""
Write-Host "[SETUP]  Registering Windows Scheduled Task '$TaskName'..." -ForegroundColor Cyan

# Remove any old version first (clean re-register)
if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false | Out-Null
    Write-Host "[INFO]   Replaced existing task." -ForegroundColor Yellow
}

$action = New-ScheduledTaskAction `
    -Execute  $PhpExe `
    -Argument "`"$ScriptPath`""

# Trigger: at boot, then repeat every 1 minute indefinitely
$trigger = New-ScheduledTaskTrigger -AtStartup
$trigger.RepetitionInterval = (New-TimeSpan -Minutes 1)
$trigger.RepetitionDuration = [System.TimeSpan]::MaxValue

$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit      (New-TimeSpan -Seconds 55) `
    -MultipleInstances       IgnoreNew `
    -RestartOnIdle           $false `
    -StartWhenAvailable      $true `
    -RunOnlyIfNetworkAvailable $true

Register-ScheduledTask `
    -TaskName  $TaskName `
    -Action    $action `
    -Trigger   $trigger `
    -Settings  $settings `
    -RunLevel  Highest `
    -Force | Out-Null

if ($LASTEXITCODE -eq 0 -or (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue)) {
    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
    Write-Host " [SUCCESS] Cron task registered!" -ForegroundColor Green
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
    Write-Host "  Task name : $TaskName"
    Write-Host "  PHP       : $PhpExe"
    Write-Host "  Script    : $ScriptPath"
    Write-Host "  Schedule  : every 1 minute (starts at boot)"
    Write-Host ""
    Write-Host " To run it now (without waiting for boot):" -ForegroundColor Cyan
    Write-Host "  Start-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host " To remove it:" -ForegroundColor Cyan
    Write-Host "  .\setup-cron.ps1 -Uninstall" -ForegroundColor White
    Write-Host ""
    Write-Host " HTTP trigger (for n8n / Make / Zapier):" -ForegroundColor Cyan
    Write-Host "  GET {base_url}/api/run-cron?api_key={your_key}" -ForegroundColor White
    Write-Host "  GET {base_url}/api/run-cron?api_key={your_key}&force=true" -ForegroundColor White
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green

    # Optionally start it immediately
    $startNow = Read-Host "`nRun the task now? (y/n)"
    if ($startNow -match '^[Yy]') {
        Start-ScheduledTask -TaskName $TaskName
        Write-Host "[OK] Task started." -ForegroundColor Green
    }
} else {
    Write-Host "[ERROR] Failed to register the scheduled task." -ForegroundColor Red
    exit 1
}
