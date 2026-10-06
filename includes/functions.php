<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return APP_BASE_PATH . ($path === '/' ? '' : $path);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function upload_url(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        $allowed = APP_URL;
        if (!str_starts_with($path, $allowed)) {
            $path = url('/');
        }
    } else {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function store_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['csrf_token']);
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function app_log(string $channel, string $message): void
{
    $dir = NB_ROOT . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . "] [{$channel}] {$message}" . PHP_EOL;
    @file_put_contents($dir . '/' . $channel . '.log', $line, FILE_APPEND);
}

function money(float|int|string $amount): string
{
    return CURRENCY_SYMBOL . ' ' . number_format((float) $amount, 2);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item-' . bin2hex(random_bytes(3));
}

function unique_slug(string $base, string $table = 'products', ?int $ignoreId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 1;
    $pdo = db();
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$candidate];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i;
        $i++;
    }
}

function generate_order_number(): string
{
    $date = date('Ymd');
    $prefix = 'ORD-' . $date . '-';
    $pdo = db();

    // Use next free sequence for today's prefix (avoids collisions with seed/old rows).
    $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY order_number DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');
    $next = 1;
    if ($last !== '' && preg_match('/(\d{6})$/', $last, $m)) {
        $next = (int) $m[1] + 1;
    }

    // Extra safety if a race or gap exists.
    for ($i = 0; $i < 50; $i++) {
        $candidate = sprintf('ORD-%s-%06d', $date, $next + $i);
        $check = $pdo->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
        $check->execute([$candidate]);
        if (!$check->fetch()) {
            return $candidate;
        }
    }

    return sprintf('ORD-%s-%s', $date, strtoupper(bin2hex(random_bytes(3))));
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function request(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must include an uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must include a lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must include a number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must include a special character.';
    }
    return null;
}

function paginate(int $total, int $page, int $perPage = 12): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => $pages,
        'offset' => $offset,
    ];
}

