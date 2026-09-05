<?php

include_once('includes/dbconnection.php');

// Security Check: Redirect if not logged in
if (!isset($_SESSION['bpmsaid']) || strlen($_SESSION['bpmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

$adminid = $_SESSION['bpmsaid'];
$headerQuery = mysqli_query($con, "SELECT FullName, Image FROM tblusers WHERE id='$adminid'");
$adminData = mysqli_fetch_array($headerQuery);

$fullName = !empty($adminData['FullName']) ? $adminData['FullName'] : "Admin";
$adminImage = $adminData['Image'];
$initial = strtoupper(substr($fullName, 0, 1));

// Notification Badge Logic
$ret = mysqli_query($con, "SELECT ID FROM tblappointment WHERE Status='Pending' OR Status='' OR Status='0'");
$new_count = mysqli_num_rows($ret);
$cash_ret = mysqli_query($con, "SELECT id FROM tblpayments WHERE provider='cash' AND status='Pending'");
$cash_count = mysqli_num_rows($cash_ret);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BPMS Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="includes/header.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700&display=swap" rel="stylesheet">
  <style>
    /* Toast Notification Style (Admin Only) */
    #reminder-toast {
        visibility: hidden;
        min-width: 280px;
        background-color: #27ae60; /* Professional Green */
        color: #fff;
        text-align: center;
        border-radius: 8px;
        padding: 16px;
        position: fixed;
        z-index: 10000;
        right: 30px;
        bottom: 30px;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: visibility 0s, opacity 0.5s linear;
        opacity: 0;
    }
    #reminder-toast.show {
        visibility: visible;
        opacity: 1;
    }
  </style>
</head>
<body>

<div id="reminder-toast"><i class="fa fa-paper-plane"></i> <span id="toast-msg"></span></div>

<header id="header">
  <div class="logo">
    <i class="fa-solid fa-bars" id="hamburger"></i>
    <div class="logo2">
      <h1>BPMS</h1>
      <p>AdminPanel</p>
    </div>
  </div>

  <div class="icon">
    <div class="bell">
      <a href="new-appointment.php" style="position: relative; text-decoration: none;">
        <i class="fa-solid fa-bell"></i>
        <?php if($new_count > 0): ?>
          <span class="notification-badge"><?php echo $new_count; ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="bell">
      <a href="invoices.php" style="position: relative; text-decoration: none;" title="Cash payments awaiting confirmation">
        <i class="fa-solid fa-money-bill-wave"></i>
        <?php if($cash_count > 0): ?>
          <span class="notification-badge"><?php echo $cash_count; ?></span>
        <?php endif; ?>
      </a>
    </div>

    <div class="admin-profile-container" style="position: relative; display: flex; align-items: center;">
      <div class="user" onclick="toggleDropdown()" style="cursor: pointer;">
        <div class="avatar-circle">
            <?php if(empty($adminImage)): ?>
                <?php echo $initial; ?>
            <?php else: ?>
                <img src="images/<?php echo $adminImage; ?>" alt="Admin Profile" class="profile-pic">
            <?php endif; ?>
        </div>

        <div class="user-text">
          <h3><?php echo $fullName; ?></h3>
          <p>Administrator</p>
        </div>
        <img src="images/dropdown.svg" alt="dropdown" id="drop-arrow" style="transition: transform 0.3s;">
      </div>

      <ul class="dropdown-list" id="adminMenu">
        <li><a href="settings.php"><i class="fa-solid fa-cog"></i> Settings</a></li>
        <li><a href="admin-profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 5px 0;">
        <li><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</header>

<script>
// --- 1. UI DROPDOWN LOGIC ---
function toggleDropdown() {
    const menu = document.getElementById('adminMenu');
    const arrow = document.getElementById('drop-arrow');
    menu.classList.toggle('active');
    
    if(menu.classList.contains('active')) {
        arrow.style.transform = "rotate(180deg)";
    } else {
        arrow.style.transform = "rotate(0deg)";
    }
}

</script>

</body>
</html>