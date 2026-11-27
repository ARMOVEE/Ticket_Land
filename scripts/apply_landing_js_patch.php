<?php
declare(strict_types=1);

$file = dirname(__DIR__) . '/public/assets/js/landing.js';
$js = file_get_contents($file);
if ($js === false) {
    throw new RuntimeException("Could not read {$file}");
}

// 1. Add validGmail, validNik, validWa, and resolvePoster
$searchTop = "  function validEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
  }";

$replaceTop = "  function validEmail(v) {
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
  }";

if (!str_contains($js, 'function validGmail(')) {
    $js = str_replace($searchTop, $replaceTop, $js);
    echo "1. Added validation helpers & resolvePoster\n";
}

// 2. Update nav-home and add nav-event
$searchNav = "  document.querySelectorAll('.nav-home').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var href = link.getAttribute('href') || '#';
      showHome();
      if (href === '#') {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });";

$replaceNav = "  document.querySelectorAll('.nav-home').forEach(function (link) {
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
  });";

if (!str_contains($js, "document.querySelectorAll('.nav-event')")) {
    $js = str_replace($searchNav, $replaceNav, $js);
    echo "2. Added nav-event listener\n";
}

// 3. Update bannerSvg and detailBannerSvg
$searchBanners = "  function bannerSvg(ev, idx) {
    if (ev.poster && String(ev.poster).trim() !== '') {
      var pSrc = String(ev.poster).trim();
      return '<img src=\"' + escapeXml(pSrc) + '\" alt=\"' + escapeXml(ev.nama) + '\" loading=\"lazy\" style=\"width: 100%; height: 164px; object-fit: cover; display: block;\" onerror=\"this.style.display=\\'none\\';if(this.nextElementSibling){this.nextElementSibling.style.display=\\'block\\';}\">'
        + '<div style=\"display:none;\">' + bannerFallbackSvg(ev, idx) + '</div>';
    }
    return bannerFallbackSvg(ev, idx);
  }

  function detailBannerFallbackSvg(ev) {";

$replaceBanners = "  function bannerSvg(ev, idx) {
    var pSrc = resolvePoster(ev.poster);
    if (pSrc !== '') {
      return '<img src=\"' + escapeXml(pSrc) + '\" alt=\"' + escapeXml(ev.nama) + '\" loading=\"lazy\" style=\"width: 100%; height: 164px; object-fit: cover; object-position: center; display: block;\" onerror=\"this.style.display=\\'none\\';if(this.nextElementSibling){this.nextElementSibling.style.display=\\'block\\';}\">'
        + '<div style=\"display:none;\">' + bannerFallbackSvg(ev, idx) + '</div>';
    }
    return bannerFallbackSvg(ev, idx);
  }

  function detailBannerFallbackSvg(ev) {";

if (str_contains($js, $searchBanners)) {
    $js = str_replace($searchBanners, $replaceBanners, $js);
    echo "3a. Updated bannerSvg\n";
}

$searchDetailBanner = "  function detailBannerSvg(ev) {
    if (ev.poster && String(ev.poster).trim() !== '') {
      var pSrc = String(ev.poster).trim();
      var catName = ev.kategori_nama || 'Event';
      return '<div class=\"detail-poster-wrap\" style=\"position:relative;width:100%;height:320px;overflow:hidden;background:#084A2E;border-radius:12px;\">'
        + '<img src=\"' + escapeXml(pSrc) + '\" alt=\"' + escapeXml(ev.nama) + '\" style=\"width:100%;height:100%;object-fit:cover;display:block;\" onerror=\"this.parentElement.style.display=\\'none\\';if(this.parentElement.nextElementSibling){this.parentElement.nextElementSibling.style.display=\\'block\\';}\">'";

$replaceDetailBanner = "  function detailBannerSvg(ev) {
    var pSrc = resolvePoster(ev.poster);
    if (pSrc !== '') {
      var catName = ev.kategori_nama || 'Event';
      return '<div class=\"detail-poster-wrap\" style=\"position:relative;width:100%;height:320px;overflow:hidden;background:#084A2E;border-radius:12px;\">'
        + '<img src=\"' + escapeXml(pSrc) + '\" alt=\"' + escapeXml(ev.nama) + '\" style=\"width:100%;height:100%;object-fit:cover;object-position:center;display:block;\" onerror=\"this.parentElement.style.display=\\'none\\';if(this.parentElement.nextElementSibling){this.parentElement.nextElementSibling.style.display=\\'block\\';}\">'";

if (str_contains($js, $searchDetailBanner)) {
    $js = str_replace($searchDetailBanner, $replaceDetailBanner, $js);
    echo "3b. Updated detailBannerSvg\n";
}

// 4. Update btnExploreAll
$searchExploreAll = "  var btnExploreAll = document.getElementById('btnExploreAll');
  if (btnExploreAll) {
    btnExploreAll.addEventListener('click', function () {
      openExplore('Semua', '');
    });
  }";

$replaceExploreAll = "  var btnExploreAll = document.getElementById('btnExploreAll');
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
  }";

if (str_contains($js, $searchExploreAll)) {
    $js = str_replace($searchExploreAll, $replaceExploreAll, $js);
    echo "4. Updated btnExploreAll\n";
}

// 5. Update startCheckout timer to 900
$searchStartCheckout = "var rem = Number(res.body.remaining_seconds || 600);";
$replaceStartCheckout = "var rem = Number(res.body.remaining_seconds || 900);";
if (str_contains($js, $searchStartCheckout)) {
    $js = str_replace($searchStartCheckout, $replaceStartCheckout, $js);
    echo "5. Updated startCheckout default remaining_seconds to 900\n";
}

// 6. Update openOrder default timer to 900
$searchOpenOrder = "startTimer(durationSec || 600);";
$replaceOpenOrder = "startTimer(durationSec || 900);";
if (str_contains($js, $searchOpenOrder)) {
    $js = str_replace($searchOpenOrder, $replaceOpenOrder, $js);
    echo "6. Updated openOrder default timer to 900\n";
}

file_put_contents($file, $js);
echo "Phase 1 patch saved!\n";
