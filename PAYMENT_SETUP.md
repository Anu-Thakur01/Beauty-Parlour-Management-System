# Sandbox Payment Setup for XAMPP

The Khalti and eSewa options use the providers' official test systems. No live endpoint or real customer money is used. The application sends the payment request, redirects to the provider's sandbox page, receives the callback, verifies it with the provider API, and only then marks `tblpayments.status` as `Completed`.

## 1. Database

Import `database/add_payments.sql` after `database/pms_db.sql` if `tblpayments` does not already exist.

## 2. Sandbox configuration

Create a local `.env` file by copying `.env.example`, then replace the Khalti placeholder with the sandbox merchant secret key. The application loads this file for local XAMPP requests:

```bat
setx KHALTI_SANDBOX_KEY "your_khalti_sandbox_secret_key"
setx ESEWA_SANDBOX_MERCHANT_CODE "EPAYTEST"
setx ESEWA_SANDBOX_MERCHANT_SECRET "your_esewa_sandbox_merchant_secret"
```

You can also run `set-payment-env.bat` from a Command Prompt, but restart the XAMPP Control Panel and Apache afterward so the new process inherits the variables.

The eSewa UAT merchant code is `EPAYTEST`. Obtain the eSewa sandbox secret from the provider's UAT documentation or merchant portal, and obtain the Khalti `live_secret_key` from the Khalti test merchant portal. Do not commit either secret or use a production key.

The callback URL is built from `APP_BASE_URL` when it is set. For local XAMPP, `http://localhost/parlour/user` is normally sufficient because the provider redirects the browser. If the provider cannot reach the local callback in your setup, expose the site through an HTTPS tunnel and set `APP_BASE_URL` to that HTTPS URL.

## 3. Test credentials

Khalti sandbox documentation lists test Khalti IDs `9800000000` through `9800000005`, MPIN `1111`, and OTP `987654`.

eSewa UAT documentation lists eSewa IDs `9806800001` through `9806800005`, password `Nepal@123`, MPIN `1122`, and token `123456`.

## 4. Test the flow

1. Start Apache and MySQL in XAMPP and open `http://localhost/parlour`.
2. Log in as a user and open an invoice.
3. Choose Khalti or eSewa.
4. Complete the payment on the provider's test page with the credentials above.
5. The callback verifies the transaction through the sandbox API and redirects to the invoice.
6. Confirm the invoice is marked paid only after verification.

Khalti uses `user/khalti-payment.php` and `user/khalti-callback.php`. eSewa uses `user/esewa-payment.php` and `user/esewa-callback.php`. API keys are read from environment variables in `user/include/payment-config.php`; they are not stored in the database or committed in the application code.