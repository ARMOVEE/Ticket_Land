$targetDir = "public\assets\img\events"
if (!(Test-Path $targetDir)) {
    New-Item -ItemType Directory -Force -Path $targetDir | Out-Null
}
$sourceDir = "foto-event-ticket-land-4k-semua"
Copy-Item -Path "$sourceDir\*.webp" -Destination $targetDir -Force

$expected = @(
  'tulus-live-in-concert-4k.webp',
  'java-jazz-festival-4k.webp',
  'nadin-amizah-4k.webp',
  'artjog-4k.webp',
  'giias-4k.webp',
  'synchronize-fest-4k.webp',
  'iibf-4k.webp',
  'pandji-pragiwaksono-4k.webp',
  'bromo-4k.webp',
  'tech-in-asia-4k.webp',
  'sheila-on-7-4k.webp',
  'inacraft-4k.webp',
  'raditya-dika-4k.webp',
  'stand-up-fest-4k.webp',
  'abdur-arsyad-4k.webp',
  'labuan-bajo-4k.webp',
  'dieng-4k.webp',
  'derawan-4k.webp',
  'fintech-summit-4k.webp',
  'net-zero-summit-4k.webp',
  'brand-ecommerce-4k.webp'
)

$missing = @()
foreach ($f in $expected) {
    $p = Join-Path $targetDir $f
    if (!(Test-Path $p)) {
        $missing += $f
    }
}

if ($missing.Count -gt 0) {
    Write-Host "MISSING FILES:"
    $missing | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "ALL 21 FILES VERIFIED IN public\assets\img\events!"
    Get-ChildItem $targetDir -Filter "*.webp" | Select-Object Name, Length
}
