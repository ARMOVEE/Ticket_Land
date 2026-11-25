<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="color-scheme" content="light" />
    <link rel="icon" href="data:," />
    <title>Ticket Land - Login Staff Check-in</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
      :root {
        --primary: #0B5D3B;
        --primary-dark: #084A2E;
        --mint: #E2F2E9;
        --line: #E5E7EB;
      }
      body {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        background-color: #F8F9FA;
        color: #111827;
      }
      .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
      }
      .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
      }
      .btn-outline-primary {
        color: var(--primary);
        border-color: var(--primary);
      }
      .btn-outline-primary:hover {
        background-color: var(--primary);
        border-color: var(--primary);
        color: #fff;
      }
      .card {
        border-radius: 12px;
      }
    </style>
  </head>
  <body class="bg-light">
    <div class="container">
      <div class="row justify-content-center" style="margin-top: 8vh">
        <div class="col-md-5 col-lg-4">
          <div class="text-center mb-4">
            <i class="bx bx-qr-scan text-success" style="font-size: 3.5rem; color: #0B5D3B !important"></i>
            <h1 class="h3 fw-bold mt-2 mb-0">Login Staff</h1>
            <small class="text-muted">Check-in Tiket Event - Ticket Land</small>
          </div>
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h5 text-center mb-4">Login ke Akun Staff</h2>
              <div id="loginAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>
              <form id="formLoginStaff">
                <input type="hidden" name="csrf_token" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="mb-3">
                  <label for="usernameStaff" class="form-label">Username atau Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="usernameStaff" name="username" placeholder="staff atau staff@ticketland.id" required autofocus />
                  </div>
                </div>
                <div class="mb-3">
                  <label for="passwordStaff" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" id="passwordStaff" name="password" placeholder="Masukkan password" required />
                  </div>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Masuk sebagai Staff</button>
              </form>
              <div class="alert alert-info py-2 small mt-3 mb-0" role="alert">
                Akun demo: <code>staff@ticketland.id</code> / <code>ticket1234</code>
              </div>
            </div>
          </div>
          <p class="text-center mt-3 mb-0 small text-muted">Pengelola event? <a href="/admin" class="text-decoration-none fw-semibold" style="color: #0B5D3B">Masuk sebagai Admin</a></p>
        </div>
      </div>
    </div>

    <script>
      document.getElementById('formLoginStaff').addEventListener('submit', function (e) {
        e.preventDefault();
        var alertEl = document.getElementById('loginAlert');
        var btn = this.querySelector('button[type="submit"]');
        alertEl.classList.add('d-none');
        btn.disabled = true;

        var username = document.getElementById('usernameStaff').value.trim();
        var password = document.getElementById('passwordStaff').value;
        var csrf = document.getElementById('csrfToken').value;

        fetch('/api/staff/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
          },
          body: JSON.stringify({
            csrf_token: csrf,
            username: username,
            password: password
          })
        }).then(function (res) {
          return res.json().then(function (data) {
            return { status: res.status, body: data };
          });
        }).then(function (res) {
          btn.disabled = false;
          if (res.body && res.body.ok) {
            window.location.href = res.body.redirect || '/staff';
          } else {
            alertEl.textContent = (res.body && res.body.message) || 'Login gagal. Periksa username dan password.';
            alertEl.classList.remove('d-none');
          }
        }).catch(function () {
          btn.disabled = false;
          alertEl.textContent = 'Terjadi kesalahan koneksi server.';
          alertEl.classList.remove('d-none');
        });
      });
    </script>
  </body>
</html>
