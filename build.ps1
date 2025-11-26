# WHMCS Bridge Package Build Script
# Creates a single installable Joomla 5 package containing component + plugin

$ErrorActionPreference = "Stop"

# Configuration
$projectName = "whmcsbridge"
$version = "1.0.0"
$timestamp = Get-Date -Format "yyyy-MM-dd_HHmm"
$buildDir = "build"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "WHMCS Bridge Package Builder" -ForegroundColor Cyan
Write-Host "Version: $version" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Clean build directories
if (Test-Path $buildDir) { Remove-Item -Recurse -Force $buildDir }
New-Item -ItemType Directory -Path $buildDir | Out-Null

# Remove old package files from root
Remove-Item "pkg_whmcsbridge_*.zip" -ErrorAction SilentlyContinue

# Function to create ZIP with forward slashes (required for Joomla)
function New-JoomlaZip {
    param(
        [string]$SourcePath,
        [string]$DestinationPath
    )

    Add-Type -AssemblyName System.IO.Compression.FileSystem

    $sourcePath = (Resolve-Path $SourcePath).Path
    $destinationPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($DestinationPath)

    if (Test-Path $destinationPath) {
        Remove-Item $destinationPath -Force
    }

    $zip = [System.IO.Compression.ZipFile]::Open($destinationPath, 'Create')

    try {
        Get-ChildItem -Path $sourcePath -Recurse -File | ForEach-Object {
            $relativePath = $_.FullName.Substring($sourcePath.Length + 1)
            # Convert backslashes to forward slashes for Joomla compatibility
            $entryName = $relativePath -replace '\\', '/'
            $entry = $zip.CreateEntry($entryName)
            $entryStream = $entry.Open()
            $fileStream = [System.IO.File]::OpenRead($_.FullName)
            $fileStream.CopyTo($entryStream)
            $fileStream.Close()
            $entryStream.Close()
        }
    }
    finally {
        $zip.Dispose()
    }
}

# ========================================
# Step 1: Build Component ZIP
# ========================================
Write-Host "`n[1/3] Building component package..." -ForegroundColor Yellow

$componentBuildDir = "$buildDir\com_whmcsbridge"
New-Item -ItemType Directory -Path $componentBuildDir | Out-Null

# Copy manifest to root (renamed to match Joomla convention)
Copy-Item "com_whmcsbridge\whmcsbridge.xml" "$componentBuildDir\whmcsbridge.xml"

# Copy script.php to root
Copy-Item "com_whmcsbridge\script.php" "$componentBuildDir\script.php"

# Copy admin folder
Copy-Item -Recurse "com_whmcsbridge\admin" "$componentBuildDir\admin"

# Copy media folder
Copy-Item -Recurse "com_whmcsbridge\media" "$componentBuildDir\media"

# Create component ZIP in build directory
New-JoomlaZip -SourcePath $componentBuildDir -DestinationPath "$buildDir\com_whmcsbridge.zip"
Write-Host "  Component ZIP created" -ForegroundColor Green

# ========================================
# Step 2: Build Plugin ZIP
# ========================================
Write-Host "`n[2/3] Building plugin package..." -ForegroundColor Yellow

$pluginBuildDir = "$buildDir\plg_authentication_whmcs"
New-Item -ItemType Directory -Path $pluginBuildDir | Out-Null

# Copy all plugin files
Copy-Item -Recurse "plg_authentication_whmcs\*" $pluginBuildDir

# Create plugin ZIP in build directory
New-JoomlaZip -SourcePath $pluginBuildDir -DestinationPath "$buildDir\plg_authentication_whmcs.zip"
Write-Host "  Plugin ZIP created" -ForegroundColor Green

# ========================================
# Step 3: Create Final Package ZIP
# ========================================
Write-Host "`n[3/3] Creating final package..." -ForegroundColor Yellow

$packageBuildDir = "$buildDir\pkg_whmcsbridge"
New-Item -ItemType Directory -Path $packageBuildDir | Out-Null

# Copy package manifest
Copy-Item "pkg_whmcsbridge.xml" "$packageBuildDir\pkg_whmcsbridge.xml"

# Copy package script
Copy-Item "script.php" "$packageBuildDir\script.php"

# Copy component and plugin ZIPs
Copy-Item "$buildDir\com_whmcsbridge.zip" "$packageBuildDir\com_whmcsbridge.zip"
Copy-Item "$buildDir\plg_authentication_whmcs.zip" "$packageBuildDir\plg_authentication_whmcs.zip"

# Copy language folder
Copy-Item -Recurse "language" "$packageBuildDir\language"

# Create final package ZIP in root folder
$packageName = "pkg_whmcsbridge_Joomla5_v${version}_${timestamp}.zip"
New-JoomlaZip -SourcePath $packageBuildDir -DestinationPath $packageName

# ========================================
# Cleanup and Summary
# ========================================
Remove-Item -Recurse -Force $buildDir

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan

$packageFile = Get-Item $packageName
$size = [math]::Round($packageFile.Length / 1KB, 2)

Write-Host "`nPackage created:" -ForegroundColor White
Write-Host "  $packageName ($size KB)" -ForegroundColor Yellow

Write-Host "`nPackage contents:" -ForegroundColor White
Write-Host "  - pkg_whmcsbridge.xml (package manifest)" -ForegroundColor Gray
Write-Host "  - script.php (package installer script)" -ForegroundColor Gray
Write-Host "  - com_whmcsbridge.zip (component)" -ForegroundColor Gray
Write-Host "  - plg_authentication_whmcs.zip (auth plugin)" -ForegroundColor Gray
Write-Host "  - language/ (package translations)" -ForegroundColor Gray

Write-Host "`nInstallation:" -ForegroundColor White
Write-Host "  1. Go to Joomla Admin > System > Install > Extensions" -ForegroundColor Gray
Write-Host "  2. Upload the package ZIP file" -ForegroundColor Gray
Write-Host "  3. Configure API at Components > WHMCS Bridge > Options" -ForegroundColor Gray

Write-Host "`nPackage location:" -ForegroundColor White
Write-Host "  $($packageFile.FullName)" -ForegroundColor Cyan
