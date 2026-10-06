<?php
session_start();
include 'koneksi.php';

// Cek status login
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role_user = $_SESSION['role'];

// Fitur Tambah Data (Hanya Bendahara)
if (isset($_POST['tambah']) && $role_user == 'bendahara') {
    $tanggal    = $_POST['tanggal'];
    $jenis      = $_POST['jenis'];
    $nominal    = $_POST['nominal'];
    $keterangan = $_POST['keterangan'];

    $query = "INSERT INTO transaksi (tanggal, jenis, nominal, keterangan) VALUES ('$tanggal', '$jenis', '$nominal', '$keterangan')";
    mysqli_query($koneksi, $query);
    header("Location: index.php");
    exit();
}

// Fitur Edit Data (Hanya Bendahara)
if (isset($_POST['update']) && $role_user == 'bendahara') {
    $id         = $_POST['id'];
    $tanggal    = $_POST['tanggal'];
    $jenis      = $_POST['jenis'];
    $nominal    = $_POST['nominal'];
    $keterangan = $_POST['keterangan'];

    $query = "UPDATE transaksi SET tanggal='$tanggal', jenis='$jenis', nominal='$nominal', keterangan='$keterangan' WHERE id=$id";
    mysqli_query($koneksi, $query);
    header("Location: index.php");
    exit();
}

// Fitur Hapus Data (Hanya Bendahara)
if (isset($_GET['hapus']) && $role_user == 'bendahara') {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM transaksi WHERE id = $id");
    header("Location: index.php");
    exit();
}

