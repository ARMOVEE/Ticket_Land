<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="color-scheme" content="light" />
    <link rel="icon" href="data:," />
    <title>Ticket Land - Dashboard Admin</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/assets/css/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/assets/css/boxicons.min.css" rel="stylesheet" />
    <link href="/assets/css/poppins.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/admin.css" />
  </head>
  <body class="bg-light">

    <!-- Halaman Login Admin -->
    <div id="page-login" class="page container">
      <div class="row justify-content-center" style="margin-top: 8vh">
        <div class="col-md-5 col-lg-4">
          <div class="text-center mb-4">
            <svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo tiket Ticket Land" class="text-primary" style="width:48px;height:48px" fill="none" stroke="currentColor" stroke-width="2.6"><rect x="2.5" y="4" width="43" height="22" rx="5"/><path d="M31 5.5v19" stroke-width="2" stroke-dasharray="3 3.4" stroke-linecap="round"/><circle cx="31" cy="4" r="2.4" fill="#FFFFFF"/><circle cx="31" cy="26" r="2.4" fill="#FFFFFF"/></svg>
            <h1 class="h3 fw-bold mt-2 mb-0">Ticket Land</h1>
            <small class="text-muted">Sistem Pemesanan Tiket Event</small>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h5 text-center mb-4">Login ke Akun Anda</h2>
              <div id="loginAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>
              <form id="formLogin">
                <div class="mb-3">
                  <label for="username" class="form-label">Username atau Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="admin atau demo@ticketland.id" required autofocus />
                  </div>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required />
                  </div>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Masuk</button>
              </form>
              <div class="d-flex align-items-center my-4" aria-hidden="true">
                <hr class="flex-grow-1 my-0" />
                <span class="text-muted small px-3">atau</span>
                <hr class="flex-grow-1 my-0" />
              </div>
              <p class="text-center text-muted small mb-2">Petugas check-in di pintu masuk?</p>
              <a href="/staff/login" class="btn btn-outline-primary btn-lg w-100" id="linkKeStaff"><i class="bi bi-qr-code-scan me-2" aria-hidden="true"></i>Masuk sebagai Staff - Check-in</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kerangka Dashboard Admin (Gaya Inventaris) -->
    <div id="appShell" class="d-none">
      <nav class="sidebar" id="sidebar" aria-label="Menu utama Dashboard Admin">
        <div class="logo-details" id="logoToggle" title="Ciutkan atau lebarkan sidebar">
          <svg class="logo-icon" viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo tiket Ticket Land" style="width:28px;height:28px;min-width:28px;flex:none" fill="none" stroke="currentColor" stroke-width="2.6"><rect x="2.5" y="4" width="43" height="22" rx="5"/><path d="M31 5.5v19" stroke-width="2" stroke-dasharray="3 3.4" stroke-linecap="round"/><circle cx="31" cy="4" r="2.4" fill="#FFFFFF"/><circle cx="31" cy="26" r="2.4" fill="#FFFFFF"/></svg>
          <span class="logo-name ms-2">Ticket Land</span>
        </div>
        <div class="menu-label">Menu</div>
        <ul class="nav-list">
          <li>
            <a href="/admin/dashboard" data-nav="dashboard" data-navlink="dashboard"><i class="bx bx-grid-alt" aria-hidden="true"></i><span class="link-name">Dashboard</span></a>
          </li>
          <li>
            <a href="/admin/events" data-nav="items" data-navlink="items"><i class="bx bx-collection" aria-hidden="true"></i><span class="link-name">Event</span></a>
          </li>
          <li>
            <a href="/admin/categories" data-nav="categories" data-navlink="categories"><i class="bx bx-category" aria-hidden="true"></i><span class="link-name">Kategori</span></a>
          </li>
          <li>
            <a href="/admin/transactions" data-nav="transactions" data-navlink="transactions"><i class="bx bx-transfer" aria-hidden="true"></i><span class="link-name">Transaksi</span></a>
          </li>
          <li>
            <a href="/admin/reports" data-nav="reports" data-navlink="reports"><i class="bx bx-bar-chart-alt-2" aria-hidden="true"></i><span class="link-name">Laporan</span></a>
          </li>
          <li>
            <a href="/admin/users" data-nav="users" data-navlink="users"><i class="bx bx-user" aria-hidden="true"></i><span class="link-name">Pengguna</span></a>
          </li>
          <li>
            <a href="/admin/logs" data-nav="activity" data-navlink="activity"><i class="bx bx-notepad" aria-hidden="true"></i><span class="link-name">Log</span></a>
          </li>
        </ul>
        <div class="profile-details">
          <div class="profile-content">
            <div class="profile-avatar" id="profileAvatar">A</div>
          </div>
          <div class="name-job">
            <div class="profile-name" id="profileName">Afrizal Tazul Aripin</div>
            <div class="job" id="profileJob">admin</div>
          </div>
          <a href="/admin/logout" class="logout-btn" id="btnLogout" aria-label="Logout" title="Logout"><i class="bx bx-log-out" aria-hidden="true"></i></a>
        </div>
      </nav>

      <section class="home-section">
        <header class="home-content">
          <button type="button" class="bx bx-menu" id="btnMenu" aria-label="Toggle sidebar"></button>
          <span class="text">Ticket Land</span>
        </header>

        <main class="container-fluid px-4 pb-4">
          <div id="flashArea"></div>

          <!-- 1. Halaman Dashboard -->
          <section id="page-dashboard" class="page d-none" aria-label="Ringkasan Dashboard">
            <h2 class="h3 mb-4">Dashboard</h2>

            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 mb-3">
              <div class="col">
                <div class="card stat-card h-100">
                  <div class="card-body">
                    <div class="stat-label">Total Event</div>
                    <div class="stat-value" id="statTotalEvent">0</div>
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="card stat-card h-100">
                  <div class="card-body">
                    <div class="stat-label">Total Tiket</div>
                    <div class="stat-value" id="statTotalTiket">0</div>
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="card stat-card h-100">
                  <div class="card-body">
                    <div class="stat-label">Nilai Penjualan</div>
                    <div class="stat-value" id="statNilaiPenjualan">Rp0</div>
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="card stat-card h-100">
                  <div class="card-body">
                    <div class="stat-label">Tiket Menipis</div>
                    <div class="stat-value text-danger" id="statTiketMenipis">0</div>
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="card stat-card h-100">
                  <div class="card-body">
                    <div class="stat-label">Transaksi Hari Ini</div>
                    <div class="stat-value" id="statTransaksiHariIni">0</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-lg-8">
                <div class="card h-100">
                  <div class="card-header">
                    <i class="bi bi-bar-chart-line text-primary me-1" aria-hidden="true"></i>Pergerakan Tiket 7 Hari
                  </div>
                  <div class="card-body">
                    <div style="height: 260px">
                      <canvas id="chartTiket" aria-label="Grafik batang pergerakan tiket 7 hari terakhir"></canvas>
                    </div>
                    <small class="text-muted d-block mt-3">Masuk = tiket ditambahkan/refund, Keluar = tiket terjual.</small>
                  </div>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="card h-100">
                  <div class="card-header">
                    <i class="bi bi-stack text-primary me-1" aria-hidden="true"></i>Tiket Terbanyak
                  </div>
                  <ul class="list-group list-group-flush" id="listTiketTerbanyak"></ul>
                </div>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card h-100">
                  <div class="card-header">
                    <i class="bi bi-exclamation-triangle text-danger me-1" aria-hidden="true"></i>Tiket Menipis (sisa &le; 5)
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Kode</th>
                          <th>Nama</th>
                          <th>Kategori</th>
                          <th class="text-end">Sisa</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyTiketMenipis"></tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card h-100">
                  <div class="card-header">
                    <i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i>Transaksi Terbaru
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Waktu</th>
                          <th>Event</th>
                          <th>Jenis</th>
                          <th class="text-end">Jumlah</th>
                          <th>Oleh</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyTransaksiTerbaru"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- 2. Halaman Event -->
          <section id="page-items" class="page d-none" aria-label="Kelola Data Event">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Data Event</h2>
                <small class="text-muted">Daftar event dan tiket</small>
              </div>
              <button type="button" class="btn btn-primary" id="btnTambahEvent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Event</button>
            </div>

            <ul class="nav nav-tabs mb-3">
              <li class="nav-item">
                <a class="nav-link active" href="#" id="tabBarangAktif"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Event Aktif</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#" id="tabSampah"><i class="bi bi-trash me-1" aria-hidden="true"></i>Sampah <span class="badge bg-secondary" id="badgeSampahCount">0</span></a>
              </li>
            </ul>

            <div id="panelBarangAktif">
              <form class="row g-2 mb-3" id="formFilterBarang">
                <div class="col-md-5">
                  <label for="cariBarang" class="visually-hidden">Cari kode atau nama event</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="cariBarang" placeholder="Cari kode atau nama event..." />
                  </div>
                </div>
                <div class="col-md-4">
                  <label for="filterKategori" class="visually-hidden">Filter kategori</label>
                  <select class="form-select" id="filterKategori" aria-label="Filter kategori">
                    <option value="">Semua Kategori</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-center gap-2">
                  <label for="perPage" class="form-label mb-0 text-muted">Tampilkan</label>
                  <select class="form-select" id="perPage" aria-label="Jumlah per halaman">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                  <span class="text-muted">/ halaman</span>
                </div>
              </form>

              <div class="card">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" id="tabelBarang">
                    <thead>
                      <tr>
                        <th>Poster</th>
                        <th>Kode</th>
                        <th>Nama Event</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Harga Tiket</th>
                        <th>Sisa Tiket</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyBarang"></tbody>
                  </table>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <small class="text-muted" id="infoBarang">Menampilkan 0 event</small>
                <nav aria-label="Navigasi halaman event">
                  <ul class="pagination pagination-sm mb-0" id="paginationBarang"></ul>
                </nav>
              </div>
            </div>

            <div id="panelSampah" class="d-none">
              <div class="alert alert-secondary d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                <small>Event di Sampah adalah hasil <em>soft delete</em> (Modul 05): data tidak benar-benar terhapus dan masih bisa dipulihkan.</small>
              </div>
              <div class="card">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Kode</th>
                        <th>Nama Event</th>
                        <th>Kategori</th>
                        <th>Dihapus Pada</th>
                        <th>Dihapus Oleh</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody id="tbodySampah"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <!-- 2b. Halaman Detail Event -->
          <section id="page-item-detail" class="page d-none" aria-label="Detail Event">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Detail Event</h2>
                <small class="text-muted">Informasi event dan riwayat tiketnya</small>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-nav="items"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali</button>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <div class="card h-100">
                  <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center" id="detailPosterBox">
                    <div class="bg-secondary bg-opacity-25 text-secondary d-flex flex-column align-items-center justify-content-center rounded w-100" style="height: 240px">
                      <i class="bi bi-image" style="font-size: 3rem" aria-hidden="true"></i>
                      <small class="text-muted mt-2">Poster event Ticket Land</small>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-8">
                <div class="card h-100">
                  <div class="card-header" id="detailNama">-</div>
                  <div class="table-responsive">
                    <table class="table align-middle mb-0">
                      <tbody>
                        <tr>
                          <th style="width: 180px">Kode Event</th>
                          <td><code id="detailKode">-</code></td>
                        </tr>
                        <tr>
                          <th>Nama Event</th>
                          <td id="detailNama2">-</td>
                        </tr>
                        <tr>
                          <th>Kategori</th>
                          <td id="detailKategori">-</td>
                        </tr>
                        <tr>
                          <th>Tanggal</th>
                          <td id="detailTanggal">-</td>
                        </tr>
                        <tr>
                          <th>Venue</th>
                          <td id="detailVenue">-</td>
                        </tr>
                        <tr>
                          <th>Harga Tiket</th>
                          <td id="detailHarga">-</td>
                        </tr>
                        <tr>
                          <th>Sisa Tiket</th>
                          <td><span class="badge bg-primary" id="detailStok">0 tiket</span></td>
                        </tr>
                        <tr>
                          <th>Deskripsi</th>
                          <td id="detailDeskripsi">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i>Riwayat Tiket
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Jenis</th>
                      <th>Jumlah</th>
                      <th>Sisa Tiket</th>
                      <th>Oleh</th>
                      <th>Catatan</th>
                    </tr>
                  </thead>
                  <tbody id="detailRiwayat"></tbody>
                </table>
              </div>
            </div>
          </section>

          <!-- 3. Halaman Kategori -->
          <section id="page-categories" class="page d-none" aria-label="Kelola Data Kategori">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Data Kategori</h2>
                <small class="text-muted">Daftar kategori event resmi Ticket Land (D2)</small>
              </div>
              <button type="button" class="btn btn-primary" id="btnTambahKategori"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Kategori</button>
            </div>

            <div class="card">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Nama Kategori</th>
                      <th>Deskripsi</th>
                      <th>Jumlah Event</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyKategori"></tbody>
                </table>
              </div>
            </div>
          </section>

          <!-- 4. Halaman Transaksi -->
          <section id="page-transactions" class="page d-none" aria-label="Kelola Data Transaksi">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Data Transaksi</h2>
                <small class="text-muted">Riwayat transaksi tiket masuk dan keluar serta daftar pemesanan</small>
              </div>
              <button type="button" class="btn btn-primary" id="btnTransaksiBaru"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Transaksi Baru</button>
            </div>
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Masuk = tiket ditambahkan/dikembalikan (refund), Keluar = tiket terjual. Penjualan melebihi sisa tiket ditolak dan stok tidak berubah.</p>

            <div class="btn-group mb-3" role="group" aria-label="Filter jenis transaksi">
              <button type="button" class="btn btn-outline-primary active" data-tx-filter="">Semua</button>
              <button type="button" class="btn btn-outline-primary" data-tx-filter="IN">Masuk</button>
              <button type="button" class="btn btn-outline-primary" data-tx-filter="OUT">Keluar</button>
            </div>

            <div class="card mb-4">
              <div class="card-header">
                <i class="bi bi-arrow-left-right text-primary me-1" aria-hidden="true"></i>Riwayat Pergerakan Stok Tiket
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelTransaksi">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Event</th>
                      <th>Jenis</th>
                      <th class="text-center">Jumlah</th>
                      <th>Pengguna</th>
                      <th>Catatan</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyTransaksi"></tbody>
                </table>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <i class="bi bi-receipt text-primary me-1" aria-hidden="true"></i>Daftar Pemesanan &amp; Status Tiket (D4)
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Nomor Invoice</th>
                      <th>Tanggal</th>
                      <th>Pemesan</th>
                      <th>Event &amp; Kategori</th>
                      <th class="text-center">Jumlah</th>
                      <th class="text-end">Total Bayar</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyPemesananAdmin"></tbody>
                </table>
              </div>
            </div>
          </section>

          <!-- 5. Halaman Laporan -->
          <section id="page-reports" class="page d-none" aria-label="Laporan Penjualan dan Passenger">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Laporan</h2>
                <small class="text-muted">Laporan pemesanan &amp; pembatalan dan daftar passenger</small>
              </div>
              <div class="d-flex align-items-center gap-2">
                <label for="filterLaporanEvent" class="visually-hidden">Filter Event</label>
                <select id="filterLaporanEvent" class="form-select form-select-sm" style="min-width: 200px;">
                  <option value="">Semua Event</option>
                </select>
                <button type="button" class="btn btn-success btn-sm text-nowrap" id="btnExportCsv"><i class="bi bi-download me-1" aria-hidden="true"></i>Ekspor CSV</button>
              </div>
            </div>

            <ul class="nav nav-tabs mb-3">
              <li class="nav-item">
                <a class="nav-link active" href="#" id="tabLaporanPemesanan"><i class="bi bi-file-earmark-bar-graph me-1" aria-hidden="true"></i>Laporan Pemesanan &amp; Pembatalan</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#" id="tabDaftarPassenger"><i class="bi bi-people me-1" aria-hidden="true"></i>Daftar Passenger</a>
              </li>
            </ul>

            <div id="panelLaporanPemesanan">
              <div class="card">
                <div class="card-header">
                  <i class="bi bi-file-earmark-bar-graph text-primary me-1" aria-hidden="true"></i>Ringkasan Penjualan Tiket per Event
                </div>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" id="tabelLaporan">
                    <thead>
                      <tr>
                        <th>Kode</th>
                        <th>Nama Event</th>
                        <th>Kategori</th>
                        <th class="text-end">Tiket Terjual</th>
                        <th class="text-end">Harga Tiket</th>
                        <th class="text-end">Total Penjualan</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyLaporan"></tbody>
                    <tfoot>
                      <tr class="fw-bold">
                        <td colspan="3">Total</td>
                        <td class="text-end" id="totalTiketTerjual">0</td>
                        <td></td>
                        <td class="text-end" id="totalNilaiLaporan">Rp0</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <div id="panelDaftarPassenger" class="d-none">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <span><i class="bi bi-people text-primary me-1" aria-hidden="true"></i>Daftar Passenger</span>
                  <small class="text-muted fw-normal" id="ringkasanPassenger">0 passenger · 0 sudah check-in · 0 belum</small>
                </div>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" id="tabelPassenger">
                    <thead>
                      <tr>
                        <th>Nama Pemesan</th>
                        <th>Kode Tiket</th>
                        <th>Event</th>
                        <th>Kategori Tiket</th>
                        <th>Status Check-in</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyPassenger"></tbody>
                  </table>
                </div>
              </div>
              <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Status di daftar ini mengikuti check-in lewat Scan QR di halaman staff: passenger yang baru check-in langsung berubah jadi Sudah Check-in.</p>
            </div>
          </section>

          <!-- 6. Halaman Pengguna -->
          <section id="page-users" class="page d-none" aria-label="Kelola Pengguna Sistem">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Data Pengguna</h2>
                <small class="text-muted">Daftar pengguna sistem</small>
                <small class="text-muted d-block">Akun dengan role Staff dipakai untuk login di halaman Staff Check-in (scan QR).</small>
              </div>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPengguna"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Tambah Pengguna</button>
            </div>

            <div class="card">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Username</th>
                      <th>Nama Lengkap</th>
                      <th>Role</th>
                      <th>Terdaftar</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyPengguna"></tbody>
                </table>
              </div>
            </div>
            <p class="text-muted small mt-3"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Hak akses dibedakan per peran (RBAC Modul 05 Langkah 3): admin mengelola semua data, manager menyetujui transaksi, staff memvalidasi check-in tiket.</p>
          </section>

          <!-- 7. Halaman Log Aktivitas -->
          <section id="page-activity" class="page d-none" aria-label="Log Aktivitas Sistem">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
              <div>
                <h2 class="h3 mb-0">Log Aktivitas</h2>
                <small class="text-muted">Catatan aktivitas pengguna di sistem (audit log)</small>
              </div>
            </div>

            <div class="card">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Waktu</th>
                      <th>Pengguna</th>
                      <th>Aksi</th>
                      <th>Entitas</th>
                      <th>Deskripsi</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyLogAktivitas"></tbody>
                </table>
              </div>
            </div>
          </section>

        </main>
      </section>
    </div>

    <!-- Modal Tambah / Edit Event -->
    <div class="modal fade" id="modalFormEvent" tabindex="-1" aria-labelledby="modalFormEventLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="formEvent">
            <input type="hidden" id="eventId" value="0" />
            <div class="modal-header">
              <h5 class="modal-title" id="modalFormEventLabel"><i class="bi bi-calendar-plus me-1" aria-hidden="true"></i>Tambah Event Baru</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label for="eventNama" class="form-label">Nama Event</label>
                  <input type="text" class="form-control" id="eventNama" placeholder="mis. Konser Musik Nusantara" required />
                </div>
                <div class="col-md-4">
                  <label for="eventKategoriId" class="form-label">Kategori Event</label>
                  <select class="form-select" id="eventKategoriId" required></select>
                </div>
                <div class="col-md-4">
                  <label for="eventTanggalMulai" class="form-label">Tanggal Event</label>
                  <input type="date" class="form-control" id="eventTanggalMulai" required />
                </div>
                <div class="col-md-4">
                  <label for="eventJamLabel" class="form-label">Jam Acara</label>
                  <input type="text" class="form-control" id="eventJamLabel" value="19:00 - 22:00 WIB" required />
                </div>
                <div class="col-md-4">
                  <label for="eventPenyelenggara" class="form-label">Penyelenggara</label>
                  <input type="text" class="form-control" id="eventPenyelenggara" placeholder="mis. Tiga Rambu" required />
                </div>
                <div class="col-md-8">
                  <label for="eventVenue" class="form-label">Venue / Lokasi</label>
                  <input type="text" class="form-control" id="eventVenue" placeholder="mis. Istora Senayan" required />
                </div>
                <div class="col-md-4">
                  <label for="eventKota" class="form-label">Kota</label>
                  <input type="text" class="form-control" id="eventKota" placeholder="mis. Jakarta" required />
                </div>
                <div class="col-md-4">
                  <label for="eventNamaKategoriTiket" class="form-label">Nama Kategori Tiket</label>
                  <input type="text" class="form-control" id="eventNamaKategoriTiket" value="Reguler" required />
                </div>
                <div class="col-md-4">
                  <label for="eventHarga" class="form-label">Harga Tiket (Rp)</label>
                  <input type="number" class="form-control" id="eventHarga" min="0" value="150000" required />
                </div>
                <div class="col-md-4">
                  <label for="eventStok" class="form-label">Kuota / Sisa Tiket</label>
                  <input type="number" class="form-control" id="eventStok" min="0" value="100" required />
                </div>
                <div class="col-12">
                  <label for="eventPosterFile" class="form-label">Poster Event (Opsional)</label>
                  <div class="input-group">
                    <input type="file" class="form-control" id="eventPosterFile" accept="image/jpeg,image/png,image/webp" />
                    <button type="button" class="btn btn-outline-danger d-none" id="btnHapusPoster"><i class="bi bi-trash me-1" aria-hidden="true"></i>Hapus Poster</button>
                  </div>
                  <div class="form-text">Format: JPG, PNG, atau WebP (maks. 2MB). Jika dikosongkan, poster otomatis memakai banner dinamis per kategori.</div>
                  <input type="hidden" id="eventExistingPoster" value="" />
                  <input type="hidden" id="eventHapusPoster" value="0" />
                  <div id="eventPosterPreviewBox" class="mt-2 d-none">
                    <div class="d-flex align-items-center gap-3 p-2 border rounded bg-light">
                      <img id="eventPosterPreviewImg" src="" alt="Preview Poster" class="rounded object-fit-cover" style="width: 100px; height: 60px;" />
                      <div>
                        <small class="d-block fw-semibold text-dark" id="eventPosterPreviewName">Poster terpilih</small>
                        <small class="text-muted" id="eventPosterPreviewSize"></small>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <label for="eventDeskripsi" class="form-label">Deskripsi Event</label>
                  <textarea class="form-control" id="eventDeskripsi" rows="3" placeholder="Tulis deskripsi acara..."></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan Event</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Tambah / Edit Kategori -->
    <div class="modal fade" id="modalFormKategori" tabindex="-1" aria-labelledby="modalFormKategoriLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="formKategori">
            <input type="hidden" id="kategoriId" value="0" />
            <div class="modal-header">
              <h5 class="modal-title" id="modalFormKategoriLabel"><i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Tambah Kategori</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="kategoriNama" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="kategoriNama" placeholder="mis. Musik" required />
              </div>
              <div class="mb-0">
                <label for="kategoriDeskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="kategoriDeskripsi" rows="2" placeholder="Deskripsi singkat kategori..." required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan Kategori</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Transaksi Stok Baru -->
    <div class="modal fade" id="modalTransaksiBaru" tabindex="-1" aria-labelledby="modalTransaksiBaruLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="formTransaksiBaru">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTransaksiBaruLabel"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Catat Transaksi Stok Baru</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="txTicketCategoryId" class="form-label">Event &amp; Kategori Tiket</label>
                <select class="form-select" id="txTicketCategoryId" required></select>
              </div>
              <div class="mb-3">
                <label for="txJenis" class="form-label">Jenis Transaksi</label>
                <select class="form-select" id="txJenis" required>
                  <option value="IN" selected>Masuk (Tambah Kuota / Refund)</option>
                  <option value="OUT">Keluar (Penjualan / Koreksi Stok)</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="txJumlah" class="form-label">Jumlah Tiket</label>
                <input type="number" class="form-control" id="txJumlah" min="1" value="10" required />
              </div>
              <div class="mb-0">
                <label for="txCatatan" class="form-label">Catatan</label>
                <input type="text" class="form-control" id="txCatatan" placeholder="mis. Penambahan kuota tiket" />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan Transaksi</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalHapusLabel"><i class="bi bi-exclamation-triangle text-danger me-1" aria-hidden="true"></i>Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2">Hapus <strong id="hapusNama">data ini</strong>? Data event akan dipindahkan ke Sampah (soft delete) dan masih bisa dipulihkan.</p>
            <div id="hapusSoldWarning" class="alert alert-danger py-2 small mb-0 d-none" role="alert"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-danger" id="btnKonfirmasiHapus"><i class="bi bi-trash me-1" aria-hidden="true"></i>Hapus</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Pengguna (DFD 1.1 Tambah Data Staff) -->
    <div class="modal fade" id="modalTambahPengguna" tabindex="-1" aria-labelledby="modalTambahPenggunaLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="formTambahPengguna">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTambahPenggunaLabel"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Tambah Pengguna</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="penggunaNama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="penggunaNama" placeholder="mis. Budi Santoso" required />
              </div>
              <div class="mb-3">
                <label for="penggunaUsername" class="form-label">Username</label>
                <input type="text" class="form-control" id="penggunaUsername" placeholder="mis. staff2" required />
              </div>
              <div class="mb-3">
                <label for="penggunaRole" class="form-label">Role</label>
                <select class="form-select" id="penggunaRole">
                  <option value="Staff" selected>Staff</option>
                  <option value="Manager">Manager</option>
                  <option value="Admin">Admin</option>
                </select>
              </div>
              <div class="mb-0">
                <label for="penggunaPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="penggunaPassword" placeholder="Minimal 8 karakter" minlength="8" required />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan Pengguna</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      window.TICKET_LAND_ADMIN_BOOT = <?= json_encode([
          'csrfToken'   => $csrfToken,
          'admin'       => $admin,
          'initialPage' => $initialPage,
          'state'       => $admin ? $state : null,
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="/assets/js/bootstrap.bundle.min.js" defer></script>
    <script src="/assets/js/chart.umd.min.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
  </body>
</html>
