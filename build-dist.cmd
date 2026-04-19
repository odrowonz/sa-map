@echo off
setlocal EnableExtensions
cd /d "%~dp0src" || exit /b 1

if not exist "composer.phar" (
    echo Downloading composer.phar...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile 'composer.phar' -UseBasicParsing"
    if errorlevel 1 (
        echo Failed to download composer.phar
        exit /b 1
    )
)

echo.
echo === composer install --no-dev ===
php composer.phar install --no-dev --no-interaction
if errorlevel 1 goto :fail

echo.
echo === npm run build ===
call npm run build
if errorlevel 1 goto :fail

set "ROOT=%~dp0"
:: Trailing "\" inside "..." would escape the closing quote when passing to PowerShell
set "ROOTNOBS=%ROOT:~0,-1%"
set "DST=%ROOT%build\dist"
echo.
echo === robocopy src -^> build\dist ===
if not exist "%ROOT%build" mkdir "%ROOT%build"
if exist "%DST%" rmdir /s /q "%DST%"
mkdir "%DST%" || goto :fail

robocopy "%ROOT%src" "%DST%" /MIR /XD node_modules tests /XF composer.phar /NFL /NDL /NJH /NJS /NC /NS /NP
if errorlevel 8 goto :fail

echo.
echo === merge DB_PASSWORD and APP_KEY into build\dist\.env ===
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0env-merge-dist.ps1" "%ROOTNOBS%"
if errorlevel 1 goto :fail

echo.
echo OK: "%DST%"
exit /b 0

:fail
echo.
echo BUILD FAILED.
exit /b 1
