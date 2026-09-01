# Sandbox Payment Setup for XAMPP

This project supports sandbox testing for Khalti and eSewa without using a real merchant account or real money.

## 1) Set your sandbox keys

Use the template in [.env.example](.env.example) as a reference.

For local XAMPP development, the easiest method is to add the variables in your Windows environment before starting Apache:

1. Open System Properties > Environment Variables
2. Add these variables:
   - `KHALTI_SANDBOX_KEY`
   - `ESEWA_SANDBOX_MERCHANT_CODE`
   - `ESEWA_SANDBOX_MERCHANT_SECRET`
3. Restart your terminal and Apache

Example:

```bat
set KHALTI_SANDBOX_KEY=your_khalti_sandbox_key_here
set ESEWA_SANDBOX_MERCHANT_CODE=EPAYTEST
set ESEWA_SANDBOX_MERCHANT_SECRET=your_esewa_sandbox_secret_here
```

## 2) Start your app

Open your local project in XAMPP and run the app normally through `http://localhost/parlour`.

## 3) Test the payment flow

1. Log in as a user
2. Open an invoice
3. Click Pay
4. Choose Khalti or eSewa
5. Press Pay Now
6. The app will redirect to the provider sandbox flow
7. After the sandbox callback, payment status updates in the invoice automatically

## 4) Important notes

- Do not store real production secrets in the project files
- Keep the values in environment variables or a local `.env` file outside source control
- The app falls back to a safe mocked-success flow only when no sandbox credentials are configured

## 5) What happens in the app

The payment flow already matches your existing database structure:

- `tblpayments.UserID`
- `tblpayments.BillingId`
- `tblpayments.amount`
- `tblpayments.provider`
- `tblpayments.payment_reference`
- `tblpayments.gateway_transaction_id`
- `tblpayments.status`

The user simply selects the invoice and method, then the app handles the gateway flow and updates the invoice status automatically.
