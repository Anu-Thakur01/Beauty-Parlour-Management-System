<?php 
session_start();
error_reporting(0);
include('includes/dbconnection.php'); 

if (strlen($_SESSION['bpmsaid']) == 0) {
    header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Search | BPMS Admin</title>
    <link rel="stylesheet" href="css/search-appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content" id="main-content">
    <h1 class="page-title">Search Appointment</h1>
    
    <div class="card">
        <form method="post" name="search">
            <h3 class="card-title"><i class="fa fa-search"></i> Universal Search</h3>
            <div class="form-group">
                <label class="search-label">Search by Name, Mobile, Appointment Number or User ID</label>
                <div class="search-flex" style="position: relative;">
                    <input type="text" name="searchdata" id="searchdata" class="search-input" placeholder="Search anything..." required="true" autocomplete="off">
                    <button type="submit" name="search" class="search-btn">Search</button>
                    <div id="appointment-suggestions" class="search-suggestions" style="display:none;"></div>
                    <?php if(isset($_POST['search'])): ?>
                        <a href="search-appointment.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php
    if(isset($_POST['search'])) {
        $sdata = mysqli_real_escape_string($con, $_POST['searchdata']);
    ?>
    <div class="card result-card">
        <h4 class="result-title">Search Results for: "<span><?php echo $sdata;?></span>"</h4>
        
        <table class="result-table">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Appt. No</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ret = mysqli_query($con, "
                SELECT a.*, u.FullName, u.MobileNumber 
                FROM tblappointment a
                JOIN tblusers u ON a.UserID = u.ID
                WHERE a.AppointmentNumber LIKE '%$sdata%' 
                OR a.UserID LIKE '%$sdata%'
                OR u.FullName LIKE '%$sdata%'
                OR u.MobileNumber LIKE '%$sdata%'
            ");
            
            $num = mysqli_num_rows($ret);
            if($num > 0) {
                $cnt = 1;
                while ($row = mysqli_fetch_array($ret)) {
            ?>
                <tr>
                    <td><?php echo $cnt;?></td>
                    <td><strong>#<?php echo $row['AppointmentNumber'];?></strong></td>
                    <td><?php echo $row['FullName'];?></td>
                    <td><?php echo $row['MobileNumber'];?></td>
                    <td>
                        <small><?php echo date("d-M-Y", strtotime($row['AptDate']));?></small><br>
                        <span style="font-size:12px; color:#777;"><?php echo $row['AptTime'];?></span>
                    </td>
                    <td>
                        <?php 
                        if($row['Status'] == "Selected") {
                            echo '<span class="status-done">Accepted</span>';
                        } elseif($row['Status'] == "Rejected") {
                            echo '<span class="status-rejected">Rejected</span>';
                        } else {
                            echo '<span class="status-pending">Pending</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="view-appointment.php?viewid=<?php echo $row['ID'];?>" class="view-link">
                            <i class="fa fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            <?php 
                $cnt++;
                } 
            } else { ?>
                <tr>
                    <td colspan="7" class="no-record">
                        <i class="fa fa-frown-o"></i> No appointments found matching your search.
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
<script>
(function () {
    const input = document.getElementById('searchdata');
    const suggestionsBox = document.getElementById('appointment-suggestions');

    if (!input || !suggestionsBox) return;

    input.addEventListener('input', function () {
        const q = this.value.trim();
        if (q.length < 1) {
            suggestionsBox.style.display = 'none';
            suggestionsBox.innerHTML = '';
            return;
        }

        fetch('search-suggestions.php?type=appointment&q=' + encodeURIComponent(q))
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    suggestionsBox.style.display = 'none';
                    suggestionsBox.innerHTML = '';
                    return;
                }

                suggestionsBox.innerHTML = data.map(item => {
                    return '<div class="suggestion-item" data-value="' + item.value.replace(/"/g, '&quot;') + '">' + item.label + '</div>';
                }).join('');

                suggestionsBox.style.display = 'block';

                suggestionsBox.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function () {
                        input.value = this.dataset.value;
                        suggestionsBox.style.display = 'none';
                        suggestionsBox.innerHTML = '';
                    });
                });
            })
            .catch(() => {
                suggestionsBox.style.display = 'none';
                suggestionsBox.innerHTML = '';
            });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('#searchdata') && !event.target.closest('#appointment-suggestions')) {
            suggestionsBox.style.display = 'none';
        }
    });
})();
</script>
</body>
</html>
<?php } ?>