<?php
include 'admin/db_connect.php'; // Menghubungkan ke database

// Periksa apakah parameter kd_objek diterima dari URL
if (isset($_GET['kd_objek'])) {
    $kd_objek = intval($_GET['kd_objek']); // Ambil kd_objek dari URL dan pastikan nilainya integer

    // Query utama untuk objek wisata
    $query = "
    SELECT 
        o.kd_objek, o.nama_objek, o.foto, o.alamat, o.harga_tiket, o.estimasi_waktu, o.ket_objek, 
        o.kd_fasilitas, o.kd_aktifitas,
        j.jarak_tempuh, j.waktu_tempuh,
        p.nama_pengelola, p.kontak_pengelola
    FROM objek_wisata o
    LEFT JOIN jarak j ON o.kd_jarak = j.kd_jarak
    LEFT JOIN pengelola p ON o.id_pengelola = p.id_pengelola
    WHERE o.kd_objek = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $kd_objek);
    $stmt->execute();
    $result = $stmt->get_result();

    // Periksa apakah data ditemukan
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Decode JSON kd_fasilitas dan kd_aktifitas
        $kd_fasilitas = json_decode($row['kd_fasilitas'], true);
        $kd_aktifitas = json_decode($row['kd_aktifitas'], true);

        // Query fasilitas berdasarkan kd_fasilitas
        $fasilitas = [];
        if (!empty($kd_fasilitas)) {
            $placeholders = implode(',', array_fill(0, count($kd_fasilitas), '?'));
            $queryFasilitas = "SELECT nama_fasilitas, ket_fasilitas FROM fasilitas WHERE kd_fasilitas IN ($placeholders)";
            $stmtFasilitas = $conn->prepare($queryFasilitas);
            $stmtFasilitas->bind_param(str_repeat("i", count($kd_fasilitas)), ...$kd_fasilitas);
            $stmtFasilitas->execute();
            $resultFasilitas = $stmtFasilitas->get_result();
            while ($rowFasilitas = $resultFasilitas->fetch_assoc()) {
                $fasilitas[] = $rowFasilitas;
            }
        }

        // Query aktifitas berdasarkan kd_aktifitas
        $aktifitas = [];
        if (!empty($kd_aktifitas)) {
            $placeholders = implode(',', array_fill(0, count($kd_aktifitas), '?'));
            $queryAktifitas = "SELECT nama_aktifitas, durasi_aktifitas FROM aktifitas WHERE kd_aktifitas IN ($placeholders)";
            $stmtAktifitas = $conn->prepare($queryAktifitas);
            $stmtAktifitas->bind_param(str_repeat("i", count($kd_aktifitas)), ...$kd_aktifitas);
            $stmtAktifitas->execute();
            $resultAktifitas = $stmtAktifitas->get_result();
            while ($rowAktifitas = $resultAktifitas->fetch_assoc()) {
                $aktifitas[] = $rowAktifitas;
            }
        }
    } else {
        echo "Data tidak ditemukan.";
        exit;
    }
} else {
    echo "ID tidak ditemukan.";
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail - <?= htmlspecialchars($row['nama_objek']) ?></title>
    <link rel="stylesheet" href="style-detail.css">
</head>
<body>
    <!-- Navbar -->
    <nav>
        <div class="layar-dalam">
            <div class="logo">
                <a href="index.php"><img src="asset/logo-black.png" alt="Website Logo"></a>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="index.php#aboutus">Tentang Biak</a></li>
                    <li><a href="index.php#contact">Kontak</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Detail -->
    <div class="container">
        <h1><?= htmlspecialchars($row['nama_objek']) ?></h1>

        <div class="image-container">
            <img src="admin/<?= htmlspecialchars($row['foto']) ?>" alt="<?= htmlspecialchars($row['nama_objek']) ?>">
        </div>

        <div class="content">
            <h2>Deskripsi</h2>
            <p><?= nl2br(htmlspecialchars($row['ket_objek'])) ?></p>

            <h2>Alamat</h2>
            <p><?= htmlspecialchars($row['alamat']) ?></p>

            <h2>Harga Tiket</h2>
            <p><?= htmlspecialchars($row['harga_tiket']) ?></p>

            <h2>Jarak Tempuh Dari Pusat Kota</h2>
            <p>Jarak: <?= htmlspecialchars($row['jarak_tempuh']) ?></p>
            <p>Waktu Tempuh: <?= htmlspecialchars($row['waktu_tempuh']) ?></p>

            <h2>Fasilitas</h2>
            <?php if (!empty($fasilitas)): ?>
                <ul>
                    <?php foreach ($fasilitas as $fas): ?>
                        <li><strong><?= htmlspecialchars($fas['nama_fasilitas']) ?>:</strong> <?= htmlspecialchars($fas['ket_fasilitas']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Tidak ada fasilitas tersedia.</p>
            <?php endif; ?>

            <h2>Aktifitas</h2>
            <?php if (!empty($aktifitas)): ?>
                <ul>
                    <?php foreach ($aktifitas as $akt): ?>
                        <li><strong><?= htmlspecialchars($akt['nama_aktifitas']) ?>:</strong> Durasi <?= htmlspecialchars($akt['durasi_aktifitas']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Tidak ada aktifitas tersedia.</p>
            <?php endif; ?>

            <h2>Pengelola</h2>
            <p>Nama Pengelola: <?= htmlspecialchars($row['nama_pengelola']) ?></p>
            <p>Kontak: <?= htmlspecialchars($row['kontak_pengelola']) ?></p>
        </div>

        <a href="index.php" class="back-link">Kembali ke Beranda</a>
    </div>
</body>
</html>
