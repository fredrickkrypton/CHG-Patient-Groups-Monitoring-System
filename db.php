
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "127.0.0.1";
$username = "root";
$password = ""; // Use your actual MariaDB password if one is set
$database = "afya";
$port = 3307;

try {
    $conn = mysqli_connect(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    mysqli_set_charset($conn, "utf8mb4");
    $_SESSION['conn'] = $conn;

} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to the afya database. Check your database credentials and port.");
}
?>
