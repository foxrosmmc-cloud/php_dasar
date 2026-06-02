<?php
session_start();

// --- KONFIGURASI DATABASE ---
$host = 'localhost';
$db   = 'ukm_choir';
$user = 'root'; // Sesuaikan dengan username database Anda
$pass = '';     // Sesuaikan dengan password database Anda
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Koneksi database gagal: " . $e->getMessage());
}

// --- LOGIKA LOGIN & LOGOUT ---
$login_error = '';
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Cari user berdasarkan email
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();

        // Verifikasi password (menggunakan password_verify agar aman)
        if ($user_data && password_verify($password, $user_data['password'])) {
            $_SESSION['user'] = $user_data['email'];
            header("Location: index.php?page=anggota");
            exit;
        } else {
            $login_error = "Email atau Password salah! (Hint: coba admin@harmony.com / admin)";
        }
    } else {
        $login_error = "Format email tidak valid!";
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['user']);
    header("Location: index.php?page=home");
    exit;
}

// --- LOGIKA UBAH STATUS ANGGOTA ---
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_SESSION['user'])) {
    $id = intval($_GET['id']);
    
    // Ambil status saat ini
    $stmt = $pdo->prepare('SELECT status FROM anggota WHERE id = ?');
    $stmt->execute([$id]);
    $current_status = $stmt->fetchColumn();
    
    if ($current_status) {
        $new_status = ($current_status == 'Aktif') ? 'Tidak Aktif' : 'Aktif';
        // Update status di database
        $stmt = $pdo->prepare('UPDATE anggota SET status = ? WHERE id = ?');
        $stmt->execute([$new_status, $id]);
    }
    
    header("Location: index.php?page=anggota");
    exit;
}

