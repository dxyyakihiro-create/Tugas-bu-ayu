<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/config.php';

if ($_SESSION['level'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    header('Location: ../index.php?error=invalid_product');
    exit();
}

 $id_produk = (int)$_GET['id_produk'];
 $stmt = $conn->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
 $stmt->bind_param("i", $id_produk);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php?error=product_not_found');
    exit();
}

 $produk = $result->fetch_assoc();
 $gambar_hapus = $produk['gambar'];

 $stmt_hapus = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
 $stmt_hapus->bind_param("i", $id_produk);

if ($stmt_hapus->execute()) {
    if (!empty($gambar_hapus)) {
        $path_gambar = dirname(__DIR__) . '/uploads/' . $gambar_hapus;
        if (file_exists($path_gambar)) {
            unlink($path_gambar);
        }
    }
    header('Location: ../index.php?success=product_deleted');
    exit();
} else {
    header('Location: ../index.php?error=delete_failed');
    exit();
}
?>