<?php
// ==========================================================
// Build Script - Otomatisasi Pembuatan & Upload Konfigurasi Clash
// ==========================================================

// Sertakan fungsi-fungsi yang sudah kita buat
require_once 'parser.php';
require_once 'generator.php';

// --- PENGATURAN ---
$uri_source_file = 'vless_uris.txt';
$template_file = 'template.yaml';
$output_prefix = 'clash_';
$output_extension = '.yaml';

date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu Anda

// --- FASE 1: BACA & GENERATE ---
echo "Memulai proses build...\n";

if (!file_exists($uri_source_file)) {
    die("ERROR: File sumber URI '{$uri_source_file}' tidak ditemukan.\n");
}
$textarea_input = file_get_contents($uri_source_file);
$parsed_proxies = parseMultipleVlessUris($textarea_input);

if (empty($parsed_proxies)) {
    die("ERROR: Tidak ada URI VLESS valid yang ditemukan di file sumber.\n");
}
$hasil_yaml = generateClashConfig($parsed_proxies, $template_file);

if ($hasil_yaml === false) {
    die("ERROR: Gagal membuat konten YAML dari template.\n");
}
echo "Konten YAML berhasil dibuat.\n";


// --- FASE 2: PENAMAAN FILE & PEMBERSIHAN ---
$timestamp = date('Ymd_His'); // Format: TAHUNBULANTANGGAL_JAMMENITDETIK
$new_filename = $output_prefix . $timestamp . $output_extension;

echo "Nama file baru: {$new_filename}\n";

// Hapus file clash_*.yaml lama di direktori
echo "Membersihkan file konfigurasi lama...\n";
$old_files = glob($output_prefix . '*' . $output_extension);
foreach ($old_files as $file) {
    unlink($file);
    echo "  - Menghapus {$file}\n";
}


// --- FASE 3: SIMPAN & UPLOAD ---
echo "Menyimpan file baru...\n";
file_put_contents($new_filename, $hasil_yaml);

echo "Memulai proses upload ke GitHub...\n";

// Jalankan perintah Git menggunakan `shell_exec()`
// Pastikan user PHP memiliki izin untuk menjalankan git.
// PENTING: Arahkan output error ke stdout (2>&1) agar kita bisa melihatnya.
$git_add_output = shell_exec("git add {$new_filename} 2>&1");
echo "  - git add: {$git_add_output}\n";

$commit_message = "Update config: {$timestamp}";
$git_commit_output = shell_exec("git commit -m \"{$commit_message}\" 2>&1");
echo "  - git commit: {$git_commit_output}\n";

// Ganti 'main' dengan nama branch Anda jika berbeda (misal: 'master')
$git_push_output = shell_exec("git push origin main 2>&1");
echo "  - git push: {$git_push_output}\n";


echo "=========================================\n";
echo "Proses build dan upload selesai.\n";
echo "=========================================\n";
?>