// --- LOGIKA TAMBAH ANGGOTA ---
if (isset($_POST['tambah_anggota']) && isset($_SESSION['user'])) {
    $nama = htmlspecialchars($_POST['nama']);
    $nim = htmlspecialchars($_POST['nim']);
    $vokal = htmlspecialchars($_POST['vokal']);
    $status = htmlspecialchars($_POST['status']);
    
    try {
        $stmt = $pdo->prepare('INSERT INTO anggota (nama, nim, vokal, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $nim, $vokal, $status]);
    } catch (\PDOException $e) {
        // Menangani jika NIM duplikat
        echo "<script>alert('Gagal menambah anggota! NIM mungkin sudah terdaftar.');</script>";
    }
    
    header("Location: index.php?page=anggota");
    exit;
}

// --- LOGIKA NAVIGASI HALAMAN ---
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// --- MENGHITUNG STATISTIK ANGGOTA ---
$total_anggota = $pdo->query('SELECT COUNT(*) FROM anggota')->fetchColumn();
$aktif_count = $pdo->query("SELECT COUNT(*) FROM anggota WHERE status = 'Aktif'")->fetchColumn();
$tidak_aktif_count = $total_anggota - $aktif_count;

// --- AMBIL DATA ANGGOTA UNTUK TABEL ---
$anggota_list = $pdo->query('SELECT * FROM anggota ORDER BY id DESC')->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKM Paduan Suara | Harmony Voice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-blue-600 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <span class="text-xl font-bold tracking-wider">🎼 HARMONY VOICE</span>
                </div>
                <div class="hidden md:flex space-x-6 items-center">
                    <a href="?page=home" class="hover:text-blue-200 transition <?php echo $page=='home'?'border-b-2 border-white':'' ?>">Home</a>
                    <a href="?page=tentang" class="hover:text-blue-200 transition <?php echo $page=='tentang'?'border-b-2 border-white':'' ?>">Tentang</a>
                    <a href="?page=kegiatan" class="hover:text-blue-200 transition <?php echo $page=='kegiatan'?'border-b-2 border-white':'' ?>">Kegiatan</a>
                    <a href="?page=anggota" class="hover:text-blue-200 transition <?php echo $page=='anggota'?'border-b-2 border-white':'' ?>">Anggota</a>
                    <a href="?page=pendaftaran" class="hover:text-blue-200 transition <?php echo $page=='pendaftaran'?'border-b-2 border-white':'' ?>">Pendaftaran</a>
                    
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="flex items-center space-x-3 ml-4">
                            <span class="text-xs bg-blue-800 px-2 py-1 rounded">Admin</span>
                            <a href="?action=logout" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-full text-sm font-semibold transition shadow">Log Out</a>
                        </div>
                    <?php else: ?>
                        <a href="?page=login" class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-full text-sm font-semibold transition shadow ml-4">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        
        <?php if ($page == 'home'): ?>
            <div class="bg-gradient-to-r align-middle from-blue-600 to-blue-800 text-white py-24 px-4 text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-4">Satu Suara, Sejuta Harmoni</h1>
                <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto mb-8">Selamat Datang di Website Resmi UKM Paduan Suara "Harmony Voice". Wadah kreativitas seni olah vokal terbaik di kampus.</p>
                <div class="flex justify-center space-x-4">
                    <a href="?page=pendaftaran" class="bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-blue-50 transition shadow-lg">Gabung Sekarang</a>
                    <a href="?page=tentang" class="border-2 border-white px-6 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-600 transition">Pelajari Lebih Lanjut</a>
                </div>
            </div>

            <div class="max-w-5xl mx-auto -mt-12 px-4 mb-12">
                <div class="bg-white rounded-xl shadow-xl grid grid-cols-3 divide-x text-center p-6">
                    <div>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_anggota; ?></p>
                        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Total Anggota</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-green-500"><?php echo $aktif_count; ?></p>
                        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Mengikuti Kegiatan</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-red-400"><?php echo $tidak_aktif_count; ?></p>
                        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Absen Kegiatan</p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 py-12">
                <h2 class="text-3xl font-bold text-center text-blue-900 mb-2">Dokumentasi Kegiatan</h2>
                <p class="text-center text-gray-500 mb-10">Momen berharga kami dalam mengukir prestasi dan kebersamaan</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-xl overflow-hidden shadow-md transition hover:shadow-xl flex flex-col">
                        <img src="https://images.unsplash.com/photo-1465847899084-d164df4dedc6?q=80&w=600&auto=format&fit=crop" 
                             alt="Konser Tahunan" 
                             onerror="this.onerror=null; this.src='https://placehold.co/600x400/2563eb/ffffff?text=Konser+Paduan+Suara';"
                             class="w-full h-48 object-cover bg-blue-100">
                        <div class="p-5 flex-grow">
                            <h3 class="font-bold text-xl mb-2 text-blue-900">Konser Harmoni Akbar</h3>
                            <p class="text-gray-600 text-sm">Penampilan panggung megah tahunan yang diikuti oleh seluruh tingkatan anggota paduan suara.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden shadow-md transition hover:shadow-xl flex flex-col">
                        <img src="https://images.unsplash.com/photo-1516280440614-37939bbacd6a?q=80&w=600&auto=format&fit=crop" 
                             alt="Latihan Rutin" 
                             onerror="this.onerror=null; this.src='https://placehold.co/600x400/2563eb/ffffff?text=Latihan+Rutin+Vokal';"
                             class="w-full h-48 object-cover bg-blue-100">
                        <div class="p-5 flex-grow">
                            <h3 class="font-bold text-xl mb-2 text-blue-900">Latihan Rutin Mingguan</h3>
                            <p class="text-gray-600 text-sm">Mengasah teknik vokal, olah pernapasan, dan kekompakan tim demi hasil performa maksimal.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden shadow-md transition hover:shadow-xl flex flex-col">
                        <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=600&auto=format&fit=crop" 
                             alt="Juara Kompetisi" 
                             onerror="this.onerror=null; this.src='https://placehold.co/600x400/2563eb/ffffff?text=Prestasi+Kompetisi';"
                             class="w-full h-48 object-cover bg-blue-100">
                        <div class="p-5 flex-grow">
                            <h3 class="font-bold text-xl mb-2 text-blue-900">Prestasi Kompetisi Nasional</h3>
                            <p class="text-gray-600 text-sm">Momen membanggakan saat menduduki peringkat Gold Medalist pada festival Paduan Suara Nasional.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'tentang'): ?>
            <div class="max-w-4xl mx-auto px-4 py-16">
                <h1 class="text-4xl font-bold text-blue-900 mb-6 border-b-2 border-blue-600 pb-2">Tentang UKM Paduan Suara</h1>
                <div class="bg-white p-8 rounded-2xl shadow-md space-y-6 text-lg leading-relaxed">
                    <p>
                        <strong>UKM Paduan Suara "Harmony Voice"</strong> merupakan salah satu Unit Kegiatan Mahasiswa di bidang seni yang berfokus pada pengembangan bakat, minat, dan kemampuan mahasiswa dalam bernyanyi secara berkelompok (*Choir*).
                    </p>
                    <p>
                        Didirikan dengan semangat kebersamaan, UKM ini membagi jenis suara ke dalam 4 bagian utama: <strong>Sopran, Alto, Tenor, dan Bass (SATB)</strong>. Kami tidak hanya belajar tentang teknik vokal, tetapi juga belajar menyelaraskan perbedaan ego demi terciptanya satu harmoni suara yang indah.
                    </p>
                    <h3 class="font-bold text-2xl text-blue-800 mt-8">Visi & Misi</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>Menjadi UKM paduan suara yang unggul, berprestasi, dan menjunjung tinggi nilai kekeluargaan.</li>
                        <li>Mengembangkan teknik vokal dan musikalitas setiap anggota secara bertahap.</li>
                        <li>Membawa nama baik almamater melalui kompetisi tingkat regional, nasional, maupun internasional.</li>
                    </ul>
                </div>
            </div>

        <?php elseif ($page == 'kegiatan'): ?>
            <div class="max-w-6xl mx-auto px-4 py-16">
                <h1 class="text-4xl font-bold text-blue-900 mb-8 text-center">Agenda & Kegiatan UKM</h1>
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-600 flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-bold uppercase">Mendatang</span>
                            <h3 class="text-xl font-bold text-gray-900 mt-2">Penerimaan & Audisi Anggota Baru 2026</h3>
                            <p class="text-gray-600 text-sm mt-1">Tanggal: 15 - 20 Juni 2026 | Lokasi: Aula Gedung C</p>
                        </div>
                        <span class="text-blue-600 font-semibold mt-4 md:mt-0">Wajib untuk Calon Anggota</span>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-bold uppercase">Rutin</span>
                            <h3 class="text-xl font-bold text-gray-900 mt-2">Latihan Intensif Song-Arrangement</h3>
                            <p class="text-gray-600 text-sm mt-1">Setiap Hari Selasa dan Jumat pukul 16.00 WIB | Studio Musik Pusat</p>
                        </div>
                        <span class="text-green-600 font-semibold mt-4 md:mt-0">Diikuti Anggota Aktif</span>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-gray-400 flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full font-bold uppercase">Selesai</span>
                            <h3 class="text-xl font-bold text-gray-500 mt-2">Pengisian Paduan Suara Wisuda Periode I</h3>
                            <p class="text-gray-400 text-sm mt-1">Tanggal: 10 Mei 2026 | Auditorium Utama Kampus</p>
                        </div>
                        <span class="text-gray-400 font-semibold mt-4 md:mt-0">Selesai Terlaksana</span>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'anggota'): ?>
            <div class="max-w-7xl mx-auto px-4 py-12">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-blue-900">Daftar & Biodata Anggota</h1>
                        <p class="text-gray-500 text-sm">Kelola status kehadiran keaktifan kegiatan UKM di bawah ini.</p>
                    </div>
                    
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 w-full md:w-auto">
                            <h3 class="font-bold text-blue-900 mb-2 text-sm">Form Tambah Anggota Baru</h3>
                            <form action="" method="POST" class="flex flex-wrap gap-2">
                                <input type="text" name="nama" placeholder="Nama Lengkap" required class="px-3 py-1.5 text-sm rounded border focus:outline-blue-500">
                                <input type="text" name="nim" placeholder="NIM" required class="px-3 py-1.5 text-sm rounded border focus:outline-blue-500 w-28">
                                <select name="vokal" class="px-3 py-1.5 text-sm rounded border bg-white">
                                    <option value="Sopran">Sopran</option>
                                    <option value="Alto">Alto</option>
                                    <option value="Tenor">Tenor</option>
                                    <option value="Bass">Bass</option>
                                </select>
                                <select name="status" class="px-3 py-1.5 text-sm rounded border bg-white">
                                    <option value="Aktif">Mengikuti Kegiatan</option>
                                    <option value="Tidak Aktif">Tidak Ikut Kegiatan</option>
                                </select>
                                <button type="submit" name="tambah_anggota" class="bg-blue-600 text-white px-4 py-1.5 rounded text-sm font-semibold hover:bg-blue-700 transition">Tambah</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-3 rounded-lg">
                            🔒 <strong>Info Admin:</strong> Silakan login via tombol pojok kanan atas untuk mengubah status keaktifan atau menambah anggota.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-blue-600 text-white text-sm uppercase font-semibold">
                                <th class="p-4">No</th>
                                <th class="p-4">Nama / Biodata</th>
                                <th class="p-4">NIM</th>
                                <th class="p-4">Jenis Vokal</th>
                                <th class="p-4 text-center">Status Kegiatan</th>
                                <?php if (isset($_SESSION['user'])): ?><th class="p-4 text-center">Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($anggota_list) > 0): ?>
                                <?php foreach ($anggota_list as $index => $agt): ?>
                                    <tr class="hover:bg-blue-50/50 transition">
                                        <td class="p-4 font-semibold text-gray-500"><?php echo $index + 1; ?></td>
                                        <td class="p-4">
                                            <div class="font-bold text-gray-900"><?php echo $agt['nama']; ?></div>
                                            <div class="text-xs text-gray-400">Anggota Paduan Suara</div>
                                        </td>
                                        <td class="p-4 text-gray-600 font-mono text-sm"><?php echo $agt['nim']; ?></td>
                                        <td class="p-4">
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full font-semibold"><?php echo $agt['vokal']; ?></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <?php if ($agt['status'] == 'Aktif'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    🟢 Sedang Mengikuti
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    🔴 Tidak Mengikuti
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isset($_SESSION['user'])): ?>
                                            <td class="p-4 text-center">
                                                <a href="?page=anggota&action=toggle_status&id=<?php echo $agt['id']; ?>" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded transition">
                                                    Ubah Status Kehadiran
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-500">Belum ada data anggota.</td>
                                endtr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'login'): ?>
            <div class="max-w-md mx-auto px-4 py-24">
                <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-bold text-blue-900">Sign In Admin</h2>
                        <p class="text-sm text-gray-500 mt-1">Gunakan Akun Simulasi untuk mengubah status kegiatan anggota.</p>
                    </div>

                    <?php if ($login_error): ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-4 text-center font-semibold">
                            <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?page=login" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="admin@harmony.com">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Masukkan password admin">
                            <span class="text-xs text-gray-400 mt-1 block">💡 Akun database default: <strong class="text-blue-600">admin@harmony.com</strong> | pwd: <strong class="text-blue-600">admin</strong></span>
                        </div>
                        <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
                            Masuk Ke Sistem
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-gray-900 text-gray-400 py-8 border-t border-gray-800 text-center text-sm">
        <p>&copy; 2026 UKM Paduan Suara Harmony Voice. All Rights Reserved.</p>
        <p class="mt-1 text-xs text-gray-600">Dibuat dengan kombinasi PHP, MySQL, Tailwind CSS, dan Cinta pada Musik.</p>
    </footer>

</body>
</html>