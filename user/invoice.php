<?php 
session_start();
include('include/dbconnection.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('location:logout.php');
} else {
    $uid = $_SESSION['uid']; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice History</title>
    <link rel="stylesheet" href="style6.css">
    <link rel="stylesheet" href="include/header.css">
    <link rel="stylesheet" href="include/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<?php include('include/header.php');?>

    <section class="page-banner">
        <div class="banner-overlay">
            <h1>Invoice History</h1>
            <p>View your past service billing details and download receipts.</p>
        </div>
    </section>

    <div class="breadcrumb">
        <a href="dashboard.php">Home</a>
        <span>›</span>
        <span class="active">Invoice History</span>
    </div>

    <section class="content">
        <h2 class="section-title">Your Invoices</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice Id</th>
                        <th>Customer Name</th>
                        <th>Mobile Number</th>
                        <th>Invoice Date</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $ret = mysqli_query($con, "SELECT tblusers.FullName, tblusers.MobileNumber, tblinvoice.BillingId, tblinvoice.PostingDate,
                                                  tblpayments.provider, tblpayments.status
                                           FROM tblinvoice 
                                           JOIN tblusers ON tblusers.id = tblinvoice.Userid 
                                           LEFT JOIN tblpayments ON tblpayments.UserID = tblinvoice.Userid
                                               AND tblpayments.BillingId = tblinvoice.BillingId
                                           WHERE tblinvoice.Userid='$uid' 
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
                        <td><?php echo $row['MobileNumber']; ?></td>
                        <td><?php echo date("d-M-Y", strtotime($row['PostingDate'])); ?></td>
                        <td>
                            <?php if ($row['status'] === 'Completed'): ?>
                                <strong style="color:green;">Paid</strong>
                            <?php elseif ($row['provider'] === 'cash' && $row['status'] === 'Pending'): ?>
                                <strong style="color:#b7791f;">Cash selected</strong><br>
                                <small>Awaiting confirmation</small>
                            <?php elseif ($row['status'] === 'Pending'): ?>
                                <strong style="color:#b7791f;">Unpaid</strong>
                            <?php else: ?>
                                Awaiting payment
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view-invoice.php?invoiceid=<?php echo $row['BillingId']; ?>" 
                               style="background: #2a9d8f; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px;">
                               View Details
                            </a>
                        </td>
                    </tr>
                <?php 
                    $cnt++; 
                    } 
                } else { ?>
                    <tr>
                        <td colspan="7" class="no-record">No Record Found</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

<?php include('include/footer.php');?>
</body>
</html>
<?php } ?>