(function () {
  var boot = window.TICKET_LAND_STAFF || {};
  var csrfToken = boot.csrfToken || '';
  var staff = boot.staff || {};

  var flashArea = document.getElementById('flashAreaStaff');
  var inputKode = document.getElementById('inputKodeTiket');
  var formKode = document.getElementById('formKodeTiket');
  var hasilTerakhirEl = document.getElementById('hasilTerakhir');
  var statCountEl = document.getElementById('staffCheckinCount');
  var riwayatBodyEl = document.getElementById('riwayatBody');

  var modalCheckinEl = document.getElementById('modalCheckin');
  var modalCheckin = (window.bootstrap && modalCheckinEl) ? new bootstrap.Modal(modalCheckinEl) : null;
  var modalNotFoundEl = document.getElementById('modalNotFound');
  var modalNotFound = (window.bootstrap && modalNotFoundEl) ? new bootstrap.Modal(modalNotFoundEl) : null;

  var tiketAktif = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showFlashStaff(htmlMsg, type) {
    if (!flashArea) return;
    flashArea.innerHTML =
      '<div class="alert alert-' + (type || 'success') + ' alert-dismissible fade show" role="alert">'
      + htmlMsg
      + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  }

  function apiPost(url, data) {
    var payload = Object.assign({}, data, { csrf_token: csrfToken });
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(payload)
    }).then(function (res) {
      return res.json().then(function (json) {
        return { status: res.status, body: json };
      });
    });
  }

  /* Set modal variant between Valid and Already Used */
  function setModalVariant(valid) {
    document.getElementById('modalCheckinValid').classList.toggle('d-none', !valid);
    document.getElementById('modalCheckinUsed').classList.toggle('d-none', valid);
    document.getElementById('btnBatalCheckin').classList.toggle('d-none', !valid);
    document.getElementById('btnTolakKtp').classList.toggle('d-none', !valid);
    document.getElementById('btnKonfirmasiCheckin').classList.toggle('d-none', !valid);
    document.getElementById('btnTutupCheckin').classList.toggle('d-none', valid);

    var badge = document.getElementById('modalCheckinBadge');
    if (badge) {
      badge.textContent = valid ? 'Tiket Valid' : 'Sudah Check-in';
      badge.className = 'badge ms-2 ' + (valid ? 'bg-success' : 'bg-danger');
    }
  }

  /* Periksa Kode Tiket */
  function periksaKodeTiket(rawCode) {
    var code = String(rawCode || '').trim().toUpperCase();
    if (!code) {
      showFlashStaff('<i class="bi bi-exclamation-triangle me-1"></i>Masukkan kode tiket terlebih dahulu.', 'warning');
      return;
    }

    var btnPeriksa = document.getElementById('btnPeriksaKode');
    if (btnPeriksa) btnPeriksa.disabled = true;

    apiPost('/api/staff/check-ticket', { kode_tiket: code }).then(function (res) {
      if (btnPeriksa) btnPeriksa.disabled = false;

      // Kasus 1: Kode tidak ditemukan (AC-15)
      if (res.status === 404 || (res.body && res.body.status === 'NOT_FOUND')) {
        document.getElementById('notFoundMessage').textContent =
          (res.body && res.body.message) || ('Kode tiket ' + code + ' tidak terdaftar di sistem Ticket Land.');
        if (modalNotFound) modalNotFound.show();
        updateHasilTerakhir(null, code, 'NOT_FOUND');
        prependRiwayat(code, '-', '-', 'Ditolak');
        return;
      }

      // Kasus 2: Sudah dipakai (AC-14)
      if (res.body && res.body.status === 'ALREADY_USED') {
        var usedData = res.body.data || {};
        document.getElementById('mNamaUsed').textContent = usedData.nama || '-';
        document.getElementById('mUsedAt').textContent = (usedData.used_at || '-') + ' WIB';
        setModalVariant(false);
        if (modalCheckin) modalCheckin.show();
        updateHasilTerakhir(usedData, code, 'ALREADY_USED');
        prependRiwayat(code, usedData.nama || '-', usedData.event || '-', 'Ditolak');
        return;
      }

      // Kasus 2b: Tiket Dibatalkan (Fase 4)
      if (res.body && res.body.status === 'CANCELLED') {
        var cxlData = res.body.data || {};
        var cancelMsg = res.body.message || 'Tiket sudah dibatalkan. E-tiket tidak berlaku lagi untuk check-in.';
        showFlashStaff('<i class="bi bi-x-circle-fill me-1"></i><strong>DITOLAK:</strong> ' + esc(cancelMsg), 'danger');
        updateHasilTerakhir(cxlData, code, 'CANCELLED');
        prependRiwayat(code, cxlData.nama || '-', cxlData.event || '-', 'Dibatalkan');
        return;
      }

      // Kasus 3: Tiket Valid (AC-13)
      if (res.body && res.body.ok && res.body.status === 'VALID') {
        var t = res.body.data || {};
        tiketAktif = t;

        document.getElementById('mNama').textContent = t.nama || '-';
        document.getElementById('mKode').textContent = t.kode || code;
        document.getElementById('mEvent').textContent = t.event || '-';
        document.getElementById('mKategori').innerHTML = '<span class="badge bg-primary">' + esc(t.kategori || '-') + '</span>';
        document.getElementById('mVenue').textContent = t.venue || '-';
        document.getElementById('mTanggal').textContent = t.tanggal || '-';
        document.getElementById('mIdentitas').textContent = t.identitas || '-';
        document.getElementById('mEmail').textContent = t.email || '-';

        setModalVariant(true);
        if (modalCheckin) modalCheckin.show();
        return;
      }

      // Kasus error umum
      showFlashStaff('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal memeriksa tiket.'), 'danger');
    }).catch(function () {
      if (btnPeriksa) btnPeriksa.disabled = false;
      showFlashStaff('<i class="bi bi-x-octagon me-1"></i>Terjadi kesalahan saat memeriksa tiket.', 'danger');
    });
  }

  if (formKode) {
    formKode.addEventListener('submit', function (e) {
      e.preventDefault();
      periksaKodeTiket(inputKode ? inputKode.value : '');
    });
  }

  /* Konfirmasi Check-in (Tombol Hijau) */
  document.getElementById('btnKonfirmasiCheckin').addEventListener('click', function () {
    if (!tiketAktif) return;
    var btn = this;
    btn.disabled = true;

    apiPost('/api/staff/confirm-checkin', {
      ticket_id: tiketAktif.id,
      kode_tiket: tiketAktif.kode,
      nama_pemesan: tiketAktif.nama,
      nama_event: tiketAktif.event
    }).then(function (res) {
      btn.disabled = false;
      if (!res.body || !res.body.ok) {
        showFlashStaff('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal konfirmasi check-in.'), 'danger');
        if (modalCheckin) modalCheckin.hide();
        return;
      }

      if (modalCheckin) modalCheckin.hide();

      if (statCountEl && res.body.todayCount != null) {
        statCountEl.textContent = Number(res.body.todayCount).toLocaleString('id-ID');
      }

      updateHasilTerakhir(tiketAktif, tiketAktif.kode, 'SUCCESS');
      prependRiwayat(tiketAktif.kode, tiketAktif.nama, tiketAktif.event, 'Check-in');

      showFlashStaff('<i class="bi bi-check-circle-fill me-1"></i>Check-in berhasil untuk <strong>' + esc(tiketAktif.nama) + '</strong> (' + esc(tiketAktif.kode) + ').');

      tiketAktif = null;
      if (inputKode) inputKode.value = '';
      window.scrollTo(0, 0);
    }).catch(function () {
      btn.disabled = false;
      showFlashStaff('<i class="bi bi-x-octagon me-1"></i>Koneksi gagal saat konfirmasi check-in.', 'danger');
    });
  });

  /* Penolakan KTP Tidak Cocok (Tombol Merah Batal) */
  document.getElementById('btnTolakKtp').addEventListener('click', function () {
    if (!tiketAktif) return;
    var btn = this;
    btn.disabled = true;

    apiPost('/api/staff/reject-ktp', {
      ticket_id: tiketAktif.id,
      kode_tiket: tiketAktif.kode,
      nama_pemesan: tiketAktif.nama,
      nama_event: tiketAktif.event
    }).then(function (res) {
      btn.disabled = false;
      if (modalCheckin) modalCheckin.hide();
      updateHasilTerakhir(tiketAktif, tiketAktif.kode, 'KTP_MISMATCH');
      prependRiwayat(tiketAktif.kode, tiketAktif.nama, tiketAktif.event, 'Ditolak');
      showFlashStaff('<i class="bi bi-exclamation-triangle-fill me-1"></i>Check-in dibatalkan karena nama di KTP tidak cocok. Tiket tetap belum dipakai.', 'warning');
      tiketAktif = null;
      if (inputKode) inputKode.value = '';
      window.scrollTo(0, 0);
    }).catch(function () {
      btn.disabled = false;
      if (modalCheckin) modalCheckin.hide();
      tiketAktif = null;
    });
  });

  /* Pembatalan Biasa */
  document.getElementById('btnBatalCheckin').addEventListener('click', function () {
    tiketAktif = null;
    showFlashStaff('<i class="bi bi-info-circle me-1"></i>Pemeriksaan tiket dibatalkan.', 'secondary');
  });

  /* Perbarui Kartu Hasil Terakhir */
  function updateHasilTerakhir(data, code, status) {
    if (!hasilTerakhirEl) return;
    var d = new Date();
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var jam = pad(d.getHours()) + ':' + pad(d.getMinutes());

    if (status === 'SUCCESS') {
      hasilTerakhirEl.innerHTML =
        '<div class="text-center py-2">'
        + '<i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>'
        + '<h3 class="h5 fw-bold text-success mb-1">' + esc(data.nama) + '</h3>'
        + '<p class="text-muted small mb-3"><code>' + esc(code) + '</code> &middot; ' + esc(data.event) + '</p>'
        + '<div class="badge bg-success py-2 px-3 fs-6 mb-3"><i class="bi bi-check-lg me-1"></i>Sudah Check-in</div>'
        + '<p class="small text-muted mb-0">Dikonfirmasi pada pukul <strong>' + jam + ' WIB</strong></p>'
        + '</div>';
    } else if (status === 'ALREADY_USED') {
      hasilTerakhirEl.innerHTML =
        '<div class="text-center py-2">'
        + '<i class="bi bi-x-octagon-fill text-danger fs-1 d-block mb-2"></i>'
        + '<h3 class="h5 fw-bold text-danger mb-1">' + esc(data.nama || 'Pemesan') + '</h3>'
        + '<p class="text-muted small mb-3"><code>' + esc(code) + '</code> &middot; ' + esc(data.event || '-') + '</p>'
        + '<div class="badge bg-danger py-2 px-3 fs-6 mb-3"><i class="bi bi-x-lg me-1"></i>Sudah Dipakai Sebelumnya</div>'
        + '<p class="small text-danger mb-0">Tiket ini telah dipakai pada ' + esc(data.used_at || '-') + '</p>'
        + '</div>';
    } else if (status === 'KTP_MISMATCH') {
      hasilTerakhirEl.innerHTML =
        '<div class="text-center py-2">'
        + '<i class="bi bi-exclamation-triangle-fill text-warning fs-1 d-block mb-2"></i>'
        + '<h3 class="h5 fw-bold text-dark mb-1">' + esc(data.nama) + '</h3>'
        + '<p class="text-muted small mb-3"><code>' + esc(code) + '</code> &middot; ' + esc(data.event) + '</p>'
        + '<div class="badge bg-warning text-dark py-2 px-3 fs-6 mb-3">Dibatalkan: KTP Tidak Cocok</div>'
        + '<p class="small text-muted mb-0">Tiket tetap belum digunakan.</p>'
        + '</div>';
    } else if (status === 'CANCELLED') {
      hasilTerakhirEl.innerHTML =
        '<div class="text-center py-2">'
        + '<i class="bi bi-x-circle-fill text-danger fs-1 d-block mb-2"></i>'
        + '<h3 class="h5 fw-bold text-danger mb-1">' + esc(data.nama || 'Pemesan') + '</h3>'
        + '<p class="text-muted small mb-3"><code>' + esc(code) + '</code> &middot; ' + esc(data.event || '-') + '</p>'
        + '<div class="badge bg-danger py-2 px-3 fs-6 mb-3"><i class="bi bi-x-circle me-1"></i>Tiket Sudah Dibatalkan</div>'
        + '<p class="small text-danger mb-0">Pesanan tiket ini telah resmi dibatalkan. Akses check-in ditolak.</p>'
        + '</div>';
    } else if (status === 'NOT_FOUND') {
      hasilTerakhirEl.innerHTML =
        '<div class="text-center py-2">'
        + '<i class="bi bi-question-circle-fill text-secondary fs-1 d-block mb-2"></i>'
        + '<h3 class="h5 fw-bold text-dark mb-1">Kode Tidak Terdaftar</h3>'
        + '<p class="text-muted small mb-3"><code>' + esc(code) + '</code></p>'
        + '<div class="badge bg-danger py-2 px-3 fs-6 mb-3">Kode Tidak Dikenal</div>'
        + '<p class="small text-muted mb-0">Pengunjung diarahkan ke Admin.</p>'
        + '</div>';
    }
  }

  /* Sisipkan Riwayat Check-in di Tabel */
  function prependRiwayat(code, nama, event, statusBadge) {
    if (!riwayatBodyEl) return;
    var d = new Date();
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var jam = pad(d.getHours()) + ':' + pad(d.getMinutes());

    var isCheckin = (statusBadge === 'Check-in');
    var badgeHtml = isCheckin
      ? '<span class="badge bg-success">Check-in</span>'
      : '<span class="badge bg-danger">Ditolak</span>';

    var tr = document.createElement('tr');
    tr.className = 'table-success-subtle';
    tr.innerHTML =
      '<td>' + jam + '</td>'
      + '<td><code>' + esc(code) + '</code></td>'
      + '<td>' + esc(nama) + '</td>'
      + '<td>' + esc(event) + '</td>'
      + '<td>' + badgeHtml + '</td>';

    if (riwayatBodyEl.firstChild) {
      riwayatBodyEl.insertBefore(tr, riwayatBodyEl.firstChild);
    } else {
      riwayatBodyEl.appendChild(tr);
    }
  }

  /* html5-qrcode Camera Handling & Mobile Fallbacks */
  var html5QrCode = null;
  var isScanning = false;
  var availableCameras = [];
  var currentCameraIndex = 0;

  var btnKamera = document.getElementById('btnKamera');
  var btnStopKamera = document.getElementById('btnStopKamera');
  var btnFotoQr = document.getElementById('btnFotoQr');
  var btnGantiKamera = document.getElementById('btnGantiKamera');
  var qrFileInput = document.getElementById('qrFileInput');
  var qrFrame = document.getElementById('qrFrame');
  var qrPlaceholder = document.getElementById('qrPlaceholder');
  var qrStatus = document.getElementById('qrStatus');
  var httpNotice = document.getElementById('httpSecurityNotice');

  // Deteksi lingkungan aman (HTTPS atau localhost)
  var isSecure = Boolean(window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1');
  if (!isSecure && httpNotice) {
    httpNotice.classList.remove('d-none');
  }

  function pauseScannerIfNeeded() {
    if (html5QrCode && isScanning) {
      try {
        html5QrCode.pause();
      } catch (e) {
        // Abaikan jika tidak mendukung pause
      }
    }
  }

  function resumeScannerIfNeeded() {
    if (html5QrCode && isScanning) {
      try {
        html5QrCode.resume();
      } catch (e) {
        // Abaikan jika tidak mendukung resume
      }
    }
  }

  if (modalCheckinEl) {
    modalCheckinEl.addEventListener('hidden.bs.modal', function () {
      resumeScannerIfNeeded();
    });
  }
  if (modalNotFoundEl) {
    modalNotFoundEl.addEventListener('hidden.bs.modal', function () {
      resumeScannerIfNeeded();
    });
  }

  /* 1. Ambil Foto QR / Unggah Gambar (Bekerja 100% di HP baik HTTP maupun HTTPS) */
  if (btnFotoQr && qrFileInput) {
    btnFotoQr.addEventListener('click', function () {
      qrFileInput.click();
    });

    qrFileInput.addEventListener('change', function (e) {
      var files = e.target.files;
      if (!files || files.length === 0) return;
      var file = files[0];

      showFlashStaff('<i class="bi bi-hourglass-split me-1"></i>Menganalisis foto QR tiket...', 'info');

      if (!html5QrCode) {
        try {
          html5QrCode = new Html5Qrcode('reader');
        } catch (err) {
          // Abaikan jika sudah diinisialisasi
        }
      }

      if (!html5QrCode) {
        showFlashStaff('<i class="bi bi-exclamation-triangle me-1"></i>Pemindai belum siap. Muat ulang halaman.', 'warning');
        return;
      }

      html5QrCode.scanFile(file, false)
        .then(function (decodedText) {
          var raw = String(decodedText || '').trim();
          var m = raw.match(/TKL-[A-Z0-9]+/i);
          var kode = m ? m[0].toUpperCase() : raw.toUpperCase();
          if (inputKode) inputKode.value = kode;
          showFlashStaff('<i class="bi bi-check-circle me-1"></i>QR terbaca: <strong>' + esc(kode) + '</strong>', 'success');
          periksaKodeTiket(kode);
        })
        .catch(function () {
          showFlashStaff('<i class="bi bi-exclamation-triangle-fill me-1"></i>Kode QR tidak terdeteksi pada foto. Pastikan foto QR tegak, fokus, dan tidak buram, lalu coba lagi.', 'warning');
        })
        .finally(function () {
          qrFileInput.value = '';
        });
    });
  }

  /* 2. Streaming Kamera Langsung (Scan QR Live Viewfinder) */
  function startKamera() {
    if (!window.Html5Qrcode) {
      showFlashStaff('<i class="bi bi-x-octagon me-1"></i>Library pemindai QR belum termuat. Muat ulang halaman.', 'warning');
      return;
    }

    var hasMediaDevices = Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    // Jika browser memblokir navigator.mediaDevices (mis. akses HTTP IP non-HTTPS tanpa flag secure origin),
    // tampilkan peringatan jelas tanpa mengalihkan otomatis ke mode input file foto.
    if (!hasMediaDevices) {
      showFlashStaff(
        '<i class="bi bi-shield-exclamation me-1"></i><strong>Live Scan Memerlukan HTTPS atau Localhost:</strong> Browser memblokir akses live camera pada koneksi HTTP IP biasa. Buka halaman ini melalui <strong>HTTPS</strong> (atau <code>localhost</code>) untuk Live Scan, atau tekan tombol <strong>Gunakan Kamera</strong> di samping untuk memindai dari foto.',
        'warning'
      );
      return;
    }

    if (qrPlaceholder) qrPlaceholder.classList.add('d-none');
    if (qrFrame) qrFrame.classList.remove('d-none');
    if (qrStatus) qrStatus.classList.remove('d-none');
    if (btnKamera) btnKamera.classList.add('d-none');
    if (btnStopKamera) btnStopKamera.classList.remove('d-none');

    if (!html5QrCode) {
      try {
        var qrFormats = (typeof Html5QrcodeSupportedFormats !== 'undefined')
          ? [ Html5QrcodeSupportedFormats.QR_CODE ]
          : [ 0 ];
        html5QrCode = new Html5Qrcode('reader', {
          formatsToSupport: qrFormats,
          verbose: false
        });
      } catch (e) {
        // Abaikan
      }
    }

    var qrBoxFn = function (viewfinderWidth, viewfinderHeight) {
      var edge = Math.min(viewfinderWidth, viewfinderHeight);
      var size = Math.max(160, Math.floor(edge * 0.85));
      return { width: size, height: size };
    };

    var config = {
      fps: 30,
      qrbox: qrBoxFn,
      formatsToSupport: (typeof Html5QrcodeSupportedFormats !== 'undefined')
        ? [ Html5QrcodeSupportedFormats.QR_CODE ]
        : [ 0 ],
      experimentalFeatures: {
        useBarCodeDetectorIfSupported: true
      }
    };

    var onScanSuccess = function (decodedText) {
      if (decodedText) {
        var raw = String(decodedText).trim();
        var m = raw.match(/TKL-[A-Z0-9]+/i);
        var kode = m ? m[0].toUpperCase() : raw.toUpperCase();
        if (inputKode) inputKode.value = kode;
        pauseScannerIfNeeded();
        periksaKodeTiket(kode);
      }
    };

    var onScanError = function () {
      // Abaikan frame yang belum menemukan QR
    };

    // Alur fallback kamera: Deteksi ID Kamera -> Environment (Belakang) -> User (Depan) -> General
    Html5Qrcode.getCameras().then(function (cameras) {
      if (cameras && cameras.length > 0) {
        availableCameras = cameras;
        var rearIdx = 0;
        for (var i = 0; i < cameras.length; i++) {
          var lbl = (cameras[i].label || '').toLowerCase();
          if (lbl.indexOf('back') !== -1 || lbl.indexOf('rear') !== -1 || lbl.indexOf('environment') !== -1) {
            rearIdx = i;
            break;
          }
        }
        currentCameraIndex = rearIdx;
        if (availableCameras.length > 1 && btnGantiKamera) {
          btnGantiKamera.classList.remove('d-none');
        }

        return html5QrCode.start(
          availableCameras[currentCameraIndex].id,
          config,
          onScanSuccess,
          onScanError
        );
      }

      return html5QrCode.start(
        { facingMode: 'environment' },
        config,
        onScanSuccess,
        onScanError
      );
    }).then(function () {
      isScanning = true;
    }).catch(function (err) {
      console.warn('Percobaan kamera utama gagal, mencoba fallback facingMode:', err);
      if (html5QrCode) {
        return html5QrCode.start(
          { facingMode: 'user' },
          config,
          onScanSuccess,
          onScanError
        ).then(function () {
          isScanning = true;
        });
      }
      throw err;
    }).catch(function (finalErr) {
      var errMsg = (finalErr && finalErr.message) ? finalErr.message : String(finalErr || '');
      showFlashStaff(
        '<i class="bi bi-camera-video-off me-1"></i>Streaming kamera tidak dapat dimulai (' + esc(errMsg)
        + '). Silakan gunakan tombol <strong>Gunakan Kamera</strong> di samping.',
        'warning'
      );
      stopKamera();
    });
  }

  function stopKamera() {
    if (html5QrCode && isScanning) {
      html5QrCode.stop().then(function () {
        isScanning = false;
        try { html5QrCode.clear(); } catch (e) {}
      }).catch(function () {
        isScanning = false;
      });
    }

    if (qrPlaceholder) qrPlaceholder.classList.remove('d-none');
    if (qrFrame) qrFrame.classList.add('d-none');
    if (qrStatus) qrStatus.classList.add('d-none');
    if (btnKamera) btnKamera.classList.remove('d-none');
    if (btnStopKamera) btnStopKamera.classList.add('d-none');
    if (btnGantiKamera) btnGantiKamera.classList.add('d-none');
  }

  // Tombol Ganti Kamera Depan/Belakang
  if (btnGantiKamera) {
    btnGantiKamera.addEventListener('click', function () {
      if (!html5QrCode || !isScanning || availableCameras.length < 2) return;
      currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
      var nextCam = availableCameras[currentCameraIndex];

      html5QrCode.stop().then(function () {
        isScanning = false;
        return html5QrCode.start(
          nextCam.id,
          {
            fps: 30,
            qrbox: function (w, h) {
              var m = Math.min(w, h);
              return { width: Math.floor(m * 0.85), height: Math.floor(m * 0.85) };
            },
            formatsToSupport: (typeof Html5QrcodeSupportedFormats !== 'undefined')
              ? [ Html5QrcodeSupportedFormats.QR_CODE ]
              : [ 0 ],
            experimentalFeatures: {
              useBarCodeDetectorIfSupported: true
            }
          },
          function (decodedText) {
            var m = String(decodedText).trim().match(/TKL-[A-Z0-9]+/i);
            var kode = m ? m[0].toUpperCase() : String(decodedText).trim().toUpperCase();
            if (inputKode) inputKode.value = kode;
            pauseScannerIfNeeded();
            periksaKodeTiket(kode);
          },
          function () {}
        );
      }).then(function () {
        isScanning = true;
        showFlashStaff('<i class="bi bi-arrow-repeat me-1"></i>Beralih ke: ' + esc(nextCam.label || 'Kamera ' + (currentCameraIndex + 1)), 'info');
      }).catch(function (err) {
        console.warn('Gagal beralih kamera:', err);
      });
    });
  }

  if (btnKamera) btnKamera.addEventListener('click', startKamera);
  if (btnStopKamera) btnStopKamera.addEventListener('click', stopKamera);

  var btnLogoutStaff = document.getElementById('btnLogoutStaff');
  if (btnLogoutStaff) {
    btnLogoutStaff.addEventListener('click', function (e) {
      e.preventDefault();
      window.location.href = '/staff/logout';
    });
  }

  // Auto-check query parameter for automated testing / screenshots
  var qCheck = new URLSearchParams(window.location.search).get('check');
  if (qCheck) {
    if (inputKode) inputKode.value = qCheck;
    setTimeout(function () {
      periksaKodeTiket(qCheck);
    }, 300);
  }
})();
