(function () {
  console.log('landing.js init start');
  var boot = window.TICKET_LAND_BOOT || {};
  var csrfToken = boot.csrfToken || '';
  var catalog = Array.isArray(boot.events) ? boot.events : [];
  var currentCustomer = boot.customer || null;

  var toast = document.getElementById('toast');
  var toastTimer = null;
  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2800);
  }
  window.showToast = showToast;

  function fmtRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
  }

  function validEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
  }

  function validGmail(v) {
    return /^[a-zA-Z0-9._%+-]+@gmail\.com$/i.test(String(v || '').trim());
  }

  function validNik(v) {
    return /^\d{16}$/.test(String(v || '').trim());
  }

  function validWa(v) {
    var d = String(v || '').replace(/\D/g, '');
    return d.length >= 9;
  }

  function resolvePoster(p) {
    if (!p || String(p).trim() === '') return '';
    var s = String(p).trim();
    if (s.indexOf('/') === 0 || s.indexOf('http://') === 0 || s.indexOf('https://') === 0) {
      return s;
    }
    return '/assets/img/events/' + s;
  }

  function initialsOf(name) {
    return String(name || '')
      .split(/\s+/)
      .filter(function (w) { return /^[A-Za-z]/.test(w); })
      .slice(0, 2)
      .map(function (w) { return w.charAt(0); })
      .join('')
      .toUpperCase() || 'TL';
  }

  function waDisplay(raw) {
    var digits = String(raw || '').replace(/\D/g, '');
    if (digits.charAt(0) === '0') digits = digits.slice(1);
    if (digits.indexOf('62') === 0) digits = digits.slice(2);
    return '+62' + digits;
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
      var ct = res.headers.get('content-type') || '';
      if (ct.indexOf('application/json') === -1) {
        return { status: res.status, body: { ok: false, message: 'Server merespons dengan format yang tidak dikenal (HTTP ' + res.status + ').' } };
      }
      return res.json().then(function (json) {
        return { status: res.status, body: json };
      });
    }).catch(function () {
      return { status: 0, body: { ok: false, message: 'Tidak bisa terhubung ke server. Pastikan Apache & MySQL jalan, lalu coba lagi.' } };
    });
  }

  /* Mobile menu */
  var burger = document.getElementById('burger');
  var mobileMenu = document.getElementById('mobileMenu');
  if (burger && mobileMenu) {
    burger.addEventListener('click', function () {
      var open = mobileMenu.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    mobileMenu.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        mobileMenu.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* Page Sections */
  var landingSections = document.querySelectorAll('.hero, .kategori, .rekom, .bantuan');
  var exploreSection = document.getElementById('explorePage');
  var detailSection = document.getElementById('eventDetail');
  var ticketSection = document.getElementById('ticketPage');
  var orderSection = document.getElementById('orderPage');
  var paySection = document.getElementById('payPage');
  var resultSection = document.getElementById('resultPage');
  var contactSection = document.getElementById('contactPage');

  function hideAllSubPages() {
    exploreSection.hidden = true;
    detailSection.hidden = true;
    ticketSection.hidden = true;
    orderSection.hidden = true;
    paySection.hidden = true;
    resultSection.hidden = true;
    contactSection.hidden = true;
  }

  function showHome() {
    stopTimer();
    hideAllSubPages();
    landingSections.forEach(function (s) { s.style.display = ''; });
  }

  document.querySelectorAll('.nav-home').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var href = link.getAttribute('href') || '#';
      showHome();
      if (href === '#') {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });

  document.querySelectorAll('.nav-event').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      if (mobileMenu) mobileMenu.classList.remove('open');
      if (burger) burger.setAttribute('aria-expanded', 'false');
      exploreState.sort = 'relevan';
      if (sortItems) {
        sortItems.forEach(function (si) {
          var isCur = (si.getAttribute('data-sort') === 'relevan');
          si.classList.toggle('active', isCur);
          si.setAttribute('aria-selected', isCur ? 'true' : 'false');
        });
      }
      openExplore('Semua', '');
    });
  });

  /* Explore & Search */
  var exploreGrid = document.getElementById('exploreGrid');
  var exploreCount = document.getElementById('exploreCount');
  var exploreEmpty = document.getElementById('exploreEmpty');
  var activeChip = document.getElementById('activeChip');
  var activeChipName = document.getElementById('activeChipName');
  var filterPanel = document.getElementById('filterPanel');
  var btnFilter = document.getElementById('btnFilter');
  var btnSort = document.getElementById('btnSort');
  var sortMenu = document.getElementById('sortMenu');
  var sortItems = document.querySelectorAll('.sort-item');
  var navSearchInput = document.getElementById('navSearchInput');
  var exploreSearchInput = document.getElementById('exploreSearchInput');
  var exploreSearchClear = document.getElementById('exploreSearchClear');

  var exploreState = { cat: 'Semua', sort: 'relevan', q: '' };

  var CAT_COLORS = {
    Musik: ['#0B5D3B', '#084A2E'],
    Pameran: ['#17684b', '#0f5138'],
    Komedi: ['#b45309', '#78350f'],
    Trip: ['#0369a1', '#0c4a6e'],
    Bisnis: ['#334155', '#1e293b']
  };

  function bannerFallbackSvg(ev, idx) {
    var catName = ev.kategori_nama || 'Musik';
    var c = CAT_COLORS[catName] || CAT_COLORS.Musik;
    var gid = 'xg' + idx + '_' + ev.id;
    return '<svg viewBox="0 0 320 164" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
      + '<defs><linearGradient id="' + gid + '" x1="0" y1="0" x2="1" y2="1">'
      + '<stop offset="0" stop-color="' + c[0] + '"/><stop offset="1" stop-color="' + c[1] + '"/>'
      + '</linearGradient></defs>'
      + '<rect width="320" height="164" fill="url(#' + gid + ')"/>'
      + '<circle cx="276" cy="22" r="54" fill="#ffffff" opacity=".14"/>'
      + '<circle cx="26" cy="152" r="42" fill="#ffffff" opacity=".10"/>'
      + '<text x="22" y="90" font-family="Plus Jakarta Sans, sans-serif" font-size="46" font-weight="800" fill="#ffffff" opacity=".95">' + initialsOf(ev.nama) + '</text>'
      + '<text x="24" y="120" font-family="Plus Jakarta Sans, sans-serif" font-size="12" font-weight="700" fill="#ffffff" opacity=".85" letter-spacing="2.5">' + catName.toUpperCase() + '</text>'
      + '</svg>';
  }

  function bannerSvg(ev, idx) {
    var pSrc = resolvePoster(ev.poster);
    if (pSrc !== '') {
      return '<img src="' + escapeXml(pSrc) + '" alt="' + escapeXml(ev.nama) + '" loading="lazy" style="width: 100%; height: 164px; object-fit: cover; object-position: center; display: block;" onerror="this.style.display=\'none\';if(this.nextElementSibling){this.nextElementSibling.style.display=\'block\';}">'
        + '<div style="display:none;">' + bannerFallbackSvg(ev, idx) + '</div>';
    }
    return bannerFallbackSvg(ev, idx);
  }

  function detailBannerFallbackSvg(ev) {
    var catName = ev.kategori_nama || 'Event';
    var titleShort = String(ev.nama || '').length > 26 ? String(ev.nama).slice(0, 26) + '...' : String(ev.nama || '');
    return '<svg viewBox="0 0 640 360" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
      + '<rect width="640" height="360" fill="#084A2E"/>'
      + '<ellipse cx="540" cy="52" rx="130" ry="90" fill="#f2a63b" opacity=".9"/>'
      + '<polygon points="0,250 150,170 290,230 430,150 640,240 640,360 0,360" fill="#0B5D3B"/>'
      + '<polygon points="0,310 170,240 340,300 500,225 640,285 640,360 0,360" fill="#E2F2E9" opacity=".28"/>'
      + '<text x="36" y="110" font-family="Plus Jakarta Sans, sans-serif" font-size="16" font-weight="700" fill="#E2F2E9" letter-spacing="2">' + escapeXml(String(ev.penyelenggara || '').toUpperCase()) + '</text>'
      + '<text x="34" y="172" font-family="Plus Jakarta Sans, sans-serif" font-size="36" font-weight="800" fill="#ffffff">' + escapeXml(titleShort) + '</text>'
      + '<text x="36" y="214" font-family="Plus Jakarta Sans, sans-serif" font-size="22" font-weight="700" fill="#f2a63b">' + escapeXml(catName.toUpperCase()) + ' • ' + escapeXml(ev.kode || '') + '</text>'
      + '<text x="36" y="262" font-family="Plus Jakarta Sans, sans-serif" font-size="16" fill="#E2F2E9">' + escapeXml(ev.tanggal_label || '') + ' • ' + escapeXml(ev.kota || '') + '</text>'
      + '</svg>';
  }

  function detailBannerSvg(ev) {
    var pSrc = resolvePoster(ev.poster);
    if (pSrc !== '') {
      var catName = ev.kategori_nama || 'Event';
      return '<div class="detail-poster-wrap" style="position:relative;width:100%;height:320px;overflow:hidden;background:#084A2E;border-radius:12px;">'
        + '<img src="' + escapeXml(pSrc) + '" alt="' + escapeXml(ev.nama) + '" style="width:100%;height:100%;object-fit:cover;object-position:center;display:block;" onerror="this.parentElement.style.display=\'none\';if(this.parentElement.nextElementSibling){this.parentElement.nextElementSibling.style.display=\'block\';}">'
        + '<div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);display:flex;flex-direction:column;justify-content:flex-end;padding:24px 28px;">'
        + '<div style="font-size:12px;font-weight:700;color:#f2a63b;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;">' + escapeXml(catName) + ' • ' + escapeXml(ev.kode || '') + '</div>'
        + '<div style="font-size:26px;font-weight:800;color:#ffffff;line-height:1.2;margin-bottom:6px;">' + escapeXml(ev.nama || '') + '</div>'
        + '<div style="font-size:14px;color:#E2F2E9;">' + escapeXml(ev.tanggal_label || '') + ' • ' + escapeXml(ev.venue || '') + ', ' + escapeXml(ev.kota || '') + '</div>'
        + '</div>'
        + '</div>'
        + '<div style="display:none;">' + detailBannerFallbackSvg(ev) + '</div>';
    }
    return detailBannerFallbackSvg(ev);
  }

  function escapeXml(unsafe) {
    return String(unsafe || '').replace(/[<>&'"]/g, function (c) {
      switch (c) {
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '&': return '&amp;';
        case '\'': return '&apos;';
        case '"': return '&quot;';
      }
      return c;
    });
  }

  function visibleEvents() {
    var q = exploreState.q.trim().toLowerCase();
    var list = catalog.filter(function (ev) {
      var catMatch = exploreState.cat === 'Semua' || ev.kategori_nama === exploreState.cat;
      if (!catMatch) return false;
      if (!q) return true;
      var hay = (String(ev.nama || '') + ' ' + String(ev.penyelenggara || '') + ' ' + String(ev.kota || '') + ' ' + String(ev.kode || '')).toLowerCase();
      return hay.indexOf(q) !== -1;
    });

    if (exploreState.sort === 'tanggal') {
      list.sort(function (a, b) {
        return a.tanggal_mulai < b.tanggal_mulai ? -1 : (a.tanggal_mulai > b.tanggal_mulai ? 1 : 0);
      });
    } else if (exploreState.sort === 'murah') {
      list.sort(function (a, b) { return a.harga_mulai - b.harga_mulai; });
    } else if (exploreState.sort === 'mahal') {
      list.sort(function (a, b) { return b.harga_mulai - a.harga_mulai; });
    }
    return list;
  }

  function renderExplore() {
    var list = visibleEvents();
    exploreGrid.innerHTML = '';

    list.forEach(function (ev, idx) {
      var free = Number(ev.harga_mulai) === 0;
      var soldOut = Number(ev.total_stok) < 1;

      var card = document.createElement('button');
      card.type = 'button';
      card.className = 'xcard';
      card.setAttribute('aria-label', 'Lihat detail ' + ev.nama);

      var banner = document.createElement('span');
      banner.className = 'xcard-banner';
      banner.innerHTML = bannerSvg(ev, idx);
      if (soldOut) {
        var habisBadge = document.createElement('span');
        habisBadge.className = 'xcard-habis-badge';
        habisBadge.textContent = 'HABIS';
        banner.appendChild(habisBadge);
      }

      var body = document.createElement('span');
      body.className = 'xcard-body';

      var title = document.createElement('span');
      title.className = 'xcard-title';
      title.textContent = ev.nama;
      body.appendChild(title);

      var date = document.createElement('span');
      date.className = 'xcard-date';
      date.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4" stroke-linecap="round"/></svg>';
      date.appendChild(document.createTextNode(ev.tanggal_label));
      body.appendChild(date);

      if (ev.label_sesi) {
        var sesi = document.createElement('span');
        sesi.className = 'xcard-sesi';
        sesi.textContent = ev.label_sesi;
        body.appendChild(sesi);
      }

      var price = document.createElement('span');
      if (soldOut) {
        price.className = 'xcard-price soldout';
        price.textContent = fmtRupiah(ev.harga_mulai) + ' (Habis)';
      } else {
        price.className = 'xcard-price' + (free ? ' free' : '');
        price.textContent = free ? 'GRATIS' : fmtRupiah(ev.harga_mulai);
      }
      body.appendChild(price);

      var org = document.createElement('span');
      org.className = 'xcard-org';
      var ava = document.createElement('span');
      ava.className = 'org-ava';
      ava.textContent = initialsOf(ev.penyelenggara);
      var oname = document.createElement('span');
      oname.className = 'org-name';
      oname.textContent = ev.penyelenggara;
      var ocity = document.createElement('span');
      ocity.className = 'org-city';
      ocity.textContent = ev.kota;
      org.appendChild(ava);
      org.appendChild(oname);
      org.appendChild(ocity);
      body.appendChild(org);

      card.appendChild(banner);
      card.appendChild(body);
      card.addEventListener('click', function () {
        detailReturn = 'explore';
        openDetailById(ev.id);
      });
      exploreGrid.appendChild(card);
    });

    exploreEmpty.hidden = list.length !== 0;
    var countLabel = 'Menampilkan ' + list.length + ' event';
    if (exploreState.cat !== 'Semua') countLabel += ' kategori ' + exploreState.cat;
    if (exploreState.q.trim() !== '') countLabel += ' untuk pencarian "' + exploreState.q.trim() + '"';
    exploreCount.textContent = countLabel;

    activeChip.hidden = exploreState.cat === 'Semua';
    if (exploreState.cat !== 'Semua') activeChipName.textContent = exploreState.cat;

    document.querySelectorAll('.fchip').forEach(function (chip) {
      chip.classList.toggle('on', chip.getAttribute('data-fcat') === exploreState.cat);
    });

    if (exploreSearchClear) {
      exploreSearchClear.hidden = exploreState.q.trim() === '';
    }
  }

  function openExplore(cat, query) {
    stopTimer();
    if (typeof cat === 'string' && cat !== '') {
      exploreState.cat = cat;
    }
    if (typeof query === 'string') {
      exploreState.q = query;
      if (exploreSearchInput) exploreSearchInput.value = query;
      if (navSearchInput) navSearchInput.value = query;
    }
    renderExplore();
    landingSections.forEach(function (s) { s.style.display = 'none'; });
    hideAllSubPages();
    exploreSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeExplore() {
    exploreSection.hidden = true;
    landingSections.forEach(function (s) { s.style.display = ''; });
    document.getElementById('kategori').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.querySelectorAll('.cat').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openExplore(btn.getAttribute('data-cat'), exploreState.q);
    });
  });

  var btnExploreAll = document.getElementById('btnExploreAll');
  if (btnExploreAll) {
    btnExploreAll.addEventListener('click', function () {
      exploreState.sort = 'tanggal';
      if (sortItems) {
        sortItems.forEach(function (si) {
          var isCur = (si.getAttribute('data-sort') === 'tanggal');
          si.classList.toggle('active', isCur);
          si.setAttribute('aria-selected', isCur ? 'true' : 'false');
        });
      }
      openExplore('Semua', '');
    });
  }

  document.getElementById('btnBackExplore').addEventListener('click', closeExplore);

  btnFilter.addEventListener('click', function () {
    var open = filterPanel.hidden;
    filterPanel.hidden = !open;
    btnFilter.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open && typeof closeSortMenu === 'function') {
      closeSortMenu();
    }
  });

  document.querySelectorAll('.fchip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      exploreState.cat = chip.getAttribute('data-fcat');
      renderExplore();
    });
  });

  document.getElementById('chipClear').addEventListener('click', function () {
    exploreState.cat = 'Semua';
    renderExplore();
  });

  function openSortMenu() {
    if (!sortMenu || !btnSort) return;
    sortMenu.removeAttribute('hidden');
    btnSort.setAttribute('aria-expanded', 'true');
  }

  function closeSortMenu() {
    if (!sortMenu || !btnSort) return;
    sortMenu.setAttribute('hidden', '');
    btnSort.setAttribute('aria-expanded', 'false');
  }

  if (btnSort && sortMenu) {
    btnSort.addEventListener('click', function (e) {
      e.stopPropagation();
      var isExpanded = btnSort.getAttribute('aria-expanded') === 'true';
      if (isExpanded) {
        closeSortMenu();
      } else {
        if (filterPanel && !filterPanel.hidden) {
          filterPanel.hidden = true;
          if (btnFilter) btnFilter.setAttribute('aria-expanded', 'false');
        }
        openSortMenu();
      }
    });

    sortItems.forEach(function (item) {
      item.addEventListener('click', function (e) {
        e.stopPropagation();
        var sortVal = item.getAttribute('data-sort');
        if (!sortVal) return;
        exploreState.sort = sortVal;
        sortItems.forEach(function (si) {
          var isCur = (si.getAttribute('data-sort') === sortVal);
          si.classList.toggle('active', isCur);
          si.setAttribute('aria-selected', isCur ? 'true' : 'false');
        });
        closeSortMenu();
        renderExplore();
      });
    });

    document.addEventListener('click', function (e) {
      if (!btnSort.contains(e.target) && !sortMenu.contains(e.target)) {
        closeSortMenu();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSortMenu();
    });
  }

  if (navSearchInput) {
    navSearchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        openExplore(exploreState.cat, navSearchInput.value);
      }
    });
    navSearchInput.addEventListener('input', function () {
      if (!exploreSection.hidden) {
        exploreState.q = navSearchInput.value;
        if (exploreSearchInput) exploreSearchInput.value = navSearchInput.value;
        renderExplore();
      }
    });
  }

  if (exploreSearchInput) {
    exploreSearchInput.addEventListener('input', function () {
      exploreState.q = exploreSearchInput.value;
      if (navSearchInput) navSearchInput.value = exploreSearchInput.value;
      renderExplore();
    });
  }

  if (exploreSearchClear) {
    exploreSearchClear.addEventListener('click', function () {
      exploreState.q = '';
      if (exploreSearchInput) exploreSearchInput.value = '';
      if (navSearchInput) navSearchInput.value = '';
      renderExplore();
    });
  }

  /* Customer Auth (Locked Modal UI) */
  var authDlg = document.getElementById('authDlg');
  var btnMasuk = document.getElementById('btnMasuk');
  var userChip = document.getElementById('userChip');
  var loginForm = document.getElementById('loginForm');
  var registerForm = document.getElementById('registerForm');
  var loginErr = document.getElementById('loginErr');
  var loginOk = document.getElementById('loginOk');
  var signupErr = document.getElementById('signupErr');

  function showMsg(el, msg) {
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
  }

  function showAuth(mode) {
    var isLogin = mode === 'login';
    document.getElementById('loginCard').classList.toggle('off', !isLogin);
    document.getElementById('signupCard').classList.toggle('off', isLogin);
    showMsg(loginErr, '');
    showMsg(loginOk, '');
    showMsg(signupErr, '');
    var input = document.getElementById(isLogin ? 'loginEmail' : 'regEmail');
    setTimeout(function () { if (input) input.focus({ preventScroll: true }); }, 60);
  }

  function openAuth() {
    showAuth('login');
    if (typeof authDlg.showModal === 'function' && !authDlg.open) authDlg.showModal();
  }

  var mobileMasuk = document.getElementById('mobileMasuk');
  var mobileKeluar = document.getElementById('mobileKeluar');

  function applyCustomerSession(cust) {
    currentCustomer = cust;
    if (cust && cust.nama) {
      document.getElementById('userName').textContent = cust.nama;
      document.getElementById('userAvatar').textContent = (cust.nama.trim().charAt(0) || 'P').toUpperCase();
      btnMasuk.style.display = 'none';
      userChip.classList.add('show');
      if (mobileMasuk) mobileMasuk.style.display = 'none';
      if (mobileKeluar) {
        mobileKeluar.style.display = 'block';
        var mobName = document.getElementById('mobileUserName');
        if (mobName) mobName.textContent = cust.nama;
      }
    } else {
      userChip.classList.remove('show');
      btnMasuk.style.display = '';
      if (mobileMasuk) mobileMasuk.style.display = '';
      if (mobileKeluar) mobileKeluar.style.display = 'none';
    }
  }

  applyCustomerSession(currentCustomer);

  btnMasuk.addEventListener('click', openAuth);
  if (mobileMasuk) {
    mobileMasuk.addEventListener('click', function (e) {
      e.preventDefault();
      if (mobileMenu) mobileMenu.classList.remove('open');
      if (burger) burger.setAttribute('aria-expanded', 'false');
      openAuth();
    });
  }
  if (mobileKeluar) {
    mobileKeluar.addEventListener('click', function (e) {
      e.preventDefault();
      if (mobileMenu) mobileMenu.classList.remove('open');
      if (burger) burger.setAttribute('aria-expanded', 'false');
      apiPost('/api/customer/logout', {}).then(function () {
        applyCustomerSession(null);
        showToast('Kamu sudah keluar. Sampai jumpa!');
      });
    });
  }
  document.getElementById('authClose').addEventListener('click', function () { authDlg.close(); });
  document.getElementById('toSignup').addEventListener('click', function () { showAuth('signup'); });
  document.getElementById('toLogin').addEventListener('click', function () { showAuth('login'); });

  document.getElementById('btnKeluar').addEventListener('click', function () {
    apiPost('/api/customer/logout', {}).then(function () {
      applyCustomerSession(null);
      showToast('Kamu sudah keluar. Sampai jumpa!');
    });
  });

  document.querySelectorAll('[data-eye]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-eye'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('showing', show);
      btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
      input.focus({ preventScroll: true });
    });
  });

  loginForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('loginEmail').value.trim();
    var pass = document.getElementById('loginPass').value;
    showMsg(loginOk, '');
    if (!email) { showMsg(loginErr, 'Email atau username belum diisi.'); return; }
    if (!pass) { showMsg(loginErr, 'Kata sandi belum diisi.'); return; }
    showMsg(loginErr, '');

    apiPost('/api/customer/login', { email: email, password: pass }).then(function (res) {
      if (!res.body || !res.body.ok) {
        showMsg(loginErr, (res.body && res.body.message) || 'Gagal masuk. Periksa email dan kata sandi.');
        return;
      }

      // Redirect Admin & Staff according to role
      if (res.body.redirect) {
        authDlg.close();
        showToast(res.body.message || 'Login berhasil.');
        window.location.href = res.body.redirect;
        return;
      }

      // Customer session on landing page
      applyCustomerSession(res.body.customer);
      authDlg.close();
      showToast('Selamat datang kembali, ' + (res.body.customer ? res.body.customer.nama : 'Pengguna') + '!');
    });
  });

  registerForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('regEmail').value.trim();
    var pass = document.getElementById('regPass').value;
    var pass2 = document.getElementById('regPass2').value;
    if (!validEmail(email)) { showMsg(signupErr, 'Masukkan alamat email yang valid.'); return; }
    if (pass.length < 6) { showMsg(signupErr, 'Kata sandi minimal 6 karakter.'); return; }
    if (pass !== pass2) { showMsg(signupErr, 'Konfirmasi kata sandi tidak sama.'); return; }
    showMsg(signupErr, '');

    apiPost('/api/customer/register', {
      email: email,
      password: pass,
      password_confirm: pass2
    }).then(function (res) {
      if (!res.body || !res.body.ok) {
        showMsg(signupErr, (res.body && res.body.message) || 'Pendaftaran gagal.');
        return;
      }
      applyCustomerSession(res.body.customer);
      authDlg.close();
      showToast('Akun berhasil dibuat. Selamat datang, ' + res.body.customer.nama + '!');
    });
  });

  /* Detail Event */
  var currentEvent = catalog[0] || null;
  var detailReturn = 'home';

  function findEventById(id) {
    var numId = Number(id);
    for (var i = 0; i < catalog.length; i++) {
      if (Number(catalog[i].id) === numId) return catalog[i];
    }
    return catalog[0] || null;
  }

  function openDetailById(eventId) {
    var ev = findEventById(eventId);
    if (!ev) return;
    currentEvent = ev;

    document.getElementById('detailCatName').textContent = ev.kategori_nama || 'Event';
    document.getElementById('detailTitle').textContent = ev.nama;
    document.getElementById('detailOrgLogo').textContent = initialsOf(ev.penyelenggara);
    document.getElementById('detailOrg').textContent = ev.penyelenggara;

    // Deskripsi paragraphs
    var descEl = document.getElementById('detailDesc');
    descEl.innerHTML = '';
    String(ev.deskripsi || '').split(/\n+/).forEach(function (para) {
      if (para.trim() === '') return;
      var p = document.createElement('p');
      p.textContent = para.trim();
      descEl.appendChild(p);
    });

    // Syarat & Ketentuan list
    var snkList = document.getElementById('detailSnkList');
    snkList.innerHTML = '';
    String(ev.syarat_ketentuan || '').split(/\n+/).forEach(function (line) {
      var cleaned = line.replace(/^\d+\.\s*/, '').trim();
      if (!cleaned) return;
      var li = document.createElement('li');
      li.textContent = cleaned;
      snkList.appendChild(li);
    });

    var bannerHtml = detailBannerSvg(ev);
    document.getElementById('detailBannerBox').innerHTML = bannerHtml;
    document.getElementById('ticketBannerBox').innerHTML = bannerHtml;
    document.getElementById('ticketDescBox').textContent = String(ev.deskripsi || '').split(/\n+/)[0] || '';

    document.getElementById('detailDate').textContent = ev.tanggal_label;
    document.getElementById('detailTime').textContent = ev.jam_label;
    document.getElementById('detailLoc').textContent = ev.venue + ', ' + ev.kota;

    var free = Number(ev.harga_mulai) === 0;
    var soldOut = Number(ev.total_stok) < 1;
    var priceEl = document.getElementById('detailPrice');
    priceEl.textContent = free ? 'GRATIS' : fmtRupiah(ev.harga_mulai);
    priceEl.classList.toggle('free', free);

    var btnPesanDetail = document.getElementById('btnPesanDetail');
    btnPesanDetail.disabled = soldOut;
    btnPesanDetail.textContent = soldOut ? 'Tiket Habis' : 'Pesan Sekarang';

    landingSections.forEach(function (s) { s.style.display = 'none'; });
    hideAllSubPages();
    detailSection.hidden = false;
    switchTab('deskripsi', false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeDetail() {
    detailSection.hidden = true;
    if (detailReturn === 'explore') {
      exploreSection.hidden = false;
      renderExplore();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
    landingSections.forEach(function (s) { s.style.display = ''; });
    document.getElementById('rekomendasi').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.querySelectorAll('.card[data-event-id]').forEach(function (card) {
    card.addEventListener('click', function () {
      detailReturn = 'home';
      openDetailById(card.getAttribute('data-event-id'));
    });
  });

  var btnHeroOrder = document.getElementById('btnHeroOrder');
  if (btnHeroOrder) {
    btnHeroOrder.addEventListener('click', function (e) {
      e.preventDefault();
      detailReturn = 'home';
      openDetailById(btnHeroOrder.getAttribute('data-event-id'));
    });
  }

  document.getElementById('btnBack').addEventListener('click', closeDetail);

  var detailTabs = document.querySelectorAll('.detail-tab');
  function switchTab(name, scroll) {
    detailTabs.forEach(function (t) {
      var on = t.getAttribute('data-tab') === name;
      t.classList.toggle('active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    var panelDesc = document.getElementById('panelDeskripsi');
    var panelSyarat = document.getElementById('panelSyarat');
    if (name === 'syarat') {
      panelDesc.hidden = true;
      panelSyarat.hidden = false;
    } else {
      panelDesc.hidden = false;
      panelSyarat.hidden = true;
    }
    if (scroll) {
      var target = name === 'syarat' ? panelSyarat : panelDesc;
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
  detailTabs.forEach(function (t) {
    t.addEventListener('click', function () { switchTab(t.getAttribute('data-tab'), false); });
  });

  /* Langkah 1 Checkout - Pilih Kategori */
  var ticketCategoryList = document.getElementById('ticketCategoryList');
  var btnBeli = document.getElementById('btnBeli');
  var summaryPrice = document.getElementById('summaryPrice');
  var summaryCat = document.getElementById('summaryCat');
  var summaryQtyWrap = document.getElementById('summaryQtyWrap');
  var summaryQtyVal = document.getElementById('summaryQtyVal');
  var summaryQtyMinus = document.getElementById('summaryQtyMinus');
  var summaryQtyPlus = document.getElementById('summaryQtyPlus');
  var selectedTc = null;
  var selectedTicketQty = 1;
  var checkoutToken = '';

  function updateSummaryQtyControls() {
    if (!summaryQtyWrap || !summaryQtyVal) return;
    if (selectedTc) {
      summaryQtyWrap.style.display = 'flex';
      summaryQtyVal.textContent = String(selectedTicketQty);
      var maxQ = Math.min(4, Number(selectedTc.stok_sisa));
      if (summaryQtyMinus) summaryQtyMinus.disabled = selectedTicketQty <= 1;
      if (summaryQtyPlus) summaryQtyPlus.disabled = selectedTicketQty >= maxQ;
      var isFree = Number(selectedTc.harga) === 0;
      summaryPrice.textContent = isFree ? 'GRATIS' : fmtRupiah(Number(selectedTc.harga) * selectedTicketQty);
      summaryPrice.classList.toggle('free', isFree);
    } else {
      summaryQtyWrap.style.display = 'none';
      selectedTicketQty = 1;
    }
    if (typeof updateMobileBar === 'function') updateMobileBar();
  }

  if (summaryQtyMinus) {
    summaryQtyMinus.addEventListener('click', function () {
      if (selectedTicketQty > 1) {
        selectedTicketQty--;
        updateSummaryQtyControls();
      }
    });
  }
  if (summaryQtyPlus) {
    summaryQtyPlus.addEventListener('click', function () {
      var maxQ = selectedTc ? Math.min(4, Number(selectedTc.stok_sisa)) : 4;
      if (selectedTicketQty < maxQ) {
        selectedTicketQty++;
        updateSummaryQtyControls();
      } else {
        showToast(maxQ < 4 ? ('Sisa stok kategori ini hanya ' + maxQ + ' tiket.') : 'Maksimal pembelian adalah 4 tiket dalam 1 kali transaksi.');
      }
    });
  }

  function renderTicketCategories() {
    ticketCategoryList.innerHTML = '';
    selectedTc = null;
    btnBeli.disabled = true;
    summaryCat.textContent = '';
    summaryCat.classList.remove('show');

    var freeEvent = Number(currentEvent.harga_mulai) === 0;
    summaryPrice.textContent = freeEvent ? 'GRATIS' : fmtRupiah(currentEvent.harga_mulai);
    summaryPrice.classList.toggle('free', freeEvent);

    var tcs = Array.isArray(currentEvent.ticket_categories) ? currentEvent.ticket_categories : [];
    tcs.forEach(function (tc) {
      var available = Number(tc.stok_sisa) >= 1;
      var isFree = Number(tc.harga) === 0;

      var card = document.createElement('div');
      card.className = 'ticket-card' + (available ? '' : ' soldout');
      card.setAttribute('data-tc-id', tc.id);

      var badgesRow = document.createElement('div');
      badgesRow.className = 'ticket-badges';
      var statusBadge = document.createElement('span');
      statusBadge.className = available ? 'badge-sale' : 'badge-habis';
      statusBadge.textContent = available ? 'On Sale' : 'Habis';
      var stockSpan = document.createElement('span');
      stockSpan.className = 'ticket-stock';
      stockSpan.textContent = available ? ('Tersedia ' + tc.stok_sisa + ' tiket') : 'Stok Habis';
      badgesRow.appendChild(statusBadge);
      badgesRow.appendChild(stockSpan);

      var nameP = document.createElement('p');
      nameP.className = 'ticket-name';
      nameP.textContent = tc.nama;

      var foot = document.createElement('div');
      foot.className = 'ticket-foot';
      var priceSpan = document.createElement('span');
      priceSpan.className = 'ticket-price' + (isFree ? ' free' : '');
      priceSpan.textContent = isFree ? 'GRATIS' : fmtRupiah(tc.harga);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-pilih';
      btn.disabled = !available;
      btn.textContent = available ? 'Pilih' : 'Habis';
      btn.setAttribute('aria-pressed', 'false');

      btn.addEventListener('click', function () {
        if (!available) return;
        if (selectedTc && Number(selectedTc.id) === Number(tc.id)) {
          selectedTc = null;
        } else {
          selectedTc = tc;
        }
        updateTicketCategorySelection();
      });

      foot.appendChild(priceSpan);
      foot.appendChild(btn);
      card.appendChild(badgesRow);
      card.appendChild(nameP);
      card.appendChild(foot);
      ticketCategoryList.appendChild(card);
    });
  }

  function updateTicketCategorySelection() {
    var cards = ticketCategoryList.querySelectorAll('.ticket-card');
    cards.forEach(function (card) {
      var id = Number(card.getAttribute('data-tc-id'));
      var isSel = selectedTc && Number(selectedTc.id) === id;
      card.classList.toggle('selected', !!isSel);
      var btn = card.querySelector('.btn-pilih');
      if (btn && !btn.disabled) {
        btn.textContent = isSel ? 'Terpilih' : 'Pilih';
        btn.setAttribute('aria-pressed', isSel ? 'true' : 'false');
      }
    });

    if (selectedTc) {
      selectedTicketQty = 1;
      updateSummaryQtyControls();
      var isFree = Number(selectedTc.harga) === 0;
      summaryPrice.textContent = isFree ? 'GRATIS' : fmtRupiah(selectedTc.harga);
      summaryPrice.classList.toggle('free', isFree);
      summaryCat.textContent = 'Kategori dipilih: ' + selectedTc.nama + ' (Sisa ' + selectedTc.stok_sisa + ' tiket)';
      summaryCat.classList.add('show');
      btnBeli.disabled = false;
    } else {
      updateSummaryQtyControls();
      var freeEvent = Number(currentEvent.harga_mulai) === 0;
      summaryPrice.textContent = freeEvent ? 'GRATIS' : fmtRupiah(currentEvent.harga_mulai);
      summaryPrice.classList.toggle('free', freeEvent);
      summaryCat.textContent = '';
      summaryCat.classList.remove('show');
      btnBeli.disabled = true;
    }
  }

  function openTicket() {
    if (!currentEvent) return;
    document.getElementById('ticketPageTitle').textContent = 'Pilih Kategori Tiket ' + currentEvent.nama;
    renderTicketCategories();
    detailSection.hidden = true;
    ticketSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeTicket() {
    ticketSection.hidden = true;
    detailSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.getElementById('btnPesanDetail').addEventListener('click', openTicket);
  document.getElementById('btnBackTicket').addEventListener('click', closeTicket);

  function startCheckout(qtyToRequest) {
    if (!selectedTc) return;
    var reqQty = qtyToRequest || selectedTicketQty || 1;
    btnBeli.disabled = true;
    apiPost('/api/checkout/start', { ticket_category_id: selectedTc.id, jumlah: reqQty }).then(function (res) {
      btnBeli.disabled = false;
      if (!res.body || !res.body.ok) {
        var msg = (res.body && res.body.message) || 'Stok tiket tidak mencukupi.';
        var sisa = res.body && typeof res.body.sisa === 'number' ? res.body.sisa : 0;
        if (sisa > 0) {
          var offer = confirm(msg + '\n\nApakah Anda ingin membeli ' + sisa + ' tiket yang masih tersisa?');
          if (offer) {
            selectedTicketQty = sisa;
            updateSummaryQtyControls();
            startCheckout(sisa);
            return;
          }
        }
        showToast(msg);
        refreshCatalogFromServer();
        return;
      }
      selectedTc.stok_sisa = Number(res.body.ticket_category.stok_sisa);
      checkoutToken = res.body.checkout_token || '';
      var rem = Number(res.body.remaining_seconds || 900);
      var grantedQty = Number(res.body.jumlah || reqQty);
      openOrder(rem, grantedQty);
    });
  }

  btnBeli.addEventListener('click', function () {
    startCheckout(selectedTicketQty);
  });

  function refreshCatalogFromServer() {
    fetch('/api/events').then(function (r) { return r.json(); }).then(function (data) {
      if (data && data.ok && Array.isArray(data.events)) {
        catalog = data.events;
        if (currentEvent) {
          currentEvent = findEventById(currentEvent.id);
          if (!ticketSection.hidden) {
            renderTicketCategories();
          }
        }
      }
    }).catch(function () {});
  }

  /* Langkah 2 Checkout - Detail Pesanan (Dynamic N Forms + 10-Min Timer) */
  var currentQty = 1;
  var unitPrice = 0;
  var orderTimer = null;
  var orderSeconds = 0;
  var timerText = document.getElementById('timerText');
  var payTimerText = document.getElementById('payTimerText');
  var rincianQty = document.getElementById('rincianQty');
  var rincianLine = document.getElementById('rincianLine');
  var rincianSubtotal = document.getElementById('rincianSubtotal');
  var rincianTotal = document.getElementById('rincianTotal');
  var qtyVal = document.getElementById('qtyVal');
  var qtyMinus = document.getElementById('qtyMinus');
  var qtyPlus = document.getElementById('qtyPlus');
  var attendeesContainer = document.getElementById('attendeesContainer');
  var syncOn = false;
  var attendeeDrafts = {};

  function priceText(amount) {
    return (selectedTc && Number(selectedTc.harga) === 0) ? 'GRATIS' : fmtRupiah(amount);
  }

  function setGenderRowValue(gRow, val) {
    if (!gRow) return;
    var cleanVal = val || '';
    gRow.setAttribute('data-gender-value', cleanVal);
    gRow.querySelectorAll('.gender-chip').forEach(function (c) {
      var on = cleanVal !== '' && c.getAttribute('data-gender') === cleanVal;
      c.classList.toggle('active', on);
      c.classList.toggle('on', on);
      c.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    if (cleanVal !== '') {
      gRow.classList.remove('invalid');
    }
  }

  function saveAttendeeDraftFromDom(index) {
    var nameEl = document.getElementById('attName_' + index);
    var idNumEl = document.getElementById('attIdNum_' + index);
    var emailEl = document.getElementById('attEmail_' + index);
    var waEl = document.getElementById('attWa_' + index);
    var gRow = document.getElementById('genderRow_' + index);
    if (!nameEl && !idNumEl && !emailEl && !waEl && !gRow) return;
    attendeeDrafts[index] = {
      nama: nameEl ? nameEl.value : '',
      nik: idNumEl ? idNumEl.value : '',
      email: emailEl ? emailEl.value : '',
      wa: waEl ? waEl.value : '',
      gender: gRow ? (gRow.getAttribute('data-gender-value') || '') : ''
    };
  }

  function applySyncToAllAttendees() {
    if (!syncOn) return;
    var extraCount = Math.max(0, currentQty - 1);
    var bGenderRow = document.getElementById('buyerGenderRow');
    var bGenderVal = bGenderRow ? (bGenderRow.getAttribute('data-gender-value') || '') : '';

    for (var i = 0; i < extraCount; i++) {
      var pairs = [
        ['buyerName', 'attName_' + i],
        ['buyerIdType', 'attIdType_' + i],
        ['buyerIdNum', 'attIdNum_' + i],
        ['buyerEmail', 'attEmail_' + i],
        ['buyerWa', 'attWa_' + i]
      ];
      pairs.forEach(function (pair) {
        var buyerEl = document.getElementById(pair[0]);
        var attEl = document.getElementById(pair[1]);
        if (!buyerEl || !attEl) return;
        attEl.value = buyerEl.value;
        attEl.classList.remove('invalid');
        attEl.disabled = false;
      });

      var aGenderRow = document.getElementById('genderRow_' + i);
      if (aGenderRow) {
        setGenderRowValue(aGenderRow, bGenderVal);
        aGenderRow.querySelectorAll('.gender-chip').forEach(function (chip) {
          chip.disabled = false;
        });
      }
      saveAttendeeDraftFromDom(i);
    }
  }

  /* Wire Buyer input restrictions & gender chips */
  var buyerIdNumInput = document.getElementById('buyerIdNum');
  if (buyerIdNumInput) {
    buyerIdNumInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 16);
      this.classList.remove('invalid');
      if (syncOn) applySyncToAllAttendees();
    });
  }

  var buyerWaInput = document.getElementById('buyerWa');
  if (buyerWaInput) {
    buyerWaInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '');
      this.classList.remove('invalid');
      if (syncOn) applySyncToAllAttendees();
    });
  }

  var buyerGenderRow = document.getElementById('buyerGenderRow');
  if (buyerGenderRow) {
    var bgChips = buyerGenderRow.querySelectorAll('.gender-chip');
    bgChips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var val = chip.getAttribute('data-gender');
        setGenderRowValue(buyerGenderRow, val);
        if (syncOn) applySyncToAllAttendees();
      });
    });
  }

  ['buyerName', 'buyerEmail'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', function () {
        el.classList.remove('invalid');
        if (syncOn) applySyncToAllAttendees();
      });
      el.addEventListener('change', function () {
        el.classList.remove('invalid');
        if (syncOn) applySyncToAllAttendees();
      });
    }
  });

  /* Attendee Card (Form pemegang tiket tambahan: 1..(currentQty - 1) saat currentQty >= 2) */
  function createAttendeeCard(index) {
    var num = index + 1;
    var isFirst = index === 0;
    var catName = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';

    var card = document.createElement('div');
    card.className = 'order-card attendee-card';
    card.setAttribute('data-att-index', String(index));

    var syncHtml = isFirst
      ? '<div class="sync-wrap">'
        + '<span>Samakan dengan<br />detail pemesan</span>'
        + '<button class="switch" id="syncSwitch" type="button" role="switch" aria-checked="' + (syncOn ? 'true' : 'false') + '" aria-label="Samakan dengan detail pemesan"></button>'
        + '</div>'
      : '';

    card.innerHTML =
      '<div class="order-card-head">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>'
      + '<h3>Detail Pesanan - ' + num + '</h3>'
      + syncHtml
      + '</div>'
      + '<p class="att-band">' + escapeXml(catName) + '</p>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attName_' + index + '">Nama Lengkap <span class="req">*</span></label>'
      + '<input class="o-input att-name" id="attName_' + index + '" type="text" placeholder="Masukkan nama lengkap pemegang tiket" />'
      + '</div>'
      + '<div class="o-row">'
      + '<div class="o-field">'
      + '<label class="o-label" for="attIdType_' + index + '">Tipe Identitas <span class="req">*</span></label>'
      + '<input class="o-input o-readonly-static att-idtype" id="attIdType_' + index + '" type="text" value="KTP" readonly aria-readonly="true" />'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attIdNum_' + index + '">Nomor Identitas (KTP/NIK) <span class="req">*</span></label>'
      + '<input class="o-input att-idnum" id="attIdNum_' + index + '" type="text" inputmode="numeric" maxlength="16" placeholder="Masukkan 16 digit NIK KTP pemegang tiket" />'
      + '</div>'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attEmail_' + index + '">Email <span class="req">*</span></label>'
      + '<input class="o-input att-email" id="attEmail_' + index + '" type="email" placeholder="nama@gmail.com" />'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attWa_' + index + '">No. WhatsApp <span class="req">*</span></label>'
      + '<div class="phone-group">'
      + '<span class="phone-prefix">'
      + '<svg viewBox="0 0 19 13" aria-hidden="true"><rect width="19" height="6.5" fill="#e70011"/><rect y="6.5" width="19" height="6.5" fill="#f4f4f4"/></svg>'
      + '+62'
      + '</span>'
      + '<input class="o-input att-wa" id="attWa_' + index + '" type="tel" inputmode="numeric" placeholder="81234567890" />'
      + '</div>'
      + '</div>'
      + '<div class="o-field">'
      + '<span class="o-label" id="genderLabel_' + index + '">Jenis Kelamin <span class="req">*</span></span>'
      + '<div class="gender-row att-gender-row" id="genderRow_' + index + '" data-gender-value="" role="group" aria-labelledby="genderLabel_' + index + '">'
      + '<button class="gender-chip" type="button" data-gender="Laki-laki" aria-pressed="false">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="14" r="5"/><path d="M14 10 20 4M15 4h5v5"/></svg>'
      + 'Laki-laki'
      + '</button>'
      + '<button class="gender-chip" type="button" data-gender="Perempuan" aria-pressed="false">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="9" r="5"/><path d="M12 14v7M8.5 17.5h7"/></svg>'
      + 'Perempuan'
      + '</button>'
      + '</div>'
      + '</div>';

    // Restore any draft values for this index
    var draft = attendeeDrafts[index];
    if (draft) {
      var nEl = card.querySelector('#attName_' + index);
      var nikEl = card.querySelector('#attIdNum_' + index);
      var emEl = card.querySelector('#attEmail_' + index);
      var wEl = card.querySelector('#attWa_' + index);
      var grEl = card.querySelector('#genderRow_' + index);
      if (nEl && draft.nama) nEl.value = draft.nama;
      if (nikEl && draft.nik) nikEl.value = draft.nik;
      if (emEl && draft.email) emEl.value = draft.email;
      if (wEl && draft.wa) wEl.value = draft.wa;
      if (grEl && draft.gender) setGenderRowValue(grEl, draft.gender);
    }

    // Restrict NIK & WA to digits
    var idNumEl = card.querySelector('#attIdNum_' + index);
    if (idNumEl) {
      idNumEl.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
        this.classList.remove('invalid');
        saveAttendeeDraftFromDom(index);
      });
    }

    var waEl = card.querySelector('#attWa_' + index);
    if (waEl) {
      waEl.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        this.classList.remove('invalid');
        saveAttendeeDraftFromDom(index);
      });
    }

    card.querySelectorAll('.o-input').forEach(function (el) {
      el.addEventListener('input', function () {
        el.classList.remove('invalid');
        saveAttendeeDraftFromDom(index);
      });
      el.addEventListener('change', function () {
        el.classList.remove('invalid');
        saveAttendeeDraftFromDom(index);
      });
    });

    // Wire gender chips (tetap bisa diedit manual meskipun toggle Samakan aktif)
    var gRow = card.querySelector('.att-gender-row');
    if (gRow) {
      var gChips = gRow.querySelectorAll('.gender-chip');
      gChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          var val = chip.getAttribute('data-gender');
          setGenderRowValue(gRow, val);
          saveAttendeeDraftFromDom(index);
        });
      });
    }

    if (isFirst) {
      var sw = card.querySelector('#syncSwitch');
      if (sw) {
        sw.addEventListener('click', function () {
          syncOn = !syncOn;
          sw.setAttribute('aria-checked', syncOn ? 'true' : 'false');
          if (syncOn) {
            applySyncToAllAttendees();
          }
        });
      }
    }

    return card;
  }

  function syncAttendeeCardsCount(targetQty) {
    var existing = attendeesContainer.querySelectorAll('.attendee-card');
    // Data Pemesan = Pemegang Tiket 1; jumlah form tambahan = (targetQty - 1)
    var neededExtra = Math.max(0, targetQty - 1);

    if (neededExtra === 0) {
      for (var k = 0; k < existing.length; k++) {
        var idxAttr = Number(existing[k].getAttribute('data-att-index'));
        saveAttendeeDraftFromDom(idxAttr);
      }
      attendeesContainer.innerHTML = '';
      return;
    }

    var existingCount = existing.length;
    if (existingCount < neededExtra) {
      for (var i = existingCount; i < neededExtra; i++) {
        var hadDraft = !!attendeeDrafts[i];
        attendeesContainer.appendChild(createAttendeeCard(i));
        if (syncOn && !hadDraft) {
          applySyncToAllAttendees();
        }
      }
    } else if (existingCount > neededExtra) {
      for (var j = existingCount - 1; j >= neededExtra; j--) {
        saveAttendeeDraftFromDom(j);
        attendeesContainer.removeChild(existing[j]);
      }
    }

    // Update category band labels
    var catName = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';
    attendeesContainer.querySelectorAll('.att-band').forEach(function (b) {
      b.textContent = catName;
    });
  }

  function updateRincian() {
    var total = unitPrice * currentQty;
    var isFree = selectedTc && Number(selectedTc.harga) === 0;
    document.getElementById('rincianCatName').textContent = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';
    rincianQty.textContent = 'x' + currentQty;
    rincianLine.textContent = priceText(total);
    rincianLine.classList.toggle('free', !!isFree);
    rincianSubtotal.textContent = priceText(total);
    rincianTotal.textContent = priceText(total);
    rincianTotal.classList.toggle('free', !!isFree);
    qtyVal.textContent = currentQty;
    qtyMinus.disabled = currentQty <= 1;
    qtyPlus.disabled = currentQty >= maxAllowedQty();
    syncAttendeeCardsCount(currentQty);
  }

  function maxAllowedQty() {
    var stok = selectedTc ? Number(selectedTc.stok_sisa) : 4;
    return Math.max(1, Math.min(4, currentQty + stok));
  }

  function setQty(n) {
    if (n > 4) {
      showToast('Maksimal pembelian adalah 4 tiket dalam 1 kali transaksi.');
      return;
    }
    if (n < 1) return;
    if (n === currentQty) return;

    qtyMinus.disabled = true;
    qtyPlus.disabled = true;
    apiPost('/api/checkout/update-qty', {
      ticket_category_id: selectedTc ? selectedTc.id : 0,
      checkout_token: checkoutToken,
      jumlah: n
    }).then(function (res) {
      qtyMinus.disabled = false;
      qtyPlus.disabled = false;
      if (!res.body || !res.body.ok) {
        showToast((res.body && res.body.message) || 'Tidak dapat mengubah jumlah tiket.');
        if (res.body && res.body.expired) {
          closeOrder(true);
        }
        return;
      }
      var prevQty = currentQty;
      currentQty = Number(res.body.jumlah || n);
      if (selectedTc && typeof selectedTc.stok_sisa !== 'undefined') {
        selectedTc.stok_sisa = Math.max(0, Number(selectedTc.stok_sisa) - (currentQty - prevQty));
      }
      updateRincian();
    });
  }

  qtyMinus.addEventListener('click', function () { setQty(currentQty - 1); });
  qtyPlus.addEventListener('click', function () { setQty(currentQty + 1); });

  function renderTimer() {
    var m = Math.floor(orderSeconds / 60);
    var s = orderSeconds % 60;
    var text = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    timerText.textContent = text;
    payTimerText.textContent = text;
  }

  function stopTimer() {
    if (orderTimer) {
      clearInterval(orderTimer);
      orderTimer = null;
    }
  }

  function startTimer(durationSec) {
    stopTimer();
    orderSeconds = durationSec || (15 * 60);
    renderTimer();
    orderTimer = setInterval(function () {
      orderSeconds--;
      if (orderSeconds <= 0) {
        orderSeconds = 0;
        renderTimer();
        stopTimer();
        if (confirmDlg.open) confirmDlg.close();
        showToast('Waktu pemesanan habis. Silakan ulangi pemesanan.');
        closeOrder(true);
        return;
      }
      renderTimer();
    }, 1000);
  }

  function openOrder(durationSec, initialQty) {
    if (!currentEvent || !selectedTc) return;
    unitPrice = Number(selectedTc.harga) || 0;
    currentQty = initialQty || 1;
    syncOn = false;
    attendeeDrafts = {};
    attendeesContainer.innerHTML = '';

    document.getElementById('orderPageTitle').textContent = currentEvent.nama;
    document.getElementById('orderPageMeta').innerHTML =
      escapeXml(currentEvent.tanggal_label) + ' &bull; ' + escapeXml(currentEvent.jam_label)
      + '<br />' + escapeXml(currentEvent.venue + ', ' + currentEvent.kota);

    if (currentCustomer) {
      var bName = document.getElementById('buyerName');
      var bEmail = document.getElementById('buyerEmail');
      if (bName && !bName.value.trim()) bName.value = currentCustomer.nama || '';
      if (bEmail && !bEmail.value.trim()) bEmail.value = currentCustomer.email || '';
    }

    updateRincian();
    ticketSection.hidden = true;
    orderSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    startTimer(durationSec || 900);
  }

  function closeOrder(skipResetCall) {
    stopTimer();
    if (!skipResetCall) {
      apiPost('/api/checkout/reset', {}).catch(function () {});
    }
    selectedTicketQty = 1;
    updateSummaryQtyControls();
    orderSection.hidden = true;
    paySection.hidden = true;
    resultSection.hidden = true;
    ticketSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.getElementById('btnBackOrder').addEventListener('click', function () { closeOrder(false); });
  document.getElementById('btnRincianBack').addEventListener('click', function () { closeOrder(false); });

  /* Validasi Detail Pesanan + Lanjutkan */
  function checkField(id, ok) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('invalid', !ok);
    return { el: el, ok: ok };
  }

  document.getElementById('btnLanjut').addEventListener('click', function () {
    var results = [];
    var toastError = null;

    function val(id) {
      var el = document.getElementById(id);
      return el ? el.value.trim() : '';
    }

    // Buyer validations
    var buyerNameOk = val('buyerName') !== '';
    var buyerNikOk = validNik(val('buyerIdNum'));
    var buyerEmailOk = validGmail(val('buyerEmail'));
    var buyerWaOk = validWa(val('buyerWa'));
    var bGenderRow = document.getElementById('buyerGenderRow');
    var buyerGenderOk = bGenderRow ? (bGenderRow.getAttribute('data-gender-value') || '') !== '' : false;

    results.push(checkField('buyerName', buyerNameOk));
    results.push(checkField('buyerIdNum', buyerNikOk));
    results.push(checkField('buyerEmail', buyerEmailOk));
    results.push(checkField('buyerWa', buyerWaOk));
    if (bGenderRow) {
      bGenderRow.classList.toggle('invalid', !buyerGenderOk);
      results.push({ el: bGenderRow, ok: buyerGenderOk });
    }

    if (!buyerNikOk && !toastError) toastError = 'Nomor KTP/NIK Data Pemesan harus berupa 16 digit angka.';
    if (!buyerEmailOk && !toastError) toastError = 'Email Data Pemesan harus menggunakan alamat @gmail.com.';
    if (!buyerWaOk && !toastError) toastError = 'Nomor WhatsApp Data Pemesan harus berupa angka (minimal 9 digit).';
    if (!buyerGenderOk && !toastError) toastError = 'Jenis Kelamin Data Pemesan wajib dipilih.';

    // Attendee validations: form tambahan sebanyak (currentQty - 1) saat currentQty >= 2
    var extraCount = Math.max(0, currentQty - 1);
    for (var i = 0; i < extraCount; i++) {
      var num = i + 1;
      var attNameOk = val('attName_' + i) !== '';
      var attNikOk = validNik(val('attIdNum_' + i));
      var attEmailOk = validGmail(val('attEmail_' + i));
      var attWaOk = validWa(val('attWa_' + i));
      var aGenderRow = document.getElementById('genderRow_' + i);
      var attGenderOk = aGenderRow ? (aGenderRow.getAttribute('data-gender-value') || '') !== '' : false;

      results.push(checkField('attName_' + i, attNameOk));
      results.push(checkField('attIdNum_' + i, attNikOk));
      results.push(checkField('attEmail_' + i, attEmailOk));
      results.push(checkField('attWa_' + i, attWaOk));
      if (aGenderRow) {
        aGenderRow.classList.toggle('invalid', !attGenderOk);
        results.push({ el: aGenderRow, ok: attGenderOk });
      }

      if (!attNikOk && !toastError) toastError = 'Nomor KTP/NIK Detail Pesanan - ' + num + ' harus berupa 16 digit angka.';
      if (!attEmailOk && !toastError) toastError = 'Email Detail Pesanan - ' + num + ' harus menggunakan alamat @gmail.com.';
      if (!attWaOk && !toastError) toastError = 'No. WhatsApp Detail Pesanan - ' + num + ' harus berupa angka (minimal 9 digit).';
      if (!attGenderOk && !toastError) toastError = 'Jenis Kelamin Detail Pesanan - ' + num + ' wajib dipilih.';
    }

    var firstBad = null;
    results.forEach(function (r) {
      if (!r.ok && !firstBad && r.el) firstBad = r.el;
    });

    if (firstBad) {
      showToast(toastError || 'Masih ada data yang belum lengkap atau belum valid. Cek kolom yang ditandai merah.');
      firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (firstBad.focus) firstBad.focus({ preventScroll: true });
      return;
    }

    openConfirm();
  });

  /* Konfirmasi Pesanan Pop-up */
  var confirmDlg = document.getElementById('confirmDlg');
  function openConfirm() {
    var name = document.getElementById('buyerName').value.trim();
    var email = document.getElementById('buyerEmail').value.trim();
    var wa = waDisplay(document.getElementById('buyerWa').value);
    document.getElementById('cfName').textContent = name;
    document.getElementById('cfEmail').textContent = email;
    document.getElementById('cfWa').textContent = wa;
    document.getElementById('cfEmail2').textContent = email;
    document.getElementById('cfWa2').textContent = wa;
    if (typeof confirmDlg.showModal === 'function' && !confirmDlg.open) confirmDlg.showModal();
  }

  document.getElementById('confirmClose').addEventListener('click', function () { confirmDlg.close(); });
  document.getElementById('btnEditData').addEventListener('click', function () { confirmDlg.close(); });
  document.getElementById('btnMengerti').addEventListener('click', function () {
    confirmDlg.close();
    if (selectedTc && Number(selectedTc.harga) === 0) {
      selectedMethod = 'Tanpa pembayaran (GRATIS)';
      startPayment();
      return;
    }
    openPay();
  });

  /* Langkah 3 Checkout - Metode Pembayaran */
  var selectedMethod = '';
  var agreeTnc = document.getElementById('agreeTnc');
  var agreePrivacy = document.getElementById('agreePrivacy');
  var btnBayar = document.getElementById('btnBayar');

  function totals() {
    var subtotal = unitPrice * currentQty;
    var isFree = selectedTc && Number(selectedTc.harga) === 0;
    var fee = isFree ? 0 : Math.round(subtotal * 0.03);
    return { subtotal: subtotal, fee: fee, total: subtotal + fee, isFree: isFree };
  }

  function updatePay() {
    var t = totals();
    document.getElementById('payPageTitle').textContent = currentEvent.nama;
    document.getElementById('payPageMeta').innerHTML =
      escapeXml(currentEvent.tanggal_label) + ' &bull; ' + escapeXml(currentEvent.jam_label)
      + '<br />' + escapeXml(currentEvent.venue + ', ' + currentEvent.kota);
    document.getElementById('payCatName').textContent = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';
    document.getElementById('payQty').textContent = 'x' + currentQty;
    var line = document.getElementById('payLine');
    line.textContent = priceText(t.subtotal);
    line.classList.toggle('free', !!t.isFree);
    document.getElementById('paySubtotal').textContent = priceText(t.subtotal);
    document.getElementById('payFee').textContent = fmtRupiah(t.fee);
    var totalEl = document.getElementById('payTotal');
    totalEl.textContent = priceText(t.total);
    totalEl.classList.toggle('free', !!t.isFree);
  }

  function openPay() {
    updatePay();
    apiPost('/api/checkout/extend-payment-session', {
      checkout_token: checkoutToken
    }).then(function (res) {
      var rem = (res.body && res.body.remaining_seconds) ? Number(res.body.remaining_seconds) : 600;
      startTimer(rem);
    }).catch(function () {
      startTimer(600);
    });
    orderSection.hidden = true;
    paySection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closePay() {
    paySection.hidden = true;
    orderSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.getElementById('btnBackPay').addEventListener('click', closePay);
  document.getElementById('btnPayBack').addEventListener('click', closePay);

  function updateBayarBtn() {
    var canPay = (selectedMethod !== '' && agreeTnc.checked && agreePrivacy.checked);
    btnBayar.disabled = !canPay;
  }

  document.querySelectorAll('[data-method]').forEach(function (el) {
    el.addEventListener('click', function () {
      selectedMethod = el.getAttribute('data-method');
      document.querySelectorAll('[data-method]').forEach(function (o) {
        var on = o === el;
        o.classList.toggle('selected', on);
        o.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      updateBayarBtn();
    });
  });

  agreeTnc.addEventListener('change', updateBayarBtn);
  agreePrivacy.addEventListener('change', updateBayarBtn);

  /* Attendees Payload for Checkout Pay */
  function collectBuyerPayload() {
    var bGenderRow = document.getElementById('buyerGenderRow');
    var bGenderVal = bGenderRow ? (bGenderRow.getAttribute('data-gender-value') || 'Laki-laki') : 'Laki-laki';
    return {
      nama: document.getElementById('buyerName').value.trim(),
      tipe_identitas: 'KTP',
      nomor_identitas: document.getElementById('buyerIdNum').value.trim(),
      email: document.getElementById('buyerEmail').value.trim(),
      jenis_kelamin: bGenderVal,
      wa: waDisplay(document.getElementById('buyerWa').value)
    };
  }

  function collectAttendeesPayload() {
    var list = [];
    // Tiket ke-1 selalu dibuat dari Data Pemesan (Form 1)
    list.push(collectBuyerPayload());

    // Tiket ke-2 s/d ke-N dibuat dari form tambahan 1 s/d (N-1)
    var extraCount = Math.max(0, currentQty - 1);
    for (var i = 0; i < extraCount; i++) {
      var gRow = document.getElementById('genderRow_' + i);
      var gVal = gRow ? (gRow.getAttribute('data-gender-value') || 'Laki-laki') : 'Laki-laki';
      list.push({
        nama: document.getElementById('attName_' + i).value.trim(),
        tipe_identitas: 'KTP',
        nomor_identitas: document.getElementById('attIdNum_' + i).value.trim(),
        email: document.getElementById('attEmail_' + i).value.trim(),
        jenis_kelamin: gVal,
        wa: waDisplay(document.getElementById('attWa_' + i).value)
      });
    }

    return list;
  }

    btnBayar.addEventListener('click', function () {
    if (btnBayar.disabled) return;
    startPayment();
  });

  document.querySelectorAll('.method-head').forEach(function (head) {
    head.addEventListener('click', function () {
      var group = head.parentElement;
      var closed = group.classList.toggle('closed');
      head.setAttribute('aria-expanded', closed ? 'false' : 'true');
    });
  });

  /* Langkah 4 Checkout - Pembayaran Simulasi & Invoice + QR */
  var payProcessing = document.getElementById('payProcessing');
  var paySuccess = document.getElementById('paySuccess');
  var paySuccessDlg = document.getElementById('paySuccessDlg');
  var lastInvoice = null;

  function renderQr(el, text) {
    try {
      el.innerHTML = QrGen.svg(text, 4, 4);
      return;
    } catch (err) {}
    var span = document.createElement('span');
    span.className = 'qr-fallback';
    span.textContent = text;
    el.appendChild(span);
  }

  function etRow(label, value, cls) {
    var wrap = document.createElement('div');
    wrap.className = 'et-row';
    var k = document.createElement('p');
    k.className = 'et-k';
    k.textContent = label;
    var v = document.createElement('p');
    v.className = cls || 'et-v';
    v.textContent = value;
    wrap.appendChild(k);
    wrap.appendChild(v);
    return wrap;
  }

  function buildEticket(inv, ticketItem) {
    var card = document.createElement('div');
    card.className = 'eticket';

    var top = document.createElement('div');
    top.className = 'eticket-top';
    var ev = document.createElement('p');
    ev.className = 'et-event';
    ev.textContent = inv.event_nama;
    var cat = document.createElement('p');
    cat.className = 'et-cat';
    cat.textContent = inv.kategori_tiket_nama + ' • Tiket ' + ticketItem.nomor_urut + ' dari ' + ticketItem.total_tiket;
    top.appendChild(ev);
    top.appendChild(cat);

    var body = document.createElement('div');
    body.className = 'eticket-body';
    var info = document.createElement('div');
    info.className = 'eticket-info';
    info.appendChild(etRow('Nama Peserta', ticketItem.nama_pemegang));
    info.appendChild(etRow('Tanggal & Jam', inv.tanggal_label + ' • ' + inv.jam_label));
    info.appendChild(etRow('Lokasi', inv.venue + ', ' + inv.kota));
    info.appendChild(etRow('Kode Tiket', ticketItem.kode_tiket, 'et-code'));

    var qrBox = document.createElement('div');
    qrBox.className = 'qr-box';
    var qrRender = document.createElement('div');
    qrRender.className = 'qr-render';
    renderQr(qrRender, ticketItem.kode_tiket);
    var cap = document.createElement('p');
    cap.className = 'qr-cap';
    cap.textContent = 'Scan QR ini saat check-in';
    qrBox.appendChild(qrRender);
    qrBox.appendChild(cap);

    body.appendChild(info);
    body.appendChild(qrBox);

    var note = document.createElement('p');
    note.className = 'eticket-note';
    note.textContent = 'Tunjukkan e-tiket ini beserta identitas resmi (KTP) di pintu masuk. Staff akan memindai QR atau memasukkan kode tiket untuk check-in. Satu tiket berlaku untuk satu orang.';

    card.appendChild(top);
    card.appendChild(body);
    card.appendChild(note);
    return card;
  }

  function renderInvoiceSuccess(inv) {
    lastInvoice = inv;
    var isFree = Number(inv.total) === 0;

    document.getElementById('invNo').textContent = inv.nomor_invoice;
    document.getElementById('invDate').textContent = inv.tanggal_transaksi;
    document.getElementById('invName').textContent = inv.pemesan.nama;
    document.getElementById('invEmail').textContent = inv.pemesan.email;
    document.getElementById('invMethod').textContent = inv.metode_pembayaran;
    document.getElementById('invEventName').textContent = inv.event_nama;
    document.getElementById('invCatName').textContent = inv.kategori_tiket_nama;
    document.getElementById('invQty').textContent = 'x' + inv.jumlah;

    var sub = document.getElementById('invSubtotal');
    sub.textContent = isFree ? 'GRATIS' : fmtRupiah(inv.subtotal);
    sub.classList.toggle('free', isFree);
    document.getElementById('invFee').textContent = fmtRupiah(inv.biaya_layanan);

    var tot = document.getElementById('invTotal');
    tot.textContent = isFree ? 'GRATIS' : fmtRupiah(inv.total);
    tot.classList.toggle('free', isFree);

    var msg = document.getElementById('successMsg');
    msg.textContent = '';
    msg.appendChild(document.createTextNode('Invoice dan ' + inv.jumlah + ' e-tiket QR sudah diterbitkan untuk '));
    var strong = document.createElement('strong');
    strong.textContent = inv.pemesan.nama;
    msg.appendChild(strong);
    msg.appendChild(document.createTextNode(' serta dikirim ke email ' + inv.pemesan.email + '.'));

    var list = document.getElementById('eticketList');
    list.innerHTML = '';
    (inv.tickets || []).forEach(function (tk) {
      list.appendChild(buildEticket(inv, tk));
    });

    payProcessing.hidden = true;
    paySuccess.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (paySuccessDlg && typeof paySuccessDlg.showModal === 'function' && !paySuccessDlg.open) {
      document.getElementById('paySuccessDlgText').textContent =
        'Invoice ' + inv.nomor_invoice + ' telah lunas dan ' + inv.jumlah + ' e-tiket QR telah dikirim ke ' + inv.pemesan.email + '.';
      paySuccessDlg.showModal();
    }

    refreshCatalogFromServer();
    showToast('Pembayaran berhasil (Mode Demo). E-tiket kamu sudah terbit.');
  }

  var btnClosePaySuccessDlg = document.getElementById('btnClosePaySuccessDlg');
  if (btnClosePaySuccessDlg && paySuccessDlg) {
    btnClosePaySuccessDlg.addEventListener('click', function () {
      paySuccessDlg.close();
    });
  }

  function startPayment() {
    stopTimer();
    var t = totals();
    orderSection.hidden = true;
    paySection.hidden = true;
    resultSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    paySuccess.hidden = true;
    payProcessing.hidden = false;
    document.getElementById('procMethod').textContent = t.isFree ? 'Tanpa pembayaran (GRATIS)' : selectedMethod;
    document.getElementById('procTotal').textContent = t.isFree ? 'Rp 0' : fmtRupiah(t.total);

    var delayMs = t.isFree ? 200 : 1800;
    var startTime = Date.now();

    apiPost('/api/checkout/pay', {
      ticket_category_id: selectedTc ? selectedTc.id : 0,
      jumlah: currentQty,
      metode: t.isFree ? 'Tanpa pembayaran (GRATIS)' : selectedMethod,
      checkout_token: checkoutToken,
      buyer: collectBuyerPayload(),
      attendees: collectAttendeesPayload()
    }).then(function (res) {
      var elapsed = Date.now() - startTime;
      var waitMore = Math.max(0, delayMs - elapsed);
      setTimeout(function () {
        if (!res.body || !res.body.ok) {
          showToast((res.body && res.body.message) || 'Pembayaran gagal diproses.');
          refreshCatalogFromServer();
          closeOrder(true);
          return;
        }
        renderInvoiceSuccess(res.body.invoice);
      }, waitMore);
    });
  }

  document.getElementById('btnDownloadInvoice').addEventListener('click', function () {
    if (!lastInvoice) return;
    var inv = lastInvoice;
    var isFree = Number(inv.total) === 0;
    var codes = (inv.tickets || []).map(function (tk) {
      return tk.kode_tiket + ' (' + tk.nama_pemegang + ')';
    });
    var lines = [
      'TICKET LAND - INVOICE (SIMULASI / MODE DEMO)',
      '----------------------------------------------',
      'Nomor Invoice    : ' + inv.nomor_invoice,
      'Tanggal          : ' + inv.tanggal_transaksi,
      'Nama Pemesan     : ' + inv.pemesan.nama,
      'Email            : ' + inv.pemesan.email,
      'Metode Pembayaran: ' + inv.metode_pembayaran,
      'Status           : LUNAS',
      '----------------------------------------------',
      'Event            : ' + inv.event_nama,
      'Kategori         : ' + inv.kategori_tiket_nama,
      'Jumlah Tiket     : ' + inv.jumlah,
      'Subtotal         : ' + (isFree ? 'GRATIS' : fmtRupiah(inv.subtotal)),
      'Biaya Layanan    : ' + fmtRupiah(inv.biaya_layanan),
      'Total Bayar      : ' + (isFree ? 'GRATIS' : fmtRupiah(inv.total)),
      '----------------------------------------------',
      'Daftar E-Tiket   : ' + codes.join(', '),
      '',
      'Invoice ini diterbitkan oleh simulasi pembayaran Ticket Land',
      'untuk keperluan demo.'
    ];
    var blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = (inv.nomor_invoice || 'invoice-ticket-land') + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 500);
  });

  var btnBackToHomeFromResult = document.getElementById('btnBackToHomeFromResult');
  if (btnBackToHomeFromResult) {
    btnBackToHomeFromResult.addEventListener('click', function () {
      showHome();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* Hubungi Kami */
  function openContact() {
    console.log('openContact called!');
    stopTimer();
    landingSections.forEach(function (s) { s.style.display = 'none'; });
    hideAllSubPages();
    contactSection.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeContact() {
    console.log('closeContact called!');
    contactSection.hidden = true;
    landingSections.forEach(function (s) { s.style.display = ''; });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.querySelectorAll('.nav-contact').forEach(function (link) {
    link.addEventListener('click', function (e) {
      console.log('nav-contact clicked!');
      e.preventDefault();
      if (mobileMenu) mobileMenu.classList.remove('open');
      if (burger) burger.setAttribute('aria-expanded', 'false');
      openContact();
    });
  });

  document.getElementById('btnBackContact').addEventListener('click', closeContact);
  document.getElementById('btnHelp').addEventListener('click', openContact);

  var hubForm = document.getElementById('hubForm');
  var hubErr = document.getElementById('hubErr');
  var hubFormWrap = document.getElementById('hubFormWrap');
  var hubSuccess = document.getElementById('hubSuccess');

  ['hubName', 'hubEmail', 'hubSubject'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function () { el.classList.remove('invalid'); });
    el.addEventListener('change', function () { el.classList.remove('invalid'); });
  });

  hubForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var nameEl = document.getElementById('hubName');
    var emailEl = document.getElementById('hubEmail');
    var subjectEl = document.getElementById('hubSubject');
    var msgEl = document.getElementById('hubMsg');

    var name = nameEl.value.trim();
    var email = emailEl.value.trim();
    var subject = subjectEl.value;
    var pesan = msgEl.value.trim();

    var nameOk = name !== '';
    var emailOk = validEmail(email);
    var subjectOk = subject !== '';

    nameEl.classList.toggle('invalid', !nameOk);
    emailEl.classList.toggle('invalid', !emailOk);
    subjectEl.classList.toggle('invalid', !subjectOk);

    if (!nameOk || !emailOk || !subjectOk) {
      hubErr.textContent = (!emailOk && email !== '')
        ? 'Alamat email yang dimasukkan belum valid. Periksa lagi ya.'
        : 'Kolom bertanda * wajib diisi. Cek kolom yang ditandai merah.';
      hubErr.style.display = 'block';
      var firstBad = !nameOk ? nameEl : (!emailOk ? emailEl : subjectEl);
      firstBad.focus({ preventScroll: true });
      return;
    }

    hubErr.style.display = 'none';
    apiPost('/api/contact', {
      nama: name,
      email: email,
      subject: subject,
      pesan: pesan
    }).then(function (res) {
      if (!res.body || !res.body.ok) {
        hubErr.textContent = (res.body && res.body.message) || 'Gagal mengirim pesan.';
        hubErr.style.display = 'block';
        return;
      }
      var msg = document.getElementById('hubSuccessMsg');
      msg.textContent = '';
      msg.appendChild(document.createTextNode('Terima kasih, '));
      var strong = document.createElement('strong');
      strong.textContent = name;
      msg.appendChild(strong);
      msg.appendChild(document.createTextNode('! Pesanmu sudah kami terima dan tercatat di sistem. Tim Ticket Land akan membalas ke email ' + email + ' secepatnya.'));
      hubFormWrap.hidden = true;
      hubSuccess.hidden = false;
      showToast('Pesan berhasil dikirim. Terima kasih sudah menghubungi Ticket Land!');
    });
  });

  document.getElementById('btnHubAgain').addEventListener('click', function () {
    hubForm.reset();
    hubSuccess.hidden = true;
    hubFormWrap.hidden = false;
    document.getElementById('hubName').focus({ preventScroll: true });
  });

  /* Deep-link view support for direct navigation & verification */
  var params = new URLSearchParams(window.location.search);
  var initView = params.get('view');
  var initCat = params.get('cat');
  var initQ = params.get('q');
  var initEventId = params.get('event') || (currentEvent ? currentEvent.id : 5);
  var initQty = parseInt(params.get('qty') || '2', 10);

  if (initView === 'explore') {
    openExplore(initCat || 'Musik', initQ || '');
    if (params.get('sort')) {
      var sortParam = params.get('sort');
      exploreState.sort = sortParam;
      sortItems.forEach(function (si) {
        var isCur = (si.getAttribute('data-sort') === sortParam);
        si.classList.toggle('active', isCur);
        si.setAttribute('aria-selected', isCur ? 'true' : 'false');
      });
      renderExplore();
    }
    if (params.get('open_sort') === '1') {
      openSortMenu();
    }
  } else if (initView === 'detail') {
    openDetailById(initEventId);
  } else if (initView === 'ticket') {
    openDetailById(initEventId);
    openTicket();
    var firstChoice = ticketCategoryList.querySelector('.btn-pilih:not(:disabled)');
    if (firstChoice) firstChoice.click();
  } else if (initView === 'order') {
    openDetailById(initEventId);
    openTicket();
    var firstBtn = ticketCategoryList.querySelector('.btn-pilih:not(:disabled)');
    if (firstBtn) firstBtn.click();
    openOrder(600);
    setQty(initQty);
  } else if (initView === 'pay') {
    openDetailById(initEventId);
    openTicket();
    var firstPayBtn = ticketCategoryList.querySelector('.btn-pilih:not(:disabled)');
    if (firstPayBtn) firstPayBtn.click();
    openOrder(600);
    setQty(initQty);
    openPay();
    var qrisTile = document.querySelector('[data-method="QRIS"]');
    if (qrisTile) qrisTile.click();
    agreeTnc.checked = true;
    agreePrivacy.checked = true;
    updateBayarBtn();
  } else if (initView === 'invoice') {
    openDetailById(initEventId);
    hideAllSubPages();
    landingSections.forEach(function (s) { s.style.display = 'none'; });
    resultSection.hidden = false;
    renderInvoiceSuccess({
      nomor_invoice: 'INV-20261125-829353',
      tanggal_transaksi: '25 November 2026 • 15:41 WIB',
      event_nama: currentEvent ? currentEvent.nama : 'ABC & Mom Expo Indonesia 2026',
      tanggal_label: currentEvent ? currentEvent.tanggal_label : '25-28 November 2026',
      jam_label: currentEvent ? currentEvent.jam_label : '09:00 - 21:00 WIB',
      venue: currentEvent ? currentEvent.venue : 'Jakarta Convention Center, Jakarta Pusat',
      kota: currentEvent ? currentEvent.kota : 'Jakarta Pusat',
      kategori_tiket_nama: 'Tiket Masuk Reguler',
      jumlah: 2,
      subtotal: 40000,
      biaya_layanan: 1200,
      total: 41200,
      metode_pembayaran: 'QRIS',
      pemesan: {
        nama: 'Afrizal Tazul Aripin',
        email: 'demo@ticketland.id',
        wa: '+6281234567890'
      },
      tickets: [
        { nomor_urut: 1, total_tiket: 2, kode_tiket: 'TKL-UAV6AE', nama_pemegang: 'Afrizal Tazul Aripin' },
        { nomor_urut: 2, total_tiket: 2, kode_tiket: 'TKL-3BQM7E', nama_pemegang: 'Dinda Kirana' }
      ]
    });
    if (paySuccessDlg && paySuccessDlg.open) paySuccessDlg.close();
  } else if (initView === 'contact') {
    openContact();
  } else if (initView === 'login') {
    openAuth();
  } else if (initView === 'burger') {
    if (mobileMenu) mobileMenu.classList.add('open');
    if (burger) burger.setAttribute('aria-expanded', 'true');
  }
  console.log('landing.js init complete');
})();
