<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php'); // Fixed: Added semicolon

// Capture inputs
$from = (!empty($_POST['fromdate'])) ? $_POST['fromdate'] : date('Y-m-d');
$to = (!empty($_POST['todate'])) ? $_POST['todate'] : date('Y-m-d');
$type = (isset($_POST['type'])) ? $_POST['type'] : 'month'; 

// SMART SWAP LOGIC: Fixes the 25-Mar to 02-Mar logic issue automatically
if (strtotime($from) > strtotime($to)) {
    $temp = $from;
    $from = $to;
    $to = $temp;
}

if ($type == 'year') {
    $from_display = date("Y", strtotime($from));
    $to_display = date("Y", strtotime($to));
    $subtitle = "Sales Report Year Wise";
    $table_header = "Year";

    $query = "SELECT YEAR(paid_at) as label, SUM(amount) as total_sales
              FROM tblpayments
              WHERE status = 'Completed' AND DATE(paid_at) BETWEEN '$from' AND '$to'
              GROUP BY YEAR(paid_at)
              ORDER BY YEAR(paid_at) ASC";
} else {
    $from_display = date("F-Y", strtotime($from));
    $to_display = date("F-Y", strtotime($to));
    $subtitle = "Sales Report Month Wise";
    $table_header = "Month / Year";

    $query = "SELECT DATE_FORMAT(paid_at, '%M / %Y') as label, SUM(amount) as total_sales
              FROM tblpayments
              WHERE status = 'Completed' AND DATE(paid_at) BETWEEN '$from' AND '$to'
              GROUP BY MONTH(paid_at), YEAR(paid_at)
              ORDER BY YEAR(paid_at) ASC, MONTH(paid_at) ASC";
}

$result = mysqli_query($con, $query);
$grand_total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report Details | Admin</title>
    <link rel="stylesheet" href="css/sales-reports-detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content" id="main-content">
    <div class="main-container">
        <h1 class="page-title">Sales Reports</h1>
        
        <div class="report-card">
            <p class="report-subtitle"><?php echo $subtitle; ?></p>
            <h3 class="report-range-header">
                Sales Report from <span style="color:#333;"><?php echo $from_display; ?></span> to <span style="color:#333;"><?php echo $to_display; ?></span>
            </h3>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">S.NO</th>
                        <th style="width: 55%;"><?php echo $table_header; ?></th>
                        <th style="width: 30%;">Total Sales (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_array($result)) {
                            $grand_total += $row['total_sales']; 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo $row['label']; ?></td>
                        <td><strong><?php echo number_format($row['total_sales'], 2); ?></strong></td>
                    </tr>
                    <?php 
                        }
                    } else { ?>
                        <tr><td colspan="3" style="text-align:center; color:red; padding:30px;">No sales found for this date range.</td></tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="total-row" style="background: #333; color: #fff;">
                        <td colspan="2" style="text-align: right; font-weight: bold; border:none;">Grand Total:</td>
                        <td style="font-weight: bold; border:none;">Rs. <?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div style="margin-top: 20px; text-align: center;" class="no-print">
            <button onclick="window.print()" class="btn-print" style="padding: 10px 20px; background: #e65100; color: white; border: none; border-radius: 4px; cursor: pointer;">
                <i class="fa fa-print"></i> Print Report
            </button>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
<?php  ?>