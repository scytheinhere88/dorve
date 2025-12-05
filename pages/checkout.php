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
    * { box-sizing: border-box; }
    .checkout-container { 
        max-width: 1400px; margin: 100px auto 60px; padding: 0 40px; 
        display: grid; grid-template-columns: 1.2fr 480px; gap: 50px; 
    }
    
    /* Modern Checkout Form */
    .checkout-form h2 { 
        font-family: 'Playfair Display', serif; font-size: 40px; 
        margin-bottom: 12px; font-weight: 700;
        background: linear-gradient(135deg, #1A1A1A 0%, #667EEA 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .checkout-subtitle {
        color: #6B7280; font-size: 16px; margin-bottom: 40px;
    }
    
    .form-section { 
        background: white; padding: 32px; border-radius: 16px; 
        margin-bottom: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #E5E7EB; transition: all 0.3s;
    }
    .form-section:hover { 
        box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
        transform: translateY(-2px);
    }
    
    .form-section h3 { 
        font-size: 22px; margin-bottom: 24px; font-weight: 700;
        display: flex; align-items: center; gap: 12px;
        color: #1F2937;
    }
    .form-section h3::before {
        content: ''; width: 4px; height: 24px;
        background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
        border-radius: 2px;
    }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { 
        display: block; margin-bottom: 10px; font-weight: 600; 
        font-size: 14px; color: #374151;
    }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; padding: 14px 18px; 
        border: 2px solid #E5E7EB; border-radius: 10px; 
        font-size: 15px; font-family: 'Inter', sans-serif;
        transition: all 0.3s; background: #F9FAFB;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
        outline: none; border-color: #667EEA; background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    .form-group textarea { min-height: 100px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    /* Modern Payment/Shipping Method Cards */
    .payment-method { 
        display: flex; align-items: center; padding: 18px 20px; 
        border: 2px solid #E5E7EB; border-radius: 12px; 
        margin-bottom: 12px; cursor: pointer; 
        transition: all 0.3s; background: #F9FAFB;
        position: relative; overflow: hidden;
    }
    .payment-method::before {
        content: ''; position: absolute; left: 0; top: 0;
        width: 0; height: 100%; 
        background: linear-gradient(90deg, rgba(102,126,234,0.1) 0%, transparent 100%);
        transition: width 0.3s;
    }
    .payment-method:hover { 
        border-color: #667EEA; background: white;
        transform: translateX(4px);
    }
    .payment-method:hover::before { width: 100%; }
    .payment-method input { margin-right: 14px; width: 20px; height: 20px; }
    .payment-method.selected {
        border-color: #667EEA; background: #EEF2FF;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    
    /* Premium Order Summary */
    .order-summary { 
        background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); 
        padding: 36px; border-radius: 20px; position: sticky; top: 120px; 
        height: fit-content; box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        color: white;
    }
    .order-summary h3 { 
        font-family: 'Playfair Display', serif; font-size: 28px; 
        margin-bottom: 28px; color: white; font-weight: 700;
    }
    
    .summary-item { 
        display: flex; justify-content: space-between; 
        margin-bottom: 16px; font-size: 15px; color: rgba(255,255,255,0.9);
        padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .summary-item:last-of-type { border-bottom: none; }
    
    /* Voucher Section in Summary */
    .voucher-section {
        margin: 24px 0; padding: 20px; 
        background: rgba(255,255,255,0.05); 
        border-radius: 12px; border: 1px dashed rgba(255,255,255,0.2);
    }
    .btn-voucher {
        width: 100%; padding: 14px; 
        background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
        color: white; border: none; border-radius: 10px; 
        font-size: 15px; font-weight: 600; cursor: pointer;
        transition: all 0.3s; display: flex; align-items: center;
        justify-content: center; gap: 10px;
    }
    .btn-voucher:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .applied-voucher {
        margin-top: 12px; padding: 12px; 
        background: rgba(16, 185, 129, 0.15);
        border-radius: 8px; font-size: 13px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .applied-voucher .code {
        font-weight: 700; font-family: 'Courier New', monospace;
        color: #10B981;
    }
    .remove-voucher {
        color: #EF4444; cursor: pointer; font-size: 18px;
        transition: all 0.2s;
    }
    .remove-voucher:hover { transform: scale(1.2); }
    
    .summary-total { 
        display: flex; justify-content: space-between; 
        padding-top: 24px; margin-top: 24px; 
        border-top: 2px solid rgba(255,255,255,0.2);
        font-size: 28px; font-weight: 700; 
        font-family: 'Playfair Display', serif;
        color: white;
    }
    
    /* ULTIMATE CHECKOUT BUTTON 🔥 */
    .btn-checkout { 
        width: 100%; padding: 20px; 
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white; border: none; border-radius: 14px; 
        font-size: 18px; font-weight: 700; cursor: pointer; 
        margin-top: 28px; transition: all 0.3s;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        position: relative; overflow: hidden;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .btn-checkout::before {
        content: ''; position: absolute; top: 50%; left: 50%;
        width: 0; height: 0; border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    .btn-checkout:hover::before {
        width: 300px; height: 300px;
    }
    .btn-checkout:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 12px 32px rgba(16, 185, 129, 0.5);
    }
    .btn-checkout:active {
        transform: translateY(-2px);
    }
    
    /* VOUCHER MODAL - SUPER PREMIUM 💎 */
    .voucher-modal {
        display: none; position: fixed; z-index: 9999; 
        left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
        animation: fadeIn 0.3s;
    }
    .voucher-modal.show { display: flex; justify-content: center; align-items: center; }
    
    .voucher-modal-content {
        background: white; border-radius: 24px; 
        max-width: 900px; width: 90%; max-height: 85vh;
        overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    
    .voucher-modal-header {
        padding: 32px; background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
        color: white; border-radius: 24px 24px 0 0; position: sticky; top: 0; z-index: 10;
    }
    .voucher-modal-header h2 {
        font-size: 32px; font-weight: 700; margin-bottom: 8px;
    }
    .voucher-modal-header p {
        font-size: 15px; opacity: 0.95;
    }
    .close-modal {
        position: absolute; right: 24px; top: 24px;
        font-size: 32px; cursor: pointer; color: white;
        transition: all 0.3s; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; background: rgba(255,255,255,0.1);
    }
    .close-modal:hover { 
        background: rgba(255,255,255,0.2); 
        transform: rotate(90deg);
    }
    
    .voucher-modal-body { padding: 32px; }
    
    .voucher-type-section {
        margin-bottom: 36px;
    }
    .voucher-type-title {
        font-size: 22px; font-weight: 700; margin-bottom: 20px;
        display: flex; align-items: center; gap: 12px;
        color: #1F2937;
    }
    
    .voucher-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .voucher-card-mini {
        background: white; border-radius: 16px; 
        border: 2px solid #E5E7EB; cursor: pointer;
        transition: all 0.3s; overflow: hidden;
        position: relative;
    }
    .voucher-card-mini:hover {
        border-color: #667EEA;
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
    }
    .voucher-card-mini.selected {
        border-color: #10B981; background: #ECFDF5;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    .voucher-card-mini.selected::after {
        content: '✓'; position: absolute; right: 12px; top: 12px;
        background: #10B981; color: white; width: 28px; height: 28px;
        border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 700;
    }
    
    .voucher-card-header-mini {
        padding: 20px; background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
        color: white;
    }
    .voucher-card-header-mini.free-shipping {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    }
    .voucher-code-mini {
        font-size: 20px; font-weight: 700; 
        font-family: 'Courier New', monospace; margin-bottom: 4px;
    }
    .voucher-name-mini {
        font-size: 14px; opacity: 0.95;
    }
    
    .voucher-card-body-mini {
        padding: 20px;
    }
    .voucher-value-mini {
        font-size: 24px; font-weight: 700; 
        color: #1F2937; margin-bottom: 12px;
    }
    .voucher-condition-mini {
        font-size: 13px; color: #6B7280; margin-bottom: 6px;
        display: flex; align-items: center; gap: 6px;
    }
    
    .modal-footer {
        padding: 24px 32px; background: #F9FAFB;
        border-top: 1px solid #E5E7EB; display: flex;
        justify-content: space-between; align-items: center;
        position: sticky; bottom: 0;
    }
    .btn-apply-vouchers {
        padding: 14px 32px; 
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white; border: none; border-radius: 10px;
        font-size: 16px; font-weight: 600; cursor: pointer;
        transition: all 0.3s;
    }
    .btn-apply-vouchers:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @media (max-width: 1024px) {
        .checkout-container { grid-template-columns: 1fr; gap: 30px; }
        .order-summary { position: relative; top: 0; }
        .voucher-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="checkout-container">
    <div class="checkout-form">
        <h2>🛍️ Checkout</h2>
        <p class="checkout-subtitle">Complete your order in just a few steps</p>

        <form action="/pages/process-order.php" method="POST" id="checkoutForm">
            <div class="form-section">
                <h3>📦 Shipping Information</h3>
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
                <h3>🚚 Shipping Method</h3>
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
                <h3>💳 Payment Method</h3>
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

            <input type="hidden" name="voucher_discount" id="voucher_discount_input" value="">
            <input type="hidden" name="voucher_free_shipping" id="voucher_free_shipping_input" value="">
            <input type="hidden" name="voucher_codes" id="voucher_codes_input" value="">
            
            <button type="submit" class="btn-checkout">
                🎉 Complete Purchase
            </button>
        </form>
    </div>

    <div class="order-summary">
        <h3>💰 Order Summary</h3>
        
        <?php foreach ($cart_items as $item): ?>
            <div class="summary-item">
                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['qty']; ?></span>
                <span><?php echo formatPrice($item['price'] * $item['qty']); ?></span>
            </div>
        <?php endforeach; ?>

        <div class="summary-item">
            <span>Subtotal</span>
            <span id="subtotal-display"><?php echo formatPrice($subtotal); ?></span>
        </div>
        <div class="summary-item">
            <span>Shipping</span>
            <span id="shipping-cost">-</span>
        </div>
        
        <!-- Voucher Section -->
        <div class="voucher-section">
            <button type="button" class="btn-voucher" onclick="openVoucherModal()">
                🎟️ <span>Apply Voucher</span>
            </button>
            <div id="applied-vouchers-container"></div>
        </div>
        
        <div class="summary-item" id="discount-row" style="display: none; color: #10B981;">
            <span>💰 Voucher Discount</span>
            <span id="discount-amount">-</span>
        </div>
        <div class="summary-item" id="free-shipping-row" style="display: none; color: #10B981;">
            <span>🚚 Free Shipping</span>
            <span id="free-shipping-amount">-</span>
        </div>

        <div class="summary-total">
            <span>Total</span>
            <span id="total"><?php echo formatPrice($subtotal); ?></span>
        </div>
    </div>
</div>

<!-- VOUCHER MODAL 💎 -->
<div class="voucher-modal" id="voucherModal">
    <div class="voucher-modal-content">
        <div class="voucher-modal-header">
            <span class="close-modal" onclick="closeVoucherModal()">&times;</span>
            <h2>🎟️ Select Your Vouchers</h2>
            <p>Choose up to 2 vouchers: 1 Free Shipping + 1 Discount</p>
        </div>
        
        <div class="voucher-modal-body">
            <div class="voucher-type-section" id="free-shipping-section">
                <div class="voucher-type-title">
                    <span>🚚</span> Free Shipping Vouchers
                </div>
                <div class="voucher-grid" id="free-shipping-vouchers">
                    <p style="color: #6B7280; text-align: center; padding: 40px;">Loading vouchers...</p>
                </div>
            </div>
            
            <div class="voucher-type-section" id="discount-section">
                <div class="voucher-type-title">
                    <span>💰</span> Discount Vouchers
                </div>
                <div class="voucher-grid" id="discount-vouchers">
                    <p style="color: #6B7280; text-align: center; padding: 40px;">Loading vouchers...</p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <div style="color: #6B7280; font-size: 14px;">
                Selected: <strong id="selected-count">0</strong> / 2 vouchers
            </div>
            <button type="button" class="btn-apply-vouchers" onclick="applySelectedVouchers()">
                ✨ Apply Vouchers
            </button>
        </div>
    </div>
</div>

<script>
// Global state
const selectedVouchers = {
    free_shipping: null,
    discount: null
};
let availableVouchers = {
    free_shipping: [],
    discount: []
};
const subtotal = <?php echo $subtotal; ?>;
let currentShipping = 0;

// Open modal and load vouchers
function openVoucherModal() {
    document.getElementById('voucherModal').classList.add('show');
    loadAvailableVouchers();
}

function closeVoucherModal() {
    document.getElementById('voucherModal').classList.remove('show');
}

// Load vouchers from API
function loadAvailableVouchers() {
    fetch(`/api/vouchers/get-available.php?cart_total=${subtotal}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                availableVouchers = data.vouchers;
                renderVouchers();
            }
        })
        .catch(e => console.error('Error loading vouchers:', e));
}

function renderVouchers() {
    // Render free shipping vouchers
    const freeShippingContainer = document.getElementById('free-shipping-vouchers');
    if (availableVouchers.free_shipping.length === 0) {
        freeShippingContainer.innerHTML = '<p style="color: #6B7280; text-align: center; padding: 40px;">No free shipping vouchers available</p>';
    } else {
        freeShippingContainer.innerHTML = availableVouchers.free_shipping.map(v => `
            <div class="voucher-card-mini ${selectedVouchers.free_shipping?.id === v.id ? 'selected' : ''}" 
                 onclick="selectVoucher('free_shipping', ${JSON.stringify(v).replace(/"/g, '&quot;')})">
                <div class="voucher-card-header-mini free-shipping">
                    <div class="voucher-code-mini">${v.code}</div>
                    <div class="voucher-name-mini">${v.name}</div>
                </div>
                <div class="voucher-card-body-mini">
                    <div class="voucher-value-mini">
                        FREE SHIPPING
                        ${v.discount_value ? `<span style="font-size: 14px; color: #6B7280;">(Max: Rp ${formatNumber(v.discount_value)})</span>` : ''}
                    </div>
                    ${v.min_purchase ? `<div class="voucher-condition-mini">📦 Min: Rp ${formatNumber(v.min_purchase)}</div>` : ''}
                    <div class="voucher-condition-mini">🔢 ${v.max_usage - v.usage_count} uses left</div>
                    <div class="voucher-condition-mini">📅 Valid until ${new Date(v.valid_until).toLocaleDateString('id-ID')}</div>
                </div>
            </div>
        `).join('');
    }
    
    // Render discount vouchers
    const discountContainer = document.getElementById('discount-vouchers');
    if (availableVouchers.discount.length === 0) {
        discountContainer.innerHTML = '<p style="color: #6B7280; text-align: center; padding: 40px;">No discount vouchers available</p>';
    } else {
        discountContainer.innerHTML = availableVouchers.discount.map(v => {
            let valueText = '';
            if (v.discount_type === 'percentage') {
                valueText = `${v.discount_value}% OFF`;
                if (v.max_discount) valueText += ` <span style="font-size: 14px; color: #6B7280;">(Max: Rp ${formatNumber(v.max_discount)})</span>`;
            } else {
                valueText = `Rp ${formatNumber(v.discount_value)} OFF`;
            }
            
            return `
                <div class="voucher-card-mini ${selectedVouchers.discount?.id === v.id ? 'selected' : ''}" 
                     onclick="selectVoucher('discount', ${JSON.stringify(v).replace(/"/g, '&quot;')})">
                    <div class="voucher-card-header-mini">
                        <div class="voucher-code-mini">${v.code}</div>
                        <div class="voucher-name-mini">${v.name}</div>
                    </div>
                    <div class="voucher-card-body-mini">
                        <div class="voucher-value-mini">${valueText}</div>
                        ${v.min_purchase ? `<div class="voucher-condition-mini">📦 Min: Rp ${formatNumber(v.min_purchase)}</div>` : ''}
                        <div class="voucher-condition-mini">🔢 ${v.max_usage - v.usage_count} uses left</div>
                        <div class="voucher-condition-mini">📅 Valid until ${new Date(v.valid_until).toLocaleDateString('id-ID')}</div>
                    </div>
                </div>
            `;
        }).join('');
    }
}

function selectVoucher(type, voucher) {
    selectedVouchers[type] = voucher;
    renderVouchers();
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = (selectedVouchers.free_shipping ? 1 : 0) + (selectedVouchers.discount ? 1 : 0);
    document.getElementById('selected-count').textContent = count;
}

function applySelectedVouchers() {
    // Update applied vouchers display
    const container = document.getElementById('applied-vouchers-container');
    container.innerHTML = '';
    
    if (selectedVouchers.free_shipping) {
        container.innerHTML += `
            <div class="applied-voucher">
                <div>
                    <span class="code">${selectedVouchers.free_shipping.code}</span>
                    <div style="font-size: 11px; opacity: 0.8;">Free Shipping</div>
                </div>
                <span class="remove-voucher" onclick="removeVoucher('free_shipping')">✕</span>
            </div>
        `;
    }
    
    if (selectedVouchers.discount) {
        container.innerHTML += `
            <div class="applied-voucher">
                <div>
                    <span class="code">${selectedVouchers.discount.code}</span>
                    <div style="font-size: 11px; opacity: 0.8;">Discount</div>
                </div>
                <span class="remove-voucher" onclick="removeVoucher('discount')">✕</span>
            </div>
        `;
    }
    
    // Recalculate totals
    recalculateTotal();
    closeVoucherModal();
}

function removeVoucher(type) {
    selectedVouchers[type] = null;
    applySelectedVouchers();
}

function recalculateTotal() {
    let total = subtotal + currentShipping;
    let discountAmount = 0;
    let freeShippingAmount = 0;
    
    // Apply discount voucher
    if (selectedVouchers.discount) {
        const v = selectedVouchers.discount;
        if (v.discount_type === 'percentage') {
            discountAmount = (subtotal * v.discount_value) / 100;
            if (v.max_discount && discountAmount > v.max_discount) {
                discountAmount = v.max_discount;
            }
        } else {
            discountAmount = v.discount_value;
        }
        total -= discountAmount;
    }
    
    // Apply free shipping voucher
    if (selectedVouchers.free_shipping) {
        freeShippingAmount = currentShipping;
        if (selectedVouchers.free_shipping.discount_value && freeShippingAmount > selectedVouchers.free_shipping.discount_value) {
            freeShippingAmount = selectedVouchers.free_shipping.discount_value;
        }
        total -= freeShippingAmount;
    }
    
    // Update display
    if (discountAmount > 0) {
        document.getElementById('discount-row').style.display = 'flex';
        document.getElementById('discount-amount').textContent = '- ' + formatPrice(discountAmount);
    } else {
        document.getElementById('discount-row').style.display = 'none';
    }
    
    if (freeShippingAmount > 0) {
        document.getElementById('free-shipping-row').style.display = 'flex';
        document.getElementById('free-shipping-amount').textContent = '- ' + formatPrice(freeShippingAmount);
    } else {
        document.getElementById('free-shipping-row').style.display = 'none';
    }
    
    document.getElementById('total').textContent = formatPrice(Math.max(0, total));
    
    // Update hidden form fields
    document.getElementById('voucher_discount_input').value = discountAmount;
    document.getElementById('voucher_free_shipping_input').value = freeShippingAmount > 0 ? 1 : 0;
    const codes = [selectedVouchers.free_shipping?.code, selectedVouchers.discount?.code].filter(Boolean).join(',');
    document.getElementById('voucher_codes_input').value = codes;
}

// Shipping cost update
document.querySelectorAll('input[name="shipping_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const selectedMethod = <?php echo json_encode($shipping_methods); ?>.find(m => m.id == this.value);
        currentShipping = parseFloat(selectedMethod.cost) || 0;
        document.getElementById('shipping-cost').textContent = formatPrice(currentShipping);
        recalculateTotal();
    });
});

// Payment method selection visual
document.querySelectorAll('.payment-method').forEach(method => {
    method.addEventListener('click', function() {
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

function formatPrice(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Close modal on outside click
document.getElementById('voucherModal').addEventListener('click', function(e) {
    if (e.target === this) closeVoucherModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
