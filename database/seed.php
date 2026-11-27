<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];

$dsnRoot = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset']);
$pdo = new PDO($dsnRoot, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
if ($schemaSql === false) {
    throw new RuntimeException('Gagal membaca database/schema.sql');
}
$pdo->exec($schemaSql);
$pdo->exec('USE `' . $db['name'] . '`');

$passHash = password_hash('ticket1234', PASSWORD_BCRYPT);

// 1. Seed Pengguna (Admin, Staff, Manager) - D1
$users = [
    [1, 'admin',    'demo@ticketland.id',    'Afrizal Tazul Aripin', $passHash, 'admin',   1, '2026-10-10 08:00:00'],
    [2, 'manager1', 'manager@ticketland.id', 'Budi Santoso',         $passHash, 'manager', 1, '2026-10-12 09:00:00'],
    [3, 'staff1',   'staff@ticketland.id',   'Sari Wulandari',       $passHash, 'staff',   1, '2026-10-15 10:00:00'],
];
$stmtUser = $pdo->prepare(
    'INSERT INTO users (id, username, email, nama, password_hash, peran, status_aktif, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       username = VALUES(username),
       email = VALUES(email),
       nama = VALUES(nama),
       password_hash = VALUES(password_hash),
       peran = VALUES(peran),
       status_aktif = VALUES(status_aktif)'
);
foreach ($users as $u) {
    $stmtUser->execute($u);
}

// 2. Seed AkunUser (Customer)
$stmtAkun = $pdo->prepare(
    'INSERT INTO akun_user (id, email, nama, password_hash, created_at)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       nama = VALUES(nama),
       password_hash = VALUES(password_hash)'
);
$stmtAkun->execute([1, 'demo@ticketland.id', 'Afrizal Tazul Aripin', $passHash, '2026-10-10 08:00:00']);

// 3. Seed 5 Kategori Event Resmi (D2): Musik, Pameran, Komedi, Trip, Bisnis
$categories = [
    [1, 'Musik',   'Konser, festival, dan pertunjukan musik live.'],
    [2, 'Pameran', 'Pameran seni, fotografi, produk keluarga, dan expo tematik.'],
    [3, 'Komedi',  'Pertunjukan stand-up comedy, open mic, dan tur komedi.'],
    [4, 'Trip',    'Open trip, jelajah kota, berkemah, dan perjalanan wisata.'],
    [5, 'Bisnis',  'Seminar, summit, talkshow, dan workshop bisnis serta investasi.'],
];
$stmtCat = $pdo->prepare(
    'INSERT INTO categories (id, nama, deskripsi)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
       nama = VALUES(nama),
       deskripsi = VALUES(deskripsi)'
);
foreach ($categories as $c) {
    $stmtCat->execute($c);
}

// 4. Seed Event Demo (D3)
$defaultSnk = "1. Tiket resmi hanya dijual melalui situs Ticket Land atau official ticketing partner yang ditunjuk oleh penyelenggara.\n"
    . "2. Satu Entry Pass berlaku untuk satu orang.\n"
    . "3. Maksimal pembelian 5 tiket dalam 1 kali transaksi.\n"
    . "4. Pengunjung wajib membawa identitas resmi (KTP/SIM/Paspor) yang sesuai dengan nama pemegang tiket.\n"
    . "5. E-tiket wajib ditunjukkan di pintu masuk dan akan divalidasi oleh staff melalui scan QR.";

$events = [
    [
        'id'               => 1,
        'kode'             => 'EVT001',
        'nama'             => 'Tulus Live in Concert',
        'category_id'      => 1,
        'tanggal_mulai'    => '2026-12-06',
        'tanggal_selesai'  => '2026-12-06',
        'tanggal_label'    => '6 Desember 2026',
        'jam_label'        => '19:00 - 22:00 WIB',
        'venue'            => 'Istora Senayan, Jakarta',
        'kota'             => 'Jakarta',
        'penyelenggara'    => 'Tiga Rambu',
        'label_sesi'       => null,
        'deskripsi'        => 'Konser tunggal Tulus membawakan album terbarunya beserta lagu-lagu hits terbaik dalam tata panggung megah di Istora Senayan.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'tulus-live-in-concert-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 2,
        'kode'             => 'EVT002',
        'nama'             => 'Jakarta International BNI Java Jazz Festival 2027',
        'category_id'      => 1,
        'tanggal_mulai'    => '2027-05-28',
        'tanggal_selesai'  => '2027-05-30',
        'tanggal_label'    => '28-30 Mei 2027',
        'jam_label'        => '15:00 - 23:30 WIB',
        'venue'            => 'JIExpo Kemayoran, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Java Festival Production',
        'label_sesi'       => 'Multi Stage',
        'deskripsi'        => 'Festival musik jazz internasional tahunan terbesar di Indonesia yang menghadirkan kolaborasi musisi legendaris tanah air dan internasional dalam belasan panggung pertunjukan.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'java-jazz-festival-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 3,
        'kode'             => 'EVT003',
        'nama'             => 'Konser Nadin Amizah - "Untuk Dunia, Cinta, dan Kotornya"',
        'category_id'      => 1,
        'tanggal_mulai'    => '2026-12-11',
        'tanggal_selesai'  => '2026-12-11',
        'tanggal_label'    => '11 Desember 2026',
        'jam_label'        => '19:30 - 22:00 WIB',
        'venue'            => 'Eldorado Dome, Bandung',
        'kota'             => 'Bandung',
        'penyelenggara'    => 'Sorai Entertainment',
        'label_sesi'       => null,
        'deskripsi'        => 'Konser tunggal intim Nadin Amizah yang membawakan untaian lagu puitis dan teatrikal dari album penuhnya di hadapan ribuan penikmat musik Bandung.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'nadin-amizah-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 4,
        'kode'             => 'EVT004',
        'nama'             => 'ARTJOG 2027 - Motif: Ramalan',
        'category_id'      => 2,
        'tanggal_mulai'    => '2027-06-25',
        'tanggal_selesai'  => '2027-08-25',
        'tanggal_label'    => '25 Juni - 25 Agustus 2027',
        'jam_label'        => '10:00 - 21:00 WIB',
        'venue'            => 'Jogja National Museum (JNM), Yogyakarta',
        'kota'             => 'Yogyakarta',
        'penyelenggara'    => 'Herimulyo Art Management',
        'label_sesi'       => null,
        'deskripsi'        => 'Pameran seni rupa kontemporer tahunan terkemuka di Indonesia yang menampilkan instalasi seni megah, patung, lukisan, dan ruang seni interaktif karya seniman ternama nusantara.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'artjog-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 5,
        'kode'             => 'EVT005',
        'nama'             => 'Gaikindo Indonesia International Auto Show (GIIAS) 2027',
        'category_id'      => 2,
        'tanggal_mulai'    => '2027-08-12',
        'tanggal_selesai'  => '2027-08-22',
        'tanggal_label'    => '12-22 Agustus 2027',
        'jam_label'        => '10:00 - 21:00 WIB',
        'venue'            => 'Indonesia Convention Exhibition (ICE) BSD City, Tangerang',
        'kota'             => 'Tangerang',
        'penyelenggara'    => 'Seven Event / Gaikindo',
        'label_sesi'       => null,
        'deskripsi'        => 'Pameran otomotif terbesar di Indonesia dan Asia Tenggara yang memamerkan ratusan merek otomotif global, peluncuran mobil konsep masa depan, dan teknologi kendaraan listrik canggih.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'giias-4k.webp',
        'is_featured'      => 1,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 6,
        'kode'             => 'EVT006',
        'nama'             => 'Synchronize Fest 2027 - Bhinneka Tunggal Musik',
        'category_id'      => 1,
        'tanggal_mulai'    => '2027-10-01',
        'tanggal_selesai'  => '2027-10-03',
        'tanggal_label'    => '1-3 Oktober 2027',
        'jam_label'        => '14:00 - 23:45 WIB',
        'venue'            => 'Gambir Expo Kemayoran, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Demajors & Dyandra Promosindo',
        'label_sesi'       => 'Multi Stage',
        'deskripsi'        => 'Festival musik multi-genre tahunan berskala nasional yang merayakan keberagaman musik Indonesia dari era 70-an, 80-an, hingga gelombang baru musisi independen tanah air.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'synchronize-fest-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 1,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 7,
        'kode'             => 'EVT007',
        'nama'             => 'Indonesia International Book Fair (IIBF) 2026',
        'category_id'      => 2,
        'tanggal_mulai'    => '2026-12-17',
        'tanggal_selesai'  => '2026-12-20',
        'tanggal_label'    => '17-20 Desember 2026',
        'jam_label'        => '09:00 - 20:00 WIB',
        'venue'            => 'Jakarta Convention Center (JCC) Senayan, Jakarta',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Ikatan Penerbit Indonesia (IKAPI)',
        'label_sesi'       => null,
        'deskripsi'        => 'Pameran literasi internasional tahunan terbesar di Indonesia yang menghadirkan ratusan penerbit buku nasional, temu penulis buku best seller, bedah buku, dan lokakarya literasi bebas biaya masuk.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'iibf-4k.webp',
        'is_featured'      => 1,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 8,
        'kode'             => 'EVT008',
        'nama'             => 'Pandji Pragiwaksono Stand Up Comedy Tour - Mens Rea Jakarta',
        'category_id'      => 3,
        'tanggal_mulai'    => '2026-12-19',
        'tanggal_selesai'  => '2026-12-19',
        'tanggal_label'    => '19 Desember 2026',
        'jam_label'        => '19:30 - 22:30 WIB',
        'venue'            => 'Teater Besar Taman Ismail Marzuki, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Comika Event',
        'label_sesi'       => null,
        'deskripsi'        => 'Spesial show pertunjukan komedi tunggal dari Pandji Pragiwaksono yang membahas isu sosial politik dan keresahan publik dengan komedi cerdas, berani, dan bernas.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'pandji-pragiwaksono-4k.webp',
        'is_featured'      => 1,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 9,
        'kode'             => 'EVT009',
        'nama'             => 'Open Trip Bromo Sunrise & Savana Teletubbies 2D1N',
        'category_id'      => 4,
        'tanggal_mulai'    => '2026-12-13',
        'tanggal_selesai'  => '2026-12-14',
        'tanggal_label'    => '13-14 Desember 2026',
        'jam_label'        => '00:30 - 14:00 WIB',
        'venue'            => 'Titik Kumpul Stasiun Kota Baru Malang, Jawa Timur',
        'kota'             => 'Malang',
        'penyelenggara'    => 'Explore Bromo Tour',
        'label_sesi'       => null,
        'deskripsi'        => 'Petualangan seru menyaksikan golden sunrise dari Bukit Penanjakan 1, mendaki bibir kawah aktif Gunung Bromo, berfoto di Bukit Teletubbies, dan melintasi lautan pasir dengan armada Jeep 4x4.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'bromo-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 10,
        'kode'             => 'EVT010',
        'nama'             => 'Tech in Asia Conference Jakarta 2026',
        'category_id'      => 5,
        'tanggal_mulai'    => '2026-12-04',
        'tanggal_selesai'  => '2026-12-05',
        'tanggal_label'    => '4-5 Desember 2026',
        'jam_label'        => '09:00 - 17:30 WIB',
        'venue'            => 'The Ritz-Carlton Pacific Place, Jakarta Selatan',
        'kota'             => 'Jakarta Selatan',
        'penyelenggara'    => 'Tech in Asia Indonesia',
        'label_sesi'       => null,
        'deskripsi'        => 'Konferensi teknologi dan ekosistem startup terkemuka di Asia Tenggara yang menghadirkan panggung diskusi para founders terdepan, investor modal ventura (VC), serta pembuat kebijakan industri digital.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'tech-in-asia-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 11,
        'kode'             => 'EVT011',
        'nama'             => 'Indonesia Digital Marketing Masterclass 2027',
        'category_id'      => 5,
        'tanggal_mulai'    => '2026-11-28',
        'tanggal_selesai'  => '2026-11-28',
        'tanggal_label'    => '28 November 2026',
        'jam_label'        => '13:00 - 17:00 WIB',
        'venue'            => 'Creative Hub Sudirman, Jakarta',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Digitalku Academy',
        'label_sesi'       => null,
        'deskripsi'        => 'Pelatihan praktik pemasaran digital dan optimasi kampanye iklan berbayar untuk bisnis ritel.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => null,
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => '2026-11-18 10:02:00',
        'deleted_by'       => 'admin',
    ],
    [
        'id'               => 12,
        'kode'             => 'EVT012',
        'nama'             => 'Indonesia Contemporary Art & Design (ICAD) 2027',
        'category_id'      => 2,
        'tanggal_mulai'    => '2026-12-03',
        'tanggal_selesai'  => '2026-12-06',
        'tanggal_label'    => '3-6 Desember 2026',
        'jam_label'        => '10:00 - 21:00 WIB',
        'venue'            => 'grandkemang Hotel, Kemang, Jakarta Selatan',
        'kota'             => 'Jakarta Selatan',
        'penyelenggara'    => 'Yayasan Desain Seni Indonesia',
        'label_sesi'       => null,
        'deskripsi'        => 'Pameran eksplorasi desain, mode, dan seni rupa kontemporer karya desainer ternama.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => null,
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => '2026-11-15 14:20:00',
        'deleted_by'       => 'admin',
    ],
    [
        'id'               => 13,
        'kode'             => 'EVT013',
        'nama'             => 'Sheila on 7 - Konser Tunggu Aku di Jakarta 2027',
        'category_id'      => 1,
        'tanggal_mulai'    => '2027-01-23',
        'tanggal_selesai'  => '2027-01-23',
        'tanggal_label'    => '23 Januari 2027',
        'jam_label'        => '19:30 - 22:30 WIB',
        'venue'            => 'Stadion Utama Gelora Bung Karno (GBK), Jakarta',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Antara Suara',
        'label_sesi'       => null,
        'deskripsi'        => 'Konser tunggal spektakuler Sheila on 7 yang membawakan puluhan hits legendaris lintas generasi dalam tata suara dan visual panggung megah di Senayan.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'sheila-on-7-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 14,
        'kode'             => 'EVT014',
        'nama'             => 'Inacraft 2027 - The Jakarta International Handicraft Trade Fair',
        'category_id'      => 2,
        'tanggal_mulai'    => '2027-02-24',
        'tanggal_selesai'  => '2027-02-28',
        'tanggal_label'    => '24-28 Februari 2027',
        'jam_label'        => '10:00 - 21:00 WIB',
        'venue'            => 'Jakarta Convention Center (JCC), Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'ASEPHI & Mediatama Event',
        'label_sesi'       => null,
        'deskripsi'        => 'Pameran kerajinan tangan dan produk seni kriya terbesar di Indonesia yang memamerkan produk batik, anyaman, keramik, perhiasan perak, dan tekstil tradisional nusantara.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'inacraft-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 15,
        'kode'             => 'EVT015',
        'nama'             => 'Raditya Dika Stand Up Comedy Show - Cerita Cintaku 2027',
        'category_id'      => 3,
        'tanggal_mulai'    => '2027-01-16',
        'tanggal_selesai'  => '2027-01-16',
        'tanggal_label'    => '16 Januari 2027',
        'jam_label'        => '19:30 - 22:00 WIB',
        'venue'            => 'Balai Sarbini, Semanggi, Jakarta Selatan',
        'kota'             => 'Jakarta Selatan',
        'penyelenggara'    => 'Rapi Films Live',
        'label_sesi'       => null,
        'deskripsi'        => 'Pertunjukan komedi tunggal Raditya Dika yang membawakan materi segar dan reflektif seputar dinamika cinta, keluarga, pernikahan, dan komedi absurditanya kehidupan modern.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'raditya-dika-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 16,
        'kode'             => 'EVT016',
        'nama'             => 'Stand Up Fest Indonesia 2027 - Parade Tawa Nusantara',
        'category_id'      => 3,
        'tanggal_mulai'    => '2027-03-06',
        'tanggal_selesai'  => '2027-03-07',
        'tanggal_label'    => '6-7 Maret 2027',
        'jam_label'        => '15:00 - 23:00 WIB',
        'venue'            => 'Tennis Indoor Senayan, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Standupindo Pusat',
        'label_sesi'       => null,
        'deskripsi'        => 'Festival komedi tunggal terbesar di Indonesia yang menampilkan parade tawa maraton dari 50 komika lintas generasi dari Sabang sampai Merauke.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'stand-up-fest-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 17,
        'kode'             => 'EVT017',
        'nama'             => 'Abdur Arsyad Solo Stand Up - Pahlawan Perang 2027',
        'category_id'      => 3,
        'tanggal_mulai'    => '2027-02-13',
        'tanggal_selesai'  => '2027-02-13',
        'tanggal_label'    => '13 Februari 2027',
        'jam_label'        => '19:30 - 21:30 WIB',
        'venue'            => 'Graha Bhakti Budaya, Taman Ismail Marzuki, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Kolektif Tawa Timur',
        'label_sesi'       => null,
        'deskripsi'        => 'Pertunjukan komedi tunggal Abdur Arsyad dengan susunan diksi berima yang ciamik, kritik tajam bernas, dan kisah perjalanan hidup anak perantauan dari timur Indonesia.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'abdur-arsyad-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 18,
        'kode'             => 'EVT018',
        'nama'             => 'Eksplor Labuan Bajo & Pulau Komodo Sailing Trip 3D2N',
        'category_id'      => 4,
        'tanggal_mulai'    => '2027-01-08',
        'tanggal_selesai'  => '2027-01-10',
        'tanggal_label'    => '8-10 Januari 2027',
        'jam_label'        => '08:00 - 17:00 WITA',
        'venue'            => 'Pelabuhan Marina Labuan Bajo, Nusa Tenggara Timur',
        'kota'             => 'Labuan Bajo',
        'penyelenggara'    => 'Bajo Liveaboard Adventure',
        'label_sesi'       => null,
        'deskripsi'        => 'Pelayaran liveaboard kapal pinisi mengarungi Taman Nasional Komodo: trekking puncak Pulau Padar, bertemu kadal purba Komodo di Pulau Rinca, dan snorkeling melihat pari manta di Manta Point.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'labuan-bajo-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 19,
        'kode'             => 'EVT019',
        'nama'             => 'Dieng Culture Festival & Heritage Tour 2027',
        'category_id'      => 4,
        'tanggal_mulai'    => '2027-08-06',
        'tanggal_selesai'  => '2027-08-08',
        'tanggal_label'    => '6-8 Agustus 2027',
        'jam_label'        => '07:00 - 22:00 WIB',
        'venue'            => 'Kompleks Candi Arjuna, Dataran Tinggi Dieng, Banjarnegara',
        'kota'             => 'Wonosobo',
        'penyelenggara'    => 'Pokdarwis Dieng Pandawa',
        'label_sesi'       => null,
        'deskripsi'        => 'Perjalanan budaya tahunan di negeri atas awan: menyaksikan prosesi sakral ruwatan rambut gimbal anak Dieng, pelepasan lampion malam hari, serta eksplorasi Kawah Sikidang dan Telaga Warna.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'dieng-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 20,
        'kode'             => 'EVT020',
        'nama'             => 'Open Trip Snorkeling Kepulauan Derawan & Maratua 4D3N',
        'category_id'      => 4,
        'tanggal_mulai'    => '2027-02-18',
        'tanggal_selesai'  => '2027-02-21',
        'tanggal_label'    => '18-21 Februari 2027',
        'jam_label'        => '09:00 - 16:00 WITA',
        'venue'            => 'Meeting Point Bandara Kalimarau, Berau, Kalimantan Timur',
        'kota'             => 'Berau',
        'penyelenggara'    => 'Derawan Paradise Tour',
        'label_sesi'       => null,
        'deskripsi'        => 'Jelajah bahari kepulauan tropis Berau: berenang bersama penyu hijau raksasa di Pulau Derawan, mengapung bersama ubur-ubur tanpa sengat di Danau Kakaban, dan menyelami keindahan laguna Pulau Maratua.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'derawan-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 21,
        'kode'             => 'EVT021',
        'nama'             => 'Indonesia Fintech Summit & Expo 2027',
        'category_id'      => 5,
        'tanggal_mulai'    => '2027-02-10',
        'tanggal_selesai'  => '2027-02-11',
        'tanggal_label'    => '10-11 Februari 2027',
        'jam_label'        => '08:30 - 17:00 WIB',
        'venue'            => 'Jakarta Convention Center (JCC) Assembly Hall, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'Asosiasi Fintech Indonesia (AFTECH)',
        'label_sesi'       => null,
        'deskripsi'        => 'KTT tahunan industri teknologi finansial nasional yang mempertemukan regulator OJK dan Bank Indonesia, unicorn fintech, payment gateway, dan perbankan digital untuk mendiskusikan percepatan inklusi keuangan.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'fintech-summit-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 22,
        'kode'             => 'EVT022',
        'nama'             => 'Indonesia Net Zero Summit & Green Business Forum 2027',
        'category_id'      => 5,
        'tanggal_mulai'    => '2027-03-24',
        'tanggal_selesai'  => '2027-03-25',
        'tanggal_label'    => '24-25 Maret 2027',
        'jam_label'        => '09:00 - 17:00 WIB',
        'venue'            => 'Fairmont Hotel Grand Ballroom, Senayan, Jakarta Pusat',
        'kota'             => 'Jakarta Pusat',
        'penyelenggara'    => 'KADIN Indonesia & Net Zero Hub',
        'label_sesi'       => null,
        'deskripsi'        => 'Konferensi strategis pimpinan korporasi, praktisi keberlanjutan (ESG), dan lembaga investasi hijau untuk merumuskan langkah dekarbonisasi dan peluang bisnis ramah lingkungan di Indonesia.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'net-zero-summit-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
    [
        'id'               => 23,
        'kode'             => 'EVT023',
        'nama'             => 'Indonesia Brand & E-Commerce Conference 2027',
        'category_id'      => 5,
        'tanggal_mulai'    => '2027-01-20',
        'tanggal_selesai'  => '2027-01-20',
        'tanggal_label'    => '20 Januari 2027',
        'jam_label'        => '09:00 - 16:30 WIB',
        'venue'            => 'The Kasablanka Hall, Mall Kota Kasablanka, Jakarta Selatan',
        'kota'             => 'Jakarta Selatan',
        'penyelenggara'    => 'D2C Brand Builders ID',
        'label_sesi'       => null,
        'deskripsi'        => 'Ajang tahunan bagi pemilik brand lokal nusantara, pegiat e-commerce, dan pelaku ritel modern untuk mempelajari tren live shopping, optimasi supply chain, dan strategi scale up bisnis digital.',
        'syarat_ketentuan' => $defaultSnk,
        'poster'           => 'brand-ecommerce-4k.webp',
        'is_featured'      => 0,
        'is_hero'          => 0,
        'deleted_at'       => null,
        'deleted_by'       => null,
    ],
];

$stmtEvent = $pdo->prepare(
    'INSERT INTO events (
        id, kode, nama, category_id, tanggal_mulai, tanggal_selesai, tanggal_label,
        jam_label, venue, kota, penyelenggara, label_sesi, deskripsi, syarat_ketentuan,
        poster, is_featured, is_hero, deleted_at, deleted_by
     ) VALUES (
        :id, :kode, :nama, :category_id, :tanggal_mulai, :tanggal_selesai, :tanggal_label,
        :jam_label, :venue, :kota, :penyelenggara, :label_sesi, :deskripsi, :syarat_ketentuan,
        :poster, :is_featured, :is_hero, :deleted_at, :deleted_by
     ) ON DUPLICATE KEY UPDATE
        nama = VALUES(nama),
        category_id = VALUES(category_id),
        tanggal_mulai = VALUES(tanggal_mulai),
        tanggal_selesai = VALUES(tanggal_selesai),
        tanggal_label = VALUES(tanggal_label),
        jam_label = VALUES(jam_label),
        venue = VALUES(venue),
        kota = VALUES(kota),
        penyelenggara = VALUES(penyelenggara),
        label_sesi = VALUES(label_sesi),
        deskripsi = VALUES(deskripsi),
        syarat_ketentuan = VALUES(syarat_ketentuan),
        poster = VALUES(poster),
        is_featured = VALUES(is_featured),
        is_hero = VALUES(is_hero)'
);
foreach ($events as $ev) {
    $stmtEvent->execute($ev);
}

// 5. Seed Kategori Tiket per Event
$ticketCategories = [
    [1,  1,  'Reguler',                   450000,  140, 90,  'On Sale'],
    [2,  1,  'VIP',                       750000,  60,  30,  'On Sale'],
    [3,  2,  'Reguler',                   150000,  150, 0,   'Habis'],
    [4,  3,  'Reguler',                   350000,  50,  3,   'On Sale'],
    [5,  4,  'Reguler',                   35000,   100, 45,  'On Sale'],
    [6,  5,  'Tiket Masuk Reguler',       20000,   300, 250, 'On Sale'],
    [7,  5,  'VIP Family Pass',           50000,   100, 80,  'On Sale'],
    [8,  6,  'Reguler',                   150000,  100, 70,  'On Sale'],
    [9,  6,  'VIP',                       300000,  50,  30,  'On Sale'],
    [10, 7,  'Tiket Masuk Gratis',        0,       200, 150, 'On Sale'],
    [11, 8,  'Reguler',                   75000,   100, 60,  'On Sale'],
    [12, 8,  'VIP',                       150000,  30,  15,  'On Sale'],
    [13, 9,  'Paket Sunrise 2D1N',        350000,  20,  2,   'On Sale'],
    [14, 10, 'General Pass',              150000,  120, 80,  'On Sale'],
    [15, 10, 'Executive VIP',             350000,  40,  20,  'On Sale'],
    [16, 11, 'Reguler',                   99000,   50,  50,  'On Sale'],
    [17, 12, 'Reguler',                   30000,   100, 100, 'On Sale'],
    [18, 13, 'Reguler',                   125000,  80,  80,  'On Sale'],
    [19, 14, 'Tiket Masuk Reguler',       25000,   200, 180, 'On Sale'],
    [20, 15, 'Reguler',                   120000,  150, 120, 'On Sale'],
    [21, 15, 'VIP',                       250000,  50,  35,  'On Sale'],
    [22, 16, 'Tiket Terusan 2 Hari',      175000,  150, 110, 'On Sale'],
    [23, 17, 'Reguler',                   85000,   100, 75,  'On Sale'],
    [24, 18, 'Paket Sailing Standar',     1850000, 25,  18,  'On Sale'],
    [25, 19, 'Paket Wisata Budaya 3D2N',  450000,  60,  45,  'On Sale'],
    [26, 20, 'Paket Island Hopping',      2200000, 20,  14,  'On Sale'],
    [27, 21, 'Exhibition Pass (Gratis)',  0,       200, 150, 'On Sale'],
    [28, 21, 'Conference Full Pass',      250000,  100, 75,  'On Sale'],
    [29, 22, 'Delegate Pass',             350000,  80,  60,  'On Sale'],
    [30, 23, 'All-Access Pass',           180000,  120, 95,  'On Sale'],
];

$stmtTc = $pdo->prepare(
    'INSERT INTO ticket_categories (id, event_id, nama, harga, kuota_awal, stok_sisa, status)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       nama = VALUES(nama),
       harga = VALUES(harga),
       kuota_awal = VALUES(kuota_awal)'
);
foreach ($ticketCategories as $tc) {
    $stmtTc->execute($tc);
}

// 6. Seed Pemesanan Demo (D4) & Tiket Demo (D5) termasuk TKL-A1B2C3, TKL-D4E5F6, TKL-USED99
$orders = [
    [1,  'INV-20261120-100001', 1, 1, 2, 1, 750000, 22500, 772500, 'QRIS',          'Lunas', 'Andi Pratama',   'KTP', '3171011201990001', 'andi@ticketland.id',  '+628121000001', '2026-11-20 10:15:00'],
    [2,  'INV-20261120-100002', null, 1, 1, 1, 450000, 13500, 463500, 'Transfer BCA',  'Lunas', 'Sari Wulandari', 'KTP', '3171015505000002', 'sari@ticketland.id',  '+628121000002', '2026-11-20 11:20:00'],
    [3,  'INV-20261120-100003', null, 1, 2, 1, 750000, 22500, 772500, 'OVO',           'Lunas', 'Rina Amelia',    'KTP', '3171014403980003', 'rina@ticketland.id',  '+628121000003', '2026-11-20 13:05:00'],
    [4,  'INV-20261121-100004', null, 1, 1, 1, 450000, 13500, 463500, 'DANA',          'Lunas', 'Maya Putri',     'KTP', '3171016208010004', 'maya@ticketland.id',  '+628121000004', '2026-11-21 09:30:00'],
    [5,  'INV-20261121-100005', null, 1, 2, 1, 750000, 22500, 772500, 'Transfer BNI',  'Lunas', 'Dewi Anggraini', 'KTP', '3171015111970005', 'dewi@ticketland.id',  '+628121000005', '2026-11-21 14:10:00'],
    [6,  'INV-20261121-100006', null, 1, 1, 1, 450000, 13500, 463500, 'ShopeePay',     'Lunas', 'Rizky Ramadhan', 'KTP', '3171011804990006', 'rizky@ticketland.id', '+628121000006', '2026-11-21 15:40:00'],
    [7,  'INV-20261122-100007', null, 3, 4, 2, 700000, 21000, 721000, 'QRIS',          'Lunas', 'Budi Hartono',   'KTP', '3273010907960007', 'budi.h@ticketland.id','+628121000007', '2026-11-22 10:02:00'],
    [8,  'INV-20261122-100008', null, 4, 5, 3, 105000, 3150,  108150, 'Transfer BRI',  'Lunas', 'Citra Lestari',  'KTP', '3471014802000008', 'citra@ticketland.id', '+628121000008', '2026-11-22 11:08:00'],
    [9,  'INV-20261123-100009', null, 5, 6, 2, 40000,  1200,  41200,  'QRIS',          'Lunas', 'Fajar Nugroho',  'KTP', '3174012209950009', 'fajar@ticketland.id', '+628121000009', '2026-11-23 16:00:00'],
    [10, 'INV-20261124-100010', null, 9, 13,1, 350000, 10500, 360500, 'Transfer Mandiri','Lunas','Intan Permata', 'KTP', '3573016612980010', 'intan@ticketland.id', '+628121000010', '2026-11-24 19:15:00'],
];

$stmtOrder = $pdo->prepare(
    'INSERT INTO orders (
        id, nomor_invoice, akun_user_id, event_id, ticket_category_id, jumlah,
        subtotal, biaya_layanan, total, metode_pembayaran, status,
        pemesan_nama, pemesan_tipe_identitas, pemesan_nomor_identitas, pemesan_email, pemesan_wa, created_at
     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        nomor_invoice = VALUES(nomor_invoice)'
);
foreach ($orders as $o) {
    $stmtOrder->execute($o);
}

$tickets = [
    [1,  1,  1, 2,  'TKL-A1B2C3', 'Andi Pratama',   'KTP', '3171011201990001', 'andi@ticketland.id',   'Laki-laki', '+628121000001', 'Belum Check-in', null,                  null, '2026-11-20 10:15:00'],
    [2,  2,  1, 1,  'TKL-D4E5F6', 'Sari Wulandari', 'KTP', '3171015505000002', 'sari@ticketland.id',   'Perempuan', '+628121000002', 'Belum Check-in', null,                  null, '2026-11-20 11:20:00'],
    [3,  3,  1, 2,  'TKL-USED99', 'Rina Amelia',    'KTP', '3171014403980003', 'rina@ticketland.id',   'Perempuan', '+628121000003', 'Sudah Check-in', '2026-11-25 18:20:00', 3,    '2026-11-20 13:05:00'],
    [4,  4,  1, 1,  'TKL-B7N4V1', 'Maya Putri',     'KTP', '3171016208010004', 'maya@ticketland.id',   'Perempuan', '+628121000004', 'Sudah Check-in', '2026-11-25 18:12:00', 3,    '2026-11-21 09:30:00'],
    [5,  5,  1, 2,  'TKL-K5L9M2', 'Dewi Anggraini', 'KTP', '3171015111970005', 'dewi@ticketland.id',   'Perempuan', '+628121000005', 'Sudah Check-in', '2026-11-25 18:03:00', 3,    '2026-11-21 14:10:00'],
    [6,  6,  1, 1,  'TKL-P8R2T4', 'Rizky Ramadhan', 'KTP', '3171011804990006', 'rizky@ticketland.id',  'Laki-laki', '+628121000006', 'Sudah Check-in', '2026-11-25 17:58:00', 3,    '2026-11-21 15:40:00'],
    [7,  7,  3, 4,  'TKL-H8J3K9', 'Budi Hartono',   'KTP', '3273010907960007', 'budi.h@ticketland.id', 'Laki-laki', '+628121000007', 'Belum Check-in', null,                  null, '2026-11-22 10:02:00'],
    [8,  8,  4, 5,  'TKL-F7T2R5', 'Citra Lestari',  'KTP', '3471014802000008', 'citra@ticketland.id',  'Perempuan', '+628121000008', 'Belum Check-in', null,                  null, '2026-11-22 11:08:00'],
    [9,  9,  5, 6,  'TKL-R9W4Q6', 'Fajar Nugroho',  'KTP', '3174012209950009', 'fajar@ticketland.id',  'Laki-laki', '+628121000009', 'Belum Check-in', null,                  null, '2026-11-23 16:00:00'],
    [10, 10, 9, 13, 'TKL-B3S8K1', 'Intan Permata',  'KTP', '3573016612980010', 'intan@ticketland.id',  'Perempuan', '+628121000010', 'Belum Check-in', null,                  null, '2026-11-24 19:15:00'],
];

$stmtTicket = $pdo->prepare(
    'INSERT INTO tickets (
        id, order_id, event_id, ticket_category_id, kode_tiket, nama_pemegang,
        tipe_identitas, nomor_identitas, email, jenis_kelamin, wa,
        status_checkin, waktu_checkin, checkin_staff_id, created_at
     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        nama_pemegang = VALUES(nama_pemegang)'
);
foreach ($tickets as $t) {
    $stmtTicket->execute($t);
}

// 7. Seed Riwayat Transaksi Stok
$stockTx = [
    [1, 1, 1,  'IN',  200, 200, 'admin',    'Kuota tiket awal',          null, '2026-11-19 09:00:00'],
    [2, 1, 1,  'OUT', 4,   120, 'sistem',   'Penjualan online',          6,    '2026-11-21 15:40:00'],
    [3, 3, 4,  'IN',  1,   5,   'manager1', 'Refund disetujui',          null, '2026-11-21 13:25:00'],
    [4, 3, 4,  'OUT', 2,   3,   'sistem',   'Penjualan online',          7,    '2026-11-22 10:02:00'],
    [5, 4, 5,  'OUT', 3,   45,  'sistem',   'Penjualan online',          8,    '2026-11-22 11:08:00'],
    [6, 5, 6,  'IN',  50,  250, 'admin',    'Penambahan kuota tiket',    null, '2026-11-23 09:14:00'],
    [7, 9, 13, 'OUT', 5,   2,   'sistem',   'Penjualan online',          10,   '2026-11-24 19:15:00'],
];
$stmtSt = $pdo->prepare(
    'INSERT INTO stock_transactions (id, event_id, ticket_category_id, jenis, jumlah, sisa_sesudah, pelaku, catatan, order_id, waktu)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       catatan = VALUES(catatan)'
);
foreach ($stockTx as $st) {
    $stmtSt->execute($st);
}

// 8. Seed Riwayat Check-in Staff
$checkinLogs = [
    [1, 6,    'TKL-P8R2T4', 'Rizky Ramadhan', 'Tulus Live in Concert', 'Check-in', 'KTP sesuai',             3, '2026-11-25 17:58:00'],
    [2, 5,    'TKL-K5L9M2', 'Dewi Anggraini', 'Tulus Live in Concert', 'Check-in', 'KTP sesuai',             3, '2026-11-25 18:03:00'],
    [3, null, 'TKL-Z3X6C8', '-',              '-',                     'Ditolak',  'Kode tidak ditemukan',   3, '2026-11-25 18:07:00'],
    [4, 4,    'TKL-B7N4V1', 'Maya Putri',     'Tulus Live in Concert', 'Check-in', 'KTP sesuai',             3, '2026-11-25 18:12:00'],
    [5, 3,    'TKL-USED99', 'Rina Amelia',    'Tulus Live in Concert', 'Check-in', 'KTP sesuai',             3, '2026-11-25 18:20:00'],
];
$stmtCl = $pdo->prepare(
    'INSERT INTO checkin_logs (id, ticket_id, kode_dipindai, nama_pemesan, nama_event, hasil, alasan, staff_id, waktu)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       kode_dipindai = VALUES(kode_dipindai)'
);
foreach ($checkinLogs as $cl) {
    $stmtCl->execute($cl);
}

// 9. Seed Log Aktivitas
$actLogs = [
    [1, '2026-11-18 10:02:00', 1, 'admin',    'delete',  'events',       'Menghapus event Workshop Digital Marketing (EVT011) melalui soft delete ke Sampah.'],
    [2, '2026-11-21 13:25:00', 2, 'manager1', 'approve', 'transactions', 'Menyetujui pengembalian 1 tiket Nadin Amizah - Live in Bandung (EVT003).'],
    [3, '2026-11-22 10:02:00', 1, 'admin',    'update',  'transactions', 'Mencatat penjualan online Nadin Amizah - Live in Bandung (EVT003): keluar 2 tiket, sisa menjadi 3.'],
    [4, '2026-11-23 09:14:00', 1, 'admin',    'create',  'events',       'Menambahkan kuota event ABC & Mom Expo Indonesia 2026 (EVT005).'],
    [5, '2026-11-25 08:30:00', 1, 'admin',    'login',   'auth',         'Pengguna admin berhasil login ke sistem Ticket Land.'],
];
$stmtAl = $pdo->prepare(
    'INSERT INTO activity_logs (id, waktu, user_id, username, aksi, entitas, deskripsi)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       deskripsi = VALUES(deskripsi)'
);
foreach ($actLogs as $al) {
    $stmtAl->execute($al);
}

echo "Migrasi & seed db_ticketland berhasil dijalankan.\n";
