<?php
 $host = "localhost";
 $user = "root";
 $pass = "";
 $db = "db_penjualan"; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Koneksi gagal: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>