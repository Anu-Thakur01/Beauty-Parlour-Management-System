<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['bpmsaid']) == 0) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'invoice';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

$q = mysqli_real_escape_string($con, $q);
$prefixQuery = $q . '%';
$suggestions = [];

if ($type === 'appointment') {
    $query = mysqli_query($con, "
        SELECT DISTINCT u.FullName, a.AppointmentNumber, a.UserID
        FROM tblappointment a
        JOIN tblusers u ON a.UserID = u.ID
        WHERE u.FullName LIKE '$prefixQuery'
           OR a.AppointmentNumber LIKE '$prefixQuery'
           OR u.MobileNumber LIKE '$prefixQuery'
           OR a.UserID LIKE '$prefixQuery'
        ORDER BY u.FullName ASC, a.AppointmentNumber ASC
        LIMIT 10");

    while ($row = mysqli_fetch_array($query)) {
        $name = trim((string) $row['FullName']);
        $apptNo = trim((string) $row['AppointmentNumber']);
        $userId = trim((string) $row['UserID']);

        if ($name !== '') {
            $suggestions[] = ['label' => $name, 'value' => $name];
        } elseif ($apptNo !== '') {
            $suggestions[] = ['label' => $apptNo, 'value' => $apptNo];
        } elseif ($userId !== '') {
            $suggestions[] = ['label' => $userId, 'value' => $userId];
        }
    }
} else {
    $query = mysqli_query($con, "
        SELECT DISTINCT u.FullName, i.BillingId, u.MobileNumber
        FROM tblinvoice i
        JOIN tblusers u ON u.id = i.Userid
        WHERE u.FullName LIKE '$prefixQuery'
           OR i.BillingId LIKE '$prefixQuery'
           OR u.MobileNumber LIKE '$prefixQuery'
        ORDER BY u.FullName ASC, i.BillingId ASC
        LIMIT 10");

    while ($row = mysqli_fetch_array($query)) {
        $name = trim((string) $row['FullName']);
        $billingId = trim((string) $row['BillingId']);
        $mobile = trim((string) $row['MobileNumber']);

        if ($name !== '') {
            $suggestions[] = ['label' => $name, 'value' => $name];
        } elseif ($billingId !== '') {
            $suggestions[] = ['label' => $billingId, 'value' => $billingId];
        } elseif ($mobile !== '') {
            $suggestions[] = ['label' => $mobile, 'value' => $mobile];
        }
    }
}

$seen = [];
$finalSuggestions = [];
foreach ($suggestions as $item) {
    $key = strtolower($item['value']);
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $finalSuggestions[] = $item;
    }
}

header('Content-Type: application/json');
echo json_encode(array_slice($finalSuggestions, 0, 8));
