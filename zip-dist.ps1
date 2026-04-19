# Zip contents of build\dist into build\dist\dist.zip (staging outside dist to avoid locking).
param(
    [Parameter(Mandatory = $true)]
    [string] $ProjectRoot
)

$ProjectRoot = $ProjectRoot.Trim().Trim('"').TrimEnd('\', '/')
$dst = Join-Path $ProjectRoot 'build\dist'
$outZip = Join-Path $dst 'dist.zip'
$staging = Join-Path $ProjectRoot 'build\_dist_pack.zip'

if (-not (Test-Path -LiteralPath $dst)) {
    Write-Host 'zip-dist: skip (build\dist missing)'
    exit 0
}

$items = @(Get-ChildItem -LiteralPath $dst -Force -ErrorAction Stop | Where-Object { $_.Name -ne 'dist.zip' })

if ($items.Count -eq 0) {
    Write-Host 'zip-dist: nothing to pack'
    exit 0
}

Remove-Item -LiteralPath $staging -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath $outZip -Force -ErrorAction SilentlyContinue

Compress-Archive -Path ($items | ForEach-Object { $_.FullName }) -DestinationPath $staging -CompressionLevel Fastest -Force

Move-Item -LiteralPath $staging -Destination $outZip -Force

Write-Host "zip-dist: wrote $($outZip)"
