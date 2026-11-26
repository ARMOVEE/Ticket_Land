<?php
/**
 * View Landing Page & Checkout 4 Langkah - Ticket Land
 * Mengikuti desain mockup "Ticket Land - Landing Page (1).html" dan PRD v1.0.
 */
$catIcons = [
    'Musik'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 18.5a3 3 0 1 1-2-2.83V5.2c0-.4.27-.76.66-.87l8-2.2A1 1 0 0 1 17.5 3.1v11.4a3 3 0 1 1-2-2.83V6.03L9 7.8v10.7Z"/></svg>',
    'Pameran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 3.5h16V11a8 8 0 0 1-16 0V3.5Z"/><path d="M8.2 9.2c.5-.7 1.6-.7 2.1 0M13.7 9.2c.5-.7 1.6-.7 2.1 0" stroke-linecap="round"/><path d="M8.8 14.2c.9 1.1 2 1.6 3.2 1.6s2.3-.5 3.2-1.6" stroke-linecap="round"/></svg>',
    'Komedi'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3.5a8.5 8.5 0 1 0 0 17c1.2 0 1.7-.6 1.7-1.4 0-.9-.7-1.3-.7-2.1 0-.9.7-1.5 1.7-1.5H17a4 4 0 0 0 4-4c0-4.3-4-7.5-9-7.5Z"/><circle cx="7.6" cy="10" r="1.15" fill="currentColor" stroke="none"/><circle cx="11" cy="6.8" r="1.15" fill="currentColor" stroke="none"/><circle cx="15.4" cy="8.6" r="1.15" fill="currentColor" stroke="none"/><circle cx="7.9" cy="14.6" r="1.15" fill="currentColor" stroke="none"/></svg>',
    'Trip'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.4 4.6a1.6 1.6 0 0 0-2.1-.5L11 8.6 4.9 5.7a.8.8 0 0 0-.9.2l-.7.8a.8.8 0 0 0 .1 1.1L8.6 12l-3.4.4-2-1.6a.8.8 0 0 0-1.2.3l-.9 1.9a.8.8 0 0 0 .3 1L6 17.6l1.3 3.1a.8.8 0 0 0 1.1.4l1.8-1a.8.8 0 0 0 .3-1.1l-1.5-2 1-3.3 4.5 5.2c.3.3.8.4 1.1.1l.8-.7a.8.8 0 0 0 .1-.9l-2.8-6.1 4.5-4.6a1.6 1.6 0 0 0 .2-2.1Z" transform="rotate(8 12 12)"/></svg>',
    'Bisnis'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="7.5" width="18" height="12" rx="2"/><path d="M9 7.5V6a3 3 0 0 1 3-3v0a3 3 0 0 1 3 3v1.5M3 12.5h18"/></svg>',
];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="color-scheme" content="light" />
    <link rel="icon" href="data:," />
    <title>Ticket Land - Your Best Event Partner</title>
    <link href="/assets/css/plus-jakarta-sans.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/landing.css" />
  </head>
  <body>

    <header>
      <div class="nav">
        <a class="brand nav-home" href="#" aria-label="Ticket Land">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
          Ticket Land
        </a>
        <nav class="nav-links" aria-label="Navigasi utama">
          <a href="#" class="active nav-home">Beranda</a>
          <a href="#kategori" class="nav-home">Kategori</a>
          <a href="#rekomendasi" class="nav-home">Tentang</a>
          <a href="#" class="nav-contact">Hubungi Kami</a>
        </nav>
        <button class="btn-masuk" id="btnMasuk" type="button">Masuk</button>
        <div class="user-chip" id="userChip">
          <span class="avatar" id="userAvatar">A</span>
          <span id="userName">Pengguna</span>
          <button class="btn-keluar" id="btnKeluar" type="button">Keluar</button>
        </div>
        <button class="burger" id="burger" type="button" aria-label="Buka menu" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
      </div>
      <div class="mobile-menu" id="mobileMenu">
        <a href="#" class="nav-home">Beranda</a>
        <a href="#kategori" class="nav-home">Kategori</a>
        <a href="#rekomendasi" class="nav-home">Tentang</a>
        <a href="#" class="nav-contact">Hubungi Kami</a>
        <a href="#" id="mobileMasuk">Masuk</a>
        <a href="#" id="mobileKeluar" style="display:none;color:#b91c1c">Keluar (<span id="mobileUserName">Pengguna</span>)</a>
      </div>
    </header>

    <main>
      <section class="hero">
        <div class="wrap">
          <div class="hero-art" role="img" aria-label="Ilustrasi pegunungan hijau dengan matahari terbit">
            <svg viewBox="0 0 880 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
              <defs>
                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0" stop-color="#c2ded2"/>
                  <stop offset=".62" stop-color="#dfe5cd"/>
                  <stop offset="1" stop-color="#eef0da"/>
                </linearGradient>
              </defs>
              <rect width="880" height="400" fill="url(#sky)"/>
              <ellipse cx="648" cy="96" rx="82" ry="48" fill="#f2a63b"/>
              <polygon points="0,252 190,152 348,228 512,118 690,232 880,172 880,400 0,400" fill="#6fae97"/>
              <polygon points="0,330 168,248 338,322 522,228 700,330 880,282 880,400 0,400" fill="#0B5D3B"/>
            </svg>
          </div>

          <?php if ($heroEvent): ?>
          <div class="hero-text">
            <span class="pill">Recently add event</span>
            <h1><?= htmlspecialchars($heroEvent['nama'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="hero-by">Oleh <?= htmlspecialchars($heroEvent['penyelenggara'], ENT_QUOTES, 'UTF-8') ?></p>
            <a class="btn-order" href="#rekomendasi" id="btnHeroOrder" data-event-id="<?= (int) $heroEvent['id'] ?>">Pesan Sekarang</a>
            <p class="hero-note">Slot tiket terbatas untuk <?= (int) $heroEvent['total_stok'] ?> orang tercepat</p>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="kategori" id="kategori">
        <div class="wrap">
          <h2>Pilih Kategori Favoritmu!</h2>
          <p>Jelajahi petualangan sensasi menonton dengan banyaknya event setiap harinya</p>

          <div class="cat-row" role="group" aria-label="Kategori event">
            <?php foreach ($categories as $cat):
              $cName = (string) $cat['nama'];
              $iconSvg = $catIcons[$cName] ?? $catIcons['Musik'];
            ?>
            <button class="cat" type="button" data-cat="<?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?>">
              <span class="circle">
                <?= $iconSvg ?>
              </span>
              <span><?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <p class="cat-note" id="catNote" aria-live="polite"></p>
        </div>
      </section>

      <section class="rekom" id="rekomendasi">
        <div class="wrap">
          <div class="rekom-head">
            <h2>Rekomendasi Event Akhir Pekan</h2>
            <button type="button" class="btn-explore-all" id="btnExploreAll">Jelajahi Semua Event</button>
          </div>
          <div class="rekom-grid">
            <?php foreach ($recommendedEvents as $rec):
              $isFree = (int) $rec['harga_mulai'] === 0;
              $hasMulti = count($rec['ticket_categories'] ?? []) > 1;
              $priceLabel = $isFree
                ? 'GRATIS'
                : (($hasMulti ? 'Mulai dari ' : '') . 'Rp ' . number_format((int) $rec['harga_mulai'], 0, ',', '.'));
            ?>
            <article class="card" tabindex="0" role="button" data-event-id="<?= (int) $rec['id'] ?>" aria-label="Lihat detail <?= htmlspecialchars($rec['nama'], ENT_QUOTES, 'UTF-8') ?>">
              <div class="thumb" role="img" aria-label="Gambar event <?= htmlspecialchars($rec['nama'], ENT_QUOTES, 'UTF-8') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 18.5 10 13l3.5 3L17 12.5l3 2.8"/></svg>
              </div>
              <h3><?= htmlspecialchars($rec['nama'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="meta"><?= htmlspecialchars($rec['tanggal_label'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars($rec['kota'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="meta">Oleh <?= htmlspecialchars($rec['penyelenggara'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="price <?= $isFree ? 'green' : 'red' ?>"><?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?></p>
              <span class="lihat">Lihat Detail <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="explore" id="explorePage" hidden aria-label="Jelajahi event berdasarkan kategori">
        <div class="wrap">
          <button class="btn-back" id="btnBackExplore" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali
          </button>

          <div class="explore-search-row">
            <div class="explore-search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
              <input type="search" id="exploreSearchInput" aria-label="Cari event berdasarkan judul, penyelenggara, atau kota" placeholder="Cari judul event, penyelenggara, atau kota..." autocomplete="off" />
              <button type="button" class="explore-search-clear" id="exploreSearchClear" hidden>Bersihkan</button>
            </div>
          </div>

          <div class="explore-toolbar">
            <div class="explore-toolbar-left">
              <button class="btn-filter" id="btnFilter" type="button" aria-expanded="false" aria-controls="filterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18l-7 8v5.3L10 21v-8L3 5Z"/></svg>
                Filter
              </button>
              <span class="active-chip" id="activeChip" hidden>
                <span id="activeChipName">Musik</span>
                <button id="chipClear" type="button" aria-label="Hapus filter kategori">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>
                </button>
              </span>
            </div>

            <div class="dropdown-sort" id="dropdownSort">
              <button type="button" class="btn-sort" id="btnSort" aria-expanded="false" aria-haspopup="listbox" aria-label="Urutkan event">
                <span>Urutkan</span>
                <svg class="sort-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9.5 6 6 6-6"/></svg>
              </button>
              <ul class="sort-menu" id="sortMenu" role="listbox" aria-label="Pilihan urutan" hidden>
                <li role="option" class="sort-item active" data-sort="relevan" aria-selected="true">
                  <span>Paling Relevan</span>
                  <svg class="sort-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </li>
                <li role="option" class="sort-item" data-sort="tanggal" aria-selected="false">
                  <span>Tanggal Terdekat</span>
                  <svg class="sort-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </li>
                <li role="option" class="sort-item" data-sort="murah" aria-selected="false">
                  <span>Harga Terendah</span>
                  <svg class="sort-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </li>
                <li role="option" class="sort-item" data-sort="mahal" aria-selected="false">
                  <span>Harga Tertinggi</span>
                  <svg class="sort-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </li>
              </ul>
            </div>
          </div>

          <div class="filter-panel" id="filterPanel" hidden>
            <p class="filter-label">Pilih Kategori</p>
            <div class="fchip-row">
              <button class="fchip" type="button" data-fcat="Semua">Semua</button>
              <?php foreach ($categories as $cat): ?>
              <button class="fchip" type="button" data-fcat="<?= htmlspecialchars($cat['nama'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['nama'], ENT_QUOTES, 'UTF-8') ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <p class="explore-count" id="exploreCount" aria-live="polite"></p>
          <div class="explore-grid" id="exploreGrid"></div>
          <p class="explore-empty" id="exploreEmpty" hidden>Belum ada event yang cocok dengan filter atau kata kunci pencarian ini. Coba kata kunci atau kategori lain.</p>
        </div>
      </section>

      <section class="detail" id="eventDetail" hidden aria-labelledby="detailTitle">
        <div class="wrap">
          <button class="btn-back" id="btnBack" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali
          </button>
          <span class="detail-cat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.1" fill="currentColor" stroke="none"/></svg>
            <span id="detailCatName">Pameran</span>
          </span>
          <h2 class="detail-title" id="detailTitle">-</h2>

          <div class="detail-grid">
            <div class="detail-main">
              <p class="detail-label">Penyelenggara</p>
              <div class="organizer">
                <span class="organizer-logo" id="detailOrgLogo">TL</span>
                <span class="organizer-name" id="detailOrg">-</span>
              </div>

              <p class="detail-label">Media Sosial</p>
              <div class="social-row">
                <a class="social-pill" href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram penyelenggara">
                  <span class="s-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg></span>
                  Instagram
                </a>
                <a class="social-pill tiktok" href="https://tiktok.com" target="_blank" rel="noopener noreferrer" aria-label="TikTok penyelenggara">
                  <span class="s-ic"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 3c.4 2.2 1.8 3.6 4.1 3.8v3c-1.6 0-3-.5-4.1-1.3v6.3a5.9 5.9 0 1 1-5.9-5.9c.3 0 .7 0 1 .1v3.1a2.8 2.8 0 1 0 1.9 2.7V3h3Z"/></svg></span>
                  TikTok
                </a>
              </div>

              <div class="detail-tabs" role="tablist" aria-label="Detail event">
                <button class="detail-tab active" type="button" role="tab" aria-selected="true" data-tab="deskripsi">Deskripsi</button>
                <button class="detail-tab" type="button" role="tab" aria-selected="false" data-tab="syarat">Syarat &amp; Ketentuan</button>
              </div>

              <div class="detail-panel" id="panelDeskripsi">
                <h3>Deskripsi</h3>
                <div id="detailDesc"></div>
              </div>

              <div class="detail-panel" id="panelSyarat" hidden>
                <h3>Syarat &amp; Ketentuan</h3>
                <ol id="detailSnkList"></ol>
              </div>
            </div>

            <aside class="detail-side">
              <div class="detail-banner" id="detailBannerBox" role="img" aria-label="Banner event"></div>

              <h3>Detail Acara</h3>
              <div class="detail-info">
                <div class="info-row">
                  <span class="i-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4" stroke-linecap="round"/></svg></span>
                  <span id="detailDate">-</span>
                </div>
                <div class="info-row">
                  <span class="i-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                  <span id="detailTime">-</span>
                </div>
                <div class="info-row">
                  <span class="i-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M12 21s7-5.8 7-11a7 7 0 1 0-14 0c0 5.2 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
                  <span id="detailLoc">-</span>
                </div>
              </div>

              <div class="price-box">
                <div>
                  <p class="plabel">Harga mulai dari</p>
                  <p class="pvalue" id="detailPrice">Rp 0</p>
                </div>
                <button class="btn-pesan" id="btnPesanDetail" type="button">Pesan Sekarang</button>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <section class="ticket" id="ticketPage" hidden aria-labelledby="ticketPageTitle">
        <div class="steps-bar">
          <div class="steps" aria-label="Langkah pemesanan">
            <span class="step active"><span class="step-num">1</span> Pilih Kategori</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">2</span> Detail Pesanan</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">3</span> Metode Pembayaran</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">4</span> Pembayaran</span>
          </div>
        </div>
        <div class="wrap">
          <button class="btn-back" id="btnBackTicket" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali ke Detail Event
          </button>
          <h2 class="sr-only" id="ticketPageTitle">Pilih Kategori Tiket</h2>

          <div class="ticket-grid">
            <div class="ticket-main">
              <div class="detail-banner" id="ticketBannerBox" role="img" aria-label="Banner event"></div>
              <div class="ticket-desc-box" id="ticketDescBox"></div>

              <span class="cat-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
                Kategori Tiket
              </span>

              <div class="ticket-panel" id="ticketCategoryList"></div>
            </div>

            <aside class="ticket-side">
              <div class="summary-card">
                <div class="summary-row">
                  <span class="s-label">Harga mulai dari</span>
                  <span class="s-price" id="summaryPrice">Rp 0</span>
                </div>
                <p class="summary-cat" id="summaryCat"></p>
                <div class="summary-qty-wrap" id="summaryQtyWrap" style="display:none; align-items:center; justify-content:space-between; margin:12px 0 16px; padding:10px 14px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                  <span style="font-size:13px; font-weight:600; color:#334155;">Jumlah Tiket</span>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" id="summaryQtyMinus" class="qty-btn" aria-label="Kurangi jumlah tiket" style="width:28px; height:28px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center;">-</button>
                    <span id="summaryQtyVal" style="font-weight:700; min-width:24px; text-align:center; font-size:14px; color:#0f172a;">1</span>
                    <button type="button" id="summaryQtyPlus" class="qty-btn" aria-label="Tambah jumlah tiket" style="width:28px; height:28px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center;">+</button>
                  </div>
                </div>
                <button class="btn-beli" id="btnBeli" type="button" disabled>Beli Sekarang</button>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <section class="order" id="orderPage" hidden aria-labelledby="orderPageTitle">
        <div class="steps-bar">
          <div class="steps" aria-label="Langkah pemesanan">
            <span class="step"><span class="step-num">1</span> Pilih Kategori</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step active"><span class="step-num">2</span> Detail Pesanan</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">3</span> Metode Pembayaran</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">4</span> Pembayaran</span>
          </div>
        </div>
        <div class="wrap">
          <button class="btn-back" id="btnBackOrder" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali ke Pilih Kategori
          </button>

          <div class="order-head">
            <h2 class="order-title" id="orderPageTitle">-</h2>
            <p class="order-meta" id="orderPageMeta">-</p>
          </div>

          <div class="order-grid">
            <div class="order-main">
              <div class="order-card">
                <div class="order-card-head">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c1.2-3.4 4-5 7.5-5s6.3 1.6 7.5 5" stroke-linecap="round"/></svg>
                  <h3>Data Pemesan</h3>
                </div>
                <div class="o-field">
                  <label class="o-label" for="buyerName">Nama Lengkap <span class="req">*</span></label>
                  <input class="o-input" id="buyerName" type="text" placeholder="Masukkan nama lengkap Anda" autocomplete="name" />
                </div>
                <div class="o-row">
                  <div class="o-field">
                    <label class="o-label" for="buyerIdType">Tipe Identitas <span class="req">*</span></label>
                    <select class="o-select" id="buyerIdType">
                      <option value="" selected>Pilih tipe identitas Anda</option>
                      <option value="KTP">KTP</option>
                      <option value="Paspor">Paspor</option>
                      <option value="SIM">SIM</option>
                    </select>
                  </div>
                  <div class="o-field">
                    <label class="o-label" for="buyerIdNum">Nomor Identitas <span class="req">*</span></label>
                    <input class="o-input" id="buyerIdNum" type="text" placeholder="Masukkan nomor identitas Anda" />
                  </div>
                </div>
                <div class="o-field">
                  <label class="o-label" for="buyerEmail">Email <span class="req">*</span></label>
                  <input class="o-input" id="buyerEmail" type="email" placeholder="Masukkan email Anda" autocomplete="email" />
                </div>
                <div class="o-field">
                  <label class="o-label" for="buyerWa">No. WhatsApp <span class="req">*</span></label>
                  <div class="phone-group">
                    <span class="phone-prefix">
                      <svg viewBox="0 0 19 13" aria-hidden="true"><rect width="19" height="6.5" fill="#e70011"/><rect y="6.5" width="19" height="6.5" fill="#f4f4f4"/></svg>
                      +62
                    </span>
                    <input class="o-input" id="buyerWa" type="tel" inputmode="tel" placeholder="81234567890" autocomplete="tel" />
                  </div>
                </div>
              </div>

              <div id="attendeesContainer"></div>
            </div>

            <aside class="order-side">
              <div class="timer-badge" role="timer" aria-label="Batas waktu pemesanan tersisa">
                <span id="timerText">10:00</span>
                <span class="t-sep">|</span>
                <span class="t-label">Batas Waktu Tersisa</span>
              </div>
              <div class="rincian-card">
                <h3>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M5 8h14l-1.2 11a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M8.5 10V6.5a3.5 3.5 0 0 1 7 0V10" stroke-linecap="round"/></svg>
                  Rincian Pesanan
                </h3>
                <div class="r-item">
                  <div>
                    <p class="r-name" id="rincianCatName">Tiket Masuk Reguler</p>
                    <p class="r-qtytext" id="rincianQty">x1</p>
                  </div>
                  <span class="r-price" id="rincianLine">Rp 0</span>
                </div>
                <div class="qty-row">
                  <span class="q-label">Jumlah Tiket</span>
                  <span class="qty-ctrl">
                    <button class="qty-btn" id="qtyMinus" type="button" aria-label="Kurangi jumlah tiket">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>
                    </button>
                    <span class="qty-val" id="qtyVal">1</span>
                    <button class="qty-btn" id="qtyPlus" type="button" aria-label="Tambah jumlah tiket">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                  </span>
                </div>
                <div class="r-line"><span>Subtotal</span><span id="rincianSubtotal">Rp 0</span></div>
                <div class="r-total"><span>Total Bayar</span><span id="rincianTotal">Rp 0</span></div>
                <div class="r-actions">
                  <button class="btn-circle" id="btnRincianBack" type="button" aria-label="Kembali ke pilih kategori">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                  </button>
                  <button class="btn-lanjut" id="btnLanjut" type="button">Lanjutkan</button>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <section class="pay" id="payPage" hidden aria-labelledby="payPageTitle">
        <div class="steps-bar">
          <div class="steps" aria-label="Langkah pemesanan">
            <span class="step"><span class="step-num">1</span> Pilih Kategori</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">2</span> Detail Pesanan</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step active"><span class="step-num">3</span> Metode Pembayaran</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">4</span> Pembayaran</span>
          </div>
        </div>
        <div class="wrap">
          <button class="btn-back" id="btnBackPay" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali ke Detail Pesanan
          </button>

          <div class="order-head">
            <h2 class="order-title" id="payPageTitle">-</h2>
            <p class="order-meta" id="payPageMeta">-</p>
          </div>

          <div class="order-grid">
            <div class="order-main">
              <div class="method-group">
                <button class="method-head" type="button" aria-expanded="true">
                  <svg class="m-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h4" stroke-linecap="round"/></svg>
                  E-Wallet/QRIS
                  <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 14.5 6-6 6 6"/></svg>
                </button>
                <div class="method-body">
                  <div class="method-grid">
                    <button class="method-tile" type="button" data-method="QRIS" aria-pressed="false"><span class="wm wm-qris">QRIS</span></button>
                    <button class="method-tile" type="button" data-method="OVO" aria-pressed="false"><span class="wm wm-ovo">OVO</span></button>
                    <button class="method-tile" type="button" data-method="DANA" aria-pressed="false"><span class="wm wm-dana">DANA</span></button>
                    <button class="method-tile" type="button" data-method="LinkAja" aria-pressed="false"><span class="wm wm-linkaja">LinkAja!</span></button>
                    <button class="method-tile" type="button" data-method="ShopeePay" aria-pressed="false"><span class="wm wm-shopee">ShopeePay</span></button>
                  </div>
                </div>
              </div>

              <div class="method-group">
                <button class="method-head" type="button" aria-expanded="true">
                  <svg class="m-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 12 4l9 5.5M4.5 9.5V20M9.5 9.5V20M14.5 9.5V20M19.5 9.5V20M3 20h18"/></svg>
                  Transfer Bank
                  <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 14.5 6-6 6 6"/></svg>
                </button>
                <div class="method-body">
                  <div class="bank-row">
                    <button class="bank-chip" type="button" data-method="Transfer BCA" aria-pressed="false">BCA</button>
                    <button class="bank-chip" type="button" data-method="Transfer BNI" aria-pressed="false">BNI</button>
                    <button class="bank-chip" type="button" data-method="Transfer BRI" aria-pressed="false">BRI</button>
                    <button class="bank-chip" type="button" data-method="Transfer Mandiri" aria-pressed="false">Mandiri</button>
                    <button class="bank-chip" type="button" data-method="Transfer CIMB Niaga" aria-pressed="false">CIMB Niaga</button>
                    <button class="bank-chip" type="button" data-method="Transfer BTN" aria-pressed="false">BTN</button>
                    <button class="bank-chip" type="button" data-method="Transfer Permata" aria-pressed="false">Permata</button>
                    <button class="bank-chip" type="button" data-method="Transfer BSI" aria-pressed="false">BSI</button>
                  </div>
                </div>
              </div>
            </div>

            <aside class="order-side">
              <div class="timer-badge" role="timer" aria-label="Batas waktu pemesanan tersisa">
                <span id="payTimerText">10:00</span>
                <span class="t-sep">|</span>
                <span class="t-label">Batas Waktu Tersisa</span>
              </div>
              <div class="rincian-card">
                <h3>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M5 8h14l-1.2 11a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M8.5 10V6.5a3.5 3.5 0 0 1 7 0V10" stroke-linecap="round"/></svg>
                  Rincian Pesanan
                </h3>
                <div class="r-item">
                  <div>
                    <p class="r-name" id="payCatName">Tiket Masuk Reguler</p>
                    <p class="r-qtytext" id="payQty">x1</p>
                  </div>
                  <span class="r-price" id="payLine">Rp 0</span>
                </div>
                <div class="r-line"><span>Subtotal</span><span id="paySubtotal">Rp 0</span></div>
                <div class="r-line"><span>Biaya Layanan</span><span id="payFee">Rp 0</span></div>
                <div class="r-total"><span>Total Bayar</span><span id="payTotal">Rp 0</span></div>
                <div class="promo-box">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
                  Lebih hemat pakai promo
                </div>
                <label class="agree-row">
                  <input type="checkbox" id="agreeTnc" />
                  <span>Saya menyetujui <a href="#rekomendasi" onclick="return false">Syarat &amp; Ketentuan</a> yang berlaku di Ticket Land</span>
                </label>
                <label class="agree-row">
                  <input type="checkbox" id="agreePrivacy" />
                  <span>Saya menyetujui <a href="#rekomendasi" onclick="return false">Kebijakan Privasi &amp; Pemrosesan Data</a> yang berlaku di Ticket Land</span>
                </label>
                <div class="r-actions">
                  <button class="btn-circle" id="btnPayBack" type="button" aria-label="Kembali ke detail pesanan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                  </button>
                  <button class="btn-bayar" id="btnBayar" type="button" disabled>Bayar Sekarang</button>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <section class="result" id="resultPage" hidden aria-label="Pembayaran">
        <div class="steps-bar">
          <div class="steps" aria-label="Langkah pemesanan">
            <span class="step"><span class="step-num">1</span> Pilih Kategori</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">2</span> Detail Pesanan</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step"><span class="step-num">3</span> Metode Pembayaran</span>
            <svg class="step-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 5 6.5 7-6.5 7"/></svg>
            <span class="step active"><span class="step-num">4</span> Pembayaran</span>
          </div>
        </div>
        <div class="wrap">
          <div class="proc-box" id="payProcessing">
            <div class="spinner" aria-hidden="true"></div>
            <span class="demo-badge"><span class="dot"></span> Mode Demo - Simulasi</span>
            <h2>Memproses Pembayaran</h2>
            <p>Sedang memproses pembayaran via <strong id="procMethod">QRIS</strong> sebesar <strong id="procTotal">Rp 0</strong>. Jangan tutup halaman ini.</p>
            <p class="proc-note">Ini simulasi untuk keperluan demo: tidak ada pembayaran sungguhan dan tidak ada dana yang dipotong.</p>
          </div>

          <div id="paySuccess" hidden>
            <div class="success-head">
              <div class="success-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4.5 12.5 5 5 10-11"/></svg>
              </div>
              <span class="demo-badge"><span class="dot"></span> Mode Demo - Simulasi</span>
              <h2>Pembayaran Berhasil</h2>
              <p id="successMsg">Invoice dan e-tiket sudah diterbitkan.</p>
            </div>

            <div class="result-grid">
              <div class="invoice-card">
                <h3>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M6 3.5h12V20l-2-1.4L14 20l-2-1.4L10 20l-2-1.4L6 20V3.5Z" stroke-linejoin="round"/><path d="M9.5 8h5M9.5 11.5h5" stroke-linecap="round"/></svg>
                  Invoice
                </h3>
                <div class="inv-row"><span class="k">Nomor Invoice</span><span class="v" id="invNo">-</span></div>
                <div class="inv-row"><span class="k">Tanggal Transaksi</span><span class="v" id="invDate">-</span></div>
                <div class="inv-row"><span class="k">Nama Pemesan</span><span class="v" id="invName">-</span></div>
                <div class="inv-row"><span class="k">Email</span><span class="v" id="invEmail">-</span></div>
                <div class="inv-row"><span class="k">Metode Pembayaran</span><span class="v" id="invMethod">-</span></div>
                <div class="inv-row"><span class="k">Status</span><span class="v"><span class="status-lunas">LUNAS</span></span></div>
                <div class="inv-sep"></div>
                <div class="inv-row"><span class="k">Event</span><span class="v" id="invEventName">-</span></div>
                <div class="inv-row"><span class="k">Kategori</span><span class="v" id="invCatName">-</span></div>
                <div class="inv-row"><span class="k">Jumlah Tiket</span><span class="v" id="invQty">x1</span></div>
                <div class="inv-sep"></div>
                <div class="inv-row"><span class="k">Subtotal</span><span class="v" id="invSubtotal">-</span></div>
                <div class="inv-row"><span class="k">Biaya Layanan</span><span class="v" id="invFee">-</span></div>
                <div class="inv-row inv-total"><span class="k">Total Bayar</span><span class="v" id="invTotal">-</span></div>
                <p class="inv-note">Invoice ini diterbitkan oleh simulasi pembayaran Ticket Land untuk keperluan demo.</p>
              </div>

              <div id="eticketList" aria-label="E-tiket"></div>
            </div>

            <div class="result-actions">
              <button class="btn-ghost" id="btnBackToHomeFromResult" type="button">Kembali ke Beranda</button>
              <button class="btn-solid" id="btnDownloadInvoice" type="button">Unduh Invoice</button>
            </div>
          </div>
        </div>
      </section>

      <section class="contact" id="contactPage" hidden aria-labelledby="contactTitle">
        <div class="wrap">
          <button class="btn-back" id="btnBackContact" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali ke Beranda
          </button>

          <div class="contact-grid">
            <div class="contact-main">
              <h2 class="contact-title" id="contactTitle">Hubungi Kami</h2>
              <p class="contact-intro">Ada pertanyaan seputar event, mengalami kendala terkait tiket atau pembayaran, atau ingin bekerja sama mengadakan event? Sampaikan melalui kontak yang tersedia atau isi formulir di samping.</p>
              <p class="contact-intro">Tim kami siap membantu kamu sebaik mungkin.</p>
              <p class="contact-hours">Jam operasional: Senin-Jumat, 09.00-17.00 WIB</p>

              <div class="hub-info">
                <div class="hub-row">
                  <span class="hub-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M12 21s7-5.8 7-11a7 7 0 1 0-14 0c0 5.2 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
                  <div>
                    <p class="hub-label">Alamat</p>
                    <p class="hub-value">Kantor Ticket Land, Jl. Jend. Sudirman No. 88,<br />Jakarta Selatan, DKI Jakarta 12190</p>
                  </div>
                </div>
                <div class="hub-row">
                  <span class="hub-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg></span>
                  <div>
                    <p class="hub-label">Telepon / WhatsApp</p>
                    <a class="hub-value" href="tel:+622155501234">(021) 5550-1234</a>
                  </div>
                </div>
                <div class="hub-row">
                  <span class="hub-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m4 7 8 6 8-6" stroke-linejoin="round"/></svg></span>
                  <div>
                    <p class="hub-label">Email</p>
                    <a class="hub-value" href="mailto:cs@ticketland.id">cs@ticketland.id</a>
                  </div>
                </div>
              </div>

              <div class="hub-socials" aria-label="Media sosial Ticket Land">
                <a class="hub-soc" href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram Ticket Land">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>
                </a>
                <a class="hub-soc" href="https://tiktok.com" target="_blank" rel="noopener noreferrer" aria-label="TikTok Ticket Land">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 3c.4 2.2 1.8 3.6 4.1 3.8v3c-1.6 0-3-.5-4.1-1.3v6.3a5.9 5.9 0 1 1-5.9-5.9c.3 0 .7 0 1 .1v3.1a2.8 2.8 0 1 0 1.9 2.7V3h3Z"/></svg>
                </a>
                <a class="hub-soc" href="https://x.com" target="_blank" rel="noopener noreferrer" aria-label="X Ticket Land">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.9 3H21l-6.8 7.8L22.2 21h-6.3l-4.9-6.4L5.4 21H2.2l7.3-8.3L1.8 3h6.4l4.4 5.9L17.9 3Zm-1.1 16.1h1.7L7.4 4.8H5.6l11.2 14.3Z"/></svg>
                </a>
                <a class="hub-soc" href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube Ticket Land">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><rect x="2.8" y="5.5" width="18.4" height="13" rx="3.5"/><path d="M10.2 9.3v5.4l4.8-2.7-4.8-2.7Z" fill="currentColor" stroke="none"/></svg>
                </a>
                <a class="hub-soc" href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook Ticket Land">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.6 21v-6.8h2.3l.4-2.7h-2.7V9.8c0-.8.3-1.3 1.4-1.3h1.3V6.1c-.3 0-1.1-.1-2-.1-2 0-3.4 1.2-3.4 3.5v2H8.5v2.7h2.4V21h2.7Z"/></svg>
                </a>
              </div>
            </div>

            <div class="hub-card">
              <div id="hubFormWrap">
                <h3>Terhubung dengan kami</h3>
                <p class="hub-sub">Isi formulir di bawah ini, tim kami akan membalas secepatnya.</p>
                <p class="hub-err" id="hubErr" role="alert"></p>
                <form id="hubForm" novalidate>
                  <div class="o-field">
                    <label class="o-label" for="hubName">Nama Lengkap <span class="req">*</span></label>
                    <input class="o-input" id="hubName" type="text" placeholder="Masukkan nama lengkap Anda" autocomplete="name" />
                  </div>
                  <div class="o-field">
                    <label class="o-label" for="hubEmail">Email <span class="req">*</span></label>
                    <input class="o-input" id="hubEmail" type="email" placeholder="Masukkan email Anda" autocomplete="email" />
                  </div>
                  <div class="o-field">
                    <label class="o-label" for="hubSubject">Subject <span class="req">*</span></label>
                    <select class="o-select" id="hubSubject">
                      <option value="" selected>Pilih subject</option>
                      <option value="Pertanyaan Umum">Pertanyaan Umum</option>
                      <option value="Kendala Tiket / Pembayaran">Kendala Tiket / Pembayaran</option>
                      <option value="Kerja Sama Event">Kerja Sama Event</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                  <div class="o-field">
                    <label class="o-label" for="hubMsg">Pesan</label>
                    <textarea class="o-input" id="hubMsg" placeholder="Tulis pesan Anda di sini"></textarea>
                  </div>
                  <button class="btn-kirim" type="submit">Kirim Pesan</button>
                </form>
              </div>
              <div class="hub-success" id="hubSuccess" hidden>
                <div class="success-ic">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4.5 12.5 5 5 10-11"/></svg>
                </div>
                <h4>Pesan Terkirim</h4>
                <p id="hubSuccessMsg">Terima kasih! Pesanmu sudah kami terima.</p>
                <button class="btn-again" id="btnHubAgain" type="button">Kirim Pesan Lainnya</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="bantuan" id="bantuan">
        <div class="wrap">
          <div class="help-box">
            <div>
              <h2>Need Help?</h2>
              <p>Send your problem now!</p>
            </div>
            <button class="btn-help" id="btnHelp" type="button">Click Me!</button>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <div class="foot">
        <a class="brand nav-home" href="#" aria-label="Ticket Land">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
          Ticket Land
        </a>
        <small>&copy; 2026 Ticket Land - Your Best Event Partner</small>
      </div>
    </footer>

    <!-- Dialog Login / Signup (Terkunci - Jangan Diubah Desainnya) -->
    <dialog id="authDlg" aria-label="Login dan Signup Ticket Land">
      <button class="auth-close" id="authClose" type="button" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>
      </button>
      <div class="auth-wrap">
        <section class="auth-card" id="loginCard" aria-labelledby="loginTitle">
          <div class="auth-brand">
            <span class="auth-logo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
            </span>
            <span class="auth-brand-name">Ticket Land</span>
          </div>
          <h3 id="loginTitle">Selamat datang kembali</h3>
          <p class="auth-sub">Masuk dengan email dan kata sandi akun Ticket Land kamu.</p>
          <p class="auth-err" id="loginErr" role="alert"></p>
          <p class="auth-ok" id="loginOk" role="status"></p>
          <form id="loginForm" method="dialog" novalidate>
            <label for="loginEmail">Email</label>
            <div class="field">
              <input id="loginEmail" class="no-toggle" type="text" inputmode="email" placeholder="nama@email.com" autocomplete="username email" required />
            </div>
            <label for="loginPass">Kata Sandi</label>
            <div class="field">
              <input id="loginPass" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required />
              <button class="eye" type="button" data-eye="loginPass" aria-label="Tampilkan kata sandi">
                <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 4l16 16"/><path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17.6 17.6 0 0 1-3.3 3.5M6.6 8A16.9 16.9 0 0 0 2.5 12S6 18 12 18c1.2 0 2.3-.2 3.3-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
              </button>
            </div>
            <button class="auth-btn" type="submit">Login</button>
          </form>
          <p class="auth-alt">Belum punya akun? <button class="auth-link" type="button" id="toSignup">Daftar</button></p>
        </section>

        <section class="auth-card off" id="signupCard" aria-labelledby="signupTitle">
          <div class="auth-brand">
            <span class="auth-logo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>
            </span>
            <span class="auth-brand-name">Ticket Land</span>
          </div>
          <h3 id="signupTitle">Buat akun baru</h3>
          <p class="auth-sub">Daftar dulu biar bisa pesan tiket event favoritmu.</p>
          <p class="auth-err" id="signupErr" role="alert"></p>
          <form id="registerForm" method="dialog" novalidate>
            <label for="regEmail">Email</label>
            <div class="field">
              <input id="regEmail" class="no-toggle" type="email" placeholder="nama@email.com" autocomplete="email" required />
            </div>
            <label for="regPass">Kata Sandi</label>
            <div class="field">
              <input id="regPass" type="password" placeholder="Buat kata sandi" autocomplete="new-password" required />
              <button class="eye" type="button" data-eye="regPass" aria-label="Tampilkan kata sandi">
                <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 4l16 16"/><path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17.6 17.6 0 0 1-3.3 3.5M6.6 8A16.9 16.9 0 0 0 2.5 12S6 18 12 18c1.2 0 2.3-.2 3.3-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
              </button>
            </div>
            <p class="field-hint">Minimal 6 karakter.</p>
            <label for="regPass2">Konfirmasi Kata Sandi</label>
            <div class="field">
              <input id="regPass2" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required />
              <button class="eye" type="button" data-eye="regPass2" aria-label="Tampilkan kata sandi">
                <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 4l16 16"/><path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17.6 17.6 0 0 1-3.3 3.5M6.6 8A16.9 16.9 0 0 0 2.5 12S6 18 12 18c1.2 0 2.3-.2 3.3-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
              </button>
            </div>
            <button class="auth-btn" type="submit">Signup</button>
          </form>
          <p class="auth-alt">Sudah punya akun? <button class="auth-link" type="button" id="toLogin">Masuk</button></p>
        </section>
      </div>
    </dialog>

    <!-- Dialog Konfirmasi Pesanan -->
    <dialog id="confirmDlg" aria-labelledby="confirmTitle">
      <div class="confirm-box">
        <button class="confirm-close" id="confirmClose" type="button" aria-label="Tutup konfirmasi">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>
        </button>
        <h3 id="confirmTitle">Konfirmasi Pesanan</h3>
        <p class="c-sub">Pastikan data sebelum melanjutkan</p>
        <div class="c-contact">
          <div class="c-row">
            <span class="c-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c1.2-3.4 4-5 7.5-5s6.3 1.6 7.5 5" stroke-linecap="round"/></svg></span>
            <span id="cfName">-</span>
          </div>
          <div class="c-row">
            <span class="c-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m4 7 8 6 8-6" stroke-linejoin="round"/></svg></span>
            <span id="cfEmail">-</span>
          </div>
          <div class="c-row">
            <span class="c-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg></span>
            <span id="cfWa">-</span>
          </div>
        </div>
        <p class="c-info-title">Informasi Penting :</p>
        <ol class="c-info">
          <li>Invoice dan E-Tiket akan dikirim ke email berikut
            <span class="c-hl" id="cfEmail2">-</span>
          </li>
          <li>E-Tiket juga akan dikirim melalui WhatsApp ke nomor berikut
            <span class="c-hl" id="cfWa2">-</span>
          </li>
          <li>Jika belum menerima notifikasi email setelah pembayaran:
            <ul>
              <li>Cari &quot;Ticket Land&quot; pada kolom pencarian email</li>
              <li>Periksa folder spam/promosi pada email</li>
            </ul>
          </li>
        </ol>
        <div class="c-actions">
          <button class="btn-edit" id="btnEditData" type="button">Edit Data</button>
          <button class="btn-mengerti" id="btnMengerti" type="button">Saya Mengerti</button>
        </div>
      </div>
    </dialog>

    <!-- Dialog Pop-up Pembayaran Berhasil (FR-U-35) -->
    <dialog id="paySuccessDlg" aria-labelledby="paySuccessDlgTitle">
      <div class="pay-ok-box">
        <div class="success-ic" style="margin-bottom: 12px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4.5 12.5 5 5 10-11"/></svg>
        </div>
        <h3 id="paySuccessDlgTitle">Pembayaran Berhasil</h3>
        <p id="paySuccessDlgText">Invoice dan e-tiket QR kamu sudah terbit.</p>
        <button type="button" class="btn-solid" id="btnClosePaySuccessDlg" style="width: 100%;">Lihat Invoice &amp; E-Tiket QR</button>
      </div>
    </dialog>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script>
      window.TICKET_LAND_BOOT = <?= json_encode([
          'csrfToken'   => $csrfToken,
          'customer'    => $customer,
          'categories'  => $categories,
          'events'      => $events,
          'heroEventId' => $heroEvent ? (int) $heroEvent['id'] : 0,
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="/assets/js/qrgen.js" defer></script>
    <script src="/assets/js/landing.js" defer></script>
  </body>
</html>