function pagination_links(array $meta, string $baseUrl): string
{
    if ($meta['pages'] <= 1) {
        return '';
    }
    $html = '<nav class="nb-pagination" aria-label="Pagination"><ul class="pagination justify-content-center flex-wrap">';
    for ($i = 1; $i <= $meta['pages']; $i++) {
        $active = $i === $meta['page'] ? ' active' : '';
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

function product_primary_image(?int $productId): string
{
    if (!$productId) {
        return asset('images/placeholder-product.svg');
    }
    $stmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = ? ORDER BY display_order ASC, id ASC LIMIT 1');
    $stmt->execute([$productId]);
    $path = $stmt->fetchColumn();
    if ($path) {
        return upload_url((string) $path);
    }
    return asset('images/placeholder-product.svg');
}

function product_images(int $productId): array
{
    $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC, id ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function average_rating(int $productId): array
{
    $stmt = db()->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    $row = $stmt->fetch() ?: ['avg_rating' => 0, 'review_count' => 0];
    return [
        'avg' => round((float) ($row['avg_rating'] ?? 0), 1),
        'count' => (int) ($row['review_count'] ?? 0),
    ];
}

function render_stars(float $rating): string
{
    $html = '<span class="nb-stars" aria-label="' . e((string) $rating) . ' stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

function get_or_create_cart(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $stmt = $pdo->prepare('INSERT INTO carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())');
    $stmt->execute([$userId]);
    return (int) $pdo->lastInsertId();
}

function cart_items(int $userId): array
{
    $cartId = get_or_create_cart($userId);
    $sql = "SELECT ci.*, p.name, p.slug, p.stock, p.status AS product_status, p.sku,
                   (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
            FROM cart_items ci
            INNER JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = ?
            ORDER BY ci.id DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function cart_totals(int $userId): array
{
    $items = cart_items($userId);
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) $item['price'] * (int) $item['quantity'];
    }

    $discount = 0.0;
    $coupon = $_SESSION['coupon'] ?? null;
    if ($coupon) {
        $discount = calculate_coupon_discount($coupon, $subtotal);
    }

    $shipping = $subtotal > 0 ? SHIPPING_FEE : 0.0;
    if ($subtotal >= 100) {
        $shipping = 0.0; // free shipping threshold
    }
    $total = max(0, $subtotal - $discount + $shipping);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total' => $total,
        'coupon' => $coupon,
        'count' => array_sum(array_map(fn($i) => (int) $i['quantity'], $items)),
    ];
}

function calculate_coupon_discount(array $coupon, float $subtotal): float
{
    if ($subtotal < (float) $coupon['minimum_amount']) {
        return 0.0;
    }
    if ($coupon['discount_type'] === 'percentage') {
        $discount = $subtotal * ((float) $coupon['discount_value'] / 100);
        $max = (float) ($coupon['maximum_discount'] ?? 0);
        if ($max > 0) {
            $discount = min($discount, $max);
        }
        return round($discount, 2);
    }
    return min(round((float) $coupon['discount_value'], 2), $subtotal);
}

function fetch_valid_coupon(string $code, float $subtotal): array
{
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        return ['ok' => false, 'message' => 'Coupon not found.'];
    }
    if ($coupon['status'] !== 'active') {
        return ['ok' => false, 'message' => 'This coupon is inactive.'];
    }
    $now = date('Y-m-d H:i:s');
    if ($coupon['start_date'] && $coupon['start_date'] > $now) {
        return ['ok' => false, 'message' => 'This coupon is not active yet.'];
    }
    if ($coupon['expiry_date'] && $coupon['expiry_date'] < $now) {
        return ['ok' => false, 'message' => 'This coupon has expired.'];
    }
    if ((int) $coupon['usage_limit'] > 0 && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return ['ok' => false, 'message' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < (float) $coupon['minimum_amount']) {
        return ['ok' => false, 'message' => 'Minimum spend of ' . money((float) $coupon['minimum_amount']) . ' required.'];
    }
    return ['ok' => true, 'coupon' => $coupon];
}

function list_public_coupons(): array
{
    $stmt = db()->query(
        "SELECT * FROM coupons
         WHERE status = 'active'
           AND (start_date IS NULL OR start_date <= NOW())
           AND (expiry_date IS NULL OR expiry_date >= NOW())
           AND (usage_limit = 0 OR used_count < usage_limit)
         ORDER BY discount_value DESC, id ASC"
    );
    return $stmt->fetchAll();
}

function coupon_offer_label(array $coupon): string
{
    if ($coupon['discount_type'] === 'percentage') {
        $label = rtrim(rtrim(number_format((float) $coupon['discount_value'], 2), '0'), '.') . '% off';
    } else {
        $label = money((float) $coupon['discount_value']) . ' off';
    }
    $min = (float) $coupon['minimum_amount'];
    if ($min > 0) {
        $label .= ' · min ' . money($min);
    }
    return $label;
}

function wishlist_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function is_wishlisted(int $userId, int $productId): bool
{
    $stmt = db()->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetchColumn();
}

function add_to_cart(int $userId, int $productId, int $quantity = 1): array
{
    $quantity = max(1, $quantity);
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        return ['ok' => false, 'message' => 'Product not available.'];
    }
    if ((int) $product['stock'] < 1) {
        return ['ok' => false, 'message' => 'Product is out of stock.'];
    }

    $cartId = get_or_create_cart($userId);
    $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $stmt->execute([$cartId, $productId]);
    $existing = $stmt->fetch();

    $newQty = $quantity + (int) ($existing['quantity'] ?? 0);
    if ($newQty > (int) $product['stock']) {
        return ['ok' => false, 'message' => 'Only ' . (int) $product['stock'] . ' units available in stock.'];
    }

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ?, price = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newQty, $product['price'], $existing['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$cartId, $productId, $quantity, $product['price']]);
    }
    $pdo->prepare('UPDATE carts SET updated_at = NOW() WHERE id = ?')->execute([$cartId]);
    return ['ok' => true, 'message' => 'Added to cart.'];
}

function notify_user(int $userId, string $title, string $message, string $type = 'info'): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
    $stmt->execute([$userId, $title, $message, $type]);
}

function unread_notifications(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Download an image from a public URL and store it under uploads/.
 */
function handle_image_from_url(string $url, string $subdir = 'products'): array
{
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'message' => 'Enter a valid image URL.'];
    }
    if (!preg_match('#^https?://#i', $url)) {
        return ['ok' => false, 'message' => 'Only http/https image URLs are allowed.'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'],
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data === false || $code >= 400 || strlen($data) < 500) {
        app_log('upload', 'Image URL download failed: ' . $url);
        return ['ok' => false, 'message' => 'Could not download image from URL.'];
    }

    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;
    if (strlen($data) > $maxBytes) {
        return ['ok' => false, 'message' => 'Image exceeds ' . UPLOAD_MAX_MB . 'MB limit.'];
    }

    $tmp = tempnam(sys_get_temp_dir(), 'nbimg');
    if ($tmp === false || file_put_contents($tmp, $data) === false) {
        return ['ok' => false, 'message' => 'Could not save temporary image.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime]) || @getimagesize($tmp) === false) {
        @unlink($tmp);
        return ['ok' => false, 'message' => 'URL must point to a JPG, PNG, or WEBP image.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $relative = $subdir . '/' . $filename;
    $destDir = NB_ROOT . '/uploads/' . $subdir;
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $dest = $destDir . '/' . $filename;
    if (!@rename($tmp, $dest)) {
        if (!@copy($tmp, $dest)) {
            @unlink($tmp);
            return ['ok' => false, 'message' => 'Could not store downloaded image.'];
        }
        @unlink($tmp);
    }

    return ['ok' => true, 'path' => $relative];
}

function handle_image_upload(array $file, string $subdir = 'products'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'message' => 'No file uploaded.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed.'];
    }

    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'message' => 'File exceeds ' . UPLOAD_MAX_MB . 'MB limit.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'message' => 'Invalid file extension.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'message' => 'Invalid image file.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $relative = $subdir . '/' . $filename;
    $destDir = NB_ROOT . '/uploads/' . $subdir;
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $dest = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        app_log('upload', 'Failed to move uploaded file');
        return ['ok' => false, 'message' => 'Could not save uploaded file.'];
    }

    return ['ok' => true, 'path' => $relative];
}

function status_badge(string $status): string
{
    $map = [
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'danger',
        'pending' => 'warning',
        'paid' => 'info',
        'processing' => 'primary',
        'shipped' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
        'failed' => 'danger',
        'refunded' => 'secondary',
        'approved' => 'success',
        'hidden' => 'secondary',
        'archived' => 'dark',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . e($class) . '">' . e(ucfirst($status)) . '</span>';
}

function order_can_cancel(array $order): bool
{
    return in_array($order['order_status'], ['pending', 'paid'], true);
}

function order_timeline(array $order): array
{
    $status = $order['order_status'];
    $steps = [
        ['key' => 'pending', 'label' => 'Order Placed', 'icon' => 'fa-receipt'],
        ['key' => 'paid', 'label' => 'Payment Confirmed', 'icon' => 'fa-credit-card'],
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fa-gears'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'fa-truck'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check'],
    ];
    $orderKeys = ['pending', 'paid', 'processing', 'shipped', 'completed'];
    $currentIndex = array_search($status, $orderKeys, true);
    if ($status === 'cancelled') {
        return [
            ['label' => 'Order Placed', 'icon' => 'fa-receipt', 'state' => 'done'],
            ['label' => 'Cancelled', 'icon' => 'fa-ban', 'state' => 'cancelled'],
        ];
    }
    $out = [];
    foreach ($steps as $i => $step) {
        $state = 'upcoming';
        if ($currentIndex !== false) {
            if ($i < $currentIndex) {
                $state = 'done';
            } elseif ($i === $currentIndex) {
                $state = 'current';
            }
        }
        $out[] = ['label' => $step['label'], 'icon' => $step['icon'], 'state' => $state];
    }
    return $out;
}

function create_order_from_cart(int $userId, array $shipping, string $paymentMethod, array $paymentMeta = []): array
{
    $pdo = db();
    $totals = cart_totals($userId);
    if (empty($totals['items'])) {
        return ['ok' => false, 'message' => 'Your cart is empty.'];
    }

    try {
        $pdo->beginTransaction();

        // Lock products and revalidate stock/prices
        $lockedItems = [];
        foreach ($totals['items'] as $item) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' FOR UPDATE");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            if (!$product) {
                throw new RuntimeException('A product in your cart is no longer available.');
            }
            if ((int) $product['stock'] < (int) $item['quantity']) {
                throw new RuntimeException($product['name'] . ' has insufficient stock.');
            }
            $lockedItems[] = [
                'product' => $product,
                'quantity' => (int) $item['quantity'],
                'price' => (float) $product['price'],
            ];
        }

        $subtotal = 0.0;
        foreach ($lockedItems as $li) {
            $subtotal += $li['price'] * $li['quantity'];
        }

        $couponId = null;
        $discount = 0.0;
        $coupon = $_SESSION['coupon'] ?? null;
        if ($coupon) {
            $valid = fetch_valid_coupon((string) $coupon['code'], $subtotal);
            if (!$valid['ok']) {
                unset($_SESSION['coupon']);
                throw new RuntimeException($valid['message']);
            }
            $coupon = $valid['coupon'];
            $couponId = (int) $coupon['id'];
            $discount = calculate_coupon_discount($coupon, $subtotal);
        }

        $shippingFee = $subtotal >= 100 ? 0.0 : SHIPPING_FEE;
        $total = max(0, $subtotal - $discount + $shippingFee);
        $orderNumber = generate_order_number();

        $paymentStatus = $paymentMethod === 'cod' ? 'pending' : 'pending';
        $orderStatus = 'pending';

        $stmt = $pdo->prepare("INSERT INTO orders
            (order_number, user_id, subtotal, discount_amount, shipping_fee, total_amount, coupon_id,
             payment_method, payment_status, transaction_id, order_status,
             shipping_name, shipping_phone, shipping_address, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $orderNumber,
            $userId,
            $subtotal,
            $discount,
            $shippingFee,
            $total,
            $couponId,
            $paymentMethod,
            $paymentStatus,
            $orderStatus,
            $shipping['name'],
            $shipping['phone'],
            $shipping['address'],
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items
            (order_id, product_id, seller_id, product_name, quantity, unit_price, subtotal, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

        foreach ($lockedItems as $li) {
            $p = $li['product'];
            $line = $li['price'] * $li['quantity'];
            $itemStmt->execute([
                $orderId,
                $p['id'],
                $p['seller_id'],
                $p['name'],
                $li['quantity'],
                $li['price'],
                $line,
            ]);

            // COD / bank: reduce stock immediately; PayPal waits until capture
            if (in_array($paymentMethod, ['cod', 'bank'], true)) {
                $upd = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ? AND stock >= ?');
                $upd->execute([$li['quantity'], $p['id'], $li['quantity']]);
                if ($upd->rowCount() === 0) {
                    throw new RuntimeException('Stock conflict for ' . $p['name']);
                }
            }
        }

        if ($couponId) {
            $pdo->prepare('UPDATE coupons SET used_count = used_count + 1, updated_at = NOW() WHERE id = ?')->execute([$couponId]);
        }

        if ($paymentMethod === 'cod') {
            $pdo->prepare("INSERT INTO payments (order_id, payment_method, transaction_id, amount, currency, status, response_data, created_at, updated_at)
                VALUES (?, 'cod', NULL, ?, ?, 'pending', NULL, NOW(), NOW())")
                ->execute([$orderId, $total, CURRENCY_CODE]);
        } elseif ($paymentMethod === 'bank') {
            $bankRef = 'BANK-' . $orderNumber;
            $payerAccount = preg_replace('/\s+/', '', (string) ($paymentMeta['account_number'] ?? '')) ?? '';
            $payerName = trim((string) ($paymentMeta['account_name'] ?? ''));
            $bankPayload = json_encode([
                'payer_account_name' => $payerName,
                'payer_account_number' => $payerAccount,
                'reference' => $orderNumber,
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare('UPDATE orders SET transaction_id = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$bankRef, $orderId]);
            $pdo->prepare("INSERT INTO payments (order_id, payment_method, transaction_id, amount, currency, status, response_data, created_at, updated_at)
                VALUES (?, 'bank', ?, ?, ?, 'pending', ?, NOW(), NOW())")
                ->execute([$orderId, $bankRef, $total, CURRENCY_CODE, $bankPayload]);
        }

        // Clear cart
        $cartId = get_or_create_cart($userId);
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
        unset($_SESSION['coupon']);

        $pdo->commit();

        notify_user($userId, 'Order placed', "Your order {$orderNumber} has been placed.", 'order');
        send_order_email($userId, $orderId, 'placed');

        return ['ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'total' => $total];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('orders', $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function finalize_paid_order(int $orderId, string $transactionId, array $responseData = []): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['payment_status'] === 'paid') {
            $pdo->commit();
            return ['ok' => true, 'message' => 'Already paid.', 'duplicate' => true];
        }

        // PayPal reduces stock at payment verification. COD/bank already reduced stock at order creation.
        if (!in_array($order['payment_method'], ['cod', 'bank'], true)) {
            $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            $orderItems = $items->fetchAll();

            foreach ($orderItems as $item) {
                $upd = $pdo->prepare('UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ? AND stock >= ?');
                $upd->execute([(int) $item['quantity'], $item['product_id'], (int) $item['quantity']]);
                if ($upd->rowCount() === 0) {
                    throw new RuntimeException('Insufficient stock while finalizing payment.');
                }
            }
        }

        $nextStatus = $order['order_status'] === 'pending' ? 'paid' : $order['order_status'];
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = ?, transaction_id = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$nextStatus, $transactionId, $orderId]);

        $pdo->prepare("INSERT INTO payments (order_id, payment_method, transaction_id, amount, currency, status, response_data, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW(), NOW())")
            ->execute([
                $orderId,
                $order['payment_method'],
                $transactionId,
                $order['total_amount'],
                CURRENCY_CODE,
                json_encode($responseData),
            ]);

        $pdo->commit();
        notify_user((int) $order['user_id'], 'Payment confirmed', 'Payment received for order ' . $order['order_number'], 'payment');
        send_order_email((int) $order['user_id'], $orderId, 'paid');
        return ['ok' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('payment', $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function cancel_order(int $orderId, int $userId, bool $isAdmin = false): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if (!$isAdmin && (int) $order['user_id'] !== $userId) {
            throw new RuntimeException('Unauthorized.');
        }
        if (!order_can_cancel($order) && !$isAdmin) {
            throw new RuntimeException('This order can no longer be cancelled.');
        }
        if ($order['order_status'] === 'cancelled') {
            throw new RuntimeException('Order is already cancelled.');
        }

        // Restore stock if it was reduced (paid/COD pending with stock already deducted)
        $shouldRestore = in_array($order['payment_status'], ['paid', 'pending'], true)
            && in_array($order['order_status'], ['pending', 'paid'], true)
            && (in_array($order['payment_method'], ['cod', 'bank'], true) || $order['payment_status'] === 'paid');

        if ($shouldRestore) {
            $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $pdo->prepare('UPDATE products SET stock = stock + ?, updated_at = NOW() WHERE id = ?')
                    ->execute([(int) $item['quantity'], $item['product_id']]);
            }
        }

        $pdo->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
        $pdo->commit();
        notify_user((int) $order['user_id'], 'Order cancelled', 'Order ' . $order['order_number'] . ' was cancelled.', 'order');
        send_order_email((int) $order['user_id'], $orderId, 'cancelled');
        return ['ok' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function category_icon(string $name): string
{
    $map = [
        'Audio' => 'fa-headphones',
        'Cameras' => 'fa-camera',
        'Computers' => 'fa-laptop',
        'Fashion' => 'fa-shirt',
        'Fitness' => 'fa-dumbbell',
        'Gaming' => 'fa-gamepad',
        'Home' => 'fa-house',
        'Kitchen' => 'fa-utensils',
        'Mobiles' => 'fa-mobile-screen',
        'Outdoors' => 'fa-person-hiking',
    ];
    foreach ($map as $key => $icon) {
        if (stripos($name, $key) !== false) {
            return $icon;
        }
    }
    return 'fa-tags';
}
