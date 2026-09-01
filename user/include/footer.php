<?php 
$con = parlour_db_connection();
// Pulling contact info for the left column
$res_contact = mysqli_query($con, "SELECT * FROM tblpages WHERE PageType='contactus'");
$foot_contact = mysqli_fetch_array($res_contact);

// Pulling "About Us" info for the right column
$res_about = mysqli_query($con, "SELECT * FROM about_settings WHERE id=1");
$foot_about = mysqli_fetch_array($res_about);
?>

<footer class="footer">
  <div class="footer-container">
    
    <div class="footer-section">
      <h3>Contact Us</h3>
      <p><?php echo $foot_contact['PageDescription']; ?></p>
      <p><?php echo $foot_contact['MobileNumber']; ?></p>
      <p><?php echo $foot_contact['Email']; ?></p>
    </div>

    <div class="footer-section">
      <h3>Useful Links</h3>
      <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="aboutus.php">About</a></li>
        <li><a href="services.php">Services</a></li>
      </ul>
    </div>

    <div class="footer-section">
      <h3><?php echo $foot_about['page_title'] ?? 'About Us'; ?></h3>
      <p><?php echo $foot_about['page_description'] ?? 'Default about text...'; ?></p>
    </div>

  </div>
  </footer>