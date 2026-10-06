<?php
declare(strict_types=1);

function paypal_access_token(): ?string
{
    if (!paypal_configured()) {
        return null;
    }
    if (!empty($_SESSION['paypal_token']) && ($_SESSION['paypal_token_exp'] ?? 0) > time()) {
        return $_SESSION['paypal_token'];
    }

    $ch = curl_init(PAYPAL_API_BASE . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US'],
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $code >= 400) {
        app_log('payment', 'PayPal token error: ' . (string) $raw);
        return null;
    }
    $data = json_decode($raw, true);
    if (empty($data['access_token'])) {
        return null;
    }
    $_SESSION['paypal_token'] = $data['access_token'];
    $_SESSION['paypal_token_exp'] = time() + ((int) ($data['expires_in'] ?? 300) - 60);
    return $data['access_token'];
}

function paypal_create_order(array $order): array
{
    $token = paypal_access_token();
    if (!$token) {
        return ['ok' => false, 'message' => 'PayPal is not configured or authentication failed.'];
    }

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $order['order_number'],
            'custom_id' => (string) $order['id'],
            'amount' => [
                'currency_code' => PAYPAL_CURRENCY,
                'value' => number_format((float) $order['total_amount'], 2, '.', ''),
            ],
            'description' => 'NovaBazaar order ' . $order['order_number'],
        ]],
        'application_context' => [
            'brand_name' => APP_NAME,
            'landing_page' => 'NO_PREFERENCE',
            'user_action' => 'PAY_NOW',
            'return_url' => APP_URL . '/public/payment_success.php?order=' . urlencode($order['order_number']),
            'cancel_url' => APP_URL . '/public/payment_cancel.php?order=' . urlencode($order['order_number']),
        ],
    ];

    $ch = curl_init(PAYPAL_API_BASE . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string) $raw, true) ?? [];

    if ($code >= 400 || empty($data['id'])) {
        app_log('payment', 'PayPal create order failed: ' . (string) $raw);
        return ['ok' => false, 'message' => 'Could not create PayPal order.'];
    }

    $approve = null;
    foreach ($data['links'] ?? [] as $link) {
        if (($link['rel'] ?? '') === 'approve') {
            $approve = $link['href'];
            break;
        }
    }
    if (!$approve) {
        return ['ok' => false, 'message' => 'PayPal approve URL missing.'];
    }

    return [
        'ok' => true,
        'paypal_order_id' => $data['id'],
        'approve_url' => $approve,
        'raw' => $data,
    ];
}

function paypal_capture(string $paypalOrderId): array
{
    $token = paypal_access_token();
    if (!$token) {
        return ['ok' => false, 'message' => 'PayPal auth failed.'];
    }

    $ch = curl_init(PAYPAL_API_BASE . '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string) $raw, true) ?? [];

    if ($code >= 400) {
        app_log('payment', 'PayPal capture failed: ' . (string) $raw);
        return ['ok' => false, 'message' => 'Payment capture failed.', 'data' => $data];
    }
    return ['ok' => true, 'data' => $data];
}
