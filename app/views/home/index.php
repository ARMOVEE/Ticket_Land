<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ticket Land - Fondasi MVC &amp; Database</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --green: #0B5D3B;
      --mint: #E2F2E9;
      --ink: #131716;
      --muted: #5f6a66;
      --line: #e5e7eb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Plus Jakarta Sans", system-ui, sans-serif;
      color: var(--ink);
      background: #f4f7f5;
      padding: 32px 20px;
    }
    .wrap { max-width: 980px; margin: 0 auto; }
    .head {
      background: var(--green);
      color: #fff;
      padding: 28px 30px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }
    .head h1 { margin: 0; font-size: 24px; font-weight: 800; }
    .head p { margin: 6px 0 0; color: var(--mint); font-size: 14px; }
    .badge-ok {
      background: var(--mint);
      color: var(--green);
      padding: 8px 16px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 13.5px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 22px;
    }
    .card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 22px;
    }
    .card h2 { margin: 0 0 14px; font-size: 17px; font-weight: 800; color: var(--green); }
    table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    th, td { text-align: left; padding: 9px 8px; border-bottom: 1px solid var(--line); }
    th { color: var(--muted); font-weight: 700; font-size: 12px; text-transform: uppercase; }
    code {
      background: var(--mint);
      color: var(--green);
      padding: 3px 7px;
      border-radius: 6px;
      font-weight: 700;
    }
    .status-pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }
    .status-valid { background: var(--mint); color: var(--green); }
    .status-used { background: #fee2e2; color: #b91c1c; }
  </style>
</head>
<body>
  <main class="wrap">
    <header class="head">
      <div>
        <h1>Ticket Land - Langkah 0 (Fondasi MVC)</h1>
        <p>Koneksi PDO aktif ke basis data <strong><?= htmlspecialchars($dbName, ENT_QUOTES, 'UTF-8') ?></strong></p>
      </div>
      <span class="badge-ok">HTTP 200 &bull; PDO Connected</span>
    </header>

    <div class="grid">
      <section class="card">
        <h2>5 Kategori Event Resmi (D2)</h2>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Kategori</th>
              <th>Event Aktif</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><?= (int) $cat['id'] ?></td>
                <td><strong><?= htmlspecialchars($cat['nama'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= (int) $cat['jumlah_event'] ?> event</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="card">
        <h2>Kode Tiket Demo (D5)</h2>
        <table>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Pemegang</th>
              <th>Kategori</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($demoTickets as $t): ?>
              <tr>
                <td><code><?= htmlspecialchars($t['kode_tiket'], ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><?= htmlspecialchars($t['nama_pemegang'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($t['kategori_tiket'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?php if ($t['status_checkin'] === 'Sudah Check-in'): ?>
                    <span class="status-pill status-used">Sudah Check-in</span>
                  <?php else: ?>
                    <span class="status-pill status-valid">Belum Check-in</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>

    <section class="card" style="margin-top: 20px;">
      <h2>Daftar Event Demo Aktif (D3) - Total <?= count($events) ?> Event</h2>
      <table>
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Event</th>
            <th>Kategori</th>
            <th>Tanggal</th>
            <th>Kota</th>
            <th>Harga Mulai</th>
            <th>Sisa Stok</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td><code><?= htmlspecialchars($ev['kode'], ENT_QUOTES, 'UTF-8') ?></code></td>
              <td><strong><?= htmlspecialchars($ev['nama'], ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($ev['kategori_nama'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($ev['tanggal_label'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($ev['kota'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= (int) $ev['harga_mulai'] === 0 ? 'GRATIS' : 'Rp ' . number_format((int) $ev['harga_mulai'], 0, ',', '.') ?></td>
              <td><?= (int) $ev['total_stok'] <= 0 ? 'Habis' : (int) $ev['total_stok'] . ' tiket' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
