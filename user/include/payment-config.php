<?php

function load_local_env()
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($envFile)) {
        return;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
    }
}

function get_env_value($key, $default = '')
{
    load_local_env();
    $value = getenv($key);
    if ($value === false || $value === null || trim((string) $value) === '' || strpos((string) $value, 'your_') === 0 || strpos((string) $value, 'PASTE_YOUR_') === 0) {
        return $default;
    }

    return trim((string) $value);
}

const KHALTI_SANDBOX_KEY = 'KHALTI_SANDBOX_KEY';
const ESEWA_SANDBOX_MERCHANT_CODE = 'ESEWA_SANDBOX_MERCHANT_CODE';
const ESEWA_SANDBOX_MERCHANT_SECRET = 'ESEWA_SANDBOX_MERCHANT_SECRET';
const KHALTI_SANDBOX_API = 'https://dev.khalti.com/api/v2';
const ESEWA_SANDBOX_FORM = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
const ESEWA_SANDBOX_STATUS_API = 'https://uat.esewa.com.np/api/epay/transaction/status/';

function payment_key_is_configured($provider)
{
    if ($provider === 'khalti') {
        return get_env_value(KHALTI_SANDBOX_KEY) !== '';
    }

    if ($provider === 'esewa') {
        return get_env_value(ESEWA_SANDBOX_MERCHANT_CODE) !== '';
    }

    return false;
}

function payment_provider_label($provider)
{
    $labels = [
        'cash' => 'Cash',
        'khalti' => 'Khalti',
        'esewa' => 'eSewa',
    ];

    return $labels[$provider] ?? ucfirst((string) $provider);
}

function payment_sandbox_base_url()
{
    $configured = get_env_value('APP_BASE_URL', '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = dirname($_SERVER['PHP_SELF'] ?? '/');
    $basePath = rtrim($basePath, '/');

    return $scheme . $host . $basePath;
}

function payment_gateway_callback_url($provider, $invid)
{
    $base = payment_sandbox_base_url();
    $path = $provider === 'khalti' ? '/khalti-callback.php' : '/esewa-callback.php';
    return $base . $path . '?invoiceid=' . urlencode($invid);
}

function payment_request_json($url, array $payload, array $headers = [])
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'error' => $error,
        'data' => json_decode((string) $response, true),
    ];
}

function payment_request_form($url, array $fields)
{
    $ch = curl_init($url . '?' . http_build_query($fields));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'error' => $error,
        'data' => json_decode((string) $response, true),
    ];
}

function esewa_signature(array $response, $secret)
{
    $names = explode(',', (string) ($response['signed_field_names'] ?? ''));
    $parts = [];
    foreach ($names as $name) {
        if ($name === '' || !array_key_exists($name, $response)) {
            return '';
        }
        $parts[] = $name . '=' . $response[$name];
    }

    return base64_encode(hash_hmac('sha256', implode(',', $parts), $secret, true));
}
