<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="color-scheme" content="light" />
    <link rel="icon" href="data:," />
    <title>Ticket Land - Staff Check-in (Scan QR)</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/assets/css/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/assets/css/boxicons.min.css" rel="stylesheet" />
    <link href="/assets/css/plus-jakarta-sans.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/staff.css?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/css/staff.css') ?: time() ?>" />
  </head>
  <body>
    <!-- Staff Navbar -->
    <header class="staff-navbar d-flex align-items-center justify-content-between">
      <a href="/staff" class="staff-logo" aria-label="Ticket Land">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" style="width:28px;height:28px;color:#0B5D3B;flex:0 0 auto;" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
        <span>Ticket Land <span class="badge badge-mint ms-1 fs-6">Staff Check-in</span></span>
      </a>
      <div class="d-flex align-items-center gap-3">
        <span class="d-none d-md-inline-block text-muted small">
          Petugas: <strong class="text-dark"><?= htmlspecialchars($staff['nama'] ?? $staff['username'], ENT_QUOTES, 'UTF-8') ?></strong>
          <span class="badge bg-secondary ms-1"><?= htmlspecialchars($staff['peran'] ?? 'staff', ENT_QUOTES, 'UTF-8') ?></span>
        </span>
        <?php if (in_array($staff['peran'] ?? '', ['admin', 'manager'], true)): ?>
          <a href="/admin" class="btn btn-sm btn-outline-success"><i class="bi bi-speedometer2 me-1"></i>Ke Admin</a>
        <?php endif; ?>
        <a href="/staff/logout" class="btn btn-sm btn-outline-danger" id="btnLogoutStaff"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container-fluid px-3 px-md-4 py-4" style="max-width: 1200px">
      <div id="flashAreaStaff"></div>

      <div class="mb-4">
        <h1 class="h3 mb-0 fw-bold">Scan QR Check-in</h1>
        <p class="text-muted small mb-0">Pindai QR e-tiket pemesan atau ketik kode tiket, lalu cocokkan KTP sebelum konfirmasi.</p>
      </div>

      <!-- Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card stat-card h-100">
            <div class="card-body">
              <div class="stat-label">Check-in Hari Ini</div>
              <div class="stat-value" id="staffCheckinCount"><?= (int) $todayCount ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scanner & Hasil Terakhir -->
      <div class="row g-3 mb-4">
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
              <h2 class="h6 mb-0 fw-bold text-success" style="color: #0B5D3B !important">
                <i class="bx bx-qr-scan me-2 fs-5 align-middle"></i>Pindai QR E-Tiket
              </h2>
            </div>
            <div class="card-body p-3 p-md-4">
              <div class="scan-box text-center">
                <div id="qrPlaceholder">
                  <i class="bx bx-qr-scan" style="font-size: 4rem; color: #0B5D3B"></i>
                  <p class="mt-2 mb-3 text-muted">Arahkan kamera ke QR e-tiket pemesan</p>
                </div>
                <div id="qrFrame" class="qr-frame d-none">
                  <div id="reader"></div>
                  <span class="qr-corner tl" aria-hidden="true"></span>
                  <span class="qr-corner tr" aria-hidden="true"></span>
                  <span class="qr-corner bl" aria-hidden="true"></span>
                  <span class="qr-corner br" aria-hidden="true"></span>
                  <div class="qr-scanline" aria-hidden="true"></div>
                </div>
                <p id="qrStatus" class="small text-success fw-semibold mt-3 mb-0 d-none">
                  <i class="bi bi-camera-video me-1"></i>Kamera aktif - arahkan ke QR e-tiket pemesan.
                </p>
                <div id="httpSecurityNotice" class="alert alert-info py-2 px-3 small text-start mt-3 mb-0 d-none" style="font-size: 0.82rem">
                  <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                  <strong>Akses HP di Jaringan Lokal (HTTP):</strong> Jika streaming kamera tidak muncul karena kebijakan izin browser HP, gunakan tombol <strong>Gunakan Kamera</strong> di bawah untuk membuka kamera ponsel secara langsung.
                </div>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2">
                  <button type="button" class="btn btn-primary px-3" id="btnKamera">
                    <i class="bi bi-camera-video me-1"></i>Scan QR
                  </button>
                  <button type="button" class="btn btn-outline-danger d-none px-3" id="btnStopKamera">
                    <i class="bi bi-camera-video-off me-1"></i>Matikan Kamera
                  </button>
                  <button type="button" class="btn btn-outline-success px-3" id="btnFotoQr">
                    <i class="bi bi-camera me-1"></i>Gunakan Kamera
                  </button>
                  <input type="file" id="qrFileInput" accept="image/*" capture="environment" class="d-none" />
                </div>
                <p class="small text-muted mt-3 mb-0" style="font-size: 0.8rem">
                  Mendukung kamera belakang HP (live stream) serta pemindaian instan via foto / tangkapan layar e-tiket pemesan.
                </p>
              </div>

              <!-- Manual Code Input Fallback -->
              <form id="formKodeTiket" class="mt-4">
                <label for="inputKodeTiket" class="form-label fw-semibold">atau masukkan kode tiket secara manual</label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg text-uppercase" id="inputKodeTiket" placeholder="mis. TKL-A1B2C3" autocomplete="off" maxlength="20" style="letter-spacing: 0.05em" />
                  <button type="submit" class="btn btn-primary btn-lg" id="btnPeriksaKode">
                    <i class="bi bi-search me-1"></i>Periksa Kode
                  </button>
                </div>
                <small class="text-muted d-block mt-2">
                  Kode demo: <code>TKL-A1B2C3</code> (VIP) · <code>TKL-D4E5F6</code> (Reguler) · <code>TKL-USED99</code> (sudah dipakai)
                </small>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
              <h2 class="h6 mb-0 fw-bold text-success" style="color: #0B5D3B !important">
                <i class="bi bi-patch-check me-2 align-middle"></i>Hasil Terakhir
              </h2>
            </div>
            <div class="card-body p-3 p-md-4" id="hasilTerakhir">
              <div class="text-center py-5 text-muted">
                <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada tiket yang dipindai pada sesi ini.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Riwayat Check-in -->
      <div class="card border-0 shadow-sm" id="staffRiwayatCard">
        <div class="card-header bg-white border-bottom py-3">
          <h2 class="h6 mb-0 fw-bold text-success" style="color: #0B5D3B !important">
            <i class="bx bx-history me-2 fs-5 align-middle"></i>Riwayat Check-in
          </h2>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 90px">Waktu</th>
                <th style="width: 140px">Kode Tiket</th>
                <th>Nama Pemesan</th>
                <th>Event</th>
                <th style="width: 120px">Status</th>
              </tr>
            </thead>
            <tbody id="riwayatBody">
              <?php if (empty($recentLogs)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat check-in.</td></tr>
              <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('H:i', strtotime($log['waktu'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><code><?= htmlspecialchars($log['kode_dipindai'] ?? '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= htmlspecialchars($log['nama_pemesan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($log['nama_event'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if ($log['hasil'] === 'Check-in'): ?>
                        <span class="badge bg-success">Check-in</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Ditolak</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Modal Konfirmasi Check-in (Pop-up SOP KTP & Penolakan) -->
    <div class="modal fade" id="modalCheckin" tabindex="-1" aria-labelledby="modalCheckinLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-bottom py-3">
            <h5 class="modal-title fw-bold" id="modalCheckinLabel">Konfirmasi Check-in</h5>
            <span class="badge bg-success ms-2" id="modalCheckinBadge">Tiket Valid</span>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <!-- Varian VALID -->
            <div id="modalCheckinValid">
              <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.05em">Nama Pemesan</small>
              <div class="fw-bold text-success mb-3" style="font-size: 1.6rem; line-height: 1.2; color: #0B5D3B !important" id="mNama">-</div>
              <table class="table table-sm align-middle mt-2 mb-3">
                <tbody>
                  <tr>
                    <th style="width: 150px" class="text-muted">Kode Tiket</th>
                    <td><code id="mKode" class="fs-6">-</code></td>
                  </tr>
                  <tr>
                    <th class="text-muted">Event</th>
                    <td id="mEvent" class="fw-semibold">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">Kategori Tiket</th>
                    <td id="mKategori"><span class="badge bg-primary">-</span></td>
                  </tr>
                  <tr>
                    <th class="text-muted">Venue</th>
                    <td id="mVenue">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">Tanggal & Jam</th>
                    <td id="mTanggal">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">Identitas</th>
                    <td id="mIdentitas">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">Email</th>
                    <td id="mEmail">-</td>
                  </tr>
                </tbody>
              </table>
              <div class="alert alert-warning mb-0 border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-start">
                  <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2 mt-1"></i>
                  <div>
                    <strong>SOP Pemeriksaan KTP:</strong><br />
                    Minta pemesan menunjukkan KTP / kartu identitas. Nama di KTP <u>harus sama persis</u> dengan nama pemesan di atas sebelum menekan Konfirmasi Check-in.
                  </div>
                </div>
              </div>
            </div>

            <!-- Varian SUDAH DIPAKAI (DITOLAK) -->
            <div id="modalCheckinUsed" class="d-none">
              <small class="text-muted text-uppercase fw-semibold">Nama Pemesan</small>
              <div class="fw-bold text-danger mb-3" style="font-size: 1.4rem" id="mNamaUsed">-</div>
              <div class="alert alert-danger mb-0" role="alert">
                <i class="bi bi-x-octagon-fill me-1 fs-5 align-middle"></i>
                Tiket ini sudah dipakai pada <strong id="mUsedAt">-</strong>. Pemesan diarahkan ke petugas / Admin sesuai SOP.
              </div>
            </div>
          </div>
          <div class="modal-footer border-top bg-light">
            <button type="button" class="btn btn-outline-danger" id="btnTolakKtp">
              <i class="bi bi-x-circle me-1"></i>KTP Tidak Cocok (Batal)
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnBatalCheckin" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-success px-4" id="btnKonfirmasiCheckin" style="background-color: #0B5D3B; border-color: #0B5D3B">
              <i class="bi bi-check-lg me-1"></i>Konfirmasi Check-in
            </button>
            <button type="button" class="btn btn-secondary d-none px-4" id="btnTutupCheckin" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Kode Tidak Ditemukan -->
    <div class="modal fade" id="modalNotFound" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-bottom py-3 bg-danger text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Tiket Tidak Terdaftar</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <i class="bi bi-question-circle text-danger mb-3 d-block" style="font-size: 3.5rem"></i>
            <h3 class="h5 fw-bold mb-2">Kode Tiket Tidak Dikenal</h3>
            <p class="text-muted mb-3" id="notFoundMessage">Kode tiket yang dimasukkan tidak terdaftar di sistem Ticket Land.</p>
            <div class="alert alert-warning text-start mb-0 small">
              <i class="bi bi-info-circle me-1"></i><strong>Instruksi Petugas:</strong> Mohon arahkan pengunjung ke meja Customer Service / Admin untuk verifikasi bukti pembelian asli.
            </div>
          </div>
          <div class="modal-footer border-top bg-light">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pass bootstrap data -->
    <script>
      window.TICKET_LAND_STAFF = {
        csrfToken: <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        staff: <?= json_encode($staff, JSON_HEX_TAG | JSON_HEX_AMP) ?>
      };
    </script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/html5-qrcode.min.js"></script>
    <script src="/assets/js/qrgen.js"></script>
    <script src="/assets/js/staff.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/staff.js') ?: time() ?>"></script>
  </body>
</html>
