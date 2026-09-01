<?php
require_once __DIR__ . '/dbconnection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['uid']) && isset($con)) {
    $uid = $_SESSION['uid'];
}
?>

<nav class="navbar">
  <div class="logo">BPMS</div>
  <div class="nav-right-content">
  <ul class="nav-links">
    <li><a href="dashboard.php">Home</a></li>
    <li><a href="aboutus.php">About</a></li>
    <li><a href="services.php">Services</a></li>
    <!-- <li><a href="contact.php">Contact</a></li> -->
     <li><a href="get-appointment.php">Get-Appointment</a></li>
     <li><a href="bookinghistory.php">Booking History</a></li>
     <li><a href="invoice.php">Invoice History</a></li>
     <li><a href="logout.php" class="logout-button">Log Out</a></li>
  </ul>
  </div>
</nav>