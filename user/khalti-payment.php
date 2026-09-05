<?php
session_start();
include('include/dbconnection.php');
include('include/payment-config.php');

if (!isset($_SESSION['uid'])) {
    header('location:logout.php');
    exit;
}

$uid = (int) $_SESSION['uid'];
$invid = isset($_GET['invoiceid']) ? mysqli_real_escape_string($con, $_GET['invoiceid']) : '';
$paymentResult = mysqli_query($con, "SELECT * FROM tblpayments WHERE UserID = $uid AND BillingId = '$invid' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;

if (!$payment || $payment['provider'] !== 'khalti' || $payment['status'] !== 'Pending') {
    header('location:view-invoice.php?invoiceid=' . urlencode($invid));
    exit;
}

$userResult = mysqli_query($con, "SELECT FullName, Email, MobileNumber FROM tblusers WHERE id = $uid LIMIT 1");
$user = $userResult ? mysqli_fetch_assoc($userResult) : [];
$returnUrl = payment_gateway_callback_url('khalti', $invid);
$payload = [
    'return_url' => $returnUrl,
    'website_url' => payment_sandbox_base_url(),
    'amount' => (int) round((float) $payment['amount'] * 100),
    'purchase_order_id' => $invid,
    'purchase_order_name' => 'Parlour invoice ' . $invid,
    'customer_info' => [
        'name' => (string) ($user['FullName'] ?? 'Parlour customer'),
        'email' => (string) ($user['Email'] ?? 'customer@example.com'),
        'phone' => (string) ($user['MobileNumber'] ?? ''),
    ],
];

if (get_env_value(KHALTI_SANDBOX_KEY, '') === '') {
    exit('Khalti sandbox key is not configured.');
}

// API request: initiate a payment in Khalti's sandbox.
$result = payment_request_json(KHALTI_SANDBOX_API . '/epayment/initiate/', $payload, [
    'Authorization: Key ' . get_env_value(KHALTI_SANDBOX_KEY),
]);
$pidx = (string) ($result['data']['pidx'] ?? '');
$paymentUrl = (string) ($result['data']['payment_url'] ?? '');

if ($pidx === '' || $paymentUrl === '') {
    exit('Khalti sandbox initiation failed. Check the API key and callback URL.');
}

// Store Khalti's test payment identifier before redirecting to the sandbox page.
$update = mysqli_prepare($con, "UPDATE tblpayments SET gateway_transaction_id = ? WHERE UserID = ? AND BillingId = ? AND provider = 'khalti' AND status = 'Pending'");
mysqli_stmt_bind_param($update, 'sis', $pidx, $uid, $invid);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

header('location:' . $paymentUrl);
exit;