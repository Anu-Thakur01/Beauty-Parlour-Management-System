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
$paymentResult = mysqli_query($con, "SELECT * FROM tblpayments WHERE UserID = $uid AND BillingId = '$invid' AND provider = 'esewa' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
$response = [];

// Callback received: eSewa v2 sends the signed response in the Base64 `data` field.
if (isset($_GET['data'])) {
    $decoded = base64_decode((string) $_GET['data'], true);
    $response = $decoded === false ? [] : (json_decode($decoded, true) ?: []);
}

$transactionUuid = (string) ($response['transaction_uuid'] ?? '');
$expectedUuid = (string) ($payment['gateway_transaction_id'] ?? '');
$merchantCode = get_env_value(ESEWA_SANDBOX_MERCHANT_CODE, 'EPAYTEST');
$merchantSecret = get_env_value(ESEWA_SANDBOX_MERCHANT_SECRET, '');
$responseSignature = esewa_signature($response, $merchantSecret);

if ($payment && $transactionUuid !== '' && hash_equals($expectedUuid, $transactionUuid)
    && ($response['status'] ?? '') === 'COMPLETE'
    && ($response['product_code'] ?? '') === $merchantCode
    && $merchantSecret !== ''
    && $responseSignature !== ''
    && hash_equals((string) ($response['signature'] ?? ''), $responseSignature)
    && (string) ($response['total_amount'] ?? '') === number_format((float) $payment['amount'], 2, '.', '')) {
    // API request: verify the transaction status with eSewa's official UAT endpoint.
    $query = http_build_query([
        'product_code' => $merchantCode,
        'total_amount' => number_format((float) $payment['amount'], 2, '.', ''),
        'transaction_uuid' => $transactionUuid,
    ]);
    $verification = payment_request_form(ESEWA_SANDBOX_STATUS_API . '?' . $query, []);
    $verified = $verification['data'] ?? [];

    if (($verified['status'] ?? '') === 'COMPLETE'
        && ($verified['transaction_uuid'] ?? '') === $transactionUuid
        && (string) ($verified['product_code'] ?? '') === $merchantCode) {
        // Database update: only a verified COMPLETE response can settle the invoice.
        $transactionId = (string) ($verified['refId'] ?? $response['transaction_code'] ?? $transactionUuid);
        $update = mysqli_prepare($con, "UPDATE tblpayments SET status = 'Completed', gateway_transaction_id = ?, paid_at = NOW() WHERE UserID = ? AND BillingId = ? AND provider = 'esewa' AND status = 'Pending'");
        mysqli_stmt_bind_param($update, 'sis', $transactionId, $uid, $invid);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    }
}

header('location:view-invoice.php?invoiceid=' . urlencode($invid));
exit;