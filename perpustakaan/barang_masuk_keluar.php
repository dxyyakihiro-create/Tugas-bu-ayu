<?php
require_once 'koneksi.php';
require_once 'auth_check.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($jumlah > 0) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
            $stmt->bind_param("ii", $jumlah, $id_produk);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO barang_masuk_keluar (id_produk, tipe, jumlah, keterangan, id_user) VALUES (?, 'masuk', ?, ?, ?)");
            $stmt->bind_param("iisi", $id_produk, $jumlah, $keterangan, $id_user);
            $stmt->execute();
            
            $conn->commit();
            $success = "Stok berhasil ditambahkan dan dicatat dalam riwayat.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal menambah stok: " . $e->getMessage();
        }
    } else {
        $error = "Jumlah harus lebih dari 0";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'kurangi') {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($jumlah > 0) {
        $stmt = $conn->prepare("SELECT stok FROM produk WHERE id_produk = ?");
        $stmt->bind_param("i", $id_produk);
        $stmt->execute();
        $result = $stmt->get_result();
        $produk = $result->fetch_assoc();
        
        if ($produk['stok'] >= $jumlah) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
                $stmt->bind_param("ii", $jumlah, $id_produk);
                $stmt->execute();
                
                $stmt = $conn->prepare("INSERT INTO barang_masuk_keluar (id_produk, tipe, jumlah, keterangan, id_user) VALUES (?, 'keluar', ?, ?, ?)");
                $stmt->bind_param("iisi", $id_produk, $jumlah, $keterangan, $id_user);
                $stmt->execute();
                
                $conn->commit();
                $success = "Stok berhasil dikurangi dan dicatat dalam riwayat.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal mengurangi stok: " . $e->getMessage();
            }
        } else {
            $error = "Stok tidak mencukupi! Stok saat ini: " . $produk['stok'];
        }
    } else {
        $error = "Jumlah harus lebih dari 0";
    }
}
 $produk_list = $conn->query("SELECT * FROM produk ORDER BY nama_produk");

 $barang_masuk = $conn->query("
    SELECT bmk.*, p.nama_produk, r.username 
    FROM barang_masuk_keluar bmk
    JOIN produk p ON bmk.id_produk = p.id_produk
    JOIN registrasi r ON bmk.id_user = r.id
    WHERE bmk.tipe = 'masuk'
    ORDER BY bmk.tanggal DESC
    LIMIT 50
");

 $barang_keluar = $conn->query("
    SELECT bmk.*, p.nama_produk, r.username 
    FROM barang_masuk_keluar bmk
    JOIN produk p ON bmk.id_produk = p.id_produk
    JOIN registrasi r ON bmk.id_user = r.id
    WHERE bmk.tipe = 'keluar'
    ORDER BY bmk.tanggal DESC
    LIMIT 50
");

 // Query baru untuk laporan gabungan
 $barang_gabungan = $conn->query("
    SELECT bmk.*, p.nama_produk, r.username 
    FROM barang_masuk_keluar bmk
    JOIN produk p ON bmk.id_produk = p.id_produk
    JOIN registrasi r ON bmk.id_user = r.id
    ORDER BY bmk.tanggal DESC
    LIMIT 100
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barang Masuk/Keluar</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1, h2 { margin-top: 0; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .menu { margin-bottom: 20px; }
        .btn { display: inline-block; padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 10px; }
        .btn-primary { background-color: #007bff; }
        .btn-success { background-color: #28a745; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-secondary { background-color: #6c757d; }
        .form-section { background-color: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="number"], input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .low-stock { color: #dc3545; font-weight: bold; }
        .medium-stock { color: #ffc107; font-weight: bold; }
        .high-stock { color: #28a745; font-weight: bold; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .stock-status { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-low { background-color: #f8d7da; color: #721c24; }
        .status-medium { background-color: #fff3cd; color: #856404; }
        .status-high { background-color: #d4edda; color: #155724; }
        .tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; background-color: #f1f1f1; border: 1px solid #ddd; border-bottom: none; margin-right: 5px; }
        .tab.active { background-color: white; border-bottom: 1px solid white; margin-bottom: -1px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .badge-masuk { background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .badge-keluar { background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        
        .no-print { 
            display: block; 
        }
        .print-title {
            display: none;
        }
        @media print {
            body { 
                background-color: #fff; 
                padding: 0;
            }
            .container { 
                box-shadow: none; 
                max-width: 100%;
            }
            .no-print { 
                display: none !important; 
            }
            .print-title {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                font-size: 18px;
                font-weight: bold;
            }
            .tab-content {
                display: block !important;
            }
            .tab-content:not(:last-child) {
                page-break-after: always;
            }
            h2 {
                margin-top: 10px;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <h1>Barang Masuk/Keluar</h1>
            <div>
                Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="menu no-print">
            <a href="index.php" class="btn btn-secondary">Kembali</a>
            <a href="produk/tambah.php" class="btn btn-primary">Tambah Produk</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success no-print"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger no-print"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="tabs no-print">
            <div class="tab active" onclick="openTab(event, 'input-form')">Input Barang</div>
            <div class="tab" onclick="openTab(event, 'barang-masuk')">Barang Masuk</div>
            <div class="tab" onclick="openTab(event, 'barang-keluar')">Barang Keluar</div>
            <div class="tab" onclick="openTab(event, 'laporan-gabungan')">Laporan Gabungan</div>
            <div class="tab" onclick="openTab(event, 'daftar-produk')">Daftar Produk</div>
        </div>
        
        <div id="input-form" class="tab-content active">
            <div class="form-section">
                <h2>Barang Masuk</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_produk_masuk">Pilih Produk</label>
                            <select id="id_produk_masuk" name="id_produk" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php 
                                if ($produk_list->num_rows > 0) {
                                    $produk_list->data_seek(0);
                                    while($row = $produk_list->fetch_assoc()): 
                                        $id_field = isset($row['id_produk']) ? 'id_produk' : (isset($row['id']) ? 'id' : 'unknown');
                                ?>
                                <option value="<?= $row[$id_field] ?>"><?= htmlspecialchars($row['nama_produk']) ?> (Stok: <?= $row['stok'] ?>)</option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_masuk">Jumlah</label>
                            <input type="number" id="jumlah_masuk" name="jumlah" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="keterangan_masuk">Keterangan</label>
                        <input type="text" id="keterangan_masuk" name="keterangan" placeholder="Contoh: Pembelian dari supplier">
                    </div>
                    <button type="submit" class="btn btn-success">Tambah Stok</button>
                </form>
            </div>
            
            <div class="form-section">
                <h2>Barang Keluar</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="kurangi">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_produk_keluar">Pilih Produk</label>
                            <select id="id_produk_keluar" name="id_produk" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php 
                                if ($produk_list->num_rows > 0) {
                                    $produk_list->data_seek(0);
                                    while($row = $produk_list->fetch_assoc()): 
                                        $id_field = isset($row['id_produk']) ? 'id_produk' : (isset($row['id']) ? 'id' : 'unknown');
                                ?>
                                <option value="<?= $row[$id_field] ?>" data-stok="<?= $row['stok'] ?>">
                                    <?= htmlspecialchars($row['nama_produk']) ?> (Stok: <?= $row['stok'] ?>)
                                </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_keluar">Jumlah</label>
                            <input type="number" id="jumlah_keluar" name="jumlah" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="keterangan_keluar">Keterangan</label>
                        <input type="text" id="keterangan_keluar" name="keterangan" placeholder="Contoh: Rusak, Hilang, atau digunakan">
                    </div>
                    <button type="submit" class="btn btn-danger">Kurangi Stok</button>
                </form>
            </div>
        </div>
        
        <div id="barang-masuk" class="tab-content">
            <h2>Riwayat Barang Masuk</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Pengguna</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($barang_masuk->num_rows > 0) {
                        while($row = $barang_masuk->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><span class="badge-masuk">+<?= $row['jumlah'] ?></span></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='6'>Tidak ada data barang masuk.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="barang-keluar" class="tab-content">
            <h2>Riwayat Barang Keluar</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Pengguna</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($barang_keluar->num_rows > 0) {
                        while($row = $barang_keluar->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><span class="badge-keluar">-<?= $row['jumlah'] ?></span></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='6'>Tidak ada data barang keluar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- TAB BARU: LAPORAN GABUNGAN -->
        <div id="laporan-gabungan" class="tab-content">
            <div class="print-title">LAPORAN GABUNGAN BARANG MASUK & KELUAR</div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;" class="no-print">
                <h2 style="margin: 0;">Laporan Gabungan</h2>
                <button onclick="window.print()" class="btn btn-primary">Cetak Laporan</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Pengguna</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($barang_gabungan->num_rows > 0) {
                        while($row = $barang_gabungan->fetch_assoc()): 
                            $tipe_class = ($row['tipe'] == 'masuk') ? 'badge-masuk' : 'badge-keluar';
                            $tipe_text = ($row['tipe'] == 'masuk') ? 'Masuk' : 'Keluar';
                            $jumlah_prefix = ($row['tipe'] == 'masuk') ? '+' : '-';
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><span class="<?= $tipe_class ?>"><?= $tipe_text ?></span></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><?= $jumlah_prefix . $row['jumlah'] ?></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='7'>Tidak ada data barang masuk atau keluar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="daftar-produk" class="tab-content">
            <h2>Daftar Produk & Stok</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok Saat Ini</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($produk_list->num_rows > 0) {
                        $produk_list->data_seek(0);
                        while($row = $produk_list->fetch_assoc()): 
                            $id_field = isset($row['id_produk']) ? 'id_produk' : (isset($row['id']) ? 'id' : 'unknown');
                            
                            $stockClass = '';
                            $statusText = '';
                            $statusClass = '';
                            
                            if ($row['stok'] == 0) {
                                $stockClass = 'low-stock';
                                $statusText = 'Habis';
                                $statusClass = 'status-low';
                            } elseif ($row['stok'] < 10) {
                                $stockClass = 'medium-stock';
                                $statusText = 'Rendah';
                                $statusClass = 'status-medium';
                            } else {
                                $stockClass = 'high-stock';
                                $statusText = 'Aman';
                                $statusClass = 'status-high';
                            }
                    ?>
                    <tr>
                        <td><?= $row[$id_field] ?></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td>Rp <?= number_format($row['harga'], 2, ',', '.') ?></td>
                        <td class="<?= $stockClass ?>"><?= $row['stok'] ?></td>
                        <td><span class="stock-status <?= $statusClass ?>"><?= $statusText ?></span></td>
                        <td>
                            <a href="produk/tambah_stok.php?id=<?= $row[$id_field] ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">+ Stok</a>
                            <a href="produk/kurangi_stok.php?id=<?= $row[$id_field] ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">- Stok</a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='6'>Tidak ada produk ditemukan.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    document.getElementById('id_produk_keluar').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const maxStock = selectedOption.getAttribute('data-stok');
        const jumlahInput = document.getElementById('jumlah_keluar');
        
        if (maxStock) {
            jumlahInput.max = maxStock;
            jumlahInput.placeholder = `Maksimal: ${maxStock}`;
        } else {
            jumlahInput.max = '';
            jumlahInput.placeholder = '';
        }
    });
    </script>
</body>
</html>