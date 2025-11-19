<?php
// scripts/ensure_jwt_keys.php
// Ensures JWT private/public keys exist at config/jwt/private.pem and public.pem
// Usage: php scripts/ensure_jwt_keys.php

$projectDir = dirname(__DIR__);
$jwtDir = $projectDir . '/config/jwt';
if (!is_dir($jwtDir)) {
    mkdir($jwtDir, 0700, true);
}
$privateKey = $jwtDir . '/private.pem';
$publicKey = $jwtDir . '/public.pem';

$passphrase = getenv('JWT_PASSPHRASE') ?: (getenv('JWT_PASSPHRASE') === false ? null : getenv('JWT_PASSPHRASE'));
if (empty($passphrase)) {
    // Fallback to APP_SECRET-derived passphrase if available
    $appSecret = getenv('APP_SECRET');
    if (!empty($appSecret)) {
        $passphrase = hash('sha256', $appSecret);
    } else {
        $passphrase = 'changeit';
    }
}

if (!file_exists($privateKey) || !file_exists($publicKey)) {
    echo "Generating JWT keys in $jwtDir\n";
    $priv = escapeshellarg($privateKey);
    $pub = escapeshellarg($publicKey);
    $pass = escapeshellarg($passphrase);

    // Generate 4096-bit RSA private key encrypted with passphrase
    $cmd = "openssl genrsa -aes256 -passout pass:$pass -out $priv 4096";
    $ret = null;
    system($cmd, $ret);
    if ($ret !== 0) {
        echo "Failed to generate private key (openssl exit code $ret)\n";
        exit(1);
    }

    // Extract public key
    $cmd = "openssl rsa -in $priv -passin pass:$pass -pubout -out $pub";
    system($cmd, $ret);
    if ($ret !== 0) {
        echo "Failed to extract public key (openssl exit code $ret)\n";
        exit(1);
    }

    // Set permissions
    @chmod($privateKey, 0600);
    @chmod($publicKey, 0644);
    echo "JWT keys generated successfully.\n";
} else {
    echo "JWT keys already exist, skipping generation.\n";
}

return 0;

