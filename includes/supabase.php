<?php
/**
 * Supabase REST API 連携ヘルパー
 */

// 環境変数 or .env ファイルから読み込み
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://svefboccqujrmkfhxnvl.supabase.co');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN2ZWZib2NjcXVqcm1rZmh4bnZsIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzI5NTY0NTAsImV4cCI6MjA4ODUzMjQ1MH0.WLt8VMtkdSNgpLgGF8tGTVQfydHZxXrfFHXJh8jMyHc');

/**
 * Supabase テーブルにレコードを挿入する
 */
function supabase_insert(string $table, array $data): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => SUPABASE_URL . '/rest/v1/' . $table,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Prefer: return=representation',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'data'   => json_decode($response, true),
        'error'  => $error,
    ];
}

/**
 * Supabase テーブルからレコードを取得する
 */
function supabase_select(string $table, string $query = ''): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => SUPABASE_URL . '/rest/v1/' . $table . ($query ? '?' . $query : ''),
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?: [];
}