// Logika Filter & Cari Data
$where = [];
if (!empty($_GET['tgl_mulai']) && !empty($_GET['tgl_selesai'])) {
    $tgl_mulai   = $_GET['tgl_mulai'];
    $tgl_selesai = $_GET['tgl_selesai'];
    $where[]     = "tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}
if (!empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
    $where[] = "keterangan LIKE '%$keyword%'";
}

$query_where = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Hitung Ringkasan Keuangan
$query_masuk  = mysqli_query($koneksi, "SELECT SUM(nominal) AS total FROM transaksi WHERE jenis='masuk'");
$data_masuk   = mysqli_fetch_assoc($query_masuk);
$total_masuk  = $data_masuk['total'] ?? 0;

$query_keluar = mysqli_query($koneksi, "SELECT SUM(nominal) AS total FROM transaksi WHERE jenis='keluar'");
$data_keluar  = mysqli_fetch_assoc($query_keluar);
$total_keluar = $data_keluar['total'] ?? 0;

$total_saldo  = $total_masuk - $total_keluar;

// Ambil Data Transaksi
$transaksi = mysqli_query($koneksi, "SELECT * FROM transaksi $query_where ORDER BY tanggal DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PASKER BARUKLINTINKS</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-logout { background: #e74c3c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 5px; font-weight: bold; font-size: 0.85rem; }
        
        .summary-box { display: flex; gap: 15px; margin-bottom: 20px; }
        .card { flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; }
        .card h3 { font-size: 0.9rem; color: #7f8c8d; margin-bottom: 8px; }
        .card p { font-size: 1.4rem; font-weight: bold; }
        .saldo { color: #2980b9; } .pemasukan { color: #27ae60; } .pengeluaran { color: #c0392b; }
        
        .content-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        form.form-input { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        form.form-input input, form.form-input select, form.form-input button { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; }
        form.form-input button { grid-column: span 2; background: #27ae60; color: white; border: none; font-weight: bold; cursor: pointer; }
        
        .filter-box { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 8px; }
        .filter-box input { padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 0.85rem; }
        .btn-filter { background: #2980b9; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 0.85rem; }
        .btn-reset { background: #7f8c8d; color: white; border: none; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 0.85rem; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background-color: #f8f9fa; color: #2c3e50; }
        
        .btn-edit { background: #f39c12; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; margin-right: 4px; }
        .btn-delete { background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85rem; }

        /* Modal Edit CSS */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 12px; width: 100%; max-width: 450px; position: relative; }
        .close-btn { position: absolute; right: 15px; top: 15px; cursor: pointer; font-size: 1.2rem; font-weight: bold; color: #aaa; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <h1 style="font-size: 1.5rem;">Pencatatan Kas Pasker BaruklintinKS</h1>
                <small>Login sebagai: <b><?= $_SESSION['nama_lengkap']; ?> (<?= ucfirst($role_user); ?>)</b></small>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>

        <!-- Ringkasan Keuangan -->
        <div class="summary-box">
            <div class="card">
                <h3>TOTAL SALDO</h3>
                <p class="saldo">Rp <?= number_format($total_saldo, 0, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h3>PEMASUKAN</h3>
                <p class="pemasukan">Rp <?= number_format($total_masuk, 0, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h3>PENGELUARAN</h3>
                <p class="pengeluaran">Rp <?= number_format($total_keluar, 0, ',', '.'); ?></p>
            </div>
        </div>

        <!-- Form Tambah Transaksi (HANYA BENDAHARA) -->
        <?php if ($role_user == 'bendahara') : ?>
            <div class="content-box">
                <h2 style="margin-bottom: 15px; font-size: 1.2rem;">Tambah Transaksi</h2>
                <form action="" method="POST" class="form-input">
                    <input type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required>
                    <select name="jenis" required>
                        <option value="masuk">Pemasukan (+)</option>
                        <option value="keluar">Pengeluaran (-)</option>
                    </select>
                    <input type="number" name="nominal" placeholder="Nominal (Rp)" required>
                    <input type="text" name="keterangan" placeholder="Keterangan (misal: Beli Kertas)" required>
                    <button type="submit" name="tambah">Simpan Transaksi</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Tabel Riwayat Transaksi -->
        <div class="content-box">
            <h2 style="margin-bottom: 15px; font-size: 1.2rem;">Riwayat Transaksi</h2>
            
            <form action="" method="GET" class="filter-box">
                <input type="date" name="tgl_mulai" value="<?= $_GET['tgl_mulai'] ?? ''; ?>">
                <input type="date" name="tgl_selesai" value="<?= $_GET['tgl_selesai'] ?? ''; ?>">
                <input type="text" name="keyword" placeholder="Cari keterangan..." value="<?= $_GET['keyword'] ?? ''; ?>">
                <button type="submit" class="btn-filter">Cari / Filter</button>
                <a href="index.php" class="btn-reset">Reset</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Jenis</th>
                        <th>Nominal</th>
                        <?php if ($role_user == 'bendahara') : ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($transaksi) > 0) : ?>
                        <?php while ($row = mysqli_fetch_assoc($transaksi)) : ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                <td>
                                    <span style="color: <?= $row['jenis'] == 'masuk' ? '#27ae60' : '#c0392b'; ?>; font-weight: bold;">
                                        <?= $row['jenis'] == 'masuk' ? 'Pemasukan' : 'Pengeluaran'; ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                <?php if ($role_user == 'bendahara') : ?>
                                    <td>
                                        <button class="btn-edit" onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                        <a href="index.php?hapus=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #7f8c8d;">Data tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL POPUP EDIT TRANSAKSI -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="tutupModalEdit()">&times;</span>
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Edit Transaksi</h3>
            <form action="" method="POST" class="form-input">
                <input type="hidden" name="id" id="edit_id">
                <input type="date" name="tanggal" id="edit_tanggal" required>
                <select name="jenis" id="edit_jenis" required>
                    <option value="masuk">Pemasukan (+)</option>
                    <option value="keluar">Pengeluaran (-)</option>
                </select>
                <input type="number" name="nominal" id="edit_nominal" placeholder="Nominal (Rp)" required>
                <input type="text" name="keterangan" id="edit_keterangan" placeholder="Keterangan" required>
                <button type="submit" name="update" style="background: #f39c12;">Update Transaksi</button>
            </form>
        </div>
    </div>

    <script>
        function bukaModalEdit(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_tanggal').value = data.tanggal;
            document.getElementById('edit_jenis').value = data.jenis;
            document.getElementById('edit_nominal').value = data.nominal;
            document.getElementById('edit_keterangan').value = data.keterangan;
            
            document.getElementById('modalEdit').style.display = 'flex';
        }

        function tutupModalEdit() {
            document.getElementById('modalEdit').style.display = 'none';
        }

        // Tutup modal jika klik di luar area modal
        window.onclick = function(event) {
            const modal = document.getElementById('modalEdit');
            if (event.target == modal) {
                tutupModalEdit();
            }
        }
    </script>

</body>
</html>