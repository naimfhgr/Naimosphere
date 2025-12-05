<?php
// config.php
// Datenbank-Verbindung

$host = "localhost";
$user = "212043_1_1";
$pass = "986x@HqhA=kf";
$db   = "212043_1_1";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB-Fehler: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
