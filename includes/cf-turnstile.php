<?php
require __DIR__ . '/../config/cf-turnstile.php';
function validateTurnstile($secret) {
	$token = $_POST['cf-turnstile-response'] ?? '';
	$remoteip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
	$_SERVER['HTTP_X_FORWARDED_FOR'] ??
	$_SERVER['REMOTE_ADDR'];
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secret,
        'response' => $token
    ];
    if ($remoteip) {
        $data['remoteip'] = $remoteip;
    }
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    if ($response === FALSE) {
        return false;
    }
    return json_decode($response, true)['success'];
}