<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection (crucial for fetching the appointment time)
include('include/dbconnection.php');

if (!isset($_SESSION['uid']) || strlen($_SESSION['uid']) == 0 || ($_SESSION['role'] ?? '') !== 'user') {
    header('location:../index.php'); 
    exit();
}

$fullname = $_SESSION['fullname'];
$uid = (int) $_SESSION['uid'];

// Recommend services from the logged-in user's accepted booking history.
$personalizedServices = mysqli_query($con, "SELECT s.*, COUNT(a.ID) AS booking_count
    FROM tblappointment a
    INNER JOIN services s ON s.id = a.ServiceId
    WHERE a.UserID = $uid AND a.Status = 'Accepted' AND a.ServiceId IS NOT NULL
    GROUP BY s.id, s.service_name, s.service_desc, s.cost, s.image, s.creation_date
    ORDER BY booking_count DESC, MAX(a.BookingDate) DESC
    LIMIT 3");
$hasPersonalizedServices = $personalizedServices && mysqli_num_rows($personalizedServices) > 0;

// --- APPOINTMENT REMINDER LOGIC ---
date_default_timezone_set('Asia/Kathmandu'); // Set to Nepal timezone
$currentTime = new DateTime();
$notificationMsg = "";

// Fetch only accepted appointments for the logged-in user
$query = mysqli_query($con, "SELECT AptDate, AptTime, AppointmentNumber FROM tblappointment WHERE UserID='$uid' AND Status='Accepted'");

if ($query) {
    while($row = mysqli_fetch_array($query)) {
        // Combine Date and Time from DB into one DateTime object
        $aptDateTimeStr = $row['AptDate'] . ' ' . $row['AptTime'];
        $aptDateTime = new DateTime($aptDateTimeStr);
        
        // Calculate the difference between now and the appointment time
        if ($aptDateTime > $currentTime) {
            $diff = $currentTime->diff($aptDateTime);
            
            // Convert difference to total minutes
            $minutesLeft = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            
            // If appointment is within the next 60 minutes (1 hour)
            if ($minutesLeft <= 60 && $minutesLeft > 0) {
                $notificationMsg = "Friendly Reminder: You have an appointment (No: " . $row['AppointmentNumber'] . ") coming up in <strong>" . $minutesLeft . " minutes!</strong> Please be ready.";
                break; // We only need to show the most immediate upcoming appointment
            }
        }
    }
}
// ----------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Beauty Parlour | User Dashboard</title>

  <link rel="stylesheet" href="style1.css">
  <link rel="stylesheet" href="recommendations.css?v=1">
  <link rel="stylesheet" href="include/header.css">
  <link rel="stylesheet" href="include/footer.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  
  <style>
    /* Styling for the Notification Popup */
    .reminder-modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6); /* Black with opacity */
    }
    .reminder-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 30px;
      border-radius: 10px;
      width: 40%;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      position: relative;
      animation: slideDown 0.5s ease-out;
    }
    .reminder-content h2 { color: #ff4757; margin-bottom: 15px; }
    .reminder-content p { font-size: 18px; line-height: 1.5; color: #333; }
    .close-btn {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close-btn:hover { color: #000; }
    .okay-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 25px;
      background-color: #ff4757;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      cursor: pointer;
      border: none;
      font-size: 16px;
    }
    .okay-btn:hover { background-color: #ff6b81; }
    @keyframes slideDown {
      from { top: -100px; opacity: 0; }
      to { top: 0; opacity: 1; }
    }
  </style>
</head>

<body>
<?php include('include/header.php'); ?>

  <?php if(!empty($notificationMsg)): ?>
  <div id="aptModal" class="reminder-modal">
    <div class="reminder-content">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2><i class="fa fa-bell"></i> Upcoming Appointment!</h2>
      <p><?php echo $notificationMsg; ?></p>
      <button class="okay-btn" onclick="closeModal()">Got it!</button>
    </div>
  </div>

  <script>
    // Automatically display the modal when the page loads if there's a message
    window.onload = function() {
      document.getElementById('aptModal').style.display = 'block';
    };

    function closeModal() {
      document.getElementById('aptModal').style.display = 'none';
    }
  </script>
  <?php endif; ?>
  <div class="slideshow">
    <div class="slide">
      <img src="images/bg1.jpg" alt="Slide 1" />
      <div class="caption">
        <h2>Welcome, <?php echo htmlspecialchars($fullname); ?></h2>
        <p>Experience the best beauty services with us.</p>
        <a href="../get-appointment.php" class="btn">Get Appointment</a>
      </div>
    </div>

    <div class="slide">
      <img src="images/bg2.jpg" alt="Slide 2" />
      <div class="caption">
        <h2>Our Premium Services</h2>
        <p>Professional care for your beauty and wellness.</p>
        <a href="get-appointment.php" class="btn">Get Appointment</a>
      </div>
    </div>
  </div>

  <?php if ($hasPersonalizedServices): ?>
  <section class="dashboard-recommendations recommendation-section">
    <div class="recommendation-heading">
      <span class="section-kicker">Personalised for you</span>
      <h1>Your Top Services</h1>
      <p>Your three most frequently booked services.</p>
    </div>
    <div class="recommendation-grid">
      <?php if ($personalizedServices): while ($service = mysqli_fetch_assoc($personalizedServices)): ?>
        <article class="recommendation-card">
          <a class="recommendation-image-link" href="get-appointment.php?serviceid=<?php echo (int) $service['id']; ?>" aria-label="Book <?php echo htmlspecialchars($service['service_name']); ?>">
            <img src="../admin/images/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
          </a>
          <div class="recommendation-card-content">
            <h2><?php echo htmlspecialchars($service['service_name']); ?></h2>
            <p><?php echo htmlspecialchars($service['service_desc']); ?></p>
            <p class="recommendation-price">Rs. <?php echo number_format((float) $service['cost']); ?></p>
            <?php if ($hasPersonalizedServices): ?><span class="visit-count">Booked <?php echo (int) $service['booking_count']; ?> time<?php echo (int) $service['booking_count'] === 1 ? '' : 's'; ?></span><?php endif; ?>
            <a href="get-appointment.php?serviceid=<?php echo (int) $service['id']; ?>" class="btn">Book Again</a>
          </div>
        </article>
      <?php endwhile; endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="parlour">
    <h1>Our Salon Premium Services</h1>
    <p>Anua Hair and Beauty Salon offers a wide range of beauty services.</p>

    <div class="row">
      <div class="parlour-col">
        <img src="images/O3+ Radiant & Glow Bridal Facial.jpg" alt="Service 1" />
        <h2>O3+ Radiant & Glow Bridal Facial</h2>
         <p>The brand focuses on ingredient technology, clinical studies, and providing professional-grade skincare solutions like facial kits, serums, and packs, with a headquarters in Noida, India.</p>
         <p class="price">Cost of Service: rs1200</p>
          <a href="services.php" class="btn">Read More</a>
          <div class="layer"></div>
      </div>

      <div class="parlour-col">
        <img src="images/Fruit Facial.jpg" alt="Service 2" />
        <h2>Fruit Facial</h2>
        <p>Natural fruit-based facial for glowing skin.</p> 
        <p class="price">Cost of Service: rs.500</p>
         <a href="services.php" class="btn">Read More</a>
        <div class="layer"></div>
      </div>

      <div class="parlour-col">
        <img src="images/Charcoal Facial.jpg" alt="Service 3" />
        <h2>Charcoal Facial</h2>
        <p>Deep cleansing facial with activated charcoal.</p> 
        <p class="price">Cost of Service: rs.1000</p>
         <a href="services.php" class="btn">Read More</a>
        <div class="layer"></div>
      </div>
    </div>
  </section>

  <div class="image-container">
    <div class="overlay"></div>
    <div class="text-content">
      <h1>Come Experience the Secrets of Relaxation</h1>
      <h2>
        Best beauty expert at your home and provides beauty salon at home. 
        Home salon provide well trained beauty professionals for beauty services
        at your home including Facial, Clean Up, Bleach, waxing, pedicure, manicure.
      </h2>
      <a href="get-appointment.php" class="btn">Get an Appointment</a>
    </div>
  </div>

  <section class="salon-section">
    <div class="salon-container">
      <div class="salon-image">
        <img src="images/background pic.jpg" alt="hair salon">
      </div>

      <div class="salon-content">
        <h2>Clean and Recommended Hair Salon</h2>
        <p>
          Their array of beauty parlour services include haircuts, hair spas, colouring,
          texturing, styling, waxing, pedicures, manicures, threading, body spa,
          natural facials and more.
        </p>

        <div class="service-list">
          <ul>
            <li>Hair cut with Blow dry</li>
            <li>Color & highlights</li>
            <li>Shampoo & Set</li>
            <li>Blow Dry & Curl</li>
            <li>Advance Hair Color</li>
          </ul>

          <ul>
            <li>Back Massage</li>
            <li>Hair Treatment</li>
            <li>Face Massage</li>
            <li>Skin Care</li>
            <li>Body Therapies</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

 <?php include('include/footer.php');?>

</body>
</html>
