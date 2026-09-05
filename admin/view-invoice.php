<?php 
session_start(); // CRITICAL: This must be the very first line to prevent logout
error_reporting(0);
include('includes/dbconnection.php');

// Security Check: If the session is missing, send to login instead of just breaking
if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit();
}

// Check for Invoice ID
if(isset($_GET['invoiceid']) && !empty($_GET['invoiceid'])) {
    $invid = $_GET['invoiceid'];
} else {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Error: Invalid Invoice ID.</h2>";
    exit;
}

// Sanitize input to prevent SQL Injection
$safe_invid = mysqli_real_escape_string($con, $invid);

// Get User and Invoice Date Information
$ret = mysqli_query($con, "SELECT DISTINCT tblinvoice.Userid, tblusers.FullName, tblusers.email, tblusers.MobileNumber, tblinvoice.PostingDate
                           FROM tblusers 
                           JOIN tblinvoice ON tblusers.id = tblinvoice.Userid 
                           WHERE tblinvoice.BillingId='$safe_invid'");
$user = mysqli_fetch_array($ret);

if (!$user) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Invoice not found.</h2>";
    exit;
}

$confirmationMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cash_payment'])) {
    $confirm = mysqli_prepare($con, "UPDATE tblpayments SET status = 'Completed', paid_at = NOW() WHERE UserID = ? AND BillingId = ? AND provider = 'cash' AND status = 'Pending'");
    mysqli_stmt_bind_param($confirm, 'is', $user['Userid'], $invid);
    mysqli_stmt_execute($confirm);
    $confirmed = mysqli_stmt_affected_rows($confirm);
    mysqli_stmt_close($confirm);

    header('location:view-invoice.php?invoiceid=' . urlencode($invid) . '&confirmed=' . ($confirmed === 1 ? '1' : '0'));
    exit;
}

$paymentResult = mysqli_query($con, "SELECT provider, payment_reference, status, paid_at FROM tblpayments WHERE BillingId='$safe_invid' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
if (isset($_GET['confirmed'])) {
    $confirmationMessage = $_GET['confirmed'] === '1'
        ? 'Cash payment confirmed successfully.'
        : 'This payment was already confirmed or is not a pending cash payment.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Details | BPMS Admin</title>
    <link rel="stylesheet" href="css/view-customer.css">
    <style>
        /* Keeping your UI exactly as requested */
        .invoice-box { max-width: 800px; margin: auto; padding: 40px; border: 1px solid #eee; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .main-content { padding-top: 100px; } /* Adjust based on your header height */
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" id="main-content">
    <div class="invoice-box">
        <h2 style="text-align:center;">Parlour Service Invoice</h2>
        <hr>
        <div style="margin-bottom: 20px;">
            <p><strong>Invoice No:</strong> #<?php echo htmlspecialchars($invid); ?></p>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($user['FullName']); ?> (<?php echo htmlspecialchars($user['MobileNumber']); ?>)</p>
            <p><strong>Date:</strong> <?php echo date("d-M-Y h:i A", strtotime($user['PostingDate'])); ?></p>
        </div>

        <?php if ($confirmationMessage !== ''): ?>
            <p style="padding:12px; background:#e8f7ee; color:#207a42; border-radius:4px;"><?php echo htmlspecialchars($confirmationMessage); ?></p>
        <?php endif; ?>

        <div style="padding:15px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px;">
            <p><strong>Payment Method:</strong> <?php echo $payment ? htmlspecialchars(ucfirst($payment['provider'])) : 'Not selected'; ?></p>
            <p><strong>Payment Status:</strong>
                <?php
                if (!$payment) {
                    echo 'Awaiting Payment';
                } elseif ($payment['provider'] === 'cash' && $payment['status'] === 'Pending') {
                    echo 'Cash Payment Selected - Awaiting Confirmation';
                } else {
                    echo htmlspecialchars($payment['status']);
                }
                ?>
            </p>
            <?php if ($payment && $payment['status'] === 'Completed' && $payment['paid_at']): ?>
                <p><strong>Paid At:</strong> <?php echo date("d-M-Y h:i A", strtotime($payment['paid_at'])); ?></p>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr style="background: #f4f4f4;">
                    <th>#</th>
                    <th>Service Name</th>
                    <th>Cost (Rs.)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Fetch services linked to this specific invoice
            $ret2 = mysqli_query($con, "SELECT services.service_name, services.cost 
                                        FROM tblinvoice 
                                        JOIN services ON services.id = tblinvoice.ServiceId 
                                        WHERE tblinvoice.BillingId='$safe_invid'");
            $cnt = 1; 
            $total = 0;
            while($row2 = mysqli_fetch_array($ret2)){
            ?>
                <tr>
                    <td><?php echo $cnt; ?></td>
                    <td><?php echo htmlspecialchars($row2['service_name']); ?></td>
                    <td><?php echo number_format($row2['cost'], 2); ?></td>
                </tr>
            <?php 
                $cnt++; 
                $total += $row2['cost']; 
            } 
            ?>
                <tr style="background: #eee;">
                    <th colspan="2" style="text-align:right;">Grand Total:</th>
                    <th>Rs. <?php echo number_format($total, 2); ?></th>
                </tr>
            </tbody>
        </table>
        
        <br>
        <div style="text-align: center;">
            <?php if ($payment && $payment['provider'] === 'cash' && $payment['status'] === 'Pending'): ?>
                <form method="post" style="display:inline-block; margin-right:8px;" onsubmit="return confirm('Confirm that cash has been received for this invoice?');">
                    <button type="submit" name="confirm_cash_payment" style="padding:10px 20px; background:#2a9d8f; color:white; border:0; border-radius:5px; cursor:pointer;">
                        <i class="fa fa-check"></i> Confirm Cash Payment
                    </button>
                </form>
            <?php endif; ?>
            <a href="print-invoice.php?invoiceid=<?php echo $invid; ?>" 
               target="_blank" 
               style="padding:10px 20px; background:#e76f51; color:white; text-decoration:none; border-radius: 5px; display:inline-block;">
               <i class="fa fa-print"></i> Print Invoice
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>