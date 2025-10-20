<?php

/**
 * Mengurai sebuah string yang berisi satu atau lebih URI VLESS (dipisahkan oleh baris baru)
 * dan mengubahnya menjadi array data proxy yang terstruktur.
 *
 * @param string $uri_string String dari textarea, berisi satu atau lebih URI.
 * @return array Array berisi data proxy yang sudah diurai.
 */
function parseMultipleVlessUris(string $uri_string): array
{
    // 1. Pecah string input menjadi beberapa baris (array of URIs)
    //    - `trim()` untuk menghapus spasi/baris kosong di awal dan akhir.
    //    - `preg_split()` untuk memecah berdasarkan baris baru (\n atau \r\n).
    //    - `array_filter()` untuk menghapus baris kosong di tengah-tengah.
    $uris = array_filter(preg_split('/\r\n|\r|\n/', trim($uri_string)));

    $proxies = [];
    foreach ($uris as $uri) {
        // 2. Urai setiap URI satu per satu
        $parsed_data = parseSingleVlessUri($uri);

        // 3. Jika URI valid, tambahkan ke hasil akhir
        if ($parsed_data) {
            $proxies[] = $parsed_data;
        }
    }

    return $proxies;
}

/**
 * Mengurai satu string URI VLESS menjadi array asosiatif.
 *
 * @param string $uri URI VLESS tunggal, e.g., "vless://...".
 * @return array|null Array data proxy jika valid, atau null jika tidak.
 */
function parseSingleVlessUri(string $uri): ?array
{
    // Pastikan ini adalah URI VLESS yang valid
    if (strpos($uri, 'vless://') !== 0) {
        return null;
    }

    // Gunakan fungsi parsing URL bawaan PHP yang sangat kuat
    $parts = parse_url($uri);
    if ($parts === false) {
        return null;
    }

    // Urai bagian query (?foo=bar&baz=qux) menjadi array asosiatif
    $query_params = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query_params);
    }

    // 4. Lakukan pemetaan (mapping) dari komponen URI ke struktur data kita
    //    Ini adalah inti dari logika konversi.
    try {
        $proxy_data = [
            'name'         => isset($parts['fragment']) ? urldecode($parts['fragment']) : $parts['host'], // Gunakan host sebagai nama default jika tidak ada fragment
            'type'         => 'vless',
            'server'       => $parts['host'],
            'port'         => $parts['port'],
            'uuid'         => $parts['user'],
            'cipher'       => $query_params['encryption'] ?? 'auto', // VLESS modern biasanya 'none', di Clash di-set 'auto'
            'tls'          => isset($query_params['security']) && $query_params['security'] === 'tls',
            'udp'          => true, // Kita set default ke true, bisa disesuaikan
            'skip-cert-verify' => true, // Umumnya di-set true untuk fleksibilitas
            'servername'   => $query_params['sni'] ?? '',
            'network'      => $query_params['type'] ?? 'tcp', // Default ke tcp jika tidak ada
            'ws-opts'      => []
        ];

        // Khusus untuk WebSocket (ws)
        if ($proxy_data['network'] === 'ws') {
            $proxy_data['ws-opts'] = [
                'path'    => isset($query_params['path']) ? urldecode($query_params['path']) : '/',
                'headers' => [
                    // Jika ada host header khusus di URI, gunakan itu. Jika tidak, gunakan servername.
                    'Host' => $query_params['host'] ?? $proxy_data['servername']
                ]
            ];
        }

        // Anda bisa menambahkan logika untuk network lain di sini (gRPC, dll)

    } catch (Throwable $e) {
        // Tangkap error jika ada komponen URI yang hilang (misal: tidak ada host)
        return null;
    }

    return $proxy_data;
}

?>