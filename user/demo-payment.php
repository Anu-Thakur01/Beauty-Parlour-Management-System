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

$gatewayError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payment['status'] === 'Pending') {
    $amount = (float) $payment['amount'];
    $callbackUrl = payment_gateway_callback_url($method, $invid);

    if ($method === 'khalti') {
        $khaltiKey = get_env_value('KHALTI_SANDBOX_KEY', '');

        if ($khaltiKey === '') {
            $transactionId = 'SANDBOX-KHALTI-' . date('YmdHis') . '-' . mt_rand(100, 999);
            $complete = mysqli_prepare($con, "UPDATE tblpayments
                SET status = 'Completed', gateway_transaction_id = ?, paid_at = NOW()
                WHERE UserID = ? AND BillingId = ? AND provider = ? AND status = 'Pending'");
            mysqli_stmt_bind_param($complete, 'siss', $transactionId, $uid, $invid, $method);
            mysqli_stmt_execute($complete);
            mysqli_stmt_close($complete);
            header('location:view-invoice.php?invoiceid=' . urlencode($invid) . '&demo=success');
            exit;
        }

        $payload = [
            'return_url' => $callbackUrl,
            'website_url' => get_env_value('APP_BASE_URL', payment_sandbox_base_url()),
            'amount' => (int) round($amount * 100),
            'purchase_order_id' => $invid,
            'purchase_order_name' => 'Parlour Invoice ' . $invid,
            'customer_info' => [
                'name' => 'Parlour Customer',
                'email' => 'customer@example.com',
                'phone' => '9800000000',
            ],
            'merchant_extra' => [
                'payment_reference' => $payment['payment_reference'],
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://dev.khalti.com/api/v2/epayment/initiate/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Key ' . $khaltiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($result['payment_url'])) {
            header('Location: ' . $result['payment_url']);
            exit;
        }

        $gatewayError = !empty($result['detail']) ? $result['detail'] : 'Khalti sandbox connection failed. Check your KHALTI_SANDBOX_KEY value.';
    } elseif ($method === 'esewa') {
        $merchantCode = get_env_value('ESEWA_SANDBOX_MERCHANT_CODE', 'EPAYTEST');

        if ($merchantCode === '' || $merchantCode === 'EPAYTEST') {
            $transactionId = 'SANDBOX-ESEWA-' . date('YmdHis') . '-' . mt_rand(100, 999);
            $complete = mysqli_prepare($con, "UPDATE tblpayments
                SET status = 'Completed', gateway_transaction_id = ?, paid_at = NOW()
                WHERE UserID = ? AND BillingId = ? AND provider = ? AND status = 'Pending'");
            mysqli_stmt_bind_param($complete, 'siss', $transactionId, $uid, $invid, $method);
            mysqli_stmt_execute($complete);
            mysqli_stmt_close($complete);
            header('location:view-invoice.php?invoiceid=' . urlencode($invid) . '&demo=success');
            exit;
        }

        $esewaForm = '<!DOCTYPE html><html><body>'
            . '<form id="esewaForm" method="POST" action="https://uat.esewa.com.np/epay/main">'
            . '<input type="hidden" name="amt" value="' . number_format((float) $amount, 2, '.', '') . '">' 
            . '<input type="hidden" name="pdc" value="0">'
            . '<input type="hidden" name="psc" value="0">'
            . '<input type="hidden" name="txAmt" value="0">'
            . '<input type="hidden" name="tAmt" value="' . number_format((float) $amount, 2, '.', '') . '">' 
            . '<input type="hidden" name="pid" value="' . htmlspecialchars((string) $payment['payment_reference']) . '">' 
            . '<input type="hidden" name="scd" value="' . htmlspecialchars($merchantCode) . '">' 
            . '<input type="hidden" name="su" value="' . htmlspecialchars($callbackUrl) . '">' 
            . '<input type="hidden" name="fu" value="' . htmlspecialchars($callbackUrl) . '">' 
            . '</form><script>document.getElementById("esewaForm").submit();</script></body></html>';
        echo $esewaForm;
        exit;
    }
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

        <?php if ($gatewayError !== ''): ?>
            <div class="demo-message"><?php echo htmlspecialchars($gatewayError); ?></div>
        <?php endif; ?>

        <form method="post">
            <button type="submit" class="demo-pay-button">Pay Now</button>
        </form>
        <a class="demo-cancel" href="payment.php?invoiceid=<?php echo urlencode($invid); ?>">Cancel and return</a>
    </section>
</main>
</body>
</html>
