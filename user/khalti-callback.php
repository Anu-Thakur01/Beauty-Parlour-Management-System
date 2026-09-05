<?php
session_start();
include('include/dbconnection.php');
include('include/payment-config.php');

if (!isset($_SESSION['uid'])) {
    header('location:login.php');
    exit;
}

$uid = (int) $_SESSION['uid'];
$invid = isset($_GET['invoiceid']) ? mysqli_real_escape_string($con, $_GET['invoiceid']) : '';
$pidx = trim((string) ($_GET['pidx'] ?? ''));
$paymentResult = mysqli_query($con, "SELECT * FROM tblpayments WHERE UserID = $uid AND BillingId = '$invid' AND provider = 'khalti' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;

if (!$payment || $pidx === '' || !hash_equals((string) ($payment['gateway_transaction_id'] ?? ''), $pidx)) {
    header('location:view-invoice.php?invoiceid=' . urlencode($invid));
    exit;
}

// API request: verify the callback identifier against Khalti's sandbox lookup API.
$result = payment_request_json(KHALTI_SANDBOX_API . '/epayment/lookup/', ['pidx' => $pidx], [
    'Authorization: Key ' . get_env_value(KHALTI_SANDBOX_KEY),
]);
$data = $result['data'] ?? [];

if (($data['status'] ?? '') === 'Completed'
    && ($data['pidx'] ?? '') === $pidx
    && ($data['total_amount'] ?? 0) === (int) round((float) $payment['amount'] * 100)
    && ($data['transaction_id'] ?? '') !== '') {
    // Database update: only a verified Completed response can settle the invoice.
    $transactionId = (string) $data['transaction_id'];
    $update = mysqli_prepare($con, "UPDATE tblpayments SET status = 'Completed', gateway_transaction_id = ?, paid_at = NOW() WHERE UserID = ? AND BillingId = ? AND provider = 'khalti' AND status = 'Pending'");
    mysqli_stmt_bind_param($update, 'sis', $transactionId, $uid, $invid);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
}

header('location:view-invoice.php?invoiceid=' . urlencode($invid));
exit;