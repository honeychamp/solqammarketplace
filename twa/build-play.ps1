# Build Solqam Play Store AAB (Trusted Web Activity).
# Needs: Node.js 18+ and JDK 17. If missing, use https://www.pwabuilder.com instead.
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Twa = $PSScriptRoot
$Out = Join-Path $Twa "android"

function Need($name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        Write-Host "Missing $name. Easier path: open https://www.pwabuilder.com and package https://solqam.com"
        Write-Host "Package ID must be: com.solqam.marketplace"
        exit 1
    }
}

Need node
Need npx

Write-Host "Installing Bubblewrap CLI..."
npx --yes @bubblewrap/cli --version

if (-not (Test-Path $Out)) {
    Write-Host "Init TWA from live manifest..."
    npx --yes @bubblewrap/cli init --manifest "https://solqam.com/manifest.webmanifest" --directory $Out
}

Set-Location $Out
Write-Host "Building release bundle..."
npx --yes @bubblewrap/cli build
Write-Host "AAB is under twa/android/app/build/outputs/bundle/"
Write-Host "Upload that .aab in Play Console. Then paste SHA-256 hashes into app/Config/Twa.php"
