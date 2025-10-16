<?php
session_start();
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/auth_check.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_POST['add'])) {
    $id_produk = $_POST['id_produk'];
    $_SESSION['cart'][$id_produk] = ($_SESSION['cart'][$id_produk] ?? 0) + (int)$_POST['jumlah'];
}

if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header("Location: index.php");
    exit();
}

 $receipt = null;
if (isset($_POST['bayar'])) {
    if (!empty($_SESSION['cart'])) {
        $total = 0;
        $items = [];
        $ids = array_keys($_SESSION['cart']);
        
        $produk = $conn->query("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk IN (".implode(',',$ids).")");
        
        while($p = $produk->fetch_assoc()){
            $j = $_SESSION['cart'][$p['id_produk']]; 
            if ($j > $p['stok']) { 
                $error = "Stok {$p['nama_produk']} tidak cukup!"; 
                break; 
            }
            $sub = $p['harga'] * $j;
            $total += $sub;
            
            $items[] = [
                'id_produk' => $p['id_produk'], 
                'nama' => $p['nama_produk'], 
                'harga' => $p['harga'], 
                'jml' => $j, 
                'sub' => $sub
            ];
        }

        if (!isset($error)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO penjualan (id_pelanggan, tanggal_penjualan, total_harga) VALUES (?, CURDATE(), ?)");
                $id_pelanggan = 1; 
                $stmt->bind_param("id", $id_pelanggan, $total);
                $stmt->execute();
                $id_jual = $conn->insert_id;
                
                foreach($items as $i) {
                    $stmt_detail = $conn->prepare("INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, subtotal, harga_satuan) VALUES (?, ?, ?, ?, ?)");
                    $stmt_detail->bind_param("iiidd", $id_jual, $i['id_produk'], $i['jml'], $i['sub'], $i['harga']);
                    $stmt_detail->execute();

                    $stmt_update = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
                    $stmt_update->bind_param("ii", $i['jml'], $i['id_produk']);
                    $stmt_update->execute();

                    $id_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                    $stmt_transaksi = $conn->prepare("INSERT INTO barang_masuk_keluar (id_produk, tipe, jumlah, keterangan, id_user) VALUES (?, 'keluar', ?, ?, ?)");
                    $keterangan_transaksi = "Terjual dengan ID Penjualan #{$id_jual}";
                    $stmt_transaksi->bind_param("iisi", $i['id_produk'], $i['jml'], $keterangan_transaksi, $id_user);
                    $stmt_transaksi->execute();
                }
                
                $conn->commit();
                $receipt = ['id'=>$id_jual, 'items'=>$items, 'total'=>$total];
                unset($_SESSION['cart']);

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Terjadi kesalahan saat memproses transaksi: " . $e->getMessage();
            }
        }
    } else {
        $error = "Keranjang kosong!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kasir</title>
    <style>
        @media print { .no-print { display: none !important; } body { font-family: 'Courier New', Courier, monospace; } }
        body { font-family:Arial;margin:20px; }
        table { border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .btn { padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
<center class="no-print"><h1>Kasir</h1><a href="../index.php">Kembali</a></center>

<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<?php if ($receipt): ?>
    <div id="receipt-section">
        <hr>
        <h2>Struk #<?= str_pad($receipt['id'], 5, '0', STR_PAD_LEFT) ?></h2>
        <p>Tanggal: <?= date('d-m-Y') ?></p>
        <p>Kasir: <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></p>
        <table border="1" cellpadding="5" style="border-collapse:collapse;margin:auto;">
            <tr><th>Produk</th><th>Jml</th><th>Harga</th><th>Subtotal</th></tr>
            <?php foreach($receipt['items'] as $i): ?>
            <tr>
                <td><?= $i['nama'] ?></td>
                <td><?= $i['jml'] ?></td>
                <td><?= number_format($i['harga']) ?></td>
                <td><?= number_format($i['sub']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h3>Total: Rp <?= number_format($receipt['total']) ?></h3>
        <center class="no-print">
            <button class="btn btn-primary" onclick="window.print()">Cetak Struk</button>
            <a href="index.php" class="btn btn-primary">Transaksi Baru</a>
        </center>
    </div>
<?php else: ?>
    <form method="post" class="no-print">
        <select name="id_produk" required>
            <option value="">-- Pilih Produk --</option>
            <?php 
            $list = $conn->query("SELECT id_produk, nama_produk, stok FROM produk ORDER BY nama_produk"); 
            while($r = $list->fetch_assoc()) {
                echo "<option value='{$r['id_produk']}'>{$r['nama_produk']} (Stok: {$r['stok']})</option>"; 
            }
            ?>
        </select>
        <input type="number" name="jumlah" min="1" value="1" required>
        <button type="submit" name="add" class="btn btn-primary">Tambah</button>
    </form>

    <h2 class="no-print">Keranjang</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p class="no-print">Kosong.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" style="border-collapse:collapse;" class="no-print">
            <tr><th>Produk</th><th>Jml</th><th>Subtotal</th><th>Aksi</th></tr>
            <?php 
            $total = 0; 
            $ids = array_keys($_SESSION['cart']);
            $cart_produk = $conn->query("SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN (".implode(',',$ids).")");
            while($p = $cart_produk->fetch_assoc()):
                $j = $_SESSION['cart'][$p['id_produk']]; 
                $sub = $p['harga'] * $j; 
                $total += $sub;
            ?>
            <tr>
                <td><?= $p['nama_produk'] ?></td>
                <td><?= $j ?></td>
                <td><?= number_format($sub) ?></td>
                <!-- PERBAIKAN KRUSIAL: Link Hapus menggunakan id_produk -->
                <td><a href="?remove=<?= $p['id_produk'] ?>" class="btn btn-danger">Hapus</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <h3 class="no-print">Total: Rp <?= number_format($total) ?></h3>
        <form method="post" class="no-print"><button type="submit" name="bayar" class="btn btn-primary">BAYAR</button></form>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>