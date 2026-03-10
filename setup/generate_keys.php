<?php
// Script to generate RSA keys for Yggdrasil signature
// Requires OpenSSL extension

$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

if (!extension_loaded('openssl')) {
    die("Error: The OpenSSL extension is not loaded in PHP. Please enable it in your php.ini.\n");
}

// Windows Fix: Try to locate openssl.cnf if it's not set
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !getenv('OPENSSL_CONF')) {
    $possiblePaths = [
        __DIR__ . '/../../FoxyClient/php/extras/ssl/openssl.cnf', 
        __DIR__ . '/../../../FoxyClient/php/extras/ssl/openssl.cnf', // One level higher if in a subfolder
        __DIR__ . '/../php/extras/ssl/openssl.cnf',
        'C:/php/extras/ssl/openssl.cnf',
        'C:/Program Files/Common Files/SSL/openssl.cnf',
    ];
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $config['config'] = realpath($path);
            break;
        }
    }
}

// Create the private and public key
$res = openssl_pkey_new($config);

if ($res === false) {
    echo "Error: openssl_pkey_new failed. OpenSSL reported the following errors:\n";
    while ($msg = openssl_error_string()) {
        echo " - $msg\n";
    }
    echo "\nTip: On Windows, this often means the openssl.cnf file was not found.\n";
    echo "You can try setting the OPENSSL_CONF environment variable to point to your openssl.cnf file.\n";
    die();
}

// Extract the private key from $res to $privKey
if (!openssl_pkey_export($res, $privKey, null, $config)) {
    echo "Error: openssl_pkey_export failed. OpenSSL reported the following errors:\n";
    while ($msg = openssl_error_string()) {
        echo " - $msg\n";
    }
    die();
}

// Extract the public key from $res to $pubKey
$pubInfo = openssl_pkey_get_details($res);
if (!$pubInfo) {
    die("Error: openssl_pkey_get_details failed.\n");
}
$pubKey = $pubInfo["key"];

// Save to PHP config file
$configContent = "<?php\nreturn [\n    'private_key' => " . var_export($privKey, true) . ",\n    'public_key' => " . var_export($pubKey, true) . "\n];\n?>";
file_put_contents(__DIR__ . '/../config/yggdrasil.php', $configContent);

echo "Keys generated successfully!\n";
echo "Keys saved to config/yggdrasil.php\n";
?>
