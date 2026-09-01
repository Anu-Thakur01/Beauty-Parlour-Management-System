<?php
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
if (mysqli_connect_errno()) {
    echo "Connection Fail" . mysqli_connect_error();
}
?>