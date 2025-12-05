<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();

// Validate required information before checkout
$missing_fields = [];
if (empty($user['phone'])) {
    $missing_fields[] = 'Phone Number';
}
if (empty($user['address'])) {
    $missing_fields[] = 'Shipping Address';
}

// If missing required fields, redirect to profile with message
if (!empty($missing_fields)) {
    $_SESSION['error_message'] = 'Please complete your profile before checkout. Missing: ' . implode(', ', $missing_fields);
    $_SESSION['redirect_after_profile'] = '/pages/checkout.php';
    header('Location: /member/profile.php');
    exit;
}

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, pv.size, pv.color FROM cart_items ci JOIN products p ON ci.product_id = p.id LEFT JOIN product_variants pv ON ci.variant_id = pv.id WHERE ci.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, pv.size, pv.color FROM cart_items ci JOIN products p ON ci.product_id = p.id LEFT JOIN product_variants pv ON ci.variant_id = pv.id WHERE ci.session_id = ?");
    $stmt->execute([session_id()]);
}

$cart_items = $stmt->fetchAll();

// Redirect if cart is empty
if (empty($cart_items)) {
    $_SESSION['error_message'] = 'Your cart is empty!';
    header('Location: /pages/cart.php');
    exit;
}

$subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['qty'], $cart_items));

$stmt = $pdo->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order");
$shipping_methods = $stmt->fetchAll();

$page_title = 'Checkout - Selesaikan Pembayaran Baju Wanita Online | Gratis Ongkir & COD Dorve';
$page_description = 'Checkout pesanan baju wanita Anda dengan aman. Pilih metode pembayaran: transfer bank, e-wallet, COD. Gratis ongkir min Rp500.000. Proses cepat dan mudah.';
$page_keywords = 'checkout, pembayaran online, transfer bank, cod, e-wallet, bayar baju online, selesaikan pesanan';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .checkout-container { max-width: 1200px; margin: 80px auto; padding: 0 40px; display: grid; grid-template-columns: 1fr 400px; gap: 60px; }
    .checkout-form h2 { font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 30px; }
    .form-section { margin-bottom: 40px; }
    .form-section h3 { font-size: 20px; margin-bottom: 20px; font-weight: 600; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 4px; font-size: 15px; font-family: 'Inter', sans-serif; }
    .form-group textarea { min-height: 100px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .payment-method { display: flex; align-items: center; padding: 16px; border: 2px solid rgba(0,0,0,0.1); border-radius: 8px; margin-bottom: 12px; cursor: pointer; transition: all 0.3s; }
    .payment-method:hover { border-color: var(--charcoal); }
    .payment-method input { margin-right: 12px; }
    .order-summary { background: var(--cream); padding: 30px; border-radius: 8px; position: sticky; top: 120px; height: fit-content; }
    .order-summary h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 24px; }
    .summary-item { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 15px; }
    .summary-total { display: flex; justify-content: space-between; padding-top: 20px; border-top: 2px solid rgba(0,0,0,0.1); margin-top: 20px; font-size: 20px; font-weight: 600; font-family: 'Playfair Display', serif; }
    .btn-checkout { width: 100%; padding: 18px; background: var(--charcoal); color: var(--white); border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 24px; transition: all 0.3s; }
    .btn-checkout:hover { background: #000; }
</style>

<div class="checkout-container">
    <div class="checkout-form">
        <h2>Checkout</h2>

        <form action="/pages/process-order.php" method="POST">
            <div class="form-section">
                <h3>Shipping Information</h3>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Shipping Address *</label>
                    <textarea name="address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Shipping Method</h3>
                <?php foreach ($shipping_methods as $method): ?>
                    <div class="payment-method">
                        <input type="radio" name="shipping_method" value="<?php echo $method['id']; ?>" required>
                        <div style="flex: 1;">
                            <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                            <div style="font-size: 13px; color: var(--grey);"><?php echo htmlspecialchars($method['description']); ?> - <?php echo $method['cost'] == 0 ? 'FREE' : formatPrice($method['cost']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h3>Payment Method</h3>
                <div class="payment-method" style="background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: var(--white); border-color: var(--charcoal);">
                    <input type="radio" name="payment_method" value="wallet" required>
                    <div style="flex: 1;">
                        <strong>💰 Dorve House Wallet</strong>
                        <div style="font-size: 13px; opacity: 0.9;">Balance: <?php echo formatPrice($user['wallet_balance'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment_method" value="bank_transfer">
                    <div><strong>🏦 Bank Transfer</strong></div>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment_method" value="credit_card">
                    <div><strong>💳 Credit/Debit Card</strong></div>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment_method" value="qris">
                    <div><strong>📱 QRIS</strong></div>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment_method" value="paypal">
                    <div><strong>🅿️ PayPal</strong></div>
                </div>
                <div class="payment-method">
                    <input type="radio" name="payment_method" value="cod">
                    <div><strong>💵 Cash on Delivery</strong></div>
                </div>
            </div>

            <button type="submit" class="btn-checkout">Place Order</button>
        </form>
    </div>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <?php foreach ($cart_items as $item): ?>
            <div class="summary-item">
                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['qty']; ?></span>
                <span><?php echo formatPrice($item['price'] * $item['qty']); ?></span>
            </div>
        <?php endforeach; ?>

        <div class="summary-item">
            <span>Subtotal</span>
            <span><?php echo formatPrice($subtotal); ?></span>
        </div>
        <div class="summary-item">
            <span>Shipping</span>
            <span id="shipping-cost">-</span>
        </div>

        <div class="summary-total">
            <span>Total</span>
            <span id="total"><?php echo formatPrice($subtotal); ?></span>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
