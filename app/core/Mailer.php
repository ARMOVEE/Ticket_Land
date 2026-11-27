<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/Libraries/PHPMailer/Exception.php';
require_once dirname(__DIR__) . '/Libraries/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/Libraries/PHPMailer/SMTP.php';

class Mailer
{
    /**
     * Kirim email menggunakan PHPMailer SMTP.
     * Jika konfigurasi belum lengkap atau gagal kirim, tulis ke storage/logs/mail.log.
     */
    public static function send(string $to, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $host = Env::get('SMTP_HOST', 'smtp.gmail.com');
        $port = (int) Env::get('SMTP_PORT', '587');
        $secure = strtolower(Env::get('SMTP_SECURE', 'tls'));
        $user = Env::get('SMTP_USER', '');
        $pass = Env::get('SMTP_PASS', '');
        $from = Env::get('SMTP_FROM', '');
        $fromName = Env::get('SMTP_FROM_NAME', 'Ticket Land');

        if ($from === '' && $user !== '') {
            $from = $user;
        }

        // Jika kredensial SMTP belum diset (mode offline/demo fallback)
        if ($user === '' || $pass === '') {
            self::logMail(
                '[FALLBACK - SMTP CREDENTIALS NOT SET]',
                $to,
                $toName,
                $subject,
                $htmlBody,
                $textBody,
                'SMTP_USER atau SMTP_PASS belum diset di .env. Email disimpan ke log server untuk keperluan demo.'
            );
            return true;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;

            if ($secure === 'ssl' || $port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

            $mail->send();

            self::logMail(
                '[SUCCESS - SENT VIA SMTP]',
                $to,
                $toName,
                $subject,
                $htmlBody,
                $textBody,
                'Email berhasil dikirim via SMTP Gmail (' . $host . ':' . $port . ').'
            );
            return true;
        } catch (\Throwable $e) {
            self::logMail(
                '[ERROR - SMTP FAILED]',
                $to,
                $toName,
                $subject,
                $htmlBody,
                $textBody,
                'Pengiriman SMTP gagal: ' . $e->getMessage() . '. Email dicatat di log server agar alur demo tetap lancar.'
            );
            return true; // Jangan throw/crash agar simulasi pembayaran tetap berlanjut
        }
    }

    /**
     * Catat email ke berkas log server storage/logs/mail.log
     */
    private static function logMail(
        string $statusTag,
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $note
    ): void {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/mail.log';
        $timestamp = date('Y-m-d H:i:s');
        $border = str_repeat('=', 80);

        $logEntry = <<<LOG

{$border}
WAKTU   : {$timestamp}
STATUS  : {$statusTag}
CATATAN : {$note}
KEPADA  : {$toName} <{$to}>
SUBJEK  : {$subject}
{$border}
--- TEKS PLAIN / RINGKASAN ---
{$textBody}

--- KONTEN HTML ---
{$htmlBody}

{$border}

LOG;

        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Konversi bilangan integer ke kalimat terbilang bahasa Indonesia.
     * Contoh: 900000 -> Sembilan Ratus Ribu
     */
    public static function terbilang(int $angka): string
    {
        $angka = abs($angka);
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 12) {
            $hasil = ' ' . $huruf[$angka];
        } elseif ($angka < 20) {
            $hasil = self::terbilang($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $hasil = self::terbilang((int) ($angka / 10)) . ' Puluh' . self::terbilang($angka % 10);
        } elseif ($angka < 200) {
            $hasil = ' Seratus' . self::terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = self::terbilang((int) ($angka / 100)) . ' Ratus' . self::terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $hasil = ' Seribu' . self::terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = self::terbilang((int) ($angka / 1000)) . ' Ribu' . self::terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $hasil = self::terbilang((int) ($angka / 1000000)) . ' Juta' . self::terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $hasil = self::terbilang((int) ($angka / 1000000000)) . ' Miliar' . self::terbilang($angka % 1000000000);
        } else {
            $hasil = (string) $angka;
        }

        return trim($hasil);
    }

    /**
     * Deteksi IP LAN / Wi-Fi aktif laptop secara dinamis (tanpa hardcode IP DHCP).
     */
    public static function detectLanIp(): string
    {
        // 1. Tanya tabel routing OS lewat UDP socket (tanpa mengirim paket jaringan)
        if (function_exists('stream_socket_client') && function_exists('stream_socket_get_name')) {
            $sock = @\stream_socket_client('udp://8.8.8.8:80', $errno, $errstr, 1);
            if (is_resource($sock)) {
                $sockName = @\stream_socket_get_name($sock, false);
                @\fclose($sock);
                if (is_string($sockName) && $sockName !== '') {
                    $parts = explode(':', $sockName);
                    $ip = trim($parts[0] ?? '');
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_starts_with($ip, '127.')) {
                        return $ip;
                    }
                }
            }
        }

        // 2. Fallback Windows: cari adapter di ipconfig yang memiliki Default Gateway aktif
        if (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
            $out = (string) @shell_exec('ipconfig');
            if ($out !== '') {
                $blocks = preg_split('/\r?\n\r?\n/', $out) ?: [];
                foreach ($blocks as $block) {
                    if (preg_match('/Default Gateway[ .\t]*:\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/i', $block)) {
                        if (preg_match('/IPv4 Address[ .\t]*:\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/i', $block, $mIp)) {
                            $cand = trim($mIp[1]);
                            if (!str_starts_with($cand, '127.')) {
                                return $cand;
                            }
                        }
                    }
                }
            }
        }

        // 3. Fallback hostname resolver
        $hostName = @gethostname();
        if (is_string($hostName) && $hostName !== '') {
            $resolved = @gethostbyname($hostName);
            if (is_string($resolved) && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_starts_with($resolved, '127.')) {
                return $resolved;
            }
        }

        return '';
    }

    /**
     * Mendapatkan base URL aplikasi untuk link email agar selalu bisa dibuka dari HP pembeli (LAN IP / Host Request, bukan localhost).
     */
    public static function getAppUrl(): string
    {
        $isLoopback = static function (string $host): bool {
            $h = strtolower(trim($host, '[]'));
            return $h === '' || $h === 'localhost' || $h === '127.0.0.1' || $h === '::1';
        };

        $isHttpsReq = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        $reqScheme = $isHttpsReq ? 'https' : 'http';

        $rawHttpHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $reqHost = '';
        $reqPort = '';
        if ($rawHttpHost !== '') {
            $parsedReq = @parse_url("{$reqScheme}://{$rawHttpHost}");
            $reqHost = (string) ($parsedReq['host'] ?? '');
            if (!empty($parsedReq['port'])) {
                $p = (int) $parsedReq['port'];
                if (($reqScheme === 'http' && $p !== 80) || ($reqScheme === 'https' && $p !== 443)) {
                    $reqPort = ':' . $p;
                }
            }
        }

        // (a) UTAMAKAN: Gunakan host request saat pesanan dibuat jika bukan localhost/loopback
        if ($reqHost !== '' && !$isLoopback($reqHost)) {
            return "{$reqScheme}://{$reqHost}{$reqPort}";
        }

        // (b) Jika diakses dari localhost di laptop (atau CLI), deteksi IP LAN aktif laptop secara dinamis
        $lanIp = self::detectLanIp();

        // Cek juga APP_URL di .env sebagai fallback konfigurasi scheme/port/host
        $envUrl = rtrim(Env::get('APP_URL', ''), '/');
        $envScheme = 'http';
        $envHost = '';
        $envPort = ':8000';
        if ($envUrl !== '') {
            $parsedEnv = @parse_url($envUrl);
            if (!empty($parsedEnv['scheme'])) {
                $envScheme = (string) $parsedEnv['scheme'];
            }
            if (!empty($parsedEnv['host'])) {
                $envHost = (string) $parsedEnv['host'];
            }
            if (isset($parsedEnv['port'])) {
                $ep = (int) $parsedEnv['port'];
                $envPort = (($envScheme === 'http' && $ep !== 80) || ($envScheme === 'https' && $ep !== 443)) ? ':' . $ep : '';
            } else {
                $envPort = '';
            }
        }

        // Pilih scheme dan port: utamakan dari request aktif jika ada, fallback ke APP_URL
        $finalScheme = ($rawHttpHost !== '') ? $reqScheme : $envScheme;
        $finalPort   = ($rawHttpHost !== '') ? $reqPort   : $envPort;

        if ($lanIp !== '') {
            return "{$finalScheme}://{$lanIp}{$finalPort}";
        }

        if ($envHost !== '' && !$isLoopback($envHost)) {
            return "{$finalScheme}://{$envHost}{$finalPort}";
        }

        $fallbackHost = $reqHost !== '' ? $reqHost : ($envHost !== '' ? $envHost : 'localhost');
        return "{$finalScheme}://{$fallbackHost}{$finalPort}";
    }

    /**
     * Kirim email Faktur / Invoice gaya resmi PLN e-Billing dengan Box Pembatalan
     */
    public static function sendInvoiceEmail(array $order): bool
    {
        $pemesanNama = htmlspecialchars($order['pemesan']['nama'] ?? $order['pemesan_nama'] ?? 'Pelanggan Setia');
        $pemesanEmail = $order['pemesan']['email'] ?? $order['pemesan_email'] ?? '';
        $nomorInvoice = htmlspecialchars($order['nomor_invoice'] ?? '-');
        $cancelCode = htmlspecialchars($order['cancel_code'] ?? '');
        $eventNama = htmlspecialchars($order['event_nama'] ?? 'Event Ticket Land');
        $kategoriTiket = htmlspecialchars($order['kategori_tiket_nama'] ?? 'Reguler');
        $jumlah = (int) ($order['jumlah'] ?? 1);
        $subtotal = (int) ($order['subtotal'] ?? 0);
        $biayaLayanan = (int) ($order['biaya_layanan'] ?? 0);
        $total = (int) ($order['total'] ?? 0);
        $metode = htmlspecialchars($order['metode_pembayaran'] ?? 'QRIS');
        $tanggal = htmlspecialchars($order['tanggal_transaksi'] ?? date('d F Y • H:i') . ' WIB');
        $venue = htmlspecialchars($order['venue'] ?? '-');
        $kota = htmlspecialchars($order['kota'] ?? 'Jakarta');

        $hargaSatuan = $jumlah > 0 ? (int) round($subtotal / $jumlah) : $subtotal;
        $terbilangTeks = self::terbilang($total) . ' Rupiah';
        $hargaSatuanFmt = number_format($hargaSatuan, 0, ',', '.');
        $subtotalFmt = number_format($subtotal, 0, ',', '.');
        $biayaLayananFmt = number_format($biayaLayanan, 0, ',', '.');
        $totalFmt = number_format($total, 0, ',', '.');
        $todayDate = date('d F Y');
        $currentYear = date('Y');

        $appUrl = self::getAppUrl();
        $cancelLink = "{$appUrl}/batalkan?kode={$cancelCode}";

        $subject = "Faktur Pembayaran Tiket Land #{$nomorInvoice} - {$eventNama}";

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Faktur Pembayaran Ticket Land</title>
<style type="text/css">
  body, html { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; color: #1e293b; line-height: 1.5; }
  .email-wrapper { width: 100%; background-color: #f4f6f8; padding: 20px 12px; box-sizing: border-box; }
  .email-container { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); box-sizing: border-box; }
  .header { background: #0B5D3B; color: #ffffff; padding: 24px 28px; display: table; width: 100%; box-sizing: border-box; }
  .header-left { display: table-cell; vertical-align: middle; }
  .header-right { display: table-cell; vertical-align: middle; text-align: right; }
  .brand-title { font-size: 22px; font-weight: 800; letter-spacing: 0.5px; margin: 0; }
  .brand-subtitle { font-size: 12px; color: #E2F2E9; margin: 4px 0 0 0; opacity: 0.9; }
  .invoice-tag { display: inline-block; background: #E2F2E9; color: #0B5D3B; font-weight: 700; font-size: 12px; padding: 4px 12px; border-radius: 6px; letter-spacing: 1px; white-space: nowrap; }
  .content { padding: 28px; box-sizing: border-box; }
  .letter-salutation { font-size: 15px; margin-bottom: 8px; }
  .letter-subject { font-size: 14px; font-weight: 600; color: #0B5D3B; margin-bottom: 20px; border-bottom: 2px solid #E2F2E9; padding-bottom: 8px; }
  .meta-table { width: 100%; margin-bottom: 24px; font-size: 13px; border-collapse: collapse; table-layout: fixed; }
  .meta-table td { padding: 4px 0; vertical-align: top; word-break: break-word; overflow-wrap: anywhere; }
  .meta-label { color: #64748b; width: 120px; padding-right: 8px; }
  .meta-value { font-weight: 600; color: #0f172a; }
  .m-order-head { display: none; }
  .m-col-label { display: none; }
  .order-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; table-layout: auto; }
  .order-table th { background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #cbd5e1; padding: 10px; text-align: left; font-weight: 600; color: #475569; }
  .order-table td { padding: 11px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; word-break: break-word; }
  .text-right { text-align: right; }
  .total-row td { font-weight: 700; font-size: 15px; color: #0B5D3B; background: #f8fafc; }
  .badge-paid { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
  .terbilang-box { background: #f8fafc; border-left: 4px solid #0B5D3B; padding: 12px 16px; margin-bottom: 24px; font-size: 13px; font-style: italic; color: #334155; word-break: break-word; }
  .cancel-box { background: #E2F2E9; border: 1.5px dashed #0B5D3B; border-radius: 10px; padding: 20px 24px; margin: 28px 0; box-sizing: border-box; }
  .cancel-title { font-size: 15px; font-weight: 700; color: #0B5D3B; margin: 0 0 8px 0; }
  .cancel-desc { font-size: 13px; color: #1e3a2b; margin: 0 0 14px 0; line-height: 1.45; }
  .cancel-code-badge { background: #ffffff; border: 2px solid #0B5D3B; color: #0B5D3B; font-family: monospace; font-size: 20px; font-weight: 800; letter-spacing: 4px; padding: 8px 18px; border-radius: 6px; display: inline-block; margin-bottom: 12px; }
  .cancel-btn { display: inline-block; background: #0B5D3B; color: #ffffff !important; text-decoration: none; font-size: 13px; font-weight: 600; padding: 10px 20px; border-radius: 6px; box-sizing: border-box; }
  .signature-section { margin-top: 32px; display: table; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 24px; box-sizing: border-box; }
  .sig-left { display: table-cell; vertical-align: top; font-size: 12px; color: #64748b; line-height: 1.5; padding-right: 12px; }
  .sig-right { display: table-cell; vertical-align: top; text-align: right; width: 200px; font-size: 13px; }
  .sig-stamp { font-size: 11px; color: #0B5D3B; font-weight: 600; border: 1.5px solid #0B5D3B; display: inline-block; padding: 4px 8px; border-radius: 4px; margin-top: 8px; }
  .footer { background: #f8fafc; padding: 18px 24px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; box-sizing: border-box; }

  @media only screen and (max-width: 600px) {
    .email-wrapper { padding: 8px 6px !important; }
    .email-container { width: 100% !important; max-width: 100% !important; border-radius: 10px !important; }
    .header { padding: 18px 16px !important; display: block !important; }
    .header-left, .header-right { display: block !important; width: 100% !important; text-align: left !important; }
    .header-right { margin-top: 10px !important; }
    .brand-title { font-size: 20px !important; }
    .content { padding: 18px 14px !important; }
    .meta-table td { font-size: 12.5px !important; padding: 4px 0 !important; }
    .meta-label { width: 108px !important; }

    /* Ubah Tabel Rincian Pembelian menjadi Blok Bertumpuk (Stacked) di HP */
    .m-order-head {
      display: block !important;
      background: #f8fafc !important;
      border-top: 1px solid #e2e8f0 !important;
      border-bottom: 2px solid #cbd5e1 !important;
      padding: 9px 12px !important;
      font-size: 12.5px !important;
      font-weight: 700 !important;
      color: #475569 !important;
    }
    .order-table,
    .order-table tbody,
    .order-table tr,
    .order-table td {
      display: block !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .order-table thead {
      display: none !important;
    }
    .order-table tr.item-row {
      border-bottom: 1px solid #cbd5e1 !important;
      padding-bottom: 4px !important;
    }
    .order-table td.col-item {
      padding: 12px 12px 8px 12px !important;
      border-bottom: 1px dashed #e2e8f0 !important;
      text-align: left !important;
    }
    .order-table td.col-qty,
    .order-table td.col-price,
    .order-table td.col-subtotal {
      padding: 7px 12px !important;
      text-align: right !important;
      border-bottom: 1px dashed #f1f5f9 !important;
      overflow: hidden !important;
    }
    .order-table td.col-subtotal {
      border-bottom: none !important;
      padding-bottom: 10px !important;
    }
    .order-table td.fee-label-cell,
    .order-table td.total-label-cell {
      display: none !important;
    }
    .order-table td.fee-val-cell {
      padding: 9px 12px !important;
      text-align: right !important;
      border-bottom: 1px solid #e2e8f0 !important;
      overflow: hidden !important;
    }
    .order-table td.total-val-cell {
      padding: 11px 12px !important;
      text-align: right !important;
      background: #f8fafc !important;
      font-weight: 700 !important;
      font-size: 14.5px !important;
      color: #0B5D3B !important;
      overflow: hidden !important;
    }
    .m-col-label {
      display: inline-block !important;
      float: left !important;
      text-align: left !important;
      color: #64748b !important;
      font-weight: 600 !important;
      max-width: 62% !important;
    }
    .total-val-cell .m-col-label {
      color: #0B5D3B !important;
      font-weight: 700 !important;
    }

    .cancel-box { padding: 16px 14px !important; margin: 20px 0 !important; }
    .cancel-btn { width: 100% !important; text-align: center !important; }
    .signature-section, .sig-left, .sig-right {
      display: block !important;
      width: 100% !important;
      text-align: left !important;
      padding-right: 0 !important;
    }
    .sig-right {
      margin-top: 16px !important;
      padding-top: 14px !important;
      border-top: 1px dashed #e2e8f0 !important;
    }
    .footer { padding: 14px 16px !important; }
  }
</style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #f4f6f8; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5;">
<div class="email-wrapper" style="width: 100%; background-color: #f4f6f8; padding: 20px 12px; box-sizing: border-box;">
<div class="email-container" style="width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-sizing: border-box;">
  <div class="header" style="background: #0B5D3B; color: #ffffff; padding: 24px 28px; display: table; width: 100%; box-sizing: border-box;">
    <div class="header-left" style="display: table-cell; vertical-align: middle;">
      <div class="brand-title" style="font-size: 22px; font-weight: 800; letter-spacing: 0.5px; margin: 0;">TICKET LAND</div>
      <div class="brand-subtitle" style="font-size: 12px; color: #E2F2E9; margin: 4px 0 0 0; opacity: 0.9;">Platform Pemesanan Tiket Resmi Event &amp; Hiburan</div>
    </div>
    <div class="header-right" style="display: table-cell; vertical-align: middle; text-align: right;">
      <div class="invoice-tag" style="display: inline-block; background: #E2F2E9; color: #0B5D3B; font-weight: 700; font-size: 12px; padding: 4px 12px; border-radius: 6px; letter-spacing: 1px; white-space: nowrap;">FAKTUR RESMI</div>
    </div>
  </div>

  <div class="content" style="padding: 28px; box-sizing: border-box;">
    <div class="letter-salutation" style="font-size: 15px; margin-bottom: 8px;">
      Kepada Yth. <strong>Bapak/Ibu {$pemesanNama}</strong>,
    </div>
    <div class="letter-subject" style="font-size: 14px; font-weight: 600; color: #0B5D3B; margin-bottom: 20px; border-bottom: 2px solid #E2F2E9; padding-bottom: 8px;">
      Hal: Informasi Konfirmasi Pembayaran dan Faktur Elektronik (e-Billing)
    </div>

    <p style="font-size: 13px; color: #475569; margin-bottom: 20px;">
      Terima kasih telah mempercayakan pembelian tiket event Anda di Ticket Land. Pembayaran Anda telah kami terima dan diverifikasi secara otomatis oleh sistem kami. Berikut adalah rincian lengkap transaksi Anda:
    </p>

    <table class="meta-table" style="width: 100%; margin-bottom: 24px; font-size: 13px; border-collapse: collapse; table-layout: fixed;">
      <tr>
        <td class="meta-label" style="color: #64748b; width: 120px; padding: 4px 8px 4px 0; vertical-align: top;">Nomor Faktur</td>
        <td class="meta-value" style="font-weight: 600; color: #0f172a; padding: 4px 0; vertical-align: top; word-break: break-word;">: {$nomorInvoice}</td>
      </tr>
      <tr>
        <td class="meta-label" style="color: #64748b; width: 120px; padding: 4px 8px 4px 0; vertical-align: top;">Tanggal Terbit</td>
        <td class="meta-value" style="font-weight: 600; color: #0f172a; padding: 4px 0; vertical-align: top; word-break: break-word;">: {$tanggal}</td>
      </tr>
      <tr>
        <td class="meta-label" style="color: #64748b; width: 120px; padding: 4px 8px 4px 0; vertical-align: top;">Metode Bayar</td>
        <td class="meta-value" style="font-weight: 600; color: #0f172a; padding: 4px 0; vertical-align: top; word-break: break-word;">: {$metode} &nbsp;<span class="badge-paid" style="background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">LUNAS</span></td>
      </tr>
      <tr>
        <td class="meta-label" style="color: #64748b; width: 120px; padding: 4px 8px 4px 0; vertical-align: top;">Nama Event</td>
        <td class="meta-value" style="font-weight: 600; color: #0f172a; padding: 4px 0; vertical-align: top; word-break: break-word;">: {$eventNama}</td>
      </tr>
      <tr>
        <td class="meta-label" style="color: #64748b; width: 120px; padding: 4px 8px 4px 0; vertical-align: top;">Lokasi / Venue</td>
        <td class="meta-value" style="font-weight: 600; color: #0f172a; padding: 4px 0; vertical-align: top; word-break: break-word;">: {$venue}, {$kota}</td>
      </tr>
    </table>

    <div class="m-order-head" style="display: none;">Rincian Pembelian</div>
    <table class="order-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
      <thead>
        <tr>
          <th style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #cbd5e1; padding: 10px; text-align: left; font-weight: 600; color: #475569;">Rincian Pembelian</th>
          <th style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #cbd5e1; padding: 10px 6px; text-align: center; font-weight: 600; color: #475569; width: 12%;">Jumlah</th>
          <th class="text-right" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #cbd5e1; padding: 10px 8px; text-align: right; font-weight: 600; color: #475569; width: 22%;">Harga Satuan</th>
          <th class="text-right" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 2px solid #cbd5e1; padding: 10px; text-align: right; font-weight: 600; color: #475569; width: 22%;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <tr class="item-row">
          <td class="col-item" style="padding: 11px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; word-break: break-word;">
            <strong>{$eventNama}</strong><br>
            <span style="color: #64748b; font-size: 12px;">Kategori: {$kategoriTiket}</span>
          </td>
          <td class="col-qty" style="padding: 11px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; text-align: center;">
            <span class="m-col-label" style="display: none;">Jumlah</span>
            <span>{$jumlah}</span>
          </td>
          <td class="col-price text-right" style="padding: 11px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; text-align: right; white-space: nowrap;">
            <span class="m-col-label" style="display: none;">Harga Satuan</span>
            <span>Rp{$hargaSatuanFmt}</span>
          </td>
          <td class="col-subtotal text-right" style="padding: 11px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; text-align: right; white-space: nowrap;">
            <span class="m-col-label" style="display: none;">Subtotal</span>
            <span style="font-weight: 600; color: #0f172a;">Rp{$subtotalFmt}</span>
          </td>
        </tr>
        <tr class="fee-row">
          <td colspan="3" class="fee-label-cell text-right" style="padding: 11px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #64748b;">Biaya Layanan &amp; Pemrosesan Sistem:</td>
          <td class="fee-val-cell text-right" style="padding: 11px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; white-space: nowrap;">
            <span class="m-col-label" style="display: none;">Biaya Layanan &amp; Pemrosesan Sistem:</span>
            <span>Rp{$biayaLayananFmt}</span>
          </td>
        </tr>
        <tr class="total-row">
          <td colspan="3" class="total-label-cell text-right" style="padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; font-size: 15px; color: #0B5D3B; background: #f8fafc;">TOTAL PEMBAYARAN:</td>
          <td class="total-val-cell text-right" style="padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; font-size: 15px; color: #0B5D3B; background: #f8fafc; white-space: nowrap;">
            <span class="m-col-label" style="display: none;">TOTAL PEMBAYARAN:</span>
            <span>Rp{$totalFmt}</span>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="terbilang-box" style="background: #f8fafc; border-left: 4px solid #0B5D3B; padding: 12px 16px; margin-bottom: 24px; font-size: 13px; font-style: italic; color: #334155;">
      <strong>Terbilang:</strong> {$terbilangTeks}
    </div>

    <!-- BOX PEMBATALAN PESANAN (HANYA ADA DI EMAIL, TIDAK ADA DI WEB) -->
    <div class="cancel-box" style="background: #E2F2E9; border: 1.5px dashed #0B5D3B; border-radius: 10px; padding: 20px 24px; margin: 28px 0; box-sizing: border-box;">
      <div class="cancel-title" style="font-size: 15px; font-weight: 700; color: #0B5D3B; margin: 0 0 8px 0;">
        📌 PUSAT PEMBATALAN PESANAN (SELF-SERVICE REFUND)
      </div>
      <p class="cancel-desc" style="font-size: 13px; color: #1e3a2b; margin: 0 0 14px 0; line-height: 1.45;">
        Apabila Anda berhalangan hadir atau perlu membatalkan pesanan ini, Anda dapat mengajukan pembatalan secara mandiri menggunakan kode unik verifikasi rahasia berikut:
      </p>
      <div style="text-align: center; margin: 12px 0;">
        <span class="cancel-code-badge" style="background: #ffffff; border: 2px solid #0B5D3B; color: #0B5D3B; font-family: monospace; font-size: 20px; font-weight: 800; letter-spacing: 4px; padding: 8px 18px; border-radius: 6px; display: inline-block; margin-bottom: 12px;">{$cancelCode}</span>
      </div>
      <p class="cancel-desc" style="font-size: 13px; color: #1e3a2b; margin: 0 0 16px 0; line-height: 1.45; text-align: center;">
        Klik tombol di bawah ini untuk menuju formulir verifikasi pembatalan tiket:
      </p>
      <div style="text-align: center;">
        <a href="{$cancelLink}" class="cancel-btn" target="_blank" style="display: inline-block; background: #0B5D3B; color: #ffffff !important; text-decoration: none; font-size: 13px; font-weight: 600; padding: 10px 20px; border-radius: 6px; box-sizing: border-box;">Ajukan Batalkan Pesanan</a>
      </div>
      <p style="font-size: 11px; color: #475569; margin: 12px 0 0 0; text-align: center; word-break: break-word;">
        Atau buka tautan berikut: <a href="{$cancelLink}" style="color: #0B5D3B; word-break: break-all;">{$cancelLink}</a><br>
        <em>*Penting: Kode unik 6-karakter ini bersifat rahasia dan merupakan kunci otorisasi pembatalan pesanan Anda. Jangan bagikan kepada pihak lain.</em>
      </p>
    </div>

    <div class="signature-section" style="margin-top: 32px; display: table; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 24px; box-sizing: border-box;">
      <div class="sig-left" style="display: table-cell; vertical-align: top; font-size: 12px; color: #64748b; line-height: 1.5; padding-right: 12px;">
        <strong>Pemberitahuan Sistem:</strong><br>
        • E-tiket resmi ber-QR Code dapat diunduh pada halaman pesanan web Anda.<br>
        • Tunjukkan QR Code saat memasuki gerbang pemeriksaan (Gate Check-in).<br>
        • Dokumen ini merupakan bukti pembayaran resmi yang sah dari Ticket Land.
      </div>
      <div class="sig-right" style="display: table-cell; vertical-align: top; text-align: right; width: 200px; font-size: 13px;">
        Jakarta, {$todayDate}<br>
        <strong>Ticket Land Finance System</strong><br><br>
        <span class="sig-stamp" style="font-size: 11px; color: #0B5D3B; font-weight: 600; border: 1.5px solid #0B5D3B; display: inline-block; padding: 4px 8px; border-radius: 4px; margin-top: 8px;">VERIFIED ELECTRONICALLY</span><br>
        <small style="color: #64748b; font-size: 11px;">Otorisasi Sistem Terpadu</small>
      </div>
    </div>
  </div>

  <div class="footer" style="background: #f8fafc; padding: 18px 24px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; box-sizing: border-box;">
    Email ini dikirimkan secara otomatis oleh Sistem Otomasi Ticket Land.<br>
    &copy; {$currentYear} Ticket Land Indonesia. Hak Cipta Dilindungi Undang-Undang.
  </div>
</div>
</div>
</body>
</html>
HTML;

        $textBody = <<<TEXT
================================================================================
FAKTUR PEMBAYARAN TICKET LAND - LUNAS
================================================================================
Kepada Yth. Bapak/Ibu {$pemesanNama}
Nomor Faktur   : {$nomorInvoice}
Tanggal        : {$tanggal}
Event          : {$eventNama} ({$venue}, {$kota})
Kategori Tiket : {$kategoriTiket}
Jumlah Tiket   : {$jumlah}
Total Bayar    : Rp{$totalFmt} (LUNAS via {$metode})
Terbilang      : {$terbilangTeks}

--------------------------------------------------------------------------------
INFORMASI PEMBATALAN PESANAN (SELF-SERVICE)
--------------------------------------------------------------------------------
Jika Anda perlu membatalkan pesanan ini, gunakan kode unik pembatalan rahasia Anda:
Kode Unik : {$cancelCode}

Ajukan pembatalan lewat tautan:
{$cancelLink}

*Penting: Kode unik dan tautan ini hanya tersedia di email ini dan tidak ditampilkan pada halaman web untuk menjaga keamanan tiket Anda.
================================================================================
TEXT;

        return self::send($pemesanEmail, $pemesanNama, $subject, $htmlBody, $textBody);
    }

    /**
     * Kirim email Konfirmasi Pembatalan Sukses
     */
    public static function sendCancellationConfirmation(array $order): bool
    {
        $pemesanNama = htmlspecialchars($order['pemesan_nama'] ?? 'Pelanggan Setia');
        $pemesanEmail = $order['pemesan_email'] ?? '';
        $nomorInvoice = htmlspecialchars($order['nomor_invoice'] ?? '-');
        $cancellationRef = htmlspecialchars($order['cancellation_ref'] ?? 'CXL-' . strtoupper(bin2hex(random_bytes(3))));
        $eventNama = htmlspecialchars($order['event_nama'] ?? 'Event');
        $kategoriTiket = htmlspecialchars($order['kategori_tiket_nama'] ?? 'Reguler');
        $total = (int) ($order['total'] ?? 0);
        $totalFmt = number_format($total, 0, ',', '.');
        $jumlah = (int) ($order['jumlah'] ?? 1);
        $waktu = date('d F Y • H:i') . ' WIB';
        $currentYear = date('Y');

        $subject = "Konfirmasi Pembatalan Pesanan Ticket Land #{$nomorInvoice} ({$cancellationRef})";

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; color: #1e293b; }
  .box { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
  .header { background: #0B5D3B; color: #fff; padding: 24px; text-align: center; }
  .badge-cancel { background: #fee2e2; color: #b91c1c; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 13px; }
  .content { padding: 32px; font-size: 14px; line-height: 1.6; }
  .ref-box { background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 20px 0; text-align: center; }
  .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="box">
  <div class="header">
    <h2 style="margin: 0; font-size: 20px;">TICKET LAND</h2>
    <div style="font-size: 12px; color: #E2F2E9; margin-top: 4px;">Konfirmasi Pengajuan Pembatalan Pesanan</div>
  </div>
  <div class="content">
    <p>Kepada Yth. <strong>{$pemesanNama}</strong>,</p>
    <p>Permintaan pembatalan tiket Anda telah <strong>berhasil diproses dan diverifikasi</strong> oleh sistem kami. Berikut rincian pembatalan Anda:</p>
    
    <div class="ref-box">
      <div style="font-size: 12px; color: #64748b;">NOMOR REFERENSI PEMBATALAN</div>
      <div style="font-size: 22px; font-weight: 800; color: #0B5D3B; font-family: monospace; margin: 6px 0;">{$cancellationRef}</div>
      <div><span class="badge-cancel">STATUS: DIBATALKAN</span></div>
    </div>

    <table style="width: 100%; font-size: 13px; border-collapse: collapse; margin-bottom: 20px;">
      <tr><td style="padding: 6px 0; color: #64748b;">Nomor Invoice</td><td style="font-weight: 600;">: {$nomorInvoice}</td></tr>
      <tr><td style="padding: 6px 0; color: #64748b;">Event</td><td style="font-weight: 600;">: {$eventNama}</td></tr>
      <tr><td style="padding: 6px 0; color: #64748b;">Jenis Tiket</td><td style="font-weight: 600;">: {$kategoriTiket} ({$jumlah} tiket)</td></tr>
      <tr><td style="padding: 6px 0; color: #64748b;">Nilai Refund</td><td style="font-weight: 600;">: Rp{$totalFmt}</td></tr>
      <tr><td style="padding: 6px 0; color: #64748b;">Waktu Pembatalan</td><td style="font-weight: 600;">: {$waktu}</td></tr>
    </table>

    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; font-size: 12px; color: #1e40af; border-radius: 4px;">
      <strong>Informasi Penting:</strong><br>
      • Tiket dan QR Code terkait pesanan ini telah dinonaktifkan permanen dan ditolak pada saat pemindaian Gate Staff.<br>
      • Dana akan dikembalikan melalui rekening atau metode pembayaran asal dalam waktu 3–5 hari kerja operasional.
    </div>
  </div>
  <div class="footer">
    &copy; {$currentYear} Ticket Land Indonesia. All Rights Reserved.
  </div>
</div>
</body>
</html>
HTML;

        $textBody = <<<TEXT
================================================================================
KONFIRMASI PEMBATALAN PESANAN TICKET LAND
================================================================================
Kepada Yth. {$pemesanNama}
Permintaan pembatalan Anda telah BERHASIL DIPROSES.

No. Referensi Pembatalan : {$cancellationRef}
Status                  : DIBATALKAN
Nomor Invoice           : {$nomorInvoice}
Event                   : {$eventNama}
Jenis Tiket             : {$kategoriTiket} ({$jumlah} tiket)
Nilai Refund            : Rp{$totalFmt}
Waktu                   : {$waktu}

Catatan: E-tiket yang dibatalkan tidak lagi berlaku untuk Gate Check-in.
Pengembalian dana diproses dalam 3-5 hari kerja.
================================================================================
TEXT;

        return self::send($pemesanEmail, $pemesanNama, $subject, $htmlBody, $textBody);
    }
}
