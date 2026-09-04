@echo off

REM Official TEST/SANDBOX provider configuration only.
REM Never put production credentials in this file.

setx KHALTI_SANDBOX_KEY "your_khalti_sandbox_secret_key_here"
setx ESEWA_SANDBOX_MERCHANT_CODE "EPAYTEST"
setx ESEWA_SANDBOX_MERCHANT_SECRET "your_esewa_sandbox_merchant_secret_here"

echo.
echo Sandbox payment variables were saved for future Apache processes.
echo Restart Apache after running this file.
echo 1. Open XAMPP and start Apache/MySQL
echo 2. Open your app in the browser
echo 3. Log in and make a test payment

echo.
