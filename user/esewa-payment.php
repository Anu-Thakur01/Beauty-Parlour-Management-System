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
$merchantCode = get_env_value(ESEWA_SANDBOX_MERCHANT_CODE, 'EPAYTEST');
$merchantSecret = get_env_value(ESEWA_SANDBOX_MERCHANT_SECRET, '');

if (!$payment || $payment['provider'] !== 'esewa' || $payment['status'] !== 'Pending') {
    header('location:view-invoice.php?invoiceid=' . urlencode($invid));
    exit;
}

if ($merchantSecret === '') {
    exit('eSewa sandbox secret is not configured.');
}

$total = number_format((float) $payment['amount'], 2, '.', '');
$transactionUuid = 'ESEWA-' . $uid . '-' . strtoupper(bin2hex(random_bytes(6)));
$signedFieldNames = 'total_amount,transaction_uuid,product_code';
$signature = base64_encode(hash_hmac('sha256', 'total_amount=' . $total . ',transaction_uuid=' . $transactionUuid . ',product_code=' . $merchantCode, $merchantSecret, true));

// Store eSewa's test transaction UUID before posting the sandbox form.
$update = mysqli_prepare($con, "UPDATE tblpayments SET gateway_transaction_id = ? WHERE UserID = ? AND BillingId = ? AND provider = 'esewa' AND status = 'Pending'");
mysqli_stmt_bind_param($update, 'sis', $transactionUuid, $uid, $invid);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

$fields = [
    'amount' => $total,
    'tax_amount' => '0',
    'total_amount' => $total,
    'transaction_uuid' => $transactionUuid,
    'product_code' => $merchantCode,
    'product_service_charge' => '0',
    'product_delivery_charge' => '0',
    'success_url' => payment_gateway_callback_url('esewa', $invid),
    'failure_url' => payment_gateway_callback_url('esewa', $invid),
    'signed_field_names' => $signedFieldNames,
    'signature' => $signature,
];
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Redirecting to eSewa Sandbox</title></head>
<body onload="document.getElementById('esewa-form').submit()">
<!-- Test payment initiation: submit the signed form to eSewa's official sandbox. -->
<form id="esewa-form" method="post" action="<?php echo htmlspecialchars(ESEWA_SANDBOX_FORM); ?>">
<?php foreach ($fields as $name => $value): ?><input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
<?php endforeach; ?></form>
<p>Redirecting to eSewa sandbox...</p>
</body></html>