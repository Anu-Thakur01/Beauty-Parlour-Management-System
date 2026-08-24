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
    header('location:payment.php?invoiceid=' . urlencode($invid));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payment['status'] === 'Pending') {
    $transactionId = 'DEMO-' . strtoupper($method) . '-' . date('YmdHis') . '-' . mt_rand(100, 999);
    $complete = mysqli_prepare($con, "UPDATE tblpayments
        SET status = 'Completed', gateway_transaction_id = ?, paid_at = NOW()
        WHERE UserID = ? AND BillingId = ? AND provider = ? AND status = 'Pending'");
    mysqli_stmt_bind_param($complete, 'siss', $transactionId, $uid, $invid, $method);
    mysqli_stmt_execute($complete);
    mysqli_stmt_close($complete);
    header('location:view-invoice.php?invoiceid=' . urlencode($invid) . '&demo=success');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="demo-payment.css?v=1">
</head>
<body>
<main class="demo-page">
    <section class="demo-card <?php echo $method; ?>-demo">
        <?php if ($method === 'esewa'): ?>
            <img class="demo-esewa-logo" src="images/payment/esewa-icon.png" alt="eSewa">
        <?php else: ?>
            <div class="demo-khalti-logo"><span>K</span><strong>khalti</strong></div>
        <?php endif; ?>
        <div class="demo-summary">
            <span>Invoice #<?php echo htmlspecialchars($invid); ?></span>
            <strong>Rs. <?php echo number_format((float) $payment['amount'], 2); ?></strong>
        </div>
        <form method="post">
            <button type="submit" class="demo-pay-button">Pay Now</button>
        </form>
        <a class="demo-cancel" href="payment.php?invoiceid=<?php echo urlencode($invid); ?>">Cancel and return</a>
    </section>
</main>
</body>
</html>
