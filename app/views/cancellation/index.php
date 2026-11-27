<?php
declare(strict_types=1);

$prefill = $prefill ?? [];
$isFromLink = !empty($prefill['is_from_link']);
$events = $events ?? [];
$flashError = $flashError ?? null;
$flashSuccess = $flashSuccess ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Batalkan Pesanan - Ticket Land') ?></title>
  
  <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/boxicons.min.css">
  <link rel="stylesheet" href="/assets/css/plus-jakarta-sans.css">
  <link rel="stylesheet" href="/assets/css/poppins.css">
  
  <style>
    :root {
      --primary: #0B5D3B;
      --primary-hover: #08492e;
      --primary-light: #E2F2E9;
      --primary-border: #a7dfc0;
      --surface: #ffffff;
      --background: #f8fafc;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --danger: #dc2626;
      --danger-light: #fee2e2;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: var(--background);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .navbar-cxl {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 16px 0;
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--primary);
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 20px;
      letter-spacing: -0.5px;
    }

    .brand-badge {
      background: var(--primary-light);
      color: var(--primary);
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .page-header-box {
      background: linear-gradient(135deg, #0B5D3B 0%, #166534 100%);
      color: #ffffff;
      padding: 40px 0 60px 0;
      margin-bottom: -30px;
    }

    .card-custom {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
      padding: 32px;
      margin-bottom: 24px;
    }

    .form-label {
      font-weight: 600;
      font-size: 13px;
      color: #334155;
      margin-bottom: 6px;
    }

    .form-control, .form-select {
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(11, 93, 59, 0.15);
    }

    .form-control[readonly] {
      background-color: #f1f5f9;
      color: #475569;
      cursor: not-allowed;
      border-color: #e2e8f0;
    }

    .code-input {
      font-family: monospace;
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 4px;
      text-transform: uppercase;
      color: var(--primary);
      background-color: #f8fafc;
    }

    .btn-submit-cxl {
      background-color: var(--primary);
      border: none;
      color: #ffffff;
      font-weight: 700;
      font-size: 15px;
      padding: 14px 28px;
      border-radius: 8px;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .btn-submit-cxl:hover:not(:disabled) {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(11, 93, 59, 0.25);
    }

    .btn-submit-cxl:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    .step-item {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
    }

    .step-num {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 14px;
      flex-shrink: 0;
      border: 1.5px solid var(--primary-border);
    }

    .step-text h6 {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 4px;
      color: #1e293b;
    }

    .step-text p {
      font-size: 12.5px;
      color: var(--text-muted);
      margin: 0;
      line-height: 1.45;
    }

    .rule-badge {
      background: #fef3c7;
      color: #92400e;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 12px;
    }

    .rule-list {
      list-style: none;
      padding: 0;
      margin: 0;
      font-size: 13px;
    }

    .rule-list li {
      position: relative;
      padding-left: 20px;
      margin-bottom: 10px;
      color: #475569;
      line-height: 1.5;
    }

    .rule-list li::before {
      content: "•";
      position: absolute;
      left: 6px;
      color: var(--primary);
      font-weight: bold;
      font-size: 16px;
    }

    .success-card {
      text-align: center;
      max-width: 620px;
      margin: 40px auto;
      padding: 48px 36px;
    }

    .success-icon-circle {
      width: 76px;
      height: 76px;
      border-radius: 50%;
      background: var(--primary-light);
      color: var(--primary);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      margin-bottom: 20px;
      border: 3px solid var(--primary-border);
    }

    .ref-badge-box {
      background: #f8fafc;
      border: 2px dashed #cbd5e1;
      border-radius: 10px;
      padding: 16px;
      margin: 24px 0;
    }

    .footer-simple {
      margin-top: auto;
      border-top: 1px solid #e2e8f0;
      background: #ffffff;
      padding: 24px 0;
      font-size: 12px;
      color: var(--text-muted);
      text-align: center;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar-cxl">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="/" class="brand-logo">
        <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:var(--primary);"></span>
        <span>Ticket Land</span>
        <span class="brand-badge">Pusat Pembatalan</span>
      </a>
      <a href="/" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 600; border-radius: 6px;">
        <i class='bx bx-arrow-back'></i> Kembali ke Beranda
      </a>
    </div>
  </nav>

  <!-- HEADER HERO -->
  <header class="page-header-box">
    <div class="container text-center">
      <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;">Pusat Pembatalan Pesanan Tiket</h1>
      <p style="font-size: 15px; color: #E2F2E9; max-width: 580px; margin: 0 auto; opacity: 0.95;">
        Pengajuan pembatalan mandiri resmi dengan verifikasi kode unik faktur pembayaran Anda.
      </p>
    </div>
  </header>

  <!-- MAIN CONTAINER -->
  <main class="container my-5" style="position: relative; z-index: 2;">

    <?php if ($flashSuccess): ?>
      <!-- TAMPILAN SUKSES PEMBATALAN -->
      <div class="card-custom success-card">
        <div class="success-icon-circle">
          <i class='bx bx-check'></i>
        </div>
        <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
          Pengajuan Pembatalan Berhasil!
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
          Pesanan tiket Anda telah resmi dibatalkan. E-tiket dan QR Code terkait telah dinonaktifkan.
        </p>

        <div class="ref-badge-box">
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
            Nomor Referensi Pembatalan
          </div>
          <div style="font-size: 26px; font-weight: 800; color: var(--primary); font-family: monospace; letter-spacing: 2px; margin: 6px 0;">
            <?= htmlspecialchars($flashSuccess['cancellation_ref'] ?? '-') ?>
          </div>
          <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 12px; padding: 4px 12px; border-radius: 12px; font-weight: 700;">
            STATUS: DIBATALKAN
          </span>
        </div>

        <div class="text-start p-3 bg-light rounded-3 mb-4" style="font-size: 13px;">
          <table class="w-100">
            <tr>
              <td class="text-muted py-1" style="width: 140px;">Nomor Invoice</td>
              <td class="fw-bold py-1">: <?= htmlspecialchars($flashSuccess['nomor_invoice'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1">Nama Pemesan</td>
              <td class="fw-bold py-1">: <?= htmlspecialchars($flashSuccess['pemesan_nama'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1">Email Pemesan</td>
              <td class="fw-bold py-1">: <?= htmlspecialchars($flashSuccess['pemesan_email'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1">Event</td>
              <td class="fw-bold py-1">: <?= htmlspecialchars($flashSuccess['event_nama'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1">Kategori & Qty</td>
              <td class="fw-bold py-1">: <?= htmlspecialchars($flashSuccess['kategori_tiket_nama'] ?? '-') ?> (<?= (int) ($flashSuccess['jumlah'] ?? 0) ?> tiket)</td>
            </tr>
            <tr>
              <td class="text-muted py-1">Total Refund</td>
              <td class="fw-bold text-success py-1">: Rp<?= number_format((int) ($flashSuccess['total'] ?? 0), 0, ',', '.') ?></td>
            </tr>
            <tr>
              <td class="text-muted py-1">Waktu Pembatalan</td>
              <td class="py-1">: <?= htmlspecialchars($flashSuccess['cancelled_at'] ?? date('d F Y • H:i') . ' WIB') ?></td>
            </tr>
          </table>
        </div>

        <div class="alert alert-info d-flex align-items-center gap-2 text-start p-3 mb-4" style="font-size: 12.5px; border-radius: 8px;">
          <i class='bx bx-info-circle fs-4 text-primary flex-shrink-0'></i>
          <div>
            Email konfirmasi pembatalan resmi telah dikirim ke <strong><?= htmlspecialchars($flashSuccess['pemesan_email'] ?? '') ?></strong>.
            Proses pengembalian dana diproses dalam <strong>3–5 hari kerja operasional</strong>.
          </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
          <a href="/" class="btn btn-success px-4 py-2 fw-bold" style="background: var(--primary); border: none; border-radius: 8px;">
            Kembali ke Beranda
          </a>
        </div>
      </div>

    <?php else: ?>

      <!-- ALERT ERROR FLASH JIKA ADA -->
      <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 10px;">
          <i class='bx bx-error-circle fs-4'></i>
          <div>
            <strong>Gagal Memproses Pembatalan:</strong> <?= htmlspecialchars($flashError) ?>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- ALERT JS AJAX MESSAGE CONTAINER -->
      <div id="cxlAlertContainer"></div>

      <div class="row g-4">
        <!-- KOLOM KIRI: FORMULIR PEMBATALAN -->
        <div class="col-lg-7">
          <div class="card-custom">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h4 style="font-size: 18px; font-weight: 800; margin: 0; color: #0f172a;">Formulir Pembatalan Tiket</h4>
                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0;">
                  Masukkan rincian pesanan dan kode unik pembatalan dari email faktur Anda.
                </p>
              </div>
              <?php if ($isFromLink): ?>
                <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 11px; padding: 6px 10px; border-radius: 6px;">
                  <i class='bx bx-link-alt'></i> Link Terverifikasi
                </span>
              <?php endif; ?>
            </div>

            <form id="formCancellation" action="/batalkan" method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

              <!-- FIELD 1: KODE UNIK PEMBATALAN (6 KARAKTER) -->
              <div class="mb-3">
                <label for="cancelCodeInput" class="form-label d-flex justify-content-between align-items-center">
                  <span>Kode Unik Pembatalan <span class="text-danger">*</span></span>
                  <small class="text-muted">6 Karakter Rahasia dari Email</small>
                </label>
                <input
                  type="text"
                  class="form-control code-input"
                  id="cancelCodeInput"
                  name="cancel_code"
                  maxlength="6"
                  required
                  placeholder="MISAL: K7X9Q2"
                  value="<?= htmlspecialchars($prefill['cancel_code'] ?? '') ?>"
                  <?= $isFromLink && !empty($prefill['cancel_code']) ? 'readonly' : '' ?>
                >
                <div class="form-text" style="font-size: 11.5px;">
                  Kode ini tertera pada kotak pembatalan di bagian paling bawah faktur email Anda.
                </div>
              </div>

              <!-- FIELD 2: NOMOR INVOICE -->
              <div class="mb-3">
                <label for="invoiceInput" class="form-label">
                  Nomor Invoice / Faktur <span class="text-danger">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  id="invoiceInput"
                  name="nomor_invoice"
                  required
                  placeholder="Contoh: INV-20261125-xxxxxx"
                  value="<?= htmlspecialchars($prefill['nomor_invoice'] ?? '') ?>"
                  <?= $isFromLink && !empty($prefill['nomor_invoice']) ? 'readonly' : '' ?>
                >
              </div>

              <!-- FIELD 3: NAMA PEMESAN -->
              <div class="mb-3">
                <label for="namaInput" class="form-label">
                  Nama Lengkap Pemesan <span class="text-danger">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  id="namaInput"
                  name="pemesan_nama"
                  required
                  placeholder="Masukkan nama lengkap sesuai pesanan"
                  value="<?= htmlspecialchars($prefill['pemesan_nama'] ?? '') ?>"
                >
                <div class="form-text" style="font-size: 11.5px;">
                  Harus sama persis dengan nama pemesan tiket saat checkout.
                </div>
              </div>

              <!-- FIELD 4: EMAIL PEMESAN -->
              <div class="mb-3">
                <label for="emailInput" class="form-label">
                  Alamat Email Pemesan <span class="text-danger">*</span>
                </label>
                <input
                  type="email"
                  class="form-control"
                  id="emailInput"
                  name="pemesan_email"
                  required
                  placeholder="nama@email.com"
                  value="<?= htmlspecialchars($prefill['pemesan_email'] ?? '') ?>"
                >
                <div class="form-text" style="font-size: 11.5px;">
                  Email tujuan penerimaan faktur dan konfirmasi refund.
                </div>
              </div>

              <!-- FIELD 5: EVENT YANG DIPESAN -->
              <div class="mb-3">
                <label for="eventSelect" class="form-label">
                  Event yang Dipesan <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="eventSelect" name="event_id" required>
                  <option value="">-- Pilih Event yang Ingin Dibatalkan --</option>
                  <?php foreach ($events as $evt): ?>
                    <?php $selected = ((int) $evt['id'] === (int) ($prefill['event_id'] ?? 0)) ? 'selected' : ''; ?>
                    <option value="<?= (int) $evt['id'] ?>" <?= $selected ?>>
                      <?= htmlspecialchars($evt['nama']) ?> (<?= htmlspecialchars($evt['kota']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- FIELD 6: ALASAN PEMBATALAN -->
              <div class="mb-4">
                <label for="alasanInput" class="form-label">
                  Alasan Pembatalan <span class="text-muted fw-normal">(Opsional)</span>
                </label>
                <textarea
                  class="form-control"
                  id="alasanInput"
                  name="alasan"
                  rows="3"
                  placeholder="Jelaskan alasan pembatalan tiket Anda (misal: jadwal bentrok, kondisi darurat, dsb.)"
                ></textarea>
              </div>

              <!-- CHECKBOX PERSETUJUAN -->
              <div class="mb-4 form-check" style="font-size: 13px;">
                <input type="checkbox" class="form-check-input" id="checkAgree" required>
                <label class="form-check-label text-muted" for="checkAgree">
                  Saya memahami bahwa pembatalan ini bersifat <strong>permanen</strong>, tiket dan QR Code tidak dapat digunakan kembali untuk Gate Check-in, dan pengembalian dana akan diproses sesuai ketentuan (3–5 hari kerja).
                </label>
              </div>

              <!-- TOMBOL SUBMIT -->
              <button type="submit" id="btnSubmitCxl" class="btn-submit-cxl">
                <i class='bx bx-x-circle fs-5'></i>
                <span id="btnText">Batalkan Pesanan Sekarang</span>
              </button>
            </form>
          </div>
        </div>

        <!-- KOLOM KANAN: PANDUAN CARA & ATURAN PEMBATALAN -->
        <div class="col-lg-5">
          <!-- PANEL 1: CARA MEMBATALKAN TIKET -->
          <div class="card-custom mb-4">
            <h5 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
              <i class='bx bx-help-circle text-success fs-5'></i> Cara Membatalkan Tiket
            </h5>

            <div class="step-item">
              <div class="step-num">1</div>
              <div class="step-text">
                <h6>Buka Email Faktur Resmi</h6>
                <p>Buka pesan email faktur e-Billing yang dikirimkan Ticket Land sesaat setelah Anda berhasil membayar tiket.</p>
              </div>
            </div>

            <div class="step-item">
              <div class="step-num">2</div>
              <div class="step-text">
                <h6>Temukan Kotak Pembatalan</h6>
                <p>Gulir ke bagian bawah email faktur untuk menemukan kotak hijau <em>"Pusat Pembatalan Pesanan"</em>.</p>
              </div>
            </div>

            <div class="step-item">
              <div class="step-num">3</div>
              <div class="step-text">
                <h6>Salin Kode Unik 6 Karakter</h6>
                <p>Salin 6-karakter kode verifikasi rahasia Anda (contoh: <code>K7X9Q2</code>) atau klik langsung tombol di email.</p>
              </div>
            </div>

            <div class="step-item">
              <div class="step-num">4</div>
              <div class="step-text">
                <h6>Verifikasi Identitas Pemesan</h6>
                <p>Isi formulir dengan Nama Lengkap dan Email yang sesuai dengan faktur untuk otorisasi keamanan tiket Anda.</p>
              </div>
            </div>

            <div class="step-item" style="margin-bottom: 0;">
              <div class="step-num">5</div>
              <div class="step-text">
                <h6>Terima Bukti Nomor Referensi</h6>
                <p>Kirim formulir dan simpan kode referensi <code>CXL-xxxxxx</code> untuk pelacakan pengembalian dana.</p>
              </div>
            </div>
          </div>

          <!-- PANEL 2: ATURAN & KEBIJAKAN PEMBATALAN -->
          <div class="card-custom">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h5 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-shield-quarter text-success fs-5'></i> Aturan Pembatalan
              </h5>
              <span class="rule-badge">DRAF KEBIJAKAN</span>
            </div>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
              Ketentuan operasional pengembalian tiket platform Ticket Land:
            </p>

            <ul class="rule-list">
              <li>
                <strong>Batas Waktu Pengajuan:</strong> Pembatalan mandiri dapat diajukan maksimal <strong>H-3 (72 jam)</strong> sebelum jadwal pelaksanaan event.
              </li>
              <li>
                <strong>Penonaktifan Instan:</strong> Begitu pengajuan disetujui, QR Code e-tiket otomatis <strong>DIBATALKAN</strong> dan ditolak pada pemindai pintu masuk Staff.
              </li>
              <li>
                <strong>Proses Pengembalian Dana:</strong> Refund akan dikreditkan kembali ke metode pembayaran asal dalam waktu <strong>3–5 hari kerja operasional</strong>.
              </li>
              <li>
                <strong>Biaya Layanan:</strong> Biaya pemrosesan sistem platform bersifat non-refundable sesuai ketentuan penyelenggara event.
              </li>
              <li>
                <strong>Tiket Sudah Terpakai:</strong> Tiket yang telah berstatus <em>Sudah Check-in</em> di gate tidak dapat dibatalkan dalam kondisi apa pun.
              </li>
            </ul>
          </div>
        </div>
      </div>

    <?php endif; ?>

  </main>

  <!-- FOOTER -->
  <footer class="footer-simple">
    <div class="container">
      <div>&copy; <?= date('Y') ?> Ticket Land Indonesia. Hak Cipta Dilindungi Undang-Undang.</div>
      <div class="mt-1" style="font-size: 11px;">Platform Pemesanan &amp; Validasi Tiket Terintegrasi</div>
    </div>
  </footer>

  <script src="/assets/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('formCancellation');
      if (!form) return;

      const btnSubmit = document.getElementById('btnSubmitCxl');
      const btnText = document.getElementById('btnText');
      const alertContainer = document.getElementById('cxlAlertContainer');

      form.addEventListener('submit', function (e) {
        const agreeCheck = document.getElementById('checkAgree');
        if (!agreeCheck.checked) {
          e.preventDefault();
          showAlert('danger', 'Harap setujui pernyataan pembatalan tiket sebelum melanjutkan.');
          agreeCheck.focus();
          return;
        }

        // Tampilkan loading state
        btnSubmit.disabled = true;
        btnText.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Memverifikasi data & membatalkan...";
      });

      function showAlert(type, msg) {
        if (!alertContainer) return;
        alertContainer.innerHTML = `
          <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 10px;">
            <i class='bx bx-info-circle fs-4'></i>
            <div>${msg}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        `;
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  </script>
</body>
</html>
