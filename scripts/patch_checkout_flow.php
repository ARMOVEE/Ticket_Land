<?php
declare(strict_types=1);

$file = dirname(__DIR__) . '/public/assets/js/landing.js';
$js = file_get_contents($file);
if ($js === false) {
    throw new RuntimeException("Could not read {$file}");
}

// Replace createAttendeeCard up to updateRincian & collectAttendeesPayload
$oldChunkStart = "  function createAttendeeCard(index) {";
$oldChunkEnd = "  btnBayar.addEventListener('click', function () {";

$posStart = strpos($js, $oldChunkStart);
$posEnd = strpos($js, $oldChunkEnd);

if ($posStart === false || $posEnd === false || $posEnd <= $posStart) {
    throw new RuntimeException("Target markers not found in landing.js");
}

$replacementChunk = <<<'CODE'
  /* Wire Buyer input restrictions & gender chips */
  var buyerIdNumInput = document.getElementById('buyerIdNum');
  if (buyerIdNumInput) {
    buyerIdNumInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 16);
      this.classList.remove('invalid');
    });
  }

  var buyerWaInput = document.getElementById('buyerWa');
  if (buyerWaInput) {
    buyerWaInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '');
      this.classList.remove('invalid');
    });
  }

  var buyerGenderRow = document.getElementById('buyerGenderRow');
  if (buyerGenderRow) {
    var bgChips = buyerGenderRow.querySelectorAll('.gender-chip');
    bgChips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var val = chip.getAttribute('data-gender');
        buyerGenderRow.setAttribute('data-gender-value', val);
        bgChips.forEach(function (c) {
          var on = c === chip;
          c.classList.toggle('active', on);
          c.classList.toggle('on', on);
          c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        buyerGenderRow.classList.remove('invalid');
      });
    });
  }

  ['buyerName', 'buyerEmail'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', function () { el.classList.remove('invalid'); });
    }
  });

  /* Attendee Card (Ticket 2..N) */
  function createAttendeeCard(index) {
    var num = index + 1;
    var catName = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';

    var card = document.createElement('div');
    card.className = 'order-card attendee-card';
    card.setAttribute('data-att-index', String(index));

    card.innerHTML =
      '<div class="order-card-head" style="display:flex;align-items:center;justify-content:space-between;width:100%;">'
      + '<div style="display:flex;align-items:center;gap:8px;">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px;" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z"/><path d="M13.5 6v2M13.5 11v2M13.5 16v2" stroke-linecap="round" stroke-dasharray="0.5 3"/></svg>'
      + '<h3 style="margin:0;">Pemegang Tiket - ' + num + '</h3>'
      + '</div>'
      + '<button type="button" class="btn-hapus-att" data-att-index="' + index + '" aria-label="Hapus tiket ' + num + '" style="background:none;border:none;color:#dc2626;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:6px;">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>'
      + 'Hapus'
      + '</button>'
      + '</div>'
      + '<p class="att-band">' + escapeXml(catName) + '</p>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attName_' + index + '">Nama Lengkap <span class="req">*</span></label>'
      + '<input class="o-input att-name" id="attName_' + index + '" type="text" placeholder="Masukkan nama lengkap pemegang tiket" />'
      + '</div>'
      + '<div class="o-row">'
      + '<div class="o-field">'
      + '<label class="o-label" for="attIdType_' + index + '">Tipe Identitas <span class="req">*</span></label>'
      + '<select class="o-select att-idtype" id="attIdType_' + index + '">'
      + '<option value="KTP" selected>KTP</option>'
      + '</select>'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attIdNum_' + index + '">Nomor Identitas (KTP/NIK) <span class="req">*</span></label>'
      + '<input class="o-input att-idnum" id="attIdNum_' + index + '" type="text" maxlength="16" placeholder="Masukkan 16 digit NIK KTP pemegang tiket" />'
      + '</div>'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attEmail_' + index + '">Email <span class="req">*</span></label>'
      + '<input class="o-input att-email" id="attEmail_' + index + '" type="email" placeholder="nama@gmail.com" />'
      + '</div>'
      + '<div class="o-field">'
      + '<span class="o-label" id="genderLabel_' + index + '">Jenis Kelamin <span class="req">*</span></span>'
      + '<div class="gender-row att-gender-row" id="genderRow_' + index + '" data-gender-value="Laki-laki" role="group" aria-labelledby="genderLabel_' + index + '">'
      + '<button class="gender-chip active on" type="button" data-gender="Laki-laki" aria-pressed="true">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="14" r="5"/><path d="M14 10 20 4M15 4h5v5"/></svg>'
      + 'Laki-laki'
      + '</button>'
      + '<button class="gender-chip" type="button" data-gender="Perempuan" aria-pressed="false">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="9" r="5"/><path d="M12 14v7M8.5 17.5h7"/></svg>'
      + 'Perempuan'
      + '</button>'
      + '</div>'
      + '</div>'
      + '<div class="o-field">'
      + '<label class="o-label" for="attWa_' + index + '">No. WhatsApp <span class="req">*</span></label>'
      + '<div class="phone-group">'
      + '<span class="phone-prefix">'
      + '<svg viewBox="0 0 19 13" aria-hidden="true"><rect width="19" height="6.5" fill="#e70011"/><rect y="6.5" width="19" height="6.5" fill="#f4f4f4"/></svg>'
      + '+62'
      + '</span>'
      + '<input class="o-input att-wa" id="attWa_' + index + '" type="tel" inputmode="tel" placeholder="81234567890" />'
      + '</div>'
      + '</div>';

    // Restrict NIK & WA to digits
    var idNumEl = card.querySelector('#attIdNum_' + index);
    if (idNumEl) {
      idNumEl.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
        this.classList.remove('invalid');
      });
    }

    var waEl = card.querySelector('#attWa_' + index);
    if (waEl) {
      waEl.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        this.classList.remove('invalid');
      });
    }

    card.querySelectorAll('.o-input, .o-select').forEach(function (el) {
      el.addEventListener('input', function () { el.classList.remove('invalid'); });
      el.addEventListener('change', function () { el.classList.remove('invalid'); });
    });

    // Wire gender chips
    var gRow = card.querySelector('.att-gender-row');
    if (gRow) {
      var gChips = gRow.querySelectorAll('.gender-chip');
      gChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          var val = chip.getAttribute('data-gender');
          gRow.setAttribute('data-gender-value', val);
          gChips.forEach(function (c) {
            var on = c === chip;
            c.classList.toggle('active', on);
            c.classList.toggle('on', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
          });
          gRow.classList.remove('invalid');
        });
      });
    }

    // Wire Hapus button
    var btnHapus = card.querySelector('.btn-hapus-att');
    if (btnHapus) {
      btnHapus.addEventListener('click', function () {
        setQty(currentQty - 1);
      });
    }

    return card;
  }

  function syncAttendeeCardsCount(targetQty) {
    if (targetQty <= 1) {
      attendeesContainer.innerHTML = '';
      return;
    }

    var existing = attendeesContainer.querySelectorAll('.attendee-card');
    var existingCount = existing.length;
    var neededCount = targetQty - 1;

    if (existingCount < neededCount) {
      for (var i = existingCount + 1; i < targetQty; i++) {
        attendeesContainer.appendChild(createAttendeeCard(i));
      }
    } else if (existingCount > neededCount) {
      for (var j = existingCount - 1; j >= neededCount; j--) {
        attendeesContainer.removeChild(existing[j]);
      }
    }

    // Update category band labels
    var catName = selectedTc ? selectedTc.nama : 'Tiket Masuk Reguler';
    attendeesContainer.querySelectorAll('.att-band').forEach(function (b) {
      b.textContent = catName;
    });
  }

  /* Tombol Tambah Pemegang Tiket */
  var btnTambahTiket = document.getElementById('btnTambahTiket');
  var tambahTiketWrap = document.getElementById('tambahTiketWrap');
  if (btnTambahTiket) {
    btnTambahTiket.addEventListener('click', function () {
      if (currentQty >= 5) {
        showToast('Maksimal pembelian 5 tiket dalam 1 kali transaksi (BR-02).');
        return;
      }
      var maxQ = maxAllowedQty();
      if (currentQty >= maxQ) {
        showToast('Sisa stok kategori ini hanya cukup untuk ' + maxQ + ' tiket.');
        return;
      }
      setQty(currentQty + 1);
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

    if (tambahTiketWrap) {
      tambahTiketWrap.style.display = (currentQty >= 5 || currentQty >= maxAllowedQty()) ? 'none' : 'block';
    }

    updateMobileBar();
  }

  function maxAllowedQty() {
    var stok = selectedTc ? Number(selectedTc.stok_sisa) : 5;
    return Math.max(1, Math.min(5, currentQty + stok));
  }

  function setQty(n) {
    if (n > 5) {
      showToast('Maksimal pembelian 5 tiket dalam 1 kali transaksi (BR-02).');
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
      currentQty = Number(res.body.jumlah || n);
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
    updateMobileBar();
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
    updateMobileBar();
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

    // Attendee validations (Ticket 2..currentQty)
    for (var i = 1; i < currentQty; i++) {
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

      if (!attNikOk && !toastError) toastError = 'Nomor KTP/NIK Pemegang Tiket - ' + num + ' harus berupa 16 digit angka.';
      if (!attEmailOk && !toastError) toastError = 'Email Pemegang Tiket - ' + num + ' harus menggunakan alamat @gmail.com.';
      if (!attWaOk && !toastError) toastError = 'No. WhatsApp Pemegang Tiket - ' + num + ' harus berupa angka (minimal 9 digit).';
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
    updateMobileBar();
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
    updateMobileBar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closePay() {
    paySection.hidden = true;
    orderSection.hidden = false;
    updateMobileBar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.getElementById('btnBackPay').addEventListener('click', closePay);
  document.getElementById('btnPayBack').addEventListener('click', closePay);

  function updateBayarBtn() {
    var canPay = (selectedMethod !== '' && agreeTnc.checked && agreePrivacy.checked);
    btnBayar.disabled = !canPay;
    updateMobileBar();
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

  /* Sticky Bottom Bar Mobile (< 768px) Logic */
  var checkoutMobileBar = document.getElementById('checkoutMobileBar');
  var cmbPrice = document.getElementById('cmbPrice');
  var cmbBtn = document.getElementById('cmbBtn');

  function updateMobileBar() {
    if (!checkoutMobileBar || !cmbPrice || !cmbBtn) return;

    // Step 1: Pilih Kategori
    if (!ticketSection.hidden) {
      checkoutMobileBar.hidden = false;
      if (selectedTc) {
        var sub = Number(selectedTc.harga) * selectedTicketQty;
        cmbPrice.textContent = (Number(selectedTc.harga) === 0) ? 'GRATIS' : fmtRupiah(sub);
        cmbBtn.disabled = false;
        cmbBtn.textContent = 'Lanjutkan';
      } else {
        var freeEv = currentEvent && Number(currentEvent.harga_mulai) === 0;
        cmbPrice.textContent = freeEv ? 'GRATIS' : fmtRupiah(currentEvent ? currentEvent.harga_mulai : 0);
        cmbBtn.disabled = true;
        cmbBtn.textContent = 'Pilih Kategori';
      }
      return;
    }

    // Step 2: Detail Pesanan
    if (!orderSection.hidden) {
      checkoutMobileBar.hidden = false;
      var totalStep2 = unitPrice * currentQty;
      var isFreeStep2 = selectedTc && Number(selectedTc.harga) === 0;
      cmbPrice.textContent = isFreeStep2 ? 'GRATIS' : fmtRupiah(totalStep2);
      cmbBtn.disabled = false;
      cmbBtn.textContent = 'Lanjutkan';
      return;
    }

    // Step 3: Metode Pembayaran
    if (!paySection.hidden) {
      checkoutMobileBar.hidden = false;
      var t = totals();
      cmbPrice.textContent = t.isFree ? 'GRATIS' : fmtRupiah(t.total);
      var canPay = (selectedMethod !== '' && agreeTnc && agreeTnc.checked && agreePrivacy && agreePrivacy.checked);
      cmbBtn.disabled = !canPay;
      cmbBtn.textContent = 'Bayar Sekarang';
      return;
    }

    // Hidden elsewhere
    checkoutMobileBar.hidden = true;
  }

  if (cmbBtn) {
    cmbBtn.addEventListener('click', function () {
      if (!ticketSection.hidden) {
        btnBeli.click();
      } else if (!orderSection.hidden) {
        document.getElementById('btnLanjut').click();
      } else if (!paySection.hidden) {
        btnBayar.click();
      }
    });
  }

  /* Attendees Payload for Checkout Pay */
  function collectBuyerPayload() {
    return {
      nama: document.getElementById('buyerName').value.trim(),
      tipe_identitas: 'KTP',
      nomor_identitas: document.getElementById('buyerIdNum').value.trim(),
      email: document.getElementById('buyerEmail').value.trim(),
      wa: waDisplay(document.getElementById('buyerWa').value)
    };
  }

  function collectAttendeesPayload() {
    var list = [];
    var bGenderRow = document.getElementById('buyerGenderRow');
    var bGenderVal = bGenderRow ? (bGenderRow.getAttribute('data-gender-value') || 'Laki-laki') : 'Laki-laki';

    // Ticket 1: Buyer
    list.push({
      nama: document.getElementById('buyerName').value.trim(),
      tipe_identitas: 'KTP',
      nomor_identitas: document.getElementById('buyerIdNum').value.trim(),
      email: document.getElementById('buyerEmail').value.trim(),
      jenis_kelamin: bGenderVal,
      wa: waDisplay(document.getElementById('buyerWa').value)
    });

    // Ticket 2..currentQty: Attendees
    for (var i = 1; i < currentQty; i++) {
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
CODE;

// Also remove old collectBuyerPayload & collectAttendeesPayload below
$oldCollectBuyer = "  function collectBuyerPayload() {";
$oldCollectAttendees = "  function renderQr(el, text) {";

$posCollectStart = strpos($js, $oldCollectBuyer, $posEnd);
$posCollectEnd = strpos($js, $oldCollectAttendees, $posCollectStart);

if ($posCollectStart !== false && $posCollectEnd !== false) {
    // Replace old collect functions with empty string
    $js = substr($js, 0, $posCollectStart) . substr($js, $posCollectEnd);
}

// Now replace the main chunk
$posStart = strpos($js, $oldChunkStart);
$posEnd = strpos($js, $oldChunkEnd);
$js = substr($js, 0, $posStart) . $replacementChunk . "\n\n  " . substr($js, $posEnd);

file_put_contents($file, $js);
echo "Phase 2 patch saved!\n";
