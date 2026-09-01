<?php

function get_env_value($key, $default = '')
{
    $value = getenv($key);
    if ($value === false || $value === null || trim((string) $value) === '') {
        return $default;
    }

    return trim((string) $value);
}

const KHALTI_SANDBOX_KEY = 'KHALTI_SANDBOX_KEY';
const ESEWA_SANDBOX_MERCHANT_CODE = 'ESEWA_SANDBOX_MERCHANT_CODE';
const ESEWA_SANDBOX_MERCHANT_SECRET = 'ESEWA_SANDBOX_MERCHANT_SECRET';

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
    return $base . '/payment-callback.php?method=' . urlencode($provider) . '&invoiceid=' . urlencode($invid);
}
