<?php
session_start();
function parlour_db_connection()
{
    $host = getenv('DB_HOST');
    if ($host === false || trim((string) $host) === '') {
        $host = '127.0.0.1';
    }

    $port = getenv('DB_PORT');
    if ($port === false || trim((string) $port) === '') {
        $port = 3306;
    }

    $username = getenv('DB_USERNAME');
    if ($username === false || trim((string) $username) === '') {
        $username = 'root';
    }

    $password = getenv('DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $database = getenv('DB_NAME');
    if ($database === false || trim((string) $database) === '') {
        $database = 'pms_db';
    }

    return mysqli_connect($host, $username, $password, $database, (int) $port);
}

$con = parlour_db_connection();

$msg = "";
if(isset($_POST['update'])) {
    $title = mysqli_real_escape_string($con, $_POST['pagetitle']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['mobilenumber']);
    $time  = mysqli_real_escape_string($con, $_POST['timing']);
    $desc  = mysqli_real_escape_string($con, $_POST['pagedescription']);

    $query = mysqli_query($con, "UPDATE tblpages SET PageTitle='$title', Email='$email', MobileNumber='$phone', Timing='$time', PageDescription='$desc' WHERE PageType='contactus'");
    
    if ($query) { 
        $msg = "success"; 
    }
}

$ret = mysqli_query($con, "SELECT * FROM tblpages WHERE PageType='contactus'");
$row = mysqli_fetch_array($ret);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Contact Us</title>
    <link rel="stylesheet" href="css/contact-us.css">
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div id="main-content" class="main-content">
        <div class="container">
            <h1 class="title">Update Contact Us</h1>
            <div class="panel">
                <form method="post">
                    <label>Page Title</label>
                    <input type="text" name="pagetitle" value="<?php echo $row['PageTitle'];?>" class="input">
                    
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $row['Email'];?>" class="input">
                    
                    <label>Mobile Number</label>
                    <input type="text" name="mobilenumber" value="<?php echo $row['MobileNumber'];?>" class="input">
                    
                    <label>Timing</label>
                    <input type="text" name="timing" value="<?php echo $row['Timing'];?>" class="input">
                    
                    <label>Page Description (Address)</label>
                    <textarea name="pagedescription" class="textarea"><?php echo $row['PageDescription'];?></textarea>
                    
                    <div class="action-buttons">
                        <button type="submit" name="update" class="btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if($msg == "success"): ?>
    <script>
        alert("Update Successfully!");
        window.location.href='update-contact.php'; 
    </script>
    <?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>