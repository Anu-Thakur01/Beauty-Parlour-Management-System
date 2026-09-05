<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Logic to prevent logout: Check if the session variable exists
if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit();
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice List | BPMS Admin</title>
    <link rel="stylesheet" href="css/invoices.css">
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main-content" class="main-content">
    <h2 class="title">Invoice List</h2>
    
    <div class="container">
        <h3>Generated Invoices</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice ID</th>
                    <th>Customer Name</th>
                    <th>Invoice Date</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // The query remains the same as your original UI logic
            $ret = mysqli_query($con, "SELECT 
                                        tblusers.FullName, 
                                        tblinvoice.BillingId, 
                                        tblinvoice.PostingDate,
                                        tblpayments.provider,
                                        tblpayments.status
                                       FROM tblinvoice 
                                       JOIN tblusers ON tblusers.id = tblinvoice.Userid
                                       LEFT JOIN tblpayments ON tblpayments.UserID = tblinvoice.Userid
                                           AND tblpayments.BillingId = tblinvoice.BillingId
                                       GROUP BY tblinvoice.BillingId 
                                       ORDER BY tblinvoice.PostingDate DESC");
            
            $cnt = 1;
            if(mysqli_num_rows($ret) > 0) {
                while ($row = mysqli_fetch_array($ret)) {
            ?>
                <tr>
                    <td><?php echo $cnt; ?></td>
                    <td>#<?php echo $row['BillingId']; ?></td>
                    <td><?php echo $row['FullName']; ?></td>
                    <td><?php echo date("d-M-Y H:i", strtotime($row['PostingDate'])); ?></td>
                    <td>
                        <?php if ($row['provider'] === 'cash' && $row['status'] === 'Pending'): ?>
                            <strong style="color:#b7791f;">Cash Selected</strong><br>
                            <small>Awaiting confirmation</small>
                        <?php elseif ($row['status'] === 'Completed'): ?>
                            <strong style="color:#207a42;">Paid</strong>
                        <?php elseif ($row['status'] === 'Pending'): ?>
                            <strong style="color:#b7791f;">Payment Pending</strong>
                        <?php else: ?>
                            Awaiting Payment
                        <?php endif; ?>
                    </td>
                    <td class="action-buttons">
                        <a href="view-invoice.php?invoiceid=<?php echo $row['BillingId']; ?>" class="view-btn">
                            View Details
                        </a>
                    </td>
                </tr>
            <?php 
                $cnt++; 
                } 
            } else { ?>
                <tr>
                    <td colspan="6" class="no-data">No invoices found in the system.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<script src="js/script.js"></script>
</body>
</html>
<?php } ?>