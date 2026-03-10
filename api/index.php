<?php
require_once __DIR__ . '/../includes/yggdrasil.php';

$keyConfig = require __DIR__ . '/../config/yggdrasil.php';
$pubKey = $keyConfig['public_key'] ?? "";

$response = [
    "meta" => [
        "serverName" => "FoxyClient",
        "implementationName" => "FoxyClient",
        "implementationVersion" => "1.0.0",
        "links" => [
            "homepage" => get_base_url() . "/",
            "register" => get_base_url() . "/accounts/register/"
        ]
    ],
    "skinDomains" => [
        $_SERVER['HTTP_HOST']
    ],
    "signaturePublickey" => $pubKey
];

send_json_response($response);
?>
