<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/config.php';

 $sql = "SELECT * FROM produk ORDER BY id DESC";
 $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .menu {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #4CAF50;
        }
        .btn-success {
            background-color: #4CAF50;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-warning {
            background-color: #ff9800;
        }
        .btn-secondary {
            background-color: #555;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .no-image {
            width: 60px;
            height: 60px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #777;
            font-size: 12px;
        }
        .low-stock {
            color: #f44336;
            font-weight: bold;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daftar Produk</h1>
            <div>
                Selamat datang, <strong><?= $_SESSION['username'] ?></strong>
                <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>

        <div class="menu">
            <a href="../index.php" class="btn btn-secondary">Kembali</a>
            <a href="../penjualan/index.php" class="btn btn-primary">Beli Produk</a>
                <a href="tambah.php" class="btn btn-primary">Tambah Produk</a>  
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['gambar'])): ?>
                                <?php 
                                $image_path = ROOT_PATH . 'uploads/' . $row['gambar'];
                                if (file_exists($image_path)): 
                                ?>
                                    <img src="<?= ROOT_URL ?>uploads/<?= $row['gambar'] ?>?v=<?= time() ?>" 
                                         alt="<?= htmlspecialchars($row['nama_produk']) ?>" class="product-image">
                                <?php else: ?>
                                    <div class="no-image">No Image</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><?= htmlspecialchars(substr($row['deskripsi'], 0, 50)) ?>...</td>
                        <td>Rp <?= number_format($row['harga'], 2, ',', '.') ?></td>
                        <td class="<?= $row['stok'] < 10 ? 'low-stock' : '' ?>"><?= $row['stok'] ?></td>
                        <td>
                            <div class="actions">
                                <?php if ($_SESSION['level'] == 'admin'): ?>
                                    <a href="tambah_stok.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm">+</a>
                                    <a href="kurangi_stok.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">-</a>
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus produk ini?')">Hapus</a>
                                <?php else: ?>
                                    <a href="../penjualan/index.php" class="btn btn-primary btn-sm">Beli</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>Belum ada produk</h3>
                <p>Silakan tambahkan produk terlebih dahulu</p>
                <?php if ($_SESSION['level'] == 'admin'): ?>
                    <a href="tambah.php" class="btn btn-primary">Tambah Produk</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>