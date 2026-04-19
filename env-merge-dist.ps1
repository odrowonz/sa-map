# Merge DB_PASSWORD and APP_KEY from src\.env into build\dist\.env (after robocopy).
# Use ASCII-only messages so Windows PowerShell 5.x parses the file regardless of encoding.
param(
    [Parameter(Mandatory = $true)]
    [string] $ProjectRoot
)

$ProjectRoot = $ProjectRoot.Trim().Trim('"').TrimEnd('\', '/')
$srcEnv = Join-Path $ProjectRoot 'src\.env'
$dstEnv = Join-Path $ProjectRoot 'build\dist\.env'

if (-not (Test-Path -LiteralPath $dstEnv)) {
    Write-Host 'env-merge-dist: skip (build\dist\.env missing)'
    exit 0
}

if (-not (Test-Path -LiteralPath $srcEnv)) {
    Write-Host 'env-merge-dist: skip (src\.env missing; DB_PASSWORD / APP_KEY not merged)'
    exit 0
}

function Read-EnvMap {
    param([string] $Path)
    $h = @{}
    Get-Content -LiteralPath $Path -Encoding utf8 -ErrorAction Stop | ForEach-Object {
        $line = $_
        if ($line -match '^\s*#' -or [string]::IsNullOrWhiteSpace($line)) {
            return
        }
        $eq = $line.IndexOf('=')
        if ($eq -lt 1) {
            return
        }
        $name = $line.Substring(0, $eq).Trim()
        $val = $line.Substring($eq + 1)
        $h[$name] = $val
    }
    return $h
}

$src = Read-EnvMap $srcEnv
$keys = @('DB_PASSWORD', 'APP_KEY')
$present = @{}

$out = New-Object System.Collections.ArrayList
foreach ($line in Get-Content -LiteralPath $dstEnv -Encoding utf8) {
    $done = $false
    foreach ($k in $keys) {
        $esc = [regex]::Escape($k)
        if ($line -match "^\s*$esc\s*=") {
            if ($src.ContainsKey($k)) {
                [void]$out.Add("$k=$($src[$k])")
                $present[$k] = $true
            }
            else {
                [void]$out.Add($line)
            }
            $done = $true
            break
        }
    }
    if (-not $done) {
        [void]$out.Add($line)
    }
}

foreach ($k in $keys) {
    if ($src.ContainsKey($k) -and -not $present.ContainsKey($k)) {
        [void]$out.Add("$k=$($src[$k])")
    }
}

$utf8 = New-Object System.Text.UTF8Encoding $false
$text = ($out | ForEach-Object { $_ }) -join "`r`n"
[System.IO.File]::WriteAllText($dstEnv, $text + "`r`n", $utf8)

Write-Host 'env-merge-dist: merged DB_PASSWORD and APP_KEY from src\.env into build\dist\.env'
