@echo off
setlocal

REM Copy this file to a safe location and run it before starting XAMPP or opening the app.
REM This sets sandbox payment environment variables for local testing only.

set KHALTI_SANDBOX_KEY=your_khalti_sandbox_key_here
set ESEWA_SANDBOX_MERCHANT_CODE=EPAYTEST
set ESEWA_SANDBOX_MERCHANT_SECRET=your_esewa_sandbox_secret_here

echo.
echo Sandbox payment environment variables are set.
echo 1. Open XAMPP and start Apache/MySQL
echo 2. Open your app in the browser
echo 3. Log in and make a test payment

echo.
endlocal
