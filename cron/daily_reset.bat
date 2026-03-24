@echo off
setlocal enabledelayedexpansion

REM ============================================
REM Kakanin System - Daily Reset Script
REM Runs at midnight to reset order counters
REM ============================================

REM Set the path to your PHP installation
set PHP_PATH=C:\xampp\php\php.exe

REM Set the path to your PHP script
set SCRIPT_PATH=C:\xampp\htdocs\kakanin_system\cron\daily_reset.php

REM Set the path for the log file
set LOG_PATH=C:\xampp\htdocs\kakanin_system\cron\reset_log.txt

REM Get current date and time
set CURRENT_DATE=%date%
set CURRENT_TIME=%time%

REM Write start time to log
echo [%CURRENT_DATE% %CURRENT_TIME%] ========== STARTING DAILY RESET ========== >> "%LOG_PATH%"
echo [%CURRENT_DATE% %CURRENT_TIME%] Script started >> "%LOG_PATH%"

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo [%CURRENT_DATE% %CURRENT_TIME%] ERROR: PHP not found at %PHP_PATH% >> "%LOG_PATH%"
    echo [%CURRENT_DATE% %CURRENT_TIME%] Please check your PHP installation >> "%LOG_PATH%"
    exit /b 1
)

REM Check if PHP script exists
if not exist "%SCRIPT_PATH%" (
    echo [%CURRENT_DATE% %CURRENT_TIME%] ERROR: PHP script not found at %SCRIPT_PATH% >> "%LOG_PATH%"
    echo [%CURRENT_DATE% %CURRENT_TIME%] Please check your script location >> "%LOG_PATH%"
    exit /b 1
)

REM Run the PHP script
echo [%CURRENT_DATE% %CURRENT_TIME%] Running PHP script... >> "%LOG_PATH%"
cd /d C:\xampp\php
"%PHP_PATH%" "%SCRIPT_PATH%" >> "%LOG_PATH%" 2>&1

REM Save the error code
set ERROR_CODE=%errorlevel%

REM Write completion to log
echo [%CURRENT_DATE% %CURRENT_TIME%] Script finished with error code: %ERROR_CODE% >> "%LOG_PATH%"

if %ERROR_CODE% equ 0 (
    echo [%CURRENT_DATE% %CURRENT_TIME%] ✅ SUCCESS: Daily reset completed! >> "%LOG_PATH%"
) else (
    echo [%CURRENT_DATE% %CURRENT_TIME%] ❌ ERROR: Daily reset failed with code %ERROR_CODE% >> "%LOG_PATH%"
)

echo [%CURRENT_DATE% %CURRENT_TIME%] ========== END OF SCRIPT ========== >> "%LOG_PATH%"
echo. >> "%LOG_PATH%"

exit /b %ERROR_CODE%