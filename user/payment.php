<?php
session_start();
include('include/dbconnection.php');

if (!isset($_SESSION['uid'])) {
    header('location:logout.php');
    exit;
}

$uid = (int) $_SESSION['uid'];
$invid = isset($_GET['invoiceid']) ? mysqli_real_escape_string($con, $_GET['invoiceid']) : '';

if ($invid === '') {
    header('location:invoice.php');
    exit;
}

$invoiceCheck = mysqli_query($con, "SELECT COUNT(*) AS item_count, COALESCE(SUM(services.cost), 0) AS total
    FROM tblinvoice
    INNER JOIN services ON services.id = tblinvoice.ServiceId
    WHERE tblinvoice.BillingId = '$invid' AND tblinvoice.Userid = $uid");
$invoice = mysqli_fetch_assoc($invoiceCheck);

if (!$invoice || (int) $invoice['item_count'] === 0) {
    header('location:invoice.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';

    if ($method === 'cash') {
        $reference = 'CASH-' . $invid;
        $total = (float) $invoice['total'];
        $save = mysqli_prepare($con, "INSERT INTO tblpayments (UserID, BillingId, amount, provider, payment_reference, status)
            VALUES (?, ?, ?, 'cash', ?, 'Pending')
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), provider = 'cash', payment_reference = VALUES(payment_reference), status = 'Pending', gateway_transaction_id = NULL, paid_at = NULL");
        mysqli_stmt_bind_param($save, 'isds', $uid, $invid, $total, $reference);

        if (mysqli_stmt_execute($save)) {
            $message = '';
        } else {
            $message = 'We could not save your payment selection. Please try again.';
        }
        mysqli_stmt_close($save);
    } elseif ($method === 'esewa' || $method === 'khalti') {
        $reference = 'DEMO-' . strtoupper($method) . '-' . $uid . '-' . $invid;
        $total = (float) $invoice['total'];
        $save = mysqli_prepare($con, "INSERT INTO tblpayments (UserID, BillingId, amount, provider, payment_reference, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), provider = VALUES(provider), payment_reference = VALUES(payment_reference), status = 'Pending', gateway_transaction_id = NULL, paid_at = NULL");
        mysqli_stmt_bind_param($save, 'isdss', $uid, $invid, $total, $method, $reference);

        if (mysqli_stmt_execute($save)) {
            mysqli_stmt_close($save);
            header('location:demo-payment.php?invoiceid=' . urlencode($invid) . '&method=' . urlencode($method));
            exit;
        }
        mysqli_stmt_close($save);
        $message = 'We could not start the demo payment. Please try again.';
    } else {
        $message = 'Please select a valid payment method.';
    }
}

$paymentResult = mysqli_query($con, "SELECT * FROM tblpayments WHERE UserID = $uid AND BillingId = '$invid' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Invoice #<?php echo htmlspecialchars($invid); ?></title>
    <link rel="stylesheet" href="include/header.css">
    <link rel="stylesheet" href="include/footer.css">
    <link rel="stylesheet" href="payment.css?v=1">
</head>
<body>
<?php include('include/header.php'); ?>

<main class="payment-page">
    <section class="payment-card">
        <a class="back-link" href="view-invoice.php?invoiceid=<?php echo urlencode($invid); ?>">&larr; Back to invoice</a>
        <p class="payment-kicker">Invoice #<?php echo htmlspecialchars($invid); ?></p>
        <h1>Select Payment Method</h1>
        <div class="payment-total"><span>Total payable</span><strong>Rs. <?php echo number_format((float) $invoice['total'], 2); ?></strong></div>

        <?php if ($message !== ''): ?>
            <div class="payment-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($payment && $payment['status'] === 'Completed'): ?>
            <div class="payment-success">This invoice has been paid successfully.</div>
        <?php else: ?>
            <form method="post" class="payment-options">
                <button type="submit" name="payment_method" value="cash" class="payment-option cash-option<?php echo ($payment && $payment['provider'] === 'cash') ? ' is-selected' : ''; ?>">
                    <span class="payment-icon cash-icon"><i></i><i></i></span>
                    <span class="payment-option-title">Pay at Parlour</span>
                    <span class="payment-option-subtitle">Cash Payment</span>
                </button>
                <button type="submit" name="payment_method" value="esewa" class="payment-option online-option esewa-option<?php echo ($payment && $payment['provider'] === 'esewa') ? ' is-selected' : ''; ?>">
                    <span class="payment-icon payment-brand"><img src="images/payment/esewa-icon.png" alt="eSewa"></span>
                    <span class="payment-option-title">eSewa Mobile Wallet</span>
                    <span class="payment-option-subtitle">Digital Wallet</span>
                </button>
                <button type="submit" name="payment_method" value="khalti" class="payment-option online-option khalti-option<?php echo ($payment && $payment['provider'] === 'khalti') ? ' is-selected' : ''; ?>">
                    <span class="payment-icon khalti-brand" aria-label="Khalti"><span class="khalti-mark">K</span><span class="khalti-name">khalti</span></span>
                    <span class="payment-option-title">Khalti by IME</span>
                    <span class="payment-option-subtitle">Digital Wallet</span>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($payment && $payment['status'] === 'Pending' && $payment['provider'] === 'cash'): ?>
            <p class="payment-note">Payment method selected: <strong>Cash at Parlour</strong>. The staff will confirm payment after your visit.</p>
        <?php endif; ?>
    </section>
</main>

<?php include('include/footer.php'); ?>
</body>
</html>
