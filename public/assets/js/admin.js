(function () {
  var boot = window.TICKET_LAND_ADMIN_BOOT || {};
  var csrfToken = boot.csrfToken || '';
  var currentAdmin = boot.admin || null;
  var state = boot.state || {};
  var initialPage = boot.initialPage || 'login';

  var pageIds = ['login', 'dashboard', 'items', 'item-detail', 'categories', 'transactions', 'reports', 'users', 'activity'];
  var adminPages = ['dashboard', 'items', 'item-detail', 'categories', 'transactions', 'reports', 'users', 'activity'];
  var appShell = document.getElementById('appShell');
  var sidebar = document.getElementById('sidebar');
  var flashArea = document.getElementById('flashArea');
  var loginAlert = document.getElementById('loginAlert');

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fmtRupiah(n) {
    return 'Rp' + Number(n || 0).toLocaleString('id-ID');
  }

  function fmtDateShort(dtStr) {
    if (!dtStr) return '-';
    var d = new Date(String(dtStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(dtStr);
    var bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
  }

  function fmtDateTime(dtStr) {
    if (!dtStr) return '-';
    var d = new Date(String(dtStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(dtStr);
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
      + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }

  function showFlash(htmlMsg, type) {
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

  function applyAdminProfile(user) {
    currentAdmin = user;
    if (!user) return;
    var nameEl = document.getElementById('profileName');
    var jobEl = document.getElementById('profileJob');
    var avaEl = document.getElementById('profileAvatar');
    if (nameEl) nameEl.textContent = user.nama || user.username;
    if (jobEl) jobEl.textContent = user.peran || 'admin';
    if (avaEl) avaEl.textContent = String(user.nama || user.username || 'A').trim().charAt(0).toUpperCase();
  }

  /* Render all dashboard sections from live db_ticketland state */
  var chartTiket = null;

  function renderDashboardSection() {
    var sum = state.summary || {};
    document.getElementById('statTotalEvent').textContent = Number(sum.total_events || 0).toLocaleString('id-ID');
    document.getElementById('statTotalTiket').textContent = Number(sum.total_tickets || 0).toLocaleString('id-ID');
    document.getElementById('statNilaiPenjualan').textContent = fmtRupiah(sum.sales_value || 0);
    document.getElementById('statTiketMenipis').textContent = Number(sum.low_stock_cnt || 0).toLocaleString('id-ID');
    document.getElementById('statTransaksiHariIni').textContent = Number(sum.today_tx_cnt || 0).toLocaleString('id-ID');

    // Top 5 Events by remaining stock
    var topList = document.getElementById('listTiketTerbanyak');
    topList.innerHTML = '';
    var topEvents = Array.isArray(sum.top_events) ? sum.top_events : [];
    if (topEvents.length === 0) {
      topList.innerHTML = '<li class="list-group-item text-muted">Belum ada data event aktif.</li>';
    } else {
      topEvents.forEach(function (ev) {
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = esc(ev.nama) + '<span class="badge bg-primary rounded-pill">' + Number(ev.total_stok).toLocaleString('id-ID') + '</span>';
        topList.appendChild(li);
      });
    }

    // Low Stock Events (sisa 1-5)
    var tbodyLow = document.getElementById('tbodyTiketMenipis');
    tbodyLow.innerHTML = '';
    var lowList = Array.isArray(sum.low_stock) ? sum.low_stock : [];
    if (lowList.length === 0) {
      tbodyLow.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada event dengan stok menipis (1-5 tiket).</td></tr>';
    } else {
      lowList.forEach(function (ev) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td><code>' + esc(ev.kode) + '</code></td>'
          + '<td>' + esc(ev.nama) + '</td>'
          + '<td>' + esc(ev.kategori_nama) + '</td>'
          + '<td class="text-end">' + Number(ev.total_stok) + ' tiket</td>';
        tbodyLow.appendChild(tr);
      });
    }

    // Recent Stock Transactions
    var tbodyRecent = document.getElementById('tbodyTransaksiTerbaru');
    tbodyRecent.innerHTML = '';
    var recList = Array.isArray(sum.recent_tx) ? sum.recent_tx : [];
    if (recList.length === 0) {
      tbodyRecent.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada transaksi stok.</td></tr>';
    } else {
      recList.forEach(function (tx) {
        var isIn = tx.jenis === 'IN';
        var badge = isIn ? '<span class="badge bg-success">Masuk</span>' : '<span class="badge bg-danger">Keluar</span>';
        var sign = isIn ? '+' : '&minus;';
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + esc(fmtDateTime(tx.waktu)) + '</td>'
          + '<td><code>' + esc(tx.event_kode) + '</code> ' + esc(tx.event_nama) + '</td>'
          + '<td>' + badge + '</td>'
          + '<td class="text-end">' + sign + Number(tx.jumlah) + '</td>'
          + '<td>' + esc(tx.pelaku) + '</td>';
        tbodyRecent.appendChild(tr);
      });
    }

    // 7-day Chart
    var chData = sum.chart || { labels: [], in: [], out: [] };
    var ctx = document.getElementById('chartTiket');
    if (ctx && window.Chart) {
      if (chartTiket) {
        chartTiket.data.labels = chData.labels;
        chartTiket.data.datasets[0].data = chData.in;
        chartTiket.data.datasets[1].data = chData.out;
        chartTiket.update();
      } else {
        chartTiket = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chData.labels,
            datasets: [
              {
                label: 'Masuk',
                data: chData.in,
                backgroundColor: 'rgba(11,93,59,.78)',
                borderRadius: 4
              },
              {
                label: 'Keluar',
                data: chData.out,
                backgroundColor: 'rgba(220,53,69,.78)',
                borderRadius: 4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }
    }
  }

  /* Menu 2: Data Event + Pagination + Soft Delete + Sampah */
  var currentEventPage = 1;

  function populateCategoryDropdowns() {
    var cats = Array.isArray(state.categories) ? state.categories : [];
    var filterKat = document.getElementById('filterKategori');
    var formEventKat = document.getElementById('eventKategoriId');

    if (filterKat) {
      var prev = filterKat.value;
      filterKat.innerHTML = '<option value="">Semua Kategori</option>';
      cats.forEach(function (c) {
        var opt = document.createElement('option');
        opt.value = c.nama;
        opt.textContent = c.nama;
        filterKat.appendChild(opt);
      });
      filterKat.value = prev;
    }

    if (formEventKat) {
      formEventKat.innerHTML = '';
      cats.forEach(function (c) {
        var opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nama;
        formEventKat.appendChild(opt);
      });
    }

    // Also populate manual transaction ticket category dropdown
    var txSelect = document.getElementById('txTicketCategoryId');
    if (txSelect) {
      txSelect.innerHTML = '';
      (state.events || []).forEach(function (ev) {
        (ev.ticket_categories || []).forEach(function (tc) {
          var opt = document.createElement('option');
          opt.value = tc.id;
          opt.textContent = ev.kode + ' - ' + ev.nama + ' (' + tc.nama + ', Sisa: ' + tc.stok_sisa + ')';
          txSelect.appendChild(opt);
        });
      });
    }

    // Report event filter
    var repEventFilter = document.getElementById('filterLaporanEvent');
    if (repEventFilter) {
      var prevRep = repEventFilter.value;
      repEventFilter.innerHTML = '<option value="">Semua Event</option>';
      (state.events || []).forEach(function (ev) {
        var opt = document.createElement('option');
        opt.value = ev.kode;
        opt.textContent = ev.kode + ' - ' + ev.nama;
        repEventFilter.appendChild(opt);
      });
      repEventFilter.value = prevRep;
    }
  }

  function renderEventsSection() {
    var q = (document.getElementById('cariBarang').value || '').trim().toLowerCase();
    var cat = document.getElementById('filterKategori').value || '';
    var limit = parseInt(document.getElementById('perPage').value || '10', 10);

    var allActive = Array.isArray(state.events) ? state.events : [];
    var filtered = allActive.filter(function (ev) {
      var matchCat = !cat || ev.kategori_nama === cat;
      if (!matchCat) return false;
      if (!q) return true;
      var hay = (String(ev.kode || '') + ' ' + String(ev.nama || '') + ' ' + String(ev.kategori_nama || '') + ' ' + String(ev.kota || '')).toLowerCase();
      return hay.indexOf(q) !== -1;
    });

    var totalItems = filtered.length;
    var totalPages = Math.max(1, Math.ceil(totalItems / limit));
    if (currentEventPage > totalPages) currentEventPage = totalPages;
    if (currentEventPage < 1) currentEventPage = 1;

    var startIdx = (currentEventPage - 1) * limit;
    var slice = filtered.slice(startIdx, startIdx + limit);

    var tbody = document.getElementById('tbodyBarang');
    tbody.innerHTML = '';

    if (slice.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada event yang cocok dengan pencarian atau filter.</td></tr>';
    } else {
      slice.forEach(function (ev) {
        var stok = Number(ev.total_stok || 0);
        var stokBadge = '';
        if (stok <= 0) {
          stokBadge = '<span class="badge bg-danger">Habis</span>';
        } else if (stok <= 5) {
          stokBadge = '<span class="badge bg-danger">' + stok + '</span>';
        } else {
          stokBadge = '<span class="badge bg-success">' + stok + '</span>';
        }
        var hargaText = Number(ev.harga_mulai) === 0 ? 'GRATIS' : fmtRupiah(ev.harga_mulai);

        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td><div class="bg-secondary bg-opacity-25 text-secondary d-flex align-items-center justify-content-center rounded fw-bold" style="width: 48px; height: 48px; font-size: 13px;">' + esc(ev.kode) + '</div></td>'
          + '<td><code>' + esc(ev.kode) + '</code></td>'
          + '<td>' + esc(ev.nama) + '</td>'
          + '<td>' + esc(ev.kategori_nama) + '</td>'
          + '<td>' + esc(ev.tanggal_label) + '</td>'
          + '<td>' + esc(hargaText) + '</td>'
          + '<td>' + stokBadge + '</td>'
          + '<td>'
          + '<button type="button" class="btn btn-info btn-sm text-white me-1" data-detail-id="' + ev.id + '"><i class="bi bi-eye me-1"></i>Detail</button>'
          + '<button type="button" class="btn btn-warning btn-sm me-1" data-edit-event-id="' + ev.id + '"><i class="bi bi-pencil me-1"></i>Edit</button>'
          + '<button type="button" class="btn btn-danger btn-sm" data-hapus-event-id="' + ev.id + '"><i class="bi bi-trash me-1"></i>Hapus</button>'
          + '</td>';
        tbody.appendChild(tr);
      });
    }

    var infoBarang = document.getElementById('infoBarang');
    if (totalItems === 0) {
      infoBarang.textContent = 'Tidak ada event yang cocok dengan pencarian';
    } else {
      var fromNum = startIdx + 1;
      var toNum = Math.min(startIdx + slice.length, totalItems);
      infoBarang.textContent = 'Menampilkan ' + fromNum + '-' + toNum + ' dari ' + totalItems + ' event';
    }

    // Render pagination links
    var pagUl = document.getElementById('paginationBarang');
    pagUl.innerHTML = '';

    var prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (currentEventPage <= 1 ? ' disabled' : '');
    prevLi.innerHTML = '<a class="page-link" href="#" data-page="' + (currentEventPage - 1) + '">Previous</a>';
    pagUl.appendChild(prevLi);

    for (var p = 1; p <= totalPages; p++) {
      var li = document.createElement('li');
      li.className = 'page-item' + (p === currentEventPage ? ' active' : '');
      li.innerHTML = '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a>';
      pagUl.appendChild(li);
    }

    var nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (currentEventPage >= totalPages ? ' disabled' : '');
    nextLi.innerHTML = '<a class="page-link" href="#" data-page="' + (currentEventPage + 1) + '">Next</a>';
    pagUl.appendChild(nextLi);

    pagUl.querySelectorAll('[data-page]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var targetP = parseInt(a.getAttribute('data-page'), 10);
        if (targetP >= 1 && targetP <= totalPages) {
          currentEventPage = targetP;
          renderEventsSection();
        }
      });
    });

    // Wire Detail, Edit, Hapus buttons
    tbody.querySelectorAll('[data-detail-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openEventDetail(parseInt(btn.getAttribute('data-detail-id'), 10));
      });
    });
    tbody.querySelectorAll('[data-edit-event-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openEditEventModal(parseInt(btn.getAttribute('data-edit-event-id'), 10));
      });
    });
    tbody.querySelectorAll('[data-hapus-event-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openDeleteEventModal(parseInt(btn.getAttribute('data-hapus-event-id'), 10));
      });
    });

    // Render Trashed Events (Sampah)
    var trashed = Array.isArray(state.trashed_events) ? state.trashed_events : [];
    document.getElementById('badgeSampahCount').textContent = String(trashed.length);
    var tbodySampah = document.getElementById('tbodySampah');
    tbodySampah.innerHTML = '';

    if (trashed.length === 0) {
      tbodySampah.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Sampah kosong. Tidak ada event yang dihapus.</td></tr>';
    } else {
      trashed.forEach(function (ev) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td><code>' + esc(ev.kode) + '</code></td>'
          + '<td>' + esc(ev.nama) + '</td>'
          + '<td>' + esc(ev.kategori_nama) + '</td>'
          + '<td>' + esc(fmtDateShort(ev.deleted_at)) + '</td>'
          + '<td>' + esc(ev.deleted_by || 'admin') + '</td>'
          + '<td><button type="button" class="btn btn-success btn-sm" data-restore-id="' + ev.id + '"><i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan</button></td>';
        tbodySampah.appendChild(tr);
      });
    }

    tbodySampah.querySelectorAll('[data-restore-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-restore-id'), 10);
        apiPost('/api/admin/events/restore', { id: id }).then(function (res) {
          if (!res.body || !res.body.ok) {
            showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal memulihkan event.'), 'danger');
            return;
          }
          state = res.body.state;
          renderAll();
          showFlash('<i class="bi bi-arrow-counterclockwise me-1"></i>' + esc(res.body.message));
          window.scrollTo(0, 0);
        });
      });
    });
  }

  function findEventInState(id) {
    var list = state.events || [];
    for (var i = 0; i < list.length; i++) {
      if (Number(list[i].id) === Number(id)) return list[i];
    }
    return null;
  }

  function openEventDetail(eventId) {
    var ev = findEventInState(eventId);
    if (!ev) return;

    document.getElementById('detailNama').textContent = ev.nama;
    document.getElementById('detailKode').textContent = ev.kode;
    document.getElementById('detailNama2').textContent = ev.nama;
    document.getElementById('detailKategori').textContent = ev.kategori_nama;
    document.getElementById('detailTanggal').textContent = ev.tanggal_label + ' (' + ev.jam_label + ')';
    document.getElementById('detailVenue').textContent = ev.venue + ', ' + ev.kota;
    document.getElementById('detailHarga').textContent = Number(ev.harga_mulai) === 0 ? 'GRATIS' : fmtRupiah(ev.harga_mulai);
    document.getElementById('detailDeskripsi').textContent = ev.deskripsi;

    var stokBadge = document.getElementById('detailStok');
    stokBadge.textContent = Number(ev.total_stok) + ' tiket';
    stokBadge.className = 'badge ' + (Number(ev.total_stok) <= 5 ? 'bg-danger' : 'bg-primary');

    var txs = (state.stock_transactions || []).filter(function (t) {
      return Number(t.event_id) === Number(ev.id);
    });
    var rowsHtml = '';
    if (txs.length === 0) {
      rowsHtml = '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada riwayat pergerakan tiket untuk event ini.</td></tr>';
    } else {
      txs.forEach(function (r) {
        var badge = r.jenis === 'IN'
          ? '<span class="badge bg-success">Masuk</span>'
          : '<span class="badge bg-danger">Keluar</span>';
        rowsHtml += '<tr>'
          + '<td>' + esc(fmtDateShort(r.waktu)) + '</td>'
          + '<td>' + badge + '</td>'
          + '<td>' + Number(r.jumlah) + '</td>'
          + '<td>' + Number(r.sisa_sesudah) + '</td>'
          + '<td>' + esc(r.pelaku) + '</td>'
          + '<td class="text-muted">' + esc(r.catatan) + '</td>'
          + '</tr>';
      });
    }
    document.getElementById('detailRiwayat').innerHTML = rowsHtml;
    showPage('item-detail');
  }

  /* Modal Tambah & Edit Event */
  var modalFormEventEl = document.getElementById('modalFormEvent');
  var modalFormEvent = (window.bootstrap && modalFormEventEl) ? new bootstrap.Modal(modalFormEventEl) : null;

  document.getElementById('btnTambahEvent').addEventListener('click', function () {
    document.getElementById('formEvent').reset();
    document.getElementById('eventId').value = '0';
    document.getElementById('modalFormEventLabel').innerHTML = '<i class="bi bi-calendar-plus me-1"></i>Tambah Event Baru';
    document.getElementById('eventNamaKategoriTiket').value = 'Reguler';
    if (modalFormEvent) modalFormEvent.show();
  });

  function openEditEventModal(eventId) {
    var ev = findEventInState(eventId);
    if (!ev) return;
    var firstTc = (ev.ticket_categories && ev.ticket_categories[0]) ? ev.ticket_categories[0] : { nama: 'Reguler', harga: ev.harga_mulai, stok_sisa: ev.total_stok };

    document.getElementById('eventId').value = String(ev.id);
    document.getElementById('eventNama').value = ev.nama;
    document.getElementById('eventKategoriId').value = String(ev.category_id);
    document.getElementById('eventTanggalMulai').value = ev.tanggal_mulai;
    document.getElementById('eventJamLabel').value = ev.jam_label;
    document.getElementById('eventVenue').value = ev.venue;
    document.getElementById('eventKota').value = ev.kota;
    document.getElementById('eventPenyelenggara').value = ev.penyelenggara;
    document.getElementById('eventNamaKategoriTiket').value = firstTc.nama;
    document.getElementById('eventHarga').value = String(firstTc.harga);
    document.getElementById('eventStok').value = String(firstTc.stok_sisa);
    document.getElementById('eventDeskripsi').value = ev.deskripsi;
    document.getElementById('modalFormEventLabel').innerHTML = '<i class="bi bi-pencil-square me-1"></i>Edit Event ' + esc(ev.kode);
    if (modalFormEvent) modalFormEvent.show();
  }

  document.getElementById('formEvent').addEventListener('submit', function (e) {
    e.preventDefault();
    var payload = {
      id: parseInt(document.getElementById('eventId').value || '0', 10),
      nama: document.getElementById('eventNama').value.trim(),
      category_id: parseInt(document.getElementById('eventKategoriId').value || '0', 10),
      tanggal_mulai: document.getElementById('eventTanggalMulai').value,
      jam_label: document.getElementById('eventJamLabel').value.trim(),
      venue: document.getElementById('eventVenue').value.trim(),
      kota: document.getElementById('eventKota').value.trim(),
      penyelenggara: document.getElementById('eventPenyelenggara').value.trim(),
      nama_kategori_tiket: document.getElementById('eventNamaKategoriTiket').value.trim(),
      harga_reguler: parseInt(document.getElementById('eventHarga').value || '0', 10),
      stok_reguler: parseInt(document.getElementById('eventStok').value || '0', 10),
      deskripsi: document.getElementById('eventDeskripsi').value.trim()
    };

    apiPost('/api/admin/events/save', payload).then(function (res) {
      if (!res.body || !res.body.ok) {
        showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal menyimpan event.'), 'danger');
        return;
      }
      if (modalFormEvent) modalFormEvent.hide();
      state = res.body.state;
      renderAll();
      showFlash('<i class="bi bi-check-circle me-1"></i>' + esc(res.body.message));
      window.scrollTo(0, 0);
    });
  });

  /* Modal Hapus Event / Kategori */
  var modalHapusEl = document.getElementById('modalHapus');
  var modalHapus = (window.bootstrap && modalHapusEl) ? new bootstrap.Modal(modalHapusEl) : null;
  var pendingDelete = null;

  function openDeleteEventModal(eventId) {
    var ev = findEventInState(eventId);
    if (!ev) return;
    pendingDelete = { type: 'event', id: ev.id, nama: ev.nama };
    document.getElementById('hapusNama').textContent = ev.nama + ' (' + ev.kode + ')';
    var warnSold = document.getElementById('hapusSoldWarning');
    if (Number(ev.tiket_terjual || 0) > 0) {
      warnSold.textContent = 'Perhatian: Event ini sudah memiliki ' + ev.tiket_terjual + ' tiket terjual. Menghapus event akan memindahkannya ke Sampah (soft delete) tanpa menghapus riwayat transaksi.';
      warnSold.classList.remove('d-none');
    } else {
      warnSold.classList.add('d-none');
    }
    if (modalHapus) modalHapus.show();
  }

  document.getElementById('btnKonfirmasiHapus').addEventListener('click', function () {
    if (!pendingDelete) return;
    var item = pendingDelete;
    pendingDelete = null;
    if (modalHapus) modalHapus.hide();

    var url = item.type === 'category' ? '/api/admin/categories/delete' : '/api/admin/events/delete';
    apiPost(url, { id: item.id }).then(function (res) {
      if (!res.body || !res.body.ok) {
        showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal menghapus data.'), 'danger');
        window.scrollTo(0, 0);
        return;
      }
      state = res.body.state;
      renderAll();
      showFlash('<i class="bi bi-trash me-1"></i>' + esc(res.body.message));
      window.scrollTo(0, 0);
    });
  });

  /* Menu 3: Data Kategori */
  var modalFormKategoriEl = document.getElementById('modalFormKategori');
  var modalFormKategori = (window.bootstrap && modalFormKategoriEl) ? new bootstrap.Modal(modalFormKategoriEl) : null;

  function renderCategoriesSection() {
    var tbody = document.getElementById('tbodyKategori');
    tbody.innerHTML = '';
    var cats = Array.isArray(state.categories) ? state.categories : [];

    cats.forEach(function (c, idx) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>'
        + '<td>' + esc(c.nama) + '</td>'
        + '<td>' + esc(c.deskripsi) + '</td>'
        + '<td><span class="badge bg-primary">' + Number(c.jumlah_event || 0) + '</span></td>'
        + '<td>'
        + '<button type="button" class="btn btn-warning btn-sm me-1" data-edit-cat-id="' + c.id + '"><i class="bi bi-pencil me-1"></i>Edit</button>'
        + '<button type="button" class="btn btn-danger btn-sm" data-del-cat-id="' + c.id + '" data-del-cat-name="' + esc(c.nama) + '"><i class="bi bi-trash me-1"></i>Hapus</button>'
        + '</td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('[data-edit-cat-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cid = parseInt(btn.getAttribute('data-edit-cat-id'), 10);
        var cat = cats.find(function (x) { return Number(x.id) === cid; });
        if (!cat) return;
        document.getElementById('kategoriId').value = String(cat.id);
        document.getElementById('kategoriNama').value = cat.nama;
        document.getElementById('kategoriDeskripsi').value = cat.deskripsi;
        document.getElementById('modalFormKategoriLabel').innerHTML = '<i class="bi bi-pencil-square me-1"></i>Edit Kategori';
        if (modalFormKategori) modalFormKategori.show();
      });
    });

    tbody.querySelectorAll('[data-del-cat-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cid = parseInt(btn.getAttribute('data-del-cat-id'), 10);
        var cname = btn.getAttribute('data-del-cat-name');
        pendingDelete = { type: 'category', id: cid, nama: cname };
        document.getElementById('hapusNama').textContent = 'Kategori ' + cname;
        document.getElementById('hapusSoldWarning').classList.add('d-none');
        if (modalHapus) modalHapus.show();
      });
    });
  }

  document.getElementById('btnTambahKategori').addEventListener('click', function () {
    document.getElementById('formKategori').reset();
    document.getElementById('kategoriId').value = '0';
    document.getElementById('modalFormKategoriLabel').innerHTML = '<i class="bi bi-plus-circle me-1"></i>Tambah Kategori Event';
    if (modalFormKategori) modalFormKategori.show();
  });

  document.getElementById('formKategori').addEventListener('submit', function (e) {
    e.preventDefault();
    var payload = {
      id: parseInt(document.getElementById('kategoriId').value || '0', 10),
      nama: document.getElementById('kategoriNama').value.trim(),
      deskripsi: document.getElementById('kategoriDeskripsi').value.trim()
    };
    apiPost('/api/admin/categories/save', payload).then(function (res) {
      if (!res.body || !res.body.ok) {
        showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal menyimpan kategori.'), 'danger');
        return;
      }
      if (modalFormKategori) modalFormKategori.hide();
      state = res.body.state;
      renderAll();
      showFlash('<i class="bi bi-check-circle me-1"></i>' + esc(res.body.message));
      window.scrollTo(0, 0);
    });
  });

  /* Menu 4: Data Transaksi (Stok Masuk/Keluar + Pemesanan) */
  var currentTxFilter = '';
  var modalTransaksiBaruEl = document.getElementById('modalTransaksiBaru');
  var modalTransaksiBaru = (window.bootstrap && modalTransaksiBaruEl) ? new bootstrap.Modal(modalTransaksiBaruEl) : null;

  function renderTransactionsSection() {
    var tbody = document.getElementById('tbodyTransaksi');
    tbody.innerHTML = '';
    var list = (state.stock_transactions || []).filter(function (tx) {
      return !currentTxFilter || tx.jenis === currentTxFilter;
    });

    if (list.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data transaksi untuk filter ini.</td></tr>';
    } else {
      list.forEach(function (tx) {
        var badge = tx.jenis === 'IN'
          ? '<span class="badge bg-success">Masuk</span>'
          : '<span class="badge bg-danger">Keluar</span>';
        var tr = document.createElement('tr');
        tr.setAttribute('data-jenis', tx.jenis);
        tr.innerHTML =
          '<td>' + esc(fmtDateShort(tx.waktu)) + '</td>'
          + '<td>' + esc(tx.event_nama) + ' <small class="text-muted">(' + esc(tx.kategori_tiket_nama) + ')</small></td>'
          + '<td>' + badge + '</td>'
          + '<td class="text-center">' + Number(tx.jumlah) + '</td>'
          + '<td>' + esc(tx.pelaku) + '</td>'
          + '<td class="text-muted">' + esc(tx.catatan) + '</td>';
        tbody.appendChild(tr);
      });
    }

    // Also render Orders Table
    var tbodyOrders = document.getElementById('tbodyPemesananAdmin');
    if (tbodyOrders) {
      tbodyOrders.innerHTML = '';
      var orders = Array.isArray(state.orders) ? state.orders : [];
      if (orders.length === 0) {
        tbodyOrders.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Belum ada pemesanan tiket.</td></tr>';
      } else {
        orders.forEach(function (o) {
          var badgeStatus = o.status === 'Lunas'
            ? '<span class="badge bg-success">Lunas</span>'
            : '<span class="badge bg-secondary">' + esc(o.status) + '</span>';
          var tr = document.createElement('tr');
          tr.innerHTML =
            '<td><code>' + esc(o.nomor_invoice) + '</code></td>'
            + '<td>' + esc(fmtDateShort(o.created_at)) + '</td>'
            + '<td>' + esc(o.pemesan_nama) + ' <small class="text-muted d-block">' + esc(o.pemesan_email) + '</small></td>'
            + '<td>' + esc(o.event_nama) + ' <small class="text-muted">(' + esc(o.kategori_tiket_nama) + ')</small></td>'
            + '<td class="text-center">' + Number(o.jumlah) + '</td>'
            + '<td class="text-end">' + fmtRupiah(o.total) + '</td>'
            + '<td>' + badgeStatus + '</td>';
          tbodyOrders.appendChild(tr);
        });
      }
    }
  }

  document.getElementById('btnTransaksiBaru').addEventListener('click', function () {
    document.getElementById('formTransaksiBaru').reset();
    if (modalTransaksiBaru) modalTransaksiBaru.show();
  });

  document.getElementById('formTransaksiBaru').addEventListener('submit', function (e) {
    e.preventDefault();
    var payload = {
      ticket_category_id: parseInt(document.getElementById('txTicketCategoryId').value || '0', 10),
      jenis: document.getElementById('txJenis').value,
      jumlah: parseInt(document.getElementById('txJumlah').value || '0', 10),
      catatan: document.getElementById('txCatatan').value.trim()
    };

    apiPost('/api/admin/transactions/create', payload).then(function (res) {
      if (!res.body || !res.body.ok) {
        showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Transaksi ditolak.'), 'danger');
        if (modalTransaksiBaru) modalTransaksiBaru.hide();
        window.scrollTo(0, 0);
        return;
      }
      if (modalTransaksiBaru) modalTransaksiBaru.hide();
      state = res.body.state;
      renderAll();
      showFlash('<i class="bi bi-check-circle me-1"></i>' + esc(res.body.message));
      window.scrollTo(0, 0);
    });
  });

  /* Menu 5: Laporan & Daftar Passenger */
  function renderReportsSection() {
    var filterEv = (document.getElementById('filterLaporanEvent') && document.getElementById('filterLaporanEvent').value) || '';
    var rep = state.sales_report || { rows: [], total_terjual: 0, total_penjualan: 0 };
    var rows = (rep.rows || []).filter(function (r) {
      return !filterEv || r.kode === filterEv;
    });

    var tbodyLap = document.getElementById('tbodyLaporan');
    tbodyLap.innerHTML = '';
    var sumSold = 0;
    var sumRev = 0;

    rows.forEach(function (r) {
      sumSold += Number(r.tiket_terjual || 0);
      sumRev += Number(r.total_penjualan || 0);
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><code>' + esc(r.kode) + '</code></td>'
        + '<td>' + esc(r.nama) + '</td>'
        + '<td>' + esc(r.kategori_nama) + '</td>'
        + '<td class="text-end">' + Number(r.tiket_terjual) + '</td>'
        + '<td class="text-end">' + fmtRupiah(r.harga_mulai) + '</td>'
        + '<td class="text-end">' + fmtRupiah(r.total_penjualan) + '</td>';
      tbodyLap.appendChild(tr);
    });

    document.getElementById('totalTiketTerjual').textContent = String(sumSold);
    document.getElementById('totalNilaiLaporan').textContent = fmtRupiah(sumRev);

    // Daftar Passenger (D5)
    var passengers = (state.passengers || []).filter(function (p) {
      return !filterEv || p.event_kode === filterEv;
    });
    var tbodyPas = document.getElementById('tbodyPassenger');
    tbodyPas.innerHTML = '';
    var totalP = passengers.length;
    var sudahP = 0;

    passengers.forEach(function (p) {
      var isChecked = p.status_checkin === 'Sudah Check-in';
      if (isChecked) sudahP++;
      var badge = isChecked
        ? '<span class="badge bg-success">Sudah Check-in</span>'
        : '<span class="badge bg-secondary">Belum Check-in</span>';
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + esc(p.nama_pemegang) + '</td>'
        + '<td><code>' + esc(p.kode_tiket) + '</code></td>'
        + '<td>' + esc(p.event_nama) + '</td>'
        + '<td>' + esc(p.kategori_tiket_nama) + '</td>'
        + '<td>' + badge + '</td>';
      tbodyPas.appendChild(tr);
    });

    var ringkasan = document.getElementById('ringkasanPassenger');
    if (ringkasan) {
      ringkasan.textContent = totalP + ' passenger · ' + sudahP + ' sudah check-in · ' + (totalP - sudahP) + ' belum';
    }
  }

  var filterLaporanEvent = document.getElementById('filterLaporanEvent');
  if (filterLaporanEvent) {
    filterLaporanEvent.addEventListener('change', renderReportsSection);
  }

  /* Menu 6: Pengguna */
  function renderUsersSection() {
    var tbody = document.getElementById('tbodyPengguna');
    tbody.innerHTML = '';
    var users = Array.isArray(state.users) ? state.users : [];

    users.forEach(function (u, idx) {
      var role = String(u.peran || 'staff').toLowerCase();
      var badgeClass = role === 'admin' ? 'bg-primary' : (role === 'manager' ? 'bg-warning' : 'bg-info');
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>'
        + '<td>' + esc(u.username) + '</td>'
        + '<td>' + esc(u.nama) + '</td>'
        + '<td><span class="badge ' + badgeClass + '">' + esc(role) + '</span></td>'
        + '<td>' + esc(fmtDateShort(u.created_at)) + '</td>';
      tbody.appendChild(tr);
    });
  }

  /* Menu 7: Log Aktivitas */
  function renderActivitySection() {
    var tbody = document.getElementById('tbodyLogAktivitas');
    tbody.innerHTML = '';
    var logs = Array.isArray(state.activity_logs) ? state.activity_logs : [];

    if (logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada log aktivitas.</td></tr>';
      return;
    }

    logs.forEach(function (l) {
      var aksi = String(l.aksi || '').toLowerCase();
      var badgeCls = 'bg-info';
      if (aksi === 'create' || aksi === 'approve' || aksi === 'restore') badgeCls = 'bg-success';
      else if (aksi === 'update') badgeCls = 'bg-warning';
      else if (aksi === 'delete') badgeCls = 'bg-danger';

      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + esc(fmtDateTime(l.waktu)) + '</td>'
        + '<td>' + esc(l.username) + '</td>'
        + '<td><span class="badge ' + badgeCls + '">' + esc(l.aksi) + '</span></td>'
        + '<td>' + esc(l.entitas) + '</td>'
        + '<td>' + esc(l.deskripsi) + '</td>';
      tbody.appendChild(tr);
    });
  }

  function renderAll() {
    populateCategoryDropdowns();
    renderDashboardSection();
    renderEventsSection();
    renderCategoriesSection();
    renderTransactionsSection();
    renderReportsSection();
    renderUsersSection();
    renderActivitySection();
  }

  /* Page Navigation */
  function showPage(name) {
    pageIds.forEach(function (id) {
      var el = document.getElementById('page-' + id);
      if (el) el.classList.add('d-none');
    });
    var target = document.getElementById('page-' + name);
    if (target) target.classList.remove('d-none');

    var isAdmin = adminPages.indexOf(name) !== -1;
    appShell.classList.toggle('d-none', !isAdmin);

    if (name === 'login') {
      flashArea.innerHTML = '';
    }
    if (name === 'dashboard') {
      renderDashboardSection();
    }
    if (name === 'reports') {
      renderReportsSection();
    }

    var activeKey = name === 'item-detail' ? 'items' : name;
    document.querySelectorAll('[data-navlink]').forEach(function (link) {
      link.classList.toggle('active', link.getAttribute('data-navlink') === activeKey);
    });

    if (window.innerWidth <= 768) {
      sidebar.classList.add('close');
    }
    window.scrollTo(0, 0);
  }

  document.querySelectorAll('[data-nav]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      showPage(el.getAttribute('data-nav'));
    });
  });

  // Toggle sidebar (250px buka / 78px tutup)
  document.getElementById('btnMenu').addEventListener('click', function () {
    sidebar.classList.toggle('close');
  });
  document.getElementById('logoToggle').addEventListener('click', function () {
    sidebar.classList.toggle('close');
  });

  if (window.innerWidth <= 768) {
    sidebar.classList.add('close');
  }

  /* Admin Login & Logout */
  document.getElementById('formLogin').addEventListener('submit', function (e) {
    e.preventDefault();
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value;
    if (loginAlert) loginAlert.classList.add('d-none');

    apiPost('/api/admin/login', { username: username, password: password }).then(function (res) {
      if (!res.body || !res.body.ok) {
        if (loginAlert) {
          loginAlert.textContent = (res.body && res.body.message) || 'Login gagal.';
          loginAlert.classList.remove('d-none');
        }
        return;
      }
      applyAdminProfile(res.body.user);
      state = res.body.state;
      renderAll();
      showPage('dashboard');
      showFlash('<i class="bi bi-check-circle me-1"></i>Login berhasil! Selamat datang kembali, <strong>' + esc(res.body.user.nama) + '</strong>.');
    });
  });

  document.getElementById('btnLogout').addEventListener('click', function (e) {
    e.preventDefault();
    apiPost('/api/admin/logout', {}).then(function () {
      currentAdmin = null;
      showPage('login');
    });
  });

  /* Ekspor CSV halaman Laporan (FR-A-18, AC-20) */
  document.getElementById('btnExportCsv').addEventListener('click', function () {
    // Trigger server-side CSV download and log activity
    var rows = [['Kode', 'Nama Event', 'Kategori', 'Tiket Terjual', 'Harga Tiket', 'Total Penjualan']];
    document.querySelectorAll('#tabelLaporan tbody tr').forEach(function (tr) {
      var cells = [];
      tr.querySelectorAll('td').forEach(function (td) {
        cells.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
      });
      rows.push(cells);
    });
    var tfootCells = [
      '"Total"',
      '""',
      '""',
      '"' + document.getElementById('totalTiketTerjual').textContent.trim() + '"',
      '""',
      '"' + document.getElementById('totalNilaiLaporan').textContent.trim() + '"'
    ];
    rows.push(tfootCells);

    var csv = rows.map(function (r) { return r.join(','); }).join('\n');
    var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'laporan-penjualan-tiket.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    // Also notify server to log export activity
    fetch('/admin/reports/export-csv').catch(function () {});
    showFlash('<i class="bi bi-download me-1"></i>Laporan berhasil diekspor ke <strong>laporan-penjualan-tiket.csv</strong>.');
  });

  /* Tambah Pengguna (DFD 1.1 Tambah Data Staff) */
  var modalTambahPenggunaEl = document.getElementById('modalTambahPengguna');
  var modalTambahPengguna = (window.bootstrap && modalTambahPenggunaEl) ? new bootstrap.Modal(modalTambahPenggunaEl) : null;

  document.getElementById('formTambahPengguna').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = this;
    var nama = document.getElementById('penggunaNama').value.trim();
    var username = document.getElementById('penggunaUsername').value.trim();
    var role = document.getElementById('penggunaRole').value;
    var password = document.getElementById('penggunaPassword').value;

    apiPost('/api/admin/users/create', {
      nama: nama,
      username: username,
      peran: role,
      password: password
    }).then(function (res) {
      if (!res.body || !res.body.ok) {
        showFlash('<i class="bi bi-x-octagon me-1"></i>' + esc((res.body && res.body.message) || 'Gagal menambahkan pengguna.'), 'danger');
        if (modalTambahPengguna) modalTambahPengguna.hide();
        window.scrollTo(0, 0);
        return;
      }
      if (modalTambahPengguna) modalTambahPengguna.hide();
      form.reset();
      state = res.body.state;
      renderAll();
      showFlash('<i class="bi bi-person-check me-1"></i>' + esc(res.body.message));
      window.scrollTo(0, 0);
    });
  });

  /* Tab Laporan: Pemesanan & Pembatalan / Daftar Passenger */
  var tabLaporanPemesanan = document.getElementById('tabLaporanPemesanan');
  var tabDaftarPassenger = document.getElementById('tabDaftarPassenger');
  var btnExportCsvEl = document.getElementById('btnExportCsv');

  function tampilTabLaporan(kePassenger) {
    tabLaporanPemesanan.classList.toggle('active', !kePassenger);
    tabDaftarPassenger.classList.toggle('active', kePassenger);
    document.getElementById('panelLaporanPemesanan').classList.toggle('d-none', kePassenger);
    document.getElementById('panelDaftarPassenger').classList.toggle('d-none', !kePassenger);
    btnExportCsvEl.classList.toggle('d-none', kePassenger);
    if (kePassenger) renderReportsSection();
  }

  tabLaporanPemesanan.addEventListener('click', function (e) {
    e.preventDefault();
    tampilTabLaporan(false);
  });
  tabDaftarPassenger.addEventListener('click', function (e) {
    e.preventDefault();
    tampilTabLaporan(true);
  });

  /* Tab Event Aktif / Sampah */
  var tabAktif = document.getElementById('tabBarangAktif');
  var tabSampah = document.getElementById('tabSampah');
  tabAktif.addEventListener('click', function (e) {
    e.preventDefault();
    tabAktif.classList.add('active');
    tabSampah.classList.remove('active');
    document.getElementById('panelBarangAktif').classList.remove('d-none');
    document.getElementById('panelSampah').classList.add('d-none');
  });
  tabSampah.addEventListener('click', function (e) {
    e.preventDefault();
    tabSampah.classList.add('active');
    tabAktif.classList.remove('active');
    document.getElementById('panelSampah').classList.remove('d-none');
    document.getElementById('panelBarangAktif').classList.add('d-none');
  });

  /* Filter Event */
  document.getElementById('cariBarang').addEventListener('input', function () {
    currentEventPage = 1;
    renderEventsSection();
  });
  document.getElementById('filterKategori').addEventListener('change', function () {
    currentEventPage = 1;
    renderEventsSection();
  });
  document.getElementById('perPage').addEventListener('change', function () {
    currentEventPage = 1;
    renderEventsSection();
  });
  document.getElementById('formFilterBarang').addEventListener('submit', function (e) {
    e.preventDefault();
  });

  /* Filter Jenis Transaksi */
  document.querySelectorAll('[data-tx-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-tx-filter]').forEach(function (b) {
        b.classList.remove('active');
      });
      btn.classList.add('active');
      currentTxFilter = btn.getAttribute('data-tx-filter') || '';
      renderTransactionsSection();
    });
  });

  /* Initialize */
  applyAdminProfile(currentAdmin);
  renderAll();

  var params = new URLSearchParams(window.location.search);
  var qPage = params.get('page');
  var qTab = params.get('tab');
  var qSidebar = params.get('sidebar');

  if (currentAdmin) {
    showPage(qPage && adminPages.indexOf(qPage) !== -1 ? qPage : (initialPage === 'login' ? 'dashboard' : initialPage));
    if (qTab === 'sampah') {
      tabSampah.click();
    } else if (qTab === 'passenger') {
      tampilTabLaporan(true);
    }
    if (qSidebar === 'close') {
      sidebar.classList.add('close');
    }
  } else {
    showPage('login');
  }
})();
