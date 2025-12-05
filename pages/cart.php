<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.slug, p.price, p.discount_percent,
                           pi.image_path, pv.color, pv.size, pv.extra_price
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                           WHERE ci.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $session_id = session_id();
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.slug, p.price, p.discount_percent,
                           pi.image_path, pv.color, pv.size, pv.extra_price
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                           WHERE ci.session_id = ?");
    $stmt->execute([$session_id]);
}

$cart_items = $stmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) {
    $item_price = calculateDiscount($item['price'], $item['discount_percent']) + ($item['extra_price'] ?? 0);
    $subtotal += $item_price * $item['qty'];
}

$shipping = $subtotal >= 500000 ? 0 : 25000;
$total = $subtotal + $shipping;

$page_title = 'Keranjang Belanja - Checkout Aman & Mudah | Dorve House';
$page_description = 'Lihat keranjang belanja Anda. Lanjutkan ke checkout untuk menyelesaikan pembelian baju wanita online. Gratis ongkir min Rp500.000, pembayaran aman, COD tersedia.';
$page_keywords = 'keranjang belanja, shopping cart, checkout, belanja online, beli baju online, pembayaran aman';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .cart-container {
        max-width: 1200px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .cart-title {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 60px;
        text-align: center;
    }

    .cart-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 60px;
    }

    .cart-items {
        border-top: 1px solid rgba(0,0,0,0.08);
    }

    .cart-item {
        display: grid;
        grid-template-columns: 120px 1fr auto;
        gap: 24px;
        padding: 30px 0;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .cart-item-image {
        width: 100%;
        aspect-ratio: 3/4;
        object-fit: cover;
        background: var(--cream);
    }

    .cart-item-info h3 {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        margin-bottom: 8px;
    }

    .cart-item-variant {
        font-size: 13px;
        color: var(--grey);
        margin-bottom: 16px;
    }

    .cart-item-qty {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        border: 1px solid rgba(0,0,0,0.2);
        background: var(--white);
        cursor: pointer;
        font-size: 14px;
    }

    .qty-input {
        width: 50px;
        height: 32px;
        text-align: center;
        border: 1px solid rgba(0,0,0,0.2);
        font-size: 14px;
    }

    .remove-btn {
        background: none;
        border: none;
        color: #C41E3A;
        cursor: pointer;
        font-size: 13px;
        text-decoration: underline;
        margin-left: 16px;
    }

    .cart-item-price {
        text-align: right;
    }

    .price {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .cart-summary {
        background: var(--cream);
        padding: 40px;
        height: fit-content;
        position: sticky;
        top: 120px;
    }

    .summary-title {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 30px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        font-size: 14px;
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid rgba(0,0,0,0.2);
        font-size: 20px;
        font-weight: 600;
    }

    .checkout-btn {
        width: 100%;
        padding: 18px;
        background: var(--charcoal);
        color: var(--white);
        border: none;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        margin-top: 30px;
        transition: background 0.3s;
        text-decoration: none;
        display: block;
        text-align: center;
    }

    .checkout-btn:hover {
        background: var(--latte);
        color: var(--charcoal);
    }

    .empty-cart {
        text-align: center;
        padding: 100px 20px;
    }

    .empty-cart h2 {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        margin-bottom: 20px;
    }

    .empty-cart p {
        color: var(--grey);
        margin-bottom: 40px;
    }

    @media (max-width: 968px) {
        .cart-layout {
            grid-template-columns: 1fr;
        }

        .cart-item {
            grid-template-columns: 80px 1fr;
        }

        .cart-item-price {
            grid-column: 2;
        }
    }
</style>

<div class="cart-container">
    <h1 class="cart-title">Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="/pages/all-products.php" class="checkout-btn" style="max-width: 300px; margin: 0 auto;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $item_price = calculateDiscount($item['price'], $item['discount_percent']) + ($item['extra_price'] ?? 0);
                    $item_total = $item_price * $item['qty'];
                    ?>
                    <div class="cart-item">
                        <img src="<?php echo $item['image_path'] ? UPLOAD_URL . $item['image_path'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=300'; ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="cart-item-image">

                        <div class="cart-item-info">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <?php if ($item['color'] || $item['size']): ?>
                                <div class="cart-item-variant">
                                    <?php if ($item['color']): ?>Color: <?php echo htmlspecialchars($item['color']); ?><?php endif; ?>
                                    <?php if ($item['color'] && $item['size']): ?> | <?php endif; ?>
                                    <?php if ($item['size']): ?>Size: <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-bottom: 8px; font-size: 16px; font-weight: 600;">
                                <?php echo formatPrice($item_price); ?>
                            </div>
                            <div class="cart-item-qty">
                                <form method="POST" action="/pages/update-cart.php" style="display: flex; align-items: center; gap: 12px;">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="action" value="decrease" class="qty-btn">-</button>
                                    <input type="number" value="<?php echo $item['qty']; ?>" class="qty-input" readonly>
                                    <button type="submit" name="action" value="increase" class="qty-btn">+</button>
                                    <button type="submit" name="action" value="remove" class="remove-btn">Remove</button>
                                </form>
                            </div>
                        </div>

                        <div class="cart-item-price">
                            <div class="price"><?php echo formatPrice($item_total); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3 class="summary-title">Order Summary</h3>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatPrice($subtotal); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?php echo $shipping === 0 ? 'FREE' : formatPrice($shipping); ?></span>
                </div>

                <?php if ($shipping === 0): ?>
                    <p style="font-size: 12px; color: #2E7D32; margin-top: 8px;">✓ Free shipping applied!</p>
                <?php else: ?>
                    <p style="font-size: 12px; color: var(--grey); margin-top: 8px;">
                        Add <?php echo formatPrice(500000 - $subtotal); ?> more for free shipping
                    </p>
                <?php endif; ?>

                <div class="summary-total">
                    <span>Total</span>
                    <span><?php echo formatPrice($total); ?></span>
                </div>

                <a href="/pages/checkout.php" class="checkout-btn">Proceed to Checkout</a>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="/pages/all-products.php" style="color: var(--grey); font-size: 13px; text-decoration: none;">← Continue Shopping</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
