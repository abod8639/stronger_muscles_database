<?php
$host = "sql305.infinityfree.com"; // مثال: 192.168.1.100 أو دومين
$user = "if0_41267109";
$password = "bWtfE62xvJmht";
$database = "stronger_muscles_db";
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("❌ فشل الاتصال: " . $conn->connect_error);
}

echo "✅ الاتصال ناجح";
?>