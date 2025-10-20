<?php

/**
 * Menghasilkan konten config.yaml Clash yang lengkap dari array data proxy.
 *
 * @param array $proxies Array data proxy yang dihasilkan oleh parseMultipleVlessUris().
 * @param string $template_path Path ke file template.yaml.
 * @return string|false Konten YAML yang lengkap, atau false jika gagal.
 */
function generateClashConfig(array $proxies, string $template_path = 'template.yaml'): string|false
{
    if (!file_exists($template_path)) {
        return false;
    }

    $proxies_block = '';
    $proxy_names = '';
    
    // 1. Bangun blok 'proxies' dan daftar 'proxy_names'
    foreach ($proxies as $p) {
        // Indentasi sangat penting di YAML! (2 spasi)
        $proxies_block .= "  - name: \"" . $p['name'] . "\"\n";
        $proxies_block .= "    type: " . $p['type'] . "\n";
        $proxies_block .= "    server: " . $p['server'] . "\n";
        $proxies_block .= "    port: " . $p['port'] . "\n";
        $proxies_block .= "    uuid: " . $p['uuid'] . "\n";
        $proxies_block .= "    cipher: " . $p['cipher'] . "\n";
        $proxies_block .= "    tls: " . ($p['tls'] ? 'true' : 'false') . "\n";
        $proxies_block .= "    skip-cert-verify: " . ($p['skip-cert-verify'] ? 'true' : 'false') . "\n";
        $proxies_block .= "    servername: " . $p['servername'] . "\n";
        $proxies_block .= "    network: " . $p['network'] . "\n";
        $proxies_block .= "    udp: " . ($p['udp'] ? 'true' : 'false') . "\n";
        
        // Khusus untuk ws-opts
        if ($p['network'] === 'ws' && !empty($p['ws-opts'])) {
            $proxies_block .= "    ws-opts:\n";
            $proxies_block .= "      path: \"" . $p['ws-opts']['path'] . "\"\n";
            $proxies_block .= "      headers:\n";
            $proxies_block .= "        Host: " . $p['ws-opts']['headers']['Host'] . "\n";
        }

        // Tambahkan nama proxy ke daftar untuk proxy-groups (4 spasi indentasi)
        $proxy_names .= "      - \"" . $p['name'] . "\"\n";
    }

    // 2. Baca konten template
    $template_content = file_get_contents($template_path);

    // 3. Ganti placeholder dengan blok yang sudah kita buat
    $final_config = str_replace('%%PROXIES_BLOCK%%', $proxies_block, $template_content);
    $final_config = str_replace('%%PROXY_NAMES%%', $proxy_names, $final_config);

    return $final_config;
}
?>