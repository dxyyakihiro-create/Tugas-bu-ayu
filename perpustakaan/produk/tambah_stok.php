<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/config.php';

if (!isset($_GET['id_produk']) || !is_numeric($_GET['id_produk'])) {
    header('Location: ../barang_masuk_keluar.php?error=invalid_product');
    exit();
}

 $id_produk = (int)$_GET['id_produk'];

 $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
 $stmt->bind_param("i", $id_produk);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../barang_masuk_keluar.php?error=product_not_found');
    exit();
}

 $produk = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    if ($jumlah <= 0) {
        $error = "Jumlah harus lebih dari 0";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
            $stmt->bind_param("ii", $jumlah, $id_produk);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO barang_masuk_keluar (id_produk, tipe, jumlah, keterangan, id_user) VALUES (?, 'masuk', ?, ?, ?)");
            $stmt->bind_param("iisi", $id_produk, $jumlah, $keterangan, $id_user);
            $stmt->execute();

            $conn->commit();
            header("Location: ../barang_masuk_keluar.php?success=stock_added");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Stok</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .menu { margin-bottom: 20px; }
        .product-info { background-color: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .stock-info { display: flex; justify-content: space-between; margin: 15px 0; }
        .stock-box { text-align: center; padding: 10px; background-color: #eee; border-radius: 5px; flex: 1; margin: 0 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"], input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 10px; }
        .btn-success { background-color: #4CAF50; }
        .btn-secondary { background-color: #555; }
        .btn-danger { background-color: #f44336; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tambah Stok</h1>
            <div>
                Selamat datang, <strong><?= $_SESSION['username'] ?></strong>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="menu">
            <a href="../barang_masuk_keluar.php" class="btn btn-secondary">Kembali</a>
        </div>
        
        <div class="product-info">
            <h3><?= htmlspecialchars($produk['nama_produk']) ?></h3>
            
            <div class="stock-info">
                <div class="stock-box">
                    <div>Stok Saat Ini</div>
                    <div><?= $produk['stok'] ?></div>
                </div>
                
                <div class="stock-box" id="resultBox">
                    <div>Stok Setelah Ditambah</div>
                    <div id="resultValue"><?= $produk['stok'] + 1 ?></div>
                </div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="tambahStokForm">
            <div class="form-group">
                <label for="jumlah">Jumlah Stok yang Ditambahkan</label>
                <input type="number" id="jumlah" name="jumlah" 
                       min="1" value="1" required oninput="updateResult()">
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <input type="text" id="keterangan" name="keterangan" placeholder="Contoh: Pembelian dari supplier" required>
            </div>
            <button type="submit" class="btn btn-success">Tambah Stok</button>
            <a href="../barang_masuk_keluar.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>

    <script>
    function updateResult() {
        const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
        const stokSaatIni = <?= $produk['stok'] ?>;
        const hasil = stokSaatIni + jumlah;
        
        document.getElementById('resultValue').textContent = hasil;
        
        const resultBox = document.getElementById('resultBox');
        const resultValue = document.getElementById('resultValue');
        
        if (hasil < 5) {
            resultBox.style.backgroundColor = '#fff8e1';
            resultValue.style.color = '#ff8f00';
        } else {
            resultBox.style.backgroundColor = '#e8f5e9';
            resultValue.style.color = '#2e7d32';
        }
    }
    
    updateResult();
    </script>
</body>
</html>