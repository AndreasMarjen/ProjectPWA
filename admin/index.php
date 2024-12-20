<?php
include 'db_connect.php'; // Include your database connection file

// Fetch data for facilities, activities, distance, and managers
$fasilitas = $conn->query("SELECT * FROM fasilitas");
$aktifitas = $conn->query("SELECT * FROM aktifitas");
$jarak = $conn->query("SELECT * FROM jarak");
$pengelola = $conn->query("SELECT * FROM pengelola");

// Handle adding or editing tourist objects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        if (isset($_POST['add'])) {
            // Ambil data dari form
            $nama = $_POST['nama'];
            $alamat = $_POST['alamat'];
            $harga = $_POST['harga'];
            $estimasi_waktu = $_POST['estimasi_waktu'];
            $ket = $_POST['ket'];
            $kd_fasilitas = isset($_POST['kd_fasilitas']) ? json_encode($_POST['kd_fasilitas']) : '[]';
            $kd_aktifitas = isset($_POST['kd_aktifitas']) ? json_encode($_POST['kd_aktifitas']) : '[]';
            $jarak_tempuh = $_POST['jarak_tempuh'];
            $waktu_tempuh = $_POST['waktu_tempuh'];
            $nama_pengelola = $_POST['nama_pengelola'];
            $kontak_pengelola = $_POST['kontak_pengelola'];
        
            // Proses tambah pengelola
            $query_pengelola = "INSERT INTO pengelola (nama_pengelola, kontak_pengelola) 
                                VALUES ('$nama_pengelola', '$kontak_pengelola')";
            $conn->query($query_pengelola);
            $id_pengelola = $conn->insert_id; // Dapatkan ID pengelola yang baru ditambahkan
        
            // Proses tambah jarak
            $query_jarak = "INSERT INTO jarak (jarak_tempuh, waktu_tempuh) 
                            VALUES ('$jarak_tempuh', '$waktu_tempuh')";
            $conn->query($query_jarak);
            $kd_jarak = $conn->insert_id; // Dapatkan ID jarak yang baru ditambahkan
        
            // Proses tambah objek wisata
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = uniqid() . "_" . basename($_FILES['foto']['name']);
                $target_dir = 'uploads/';
                $foto_path = $target_dir . $file_name;
        
                // Pindahkan file yang diunggah
                move_uploaded_file($file_tmp, $foto_path);
            }
        
            $query_objek = "INSERT INTO objek_wisata (nama_objek, alamat, harga_tiket, estimasi_waktu, ket_objek, kd_fasilitas, kd_aktifitas, kd_jarak, id_pengelola, foto) 
                            VALUES ('$nama', '$alamat', '$harga', '$estimasi_waktu', '$ket', '$kd_fasilitas', '$kd_aktifitas', '$kd_jarak', '$id_pengelola', '$foto_path')";
            $conn->query($query_objek);
        
            // Redirect setelah berhasil menambahkan data
            header("Location: index.php");
            exit;
        }
        
    } elseif (isset($_POST['edit'])) {
        // Handle edit tourist object
        $kd_objek = $_POST['kd_objek'];
        $nama = $_POST['nama'];
        $alamat = $_POST['alamat'];
        $harga = $_POST['harga'];
        $estimasi_waktu = $_POST['estimasi_waktu'];
        $ket = $_POST['ket'];
        $kd_fasilitas = isset($_POST['kd_fasilitas']) ? json_encode($_POST['kd_fasilitas']) : '[]';
        $kd_aktifitas = isset($_POST['kd_aktifitas']) ? json_encode($_POST['kd_aktifitas']) : '[]';
        $jarak_tempuh = $_POST['jarak_tempuh'];
        $waktu_tempuh = $_POST['waktu_tempuh'];
        $nama_pengelola = $_POST['nama_pengelola'];
        $kontak_pengelola = $_POST['kontak_pengelola'];

        // Update tourist object data
        $query_objek = "UPDATE objek_wisata SET 
                        nama_objek = '$nama', 
                        alamat = '$alamat', 
                        harga_tiket = '$harga', 
                        estimasi_waktu = '$estimasi_waktu', 
                        ket_objek = '$ket', 
                        kd_fasilitas = '$kd_fasilitas', 
                        kd_aktifitas = '$kd_aktifitas' 
                        WHERE kd_objek = '$kd_objek'";
        $conn->query($query_objek);

        // Update distance data
        $query_jarak = "UPDATE jarak SET 
                        jarak_tempuh = '$jarak_tempuh', 
                        waktu_tempuh = '$waktu_tempuh' 
                        WHERE kd_jarak = (SELECT kd_jarak FROM objek_wisata WHERE kd_objek = '$kd_objek')";
        $conn->query($query_jarak);

        // Update manager data
        $query_pengelola = "UPDATE pengelola SET 
                            nama_pengelola = '$nama_pengelola', 
                            kontak_pengelola = '$kontak_pengelola' 
                            WHERE id_pengelola = (SELECT id_pengelola FROM objek_wisata WHERE kd_objek = '$kd_objek')";
        $conn->query($query_pengelola);

        // Handle photo upload if any
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto']['tmp_name'];
            $file_name = uniqid() . "_" . basename($_FILES['foto']['name']);
            $target_dir = 'uploads/';
            $target_file = $target_dir . $file_name;

            move_uploaded_file($file_tmp, $target_file);

            // Update tourist object photo
            $query_foto = "UPDATE objek_wisata SET foto = '$target_file' WHERE kd_objek = '$kd_objek'";
            $conn->query($query_foto);
        }

        // Redirect to admin page after update
        header("Location: index.php"); // Change 'admin_page.php' to your admin page file
        exit;
    }
}

