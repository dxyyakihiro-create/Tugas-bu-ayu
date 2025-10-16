<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/config.php';

if ($_SESSION['level'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    header("Location: ../index.php?error=invalid_product");
    exit();
}
 $id_produk = (int)$_GET['id_produk'];

 $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
 $stmt->bind_param("i", $id_produk);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php?error=product_not_found");
    exit();
}
 $produk = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $gambar_lama = $produk['gambar'];
    $gambar_baru = $gambar_lama;

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = dirname(__DIR__) . "/uploads/";
        $gambar_baru = time() . '_' . basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $gambar_baru;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $extensions = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($imageFileType, $extensions)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                if (!empty($gambar_lama) && file_exists($target_dir . $gambar_lama)) {
                    unlink($target_dir . $gambar_lama);
                }
            } else {
                $error = "Gagal mengupload gambar baru.";
            }
        } else {
            $error = "Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan!";
        }
    }
    
    if (!isset($error)) {
        $sql = "UPDATE produk SET nama_produk = ?, harga = ?, stok = ?, gambar = ? WHERE id_produk = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdisi", $nama_produk, $harga, $stok, $gambar_baru, $id_produk);
        
        if ($stmt->execute()) {
            header("Location: ../index.php?success=product_updated");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Produk</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .menu { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 10px; }
        .btn-primary { background-color: #4CAF50; }
        .btn-secondary { background-color: #555; }
        .btn-danger { background-color: #f44336; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .form-actions { margin-top: 20px; text-align: center; }
        .current-image { margin-top: 10px; }
        .current-image img { max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Produk</h1>
            <div>
                Selamat datang, <strong><?= $_SESSION['username'] ?></strong>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="menu">
            <a href="../index.php" class="btn btn-secondary">Kembali</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama_produk">Nama Produk</label>
                <input type="text" id="nama_produk" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="harga">Harga Produk</label>
                <input type="number" id="harga" name="harga" min="0" step="0.01" value="<?= htmlspecialchars($produk['harga']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stok">Stok</label>
                <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($produk['stok']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="gambar">Gambar Produk</label>
                <input type="file" id="gambar" name="gambar" accept="image/*">
                <div class="current-image">
                    <small>Gambar saat ini:</small><br>
                    <?php if (!empty($produk['gambar'])): ?>
                        <img src="../uploads/<?= $produk['gambar'] ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                    <?php else: ?>
                        <span>Tidak ada gambar</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="../index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>