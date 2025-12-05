<?php
// Set Timezone to WIB (UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'dorve_dorve');
define('DB_PASS', 'Qwerty88!');
define('DB_NAME', 'dorve_dorve');

// Site Configuration
define('SITE_URL', 'https://dorve.co/');
define('SITE_NAME', 'Dorve');
define('SITE_EMAIL', 'info@dorve.co');

// Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include Upload Handler Functions
require_once __DIR__ . '/includes/upload-handler.php';

// Include Voucher Helper Functions
require_once __DIR__ . '/includes/voucher-helper.php';

// Include SEO Helper Functions (DISABLED)
// require_once __DIR__ . '/includes/seo-helper.php';

// Language Configuration
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id';
}

$current_lang = $_SESSION['lang'];

// Language Translations
$translations = [
    'id' => [
        'home' => 'Beranda',
        'new_collection' => 'Koleksi Baru',
        'all_products' => 'Semua Produk',
        'our_story' => 'Tentang Kami',
        'faq' => 'FAQ',
        'cart' => 'Keranjang',
        'login' => 'Masuk',
        'register' => 'Daftar',
        'logout' => 'Keluar',
        'my_account' => 'Akun Saya',
        'search' => 'Cari produk...',
        'add_to_cart' => 'Tambah ke Keranjang',
        'buy_now' => 'Beli Sekarang',
        'view_details' => 'Lihat Detail',
        'price' => 'Harga',
        'stock' => 'Stok',
        'available' => 'Tersedia',
        'out_of_stock' => 'Stok Habis',
        'select_variant' => 'Pilih Varian',
        'size' => 'Ukuran',
        'color' => 'Warna',
        'qty' => 'Jumlah',
        'subtotal' => 'Subtotal',
        'total' => 'Total',
        'checkout' => 'Checkout',
        'continue_shopping' => 'Lanjut Belanja',
        'empty_cart' => 'Keranjang Kosong',
        'free_shipping' => 'Gratis Ongkir',
        'new' => 'Baru',
        'best_seller' => 'Terlaris',
        'sale' => 'Diskon',
    ],
    'en' => [
        'home' => 'Home',
        'new_collection' => 'New Collection',
        'all_products' => 'All Products',
        'our_story' => 'Our Story',
        'faq' => 'FAQ',
        'cart' => 'Cart',
        'login' => 'Login',
        'register' => 'Register',
        'logout' => 'Logout',
        'my_account' => 'My Account',
        'search' => 'Search products...',
        'add_to_cart' => 'Add to Cart',
        'buy_now' => 'Buy Now',
        'view_details' => 'View Details',
        'price' => 'Price',
        'stock' => 'Stock',
        'available' => 'Available',
        'out_of_stock' => 'Out of Stock',
        'select_variant' => 'Select Variant',
        'size' => 'Size',
        'color' => 'Color',
        'qty' => 'Quantity',
        'subtotal' => 'Subtotal',
        'total' => 'Total',
        'checkout' => 'Checkout',
        'continue_shopping' => 'Continue Shopping',
        'empty_cart' => 'Cart is Empty',
        'free_shipping' => 'Free Shipping',
        'new' => 'New',
        'best_seller' => 'Best Seller',
        'sale' => 'Sale',
    ]
];

function t($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $key;
}

function switchLanguage($lang) {
    if (in_array($lang, ['id', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit();
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getCartCount() {
    global $pdo;

    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) as count FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) as count FROM cart_items WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }

    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function calculateDiscount($price, $discount_percent) {
    return $price - ($price * $discount_percent / 100);
}

function generateOrderNumber() {
    return 'DRV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
