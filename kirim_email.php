<?php
/**
 * Fungsi pengirim email via Brevo API (HTTP), bukan SMTP.
 * Dipakai sebagai pengganti PHPMailer->send() yang butuh koneksi SMTP
 * (port 587/465/25), karena port itu umumnya diblokir di hosting gratis.
 * Brevo API jalan lewat HTTPS biasa (port 443), yang hampir tidak pernah diblokir.
 *
 * @param string $to_email   Email tujuan
 * @param string $to_name    Nama penerima
 * @param string $subject    Subjek email
 * @param string $html_body  Isi email dalam format HTML
 * @return array ['success' => bool, 'message' => string]
 */
function kirimEmailViaBrevo($to_email, $to_name, $subject, $html_body) {
    require_once __DIR__ . '/config/email_config.php';

    $data = [
        "sender"      => [
            "name"  => NAMA_PENGIRIM,
            "email" => EMAIL_PENGIRIM
        ],
        "to"          => [
            [
                "email" => $to_email,
                "name"  => $to_name
            ]
        ],
        "subject"     => $subject,
        "htmlContent" => $html_body
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . BREVO_API_KEY,
        'content-type: application/json'
    ]);
    // Supaya tidak menggantung lama kalau koneksi bermasalah
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'message' => 'Koneksi ke Brevo gagal: ' . $curl_error];
    }

    // Brevo mengembalikan HTTP 201 kalau email berhasil masuk antrean kirim
    if ($http_code === 201) {
        return ['success' => true, 'message' => 'Email berhasil dikirim.'];
    }

    return ['success' => false, 'message' => 'Brevo menolak permintaan (HTTP ' . $http_code . '): ' . $response];
}