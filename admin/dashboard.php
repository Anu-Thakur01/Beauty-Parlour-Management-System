<?php
session_start();
error_reporting(E_ALL);
include('includes/dbconnection.php');

if (!isset($_SESSION['bpmsaid']) || strlen($_SESSION['bpmsaid']) == 0) {
    header('location:../index.php'); 
    exit();
} else {
    $totalcustomers = mysqli_num_rows(mysqli_query($con, "SELECT id FROM tblusers WHERE Role='user'"));
    $totalappointment = mysqli_num_rows(mysqli_query($con, "SELECT ID FROM tblappointment"));
    $totalacceptedapt = mysqli_num_rows(mysqli_query($con, "SELECT ID FROM tblappointment WHERE Status='Accepted'"));
    $totalrejectedapt = mysqli_num_rows(mysqli_query($con, "SELECT ID FROM tblappointment WHERE Status='Rejected'"));
    $totalservices = mysqli_num_rows(mysqli_query($con, "SELECT ID FROM services"));

    $query_sales = mysqli_query($con, "SELECT COALESCE(SUM(tblpayments.amount), 0) as total_revenue
                       FROM tblpayments
                       WHERE tblpayments.status = 'Completed'");
    $row_sales = mysqli_fetch_array($query_sales);
    $total_sales = $row_sales['total_revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>BPMS Admin Panel - Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main-content" class="dashboard-main">
  
  <div class="dashboard-header">
    <h2 class="header-title">
       Admin Dashboard Overview
    </h2>
  </div>

  <div class="row">
    <div class="card card1">
      <div class="card-text">
        <p>Total</p>
        <h1>Customers</h1>
      </div>
      <div class="count count1">
        <p><?php echo $totalcustomers; ?></p>
      </div>
    </div>

    <div class="card card3">
      <div class="card-text">
        <p>Total</p>
        <h1>Appointments</h1>
      </div>
      <div class="count count3">
        <p><?php echo $totalappointment; ?></p>
      </div>
    </div>

    <div class="card card-accepted">
      <div class="card-text">
        <p>Total</p>
        <h1>Accepted</h1>
      </div>
      <div class="count count-accepted">
        <p><?php echo $totalacceptedapt; ?></p>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="card card-rejected">
      <div class="card-text">
        <p>Total</p>
        <h1>Rejected</h1>
      </div>
      <div class="count count-rejected">
        <p><?php echo $totalrejectedapt; ?></p>
      </div>
    </div>

    <div class="card card-services">
      <div class="card-text">
        <p>Total</p>
        <h1>Services</h1>
      </div>
      <div class="count count-services">
        <p><?php echo $totalservices; ?></p>
      </div>
    </div>

    <div class="card card-sales">
      <div class="card-text">
        <p>Total</p>
        <h1>Sales</h1>
      </div>
      <div class="count count-sales">
        <p><?php echo number_format($total_sales); ?></p>
      </div>
    </div>
  </div>

</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
<?php } ?>