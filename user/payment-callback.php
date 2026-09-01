<?php
session_start();
include('include/dbconnection.php');

if (!isset($_SESSION['uid'])) {
    header('location:logout.php');
    exit;
}

$uid = (int) $_SESSION['uid'];
$invid = isset($_GET['invoiceid']) ? mysqli_real_escape_string($con, $_GET['invoiceid']) : '';
$method = $_GET['method'] ?? '';

if ($invid === '' || !in_array($method, ['esewa', 'khalti'], true)) {
    header('location:invoice.php');
    exit;
}

$paymentResult = mysqli_query($con, "SELECT * FROM tblpayments WHERE UserID = $uid AND BillingId = '$invid' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;

if (!$payment || $payment['provider'] !== $method) {
    header('location:view-invoice.php?invoiceid=' . urlencode($invid));
    exit;
}

$status = 'Failed';
$gatewayTransactionId = null;

if ($method === 'khalti') {
    $khaltiStatus = strtolower((string) ($_GET['status'] ?? ''));
    $khaltiAmount = isset($_GET['amount']) ? (float) $_GET['amount'] / 100 : 0;
    $khaltiOrderId = $_GET['purchase_order_id'] ?? '';
    $khaltiTransactionId = $_GET['transaction_id'] ?? '';

    if ($khaltiStatus === 'completed' && $khaltiOrderId === $invid && abs($khaltiAmount - (float) $payment['amount']) < 0.01 && $khaltiTransactionId !== '') {
        $status = 'Completed';
        $gatewayTransactionId = $khaltiTransactionId;
    }
} elseif ($method === 'esewa') {
    $esewaStatus = strtolower((string) ($_GET['status'] ?? ''));
    $oid = $_GET['oid'] ?? '';
    $amt = isset($_GET['amt']) ? (float) $_GET['amt'] : 0;
    $refId = $_GET['refId'] ?? '';

    if (($esewaStatus === 'success' || $esewaStatus === 'completed' || $esewaStatus === '1') && $oid === $invid && abs($amt - (float) $payment['amount']) < 0.01 && $refId !== '') {
        $status = 'Completed';
        $gatewayTransactionId = $refId;
    }
}

if ($gatewayTransactionId !== null) {
    $update = mysqli_prepare($con, "UPDATE tblpayments
        SET status = ?, gateway_transaction_id = ?, paid_at = NOW()
        WHERE UserID = ? AND BillingId = ? AND provider = ? AND status = 'Pending'");
    mysqli_stmt_bind_param($update, 'ssiss', $status, $gatewayTransactionId, $uid, $invid, $method);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
}

header('location:view-invoice.php?invoiceid=' . urlencode($invid));
exit;