// Handle delete operation
if (isset($_GET['delete_id'])) {
    // Code to delete tourist object
}

// Fetch tourist objects data
$wisata = $conn->query("SELECT ow.*, 
    GROUP_CONCAT(DISTINCT f.nama_fasilitas SEPARATOR ', ') AS fasilitas, 
    GROUP_CONCAT(DISTINCT a.nama_aktifitas SEPARATOR ', ') AS aktifitas, 
    j.jarak_tempuh, j.waktu_tempuh, p.nama_pengelola, p.kontak_pengelola, ow.foto
    FROM objek_wisata ow
    LEFT JOIN fasilitas f ON JSON_CONTAINS(ow.kd_fasilitas, JSON_QUOTE(f.kd_fasilitas))
    LEFT JOIN aktifitas a ON JSON_CONTAINS(ow.kd_aktifitas, JSON_QUOTE(a.kd_aktifitas))
    LEFT JOIN jarak j ON ow.kd_jarak = j.kd_jarak
    LEFT JOIN pengelola p ON ow.id_pengelola = p.id_pengelola
    GROUP BY ow.kd_objek");

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_query = "SELECT ow.*, j.jarak_tempuh, j.waktu_tempuh, p.nama_pengelola, p.kontak_pengelola 
                   FROM objek_wisata ow
                   LEFT JOIN jarak j ON ow.kd_jarak = j.kd_jarak
                   LEFT JOIN pengelola p ON ow.id_pengelola = p.id_pengelola
                   WHERE ow.kd_objek = '$edit_id'";
    $edit_result = $conn->query($edit_query);
    $edit_data = $edit_result->fetch_assoc();
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
  
    // First, delete the related entries in the database.
    $delete_query = "DELETE FROM objek_wisata WHERE kd_objek = '$delete_id'";
  
    if ($conn->query($delete_query)) {
        // Redirect to prevent re-submission of form on refresh
        echo "<script>alert('Objek Wisata berhasil dihapus'); window.location.href = 'index.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan saat menghapus objek wisata');</script>";
    }
  }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kelola Objek Wisata</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<!-- Navbar -->
