<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/config.php';

if ($_SESSION['level'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = dirname(__DIR__) . "/uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $gambar = time() . '_' . basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $gambar;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $extensions = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($imageFileType, $extensions)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                
            } else {
                $error = "Gagal mengupload gambar";
            }
        } else {
            $error = "Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan!";
        }
    }
    
    if (!isset($error)) {
        $sql = "INSERT INTO produk (nama_produk, harga, stok, gambar) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // Bind parameter: s=string, d=double, i=integer
        $stmt->bind_param("sdis", $nama_produk, $harga, $stok, $gambar);
        
        if ($stmt->execute()) {
            header("Location: ../index.php?success=product_added");
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
    <title>Tambah Produk</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .menu { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        .btn { display: inline-block; padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 10px; }
        .btn-primary { background-color: #4CAF50; }
        .btn-secondary { background-color: #555; }
        .btn-danger { background-color: #f44336; }
        .file-upload { margin-bottom: 10px; }
        .image-preview { margin-top: 10px; text-align: center; }
        .image-preview img { max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .form-actions { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tambah Produk</h1>
            <div>
                Selamat datang, <strong><?= $_SESSION['username'] ?></strong>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="menu">
            <a href="../index.php" class="btn btn-secondary">Kembali</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama_produk">Nama Produk</label>
                <input type="text" id="nama_produk" name="nama_produk" required>
            </div>
            
            <div class="form-group">
                <label for="harga">Harga Produk</label>
                <input type="number" id="harga" name="harga" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="stok">Stok Awal</label>
                <input type="number" id="stok" name="stok" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="gambar">Gambar Produk</label>
                <div class="file-upload">
                    <input type="file" id="gambar" name="gambar" accept="image/*" onchange="previewImage(this)">
                </div>
                <div id="imagePreview" class="image-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
                <a href="../index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <br>
                    <button type="button" onclick="removeImage()" class="btn btn-danger" style="margin-top: 10px;">Hapus Gambar</button>
                `;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function removeImage() {
        document.getElementById('gambar').value = '';
        document.getElementById('imagePreview').innerHTML = '';
    }
    </script>
</body>
</html>