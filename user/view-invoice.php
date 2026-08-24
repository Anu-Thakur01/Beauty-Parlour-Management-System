<?php 
session_start();
error_reporting(E_ALL);
include('include/dbconnection.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('location:logout.php');
    exit;
}

// Check if invoice ID is provided
if(isset($_GET['invoiceid']) && !empty($_GET['invoiceid'])) {
    $invid = mysqli_real_escape_string($con, $_GET['invoiceid']);
    $uid = $_SESSION['uid'];
} else {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Error: Invoice ID not found.</h2>";
    exit;
}

// Fetch Header Info (Ensures this invoice actually belongs to the logged-in user)
$ret = mysqli_query($con, "SELECT DISTINCT tblusers.FullName, tblusers.MobileNumber, tblinvoice.PostingDate 
                           FROM tblusers 
                           JOIN tblinvoice ON tblusers.id = tblinvoice.Userid 
                           WHERE tblinvoice.BillingId='$invid' AND tblinvoice.Userid='$uid'");
$user = mysqli_fetch_array($ret);

if(!$user) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied: You do not have permission to view this invoice.</h2>";
    exit;
}

$paymentResult = mysqli_query($con, "SELECT provider, status FROM tblpayments WHERE UserID='$uid' AND BillingId='$invid' LIMIT 1");
$payment = $paymentResult ? mysqli_fetch_assoc($paymentResult) : null;
$paymentLabel = 'Awaiting payment';
$paymentColor = '#b7791f';
if ($payment && $payment['status'] === 'Completed') {
    $paymentLabel = 'Paid';
    $paymentColor = 'green';
} elseif ($payment && $payment['provider'] === 'cash') {
    $paymentLabel = 'Pay at Parlour';
    $paymentColor = '#b7791f';
} elseif ($payment && $payment['status'] === 'Pending') {
    $paymentLabel = 'Payment pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Details | #<?php echo $invid; ?></title>
    <link rel="stylesheet" href="style6.css">
    <link rel="stylesheet" href="include/header.css">
    <link rel="stylesheet" href="include/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        .invoice-wrapper {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title { color: #d4a373; margin: 0; }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .table-invoice {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table-invoice th {
            background: #fdfaf7;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #eee;
        }
        .table-invoice td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .grand-total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2a9d8f;
        }
        .btn-print {
            background: #e76f51;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-print:hover { background: #cf5a3c; }
        
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include('include/header.php');?>

<div class="invoice-wrapper">
    <div class="invoice-header">
        <div>
            <h2 class="invoice-title">Service Invoice</h2>
            <p style="color: #888;">#<?php echo $invid; ?></p>
        </div>
    </div>

    <div class="info-grid">
        <div>
            <h4 style="margin-bottom: 5px;">Customer Details</h4>
            <p><strong>Name:</strong> <?php echo $user['FullName']; ?></p>
            <p><strong>Mobile:</strong> <?php echo $user['MobileNumber']; ?></p>
        </div>
        <div style="text-align: right;">
            <h4 style="margin-bottom: 5px;">Billing Details</h4>
            <p><strong>Date:</strong> <?php echo date("d-M-Y H:i", strtotime($user['PostingDate'])); ?></p>
            <p><strong>Status:</strong> <span style="color: <?php echo $paymentColor; ?>; font-weight: bold;"><?php echo $paymentLabel; ?></span></p>
        </div>
    </div>

    <table class="table-invoice">
        <thead>
            <tr>
                <th>#</th>
                <th>Service Name</th>
                <th style="text-align: right;">Cost (Rs.)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $ret2 = mysqli_query($con, "SELECT services.service_name, services.cost 
                                    FROM tblinvoice 
                                    JOIN services ON services.id = tblinvoice.ServiceId 
                                    WHERE tblinvoice.BillingId='$invid'");
        $cnt = 1; 
        $total = 0;
        while($row2 = mysqli_fetch_array($ret2)){
        ?>
            <tr>
                <td><?php echo $cnt; ?></td>
                <td><?php echo $row2['service_name']; ?></td>
                <td style="text-align: right;"><?php echo number_format($row2['cost'], 2); ?></td>
            </tr>
        <?php $cnt++; $total += $row2['cost']; } ?>
        </tbody>
    </table>

    <div class="grand-total">
        Grand Total: Rs. <?php echo number_format($total, 2); ?>
    </div>
    
    <div style="margin-top: 40px; text-align: center;">
        <?php if (!$payment || $payment['status'] !== 'Completed'): ?>
        <a href="payment.php?invoiceid=<?php echo urlencode($invid); ?>" class="btn-print" style="margin-right: 12px; background: #2a9d8f;">Choose Payment Method</a>
        <?php endif; ?>
        <a href="invoice.php" style="color: #d4a373; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to History
        </a>
    </div>
</div>

<?php include('include/footer.php');?>
</body>
</html>