<nav style="background-color: #007bff; padding: 15px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;">
    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: auto;">
        <h1 style="color: white; font-size: 24px; margin: 0;">Kelola Objek Wisata</h1>
        <ul style="list-style: none; margin: 0; padding: 0; display: flex; align-items: center;">
            <li style="margin: 0 10px;">
                <a href="fasilitas_aktifitas.php" style="text-decoration: none; color: white; font-size: 16px; padding: 8px 15px; border: 2px solid white; border-radius: 5px; transition: all 0.3s ease;">
                    Kelola Fasilitas & Aktivitas
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Form tambah atau edit objek wisata -->
<form method="POST" enctype="multipart/form-data" style="margin-top: 5rem;">
    <input type="hidden" name="kd_objek" value="<?= isset($edit_data) ? $edit_data['kd_objek'] : '' ?>">
    <input type="text" name="nama" placeholder="Nama Objek" value="<?= isset($edit_data) ? $edit_data['nama_objek'] : '' ?>" required>
    <input type="text" name="alamat" placeholder="Alamat" value="<?= isset($edit_data) ? $edit_data['alamat'] : '' ?>" required>
    <input type="number" name="harga" placeholder="Harga Tiket" value="<?= isset($edit_data) ? $edit_data['harga_tiket'] : '' ?>" required>
    <input type="text" name="estimasi_waktu" placeholder="Estimasi Waktu" value="<?= isset($edit_data) ? $edit_data['estimasi_waktu'] : '' ?>" required>
    <textarea name="ket" placeholder="Keterangan"><?= isset($edit_data) ? $edit_data['ket_objek'] : '' ?></textarea>
    <label>Foto Objek:</label>
    <input type="file" name="foto" accept="image/*">

    <label>Pilih Fasilitas:</label>
    <?php while ($row = $fasilitas->fetch_assoc()): ?>
        <input type="checkbox" name="kd_fasilitas[]" value="<?= htmlspecialchars($row['kd_fasilitas']) ?>" <?= isset($edit_data) && in_array($row['kd_fasilitas'], json_decode($edit_data['kd_fasilitas'], true)) ? 'checked' : '' ?>> <?= htmlspecialchars($row['nama_fasilitas']) ?><br>
    <?php endwhile; ?>

    <label>Pilih Aktivitas:</label>
    <?php while ($row = $aktifitas->fetch_assoc()): ?>
        <input type="checkbox" name="kd_aktifitas[]" value="<?= htmlspecialchars($row['kd_aktifitas']) ?>" <?= isset($edit_data) && in_array($row['kd_aktifitas'], json_decode($edit_data['kd_aktifitas'], true)) ? 'checked' : '' ?>> <?= htmlspecialchars($row['nama_aktifitas']) ?><br>
    <?php endwhile; ?>

    <label>Jarak Tempuh:</label>
    <input type="text" name="jarak_tempuh" placeholder="10 Km" value="<?= isset($edit_data) ? $edit_data['jarak_tempuh'] : '' ?>" required>
    <label>Waktu Tempuh:</label>
    <input type="text" name="waktu_tempuh" placeholder="30 Menit" value="<?= isset($edit_data) ? $edit_data['waktu_tempuh'] : '' ?>" required>
    <label>Nama Pengelola:</label>
    <input type="text" name="nama_pengelola" placeholder="Nama Pengelola" value="<?= isset($edit_data) ? $edit_data['nama_pengelola'] : '' ?>" required>
    <label>Kontak Pengelola:</label>
    <input type="text" name="kontak_pengelola" placeholder="081222333444" value="<?= isset($edit_data) ? $edit_data['kontak_pengelola'] : '' ?>" required>

    <button type="submit" name="<?= isset($edit_data) ? 'edit' : 'add' ?>"><?= isset($edit_data) ? 'Update Objek Wisata' : 'Tambah Objek Wisata' ?></button>
</form>

<!-- Table objek wisata -->
<h2>Daftar Objek Wisata</h2>
<table>
    <thead>
        <tr>
            <th>Nama</th>
            <th>Alamat</th>
            <th>Harga</th>
            <th>Estimasi Waktu</th>
            <th>Jarak</th>
            <th>Waktu</th>
            <th>Pengelola</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $wisata->fetch_assoc()): ?>
        <tr>
            <td><?= $row['nama_objek'] ?></td>
            <td><?= $row['alamat'] ?></td>
            <td><?= $row['harga_tiket'] ?></td>
            <td><?= $row['estimasi_waktu'] ?></td>
            <td><?= $row['jarak_tempuh'] ?></td>
            <td><?= $row['waktu_tempuh'] ?></td>
            <td><?= $row['nama_pengelola'] ?></td>
            <td><img src="<?= $row['foto'] ?>" alt="Foto Objek" width="100"></td>
            <td>
                <a href="?edit_id=<?= $row['kd_objek'] ?>">Edit</a> | 
                <a href="?delete_id=<?= $row['kd_objek'] ?>" onclick="return confirm('Yakin ingin menghapus objek wisata ini?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>