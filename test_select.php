<?php
// test_select.php
// Einfacher Check, ob Daten in air_quality liegen

require_once "config.php";

echo "Debug: test_select.php<br><br>";

$sql = "SELECT * FROM air_quality ORDER BY time DESC LIMIT 20";
$res = $conn->query($sql);

if (!$res) {
    die("SQL-Fehler: " . $conn->error);
}

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo "Ergebnis:<br><pre>";
print_r($data);
echo "</pre>";
?>
