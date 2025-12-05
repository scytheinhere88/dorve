<?php
require_once __DIR__ . '/../config.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect('/pages/all-products.php');
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/pages/all-products.php');
}

$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, color");
$stmt->execute([$product['id']]);
$variants = $stmt->fetchAll();

$colors = array_values(array_unique(array_filter(array_column($variants, 'color'))));
$sizes = array_values(array_unique(array_filter(array_column($variants, 'size'))));

$stmt = $pdo->prepare("SELECT r.*, u.name as user_name
                       FROM reviews r
                       JOIN users u ON r.user_id = u.id
                       WHERE r.product_id = ? AND r.is_approved = 1
                       ORDER BY r.created_at DESC");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

$avg_rating = 0;
if (!empty($reviews)) {
    $avg_rating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

$stmt = $pdo->prepare("SELECT p.*, pi.image_path FROM products p
                       LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                       WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
                       LIMIT 4");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();

$final_price = calculateDiscount($product['price'], $product['discount_percent']);

// Auto-generate SEO (DISABLED)
$page_title = ($product['name'] ?? 'Produk') . ' | Dorve House';
$page_description = 'Beli ' . ($product['name'] ?? 'produk') . ' di Dorve House. Harga: ' . formatPrice($product['price'] ?? 0) . '. Kualitas premium, 100% original.';
$page_keywords = ($product['name'] ?? 'produk') . ', beli online, dorve house';
// $page_title = generateProductSeoTitle($product);
// $page_description = generateProductSeoDescription($product);
// $page_keywords = generateProductSeoKeywords($product);
$og_type = 'product';
$og_image = $images[0]['image_path'] ?? $product['image_path'] ?? null;

// Add JSON-LD schemas (DISABLED)
$json_schemas = [];
// $json_schemas = [
//     generateOrganizationSchema(),
//     generateProductSchema($product, $images),
//     generateBreadcrumbSchema([
//         'Home' => '/',
//         $product['category_name'] ?? 'Produk' => '/pages/all-products.php?category=' . ($product['category_slug'] ?? ''),
//         $product['name'] => '/pages/product-detail.php?slug=' . $product['slug']
//     ])
// ];

include __DIR__ . '/../includes/header.php';
?>

<style>
    .product-detail-layout {
        max-width: 1400px;
        margin: 0 auto;
        padding: 80px 40px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
    }

    .product-gallery {
        position: sticky;
        top: 120px;
        height: fit-content;
    }

    .main-image {
        width: 100%;
        aspect-ratio: 3/4;
        object-fit: cover;
        margin-bottom: 20px;
        background: var(--cream);
    }

    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 12px;
    }

    .thumbnail {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color 0.3s;
    }

    .thumbnail:hover,
    .thumbnail.active {
        border-color: var(--charcoal);
    }

    .product-info {
        padding-top: 20px;
    }

    .breadcrumb {
        font-size: 13px;
        color: var(--grey);
        margin-bottom: 20px;
    }

    .breadcrumb a {
        color: var(--grey);
        text-decoration: none;
    }

    .breadcrumb a:hover {
        color: var(--charcoal);
    }

    .product-title {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 16px;
        line-height: 1.2;
    }

    .product-rating {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .stars {
        color: var(--latte);
    }

    .product-price-section {
        margin-bottom: 40px;
        padding-bottom: 40px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .current-price {
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .original-price {
        font-size: 20px;
        color: var(--grey);
        text-decoration: line-through;
        margin-right: 12px;
    }

    .discount-badge {
        display: inline-block;
        background: #C41E3A;
        color: var(--white);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .variant-section {
        margin-bottom: 32px;
    }

    .variant-label {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 16px;
        display: block;
    }

    .variant-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .variant-option {
        padding: 12px 24px;
        border: 1px solid rgba(0,0,0,0.2);
        background: var(--white);
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
    }

    .variant-option:hover {
        border-color: var(--charcoal);
    }

    .variant-option.selected {
        background: var(--charcoal);
        color: var(--white);
        border-color: var(--charcoal);
    }

    .variant-option.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 32px;
    }

    .qty-btn {
        width: 40px;
        height: 40px;
        border: 1px solid rgba(0,0,0,0.2);
        background: var(--white);
        cursor: pointer;
        font-size: 18px;
        transition: background 0.3s;
    }

    .qty-btn:hover {
        background: var(--cream);
    }

    .qty-input {
        width: 60px;
        height: 40px;
        text-align: center;
        border: 1px solid rgba(0,0,0,0.2);
        font-size: 16px;
        font-weight: 600;
    }

    .add-to-cart-btn {
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
        transition: all 0.3s;
        margin-bottom: 16px;
    }

    .add-to-cart-btn:hover {
        background: var(--latte);
        color: var(--charcoal);
    }

    .product-description {
        margin-top: 60px;
        padding-top: 60px;
        border-top: 1px solid rgba(0,0,0,0.08);
    }

    .description-tabs {
        display: flex;
        gap: 40px;
        margin-bottom: 40px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .tab-btn {
        padding: 16px 0;
        background: none;
        border: none;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: border-color 0.3s;
    }

    .tab-btn.active {
        border-bottom-color: var(--charcoal);
    }

    .tab-content {
        display: none;
        line-height: 1.8;
        color: var(--grey);
    }

    .tab-content.active {
        display: block;
    }

    .review-item {
        padding: 30px 0;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .reviewer-name {
        font-weight: 600;
    }

    .review-date {
        font-size: 13px;
        color: var(--grey);
    }

    .review-body {
        color: var(--grey);
        line-height: 1.7;
    }

    .related-section {
        max-width: 1400px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 36px;
        text-align: center;
        margin-bottom: 60px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }

    @media (max-width: 968px) {
        .product-detail-layout {
            grid-template-columns: 1fr;
            gap: 40px;
            padding: 40px 24px;
        }

        .product-gallery {
            position: static;
        }

        .product-title {
            font-size: 32px;
        }

        .product-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="product-detail-layout">
    <div class="product-gallery">
        <img src="<?php echo !empty($images) ? UPLOAD_URL . $images[0]['image_path'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=800'; ?>"
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             class="main-image"
             id="mainImage">

        <?php if (count($images) > 1): ?>
        <div class="thumbnail-grid">
            <?php foreach ($images as $index => $image): ?>
                <img src="<?php echo UPLOAD_URL . $image['image_path']; ?>"
                     alt="Thumbnail <?php echo $index + 1; ?>"
                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                     onclick="changeMainImage(this, '<?php echo UPLOAD_URL . $image['image_path']; ?>')">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="product-info">
        <div class="breadcrumb">
            <a href="/index.php">Home</a> /
            <a href="/pages/all-products.php">Products</a> /
            <a href="/pages/all-products.php?category=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
        </div>

        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

        <?php if (!empty($reviews)): ?>
        <div class="product-rating">
            <span class="stars">
                <?php
                $full_stars = floor($avg_rating);
                for ($i = 0; $i < $full_stars; $i++) echo '★';
                for ($i = $full_stars; $i < 5; $i++) echo '☆';
                ?>
            </span>
            <span><?php echo number_format($avg_rating, 1); ?> (<?php echo count($reviews); ?> reviews)</span>
        </div>
        <?php endif; ?>

        <div class="product-price-section">
            <?php if ($product['discount_percent'] > 0): ?>
                <div>
                    <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                    <span class="discount-badge">-<?php echo $product['discount_percent']; ?>%</span>
                </div>
                <div class="current-price"><?php echo formatPrice($final_price); ?></div>
            <?php else: ?>
                <div class="current-price"><?php echo formatPrice($product['price']); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($product['short_description']): ?>
            <p style="margin-bottom: 32px; line-height: 1.7; color: var(--grey);">
                <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
            </p>
        <?php endif; ?>

        <form action="/pages/add-to-cart.php" method="POST" id="addToCartForm">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="variant_id" id="variantIdInput">

            <?php if (!empty($colors)): ?>
            <div class="variant-section">
                <label class="variant-label">Color</label>
                <div class="variant-options">
                    <?php foreach ($colors as $color): ?>
                        <button type="button" class="variant-option" data-variant="color" data-value="<?php echo htmlspecialchars($color); ?>">
                            <?php echo htmlspecialchars($color); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($sizes)): ?>
            <div class="variant-section">
                <label class="variant-label">Size</label>
                <div class="variant-options">
                    <?php foreach ($sizes as $size): ?>
                        <button type="button" class="variant-option" data-variant="size" data-value="<?php echo htmlspecialchars($size); ?>">
                            <?php echo htmlspecialchars($size); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="quantity-selector">
                <span class="variant-label">Quantity</span>
                <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                <input type="number" name="qty" id="qtyInput" class="qty-input" value="1" min="1" max="10" readonly>
                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
            </div>

            <button type="submit" class="add-to-cart-btn">Add to Cart</button>
        </form>

        <div style="text-align: center; padding: 24px; background: var(--cream); border-radius: 8px; margin-top: 32px;">
            <p style="font-size: 14px; margin-bottom: 8px;">✓ Free shipping on orders over Rp 500.000</p>
            <p style="font-size: 14px; margin-bottom: 8px;">✓ Easy returns within 14 days</p>
            <p style="font-size: 14px;">✓ Authentic guarantee</p>
        </div>

        <div class="product-description">
            <div class="description-tabs">
                <button class="tab-btn active" onclick="switchTab('description')">Description</button>
                <button class="tab-btn" onclick="switchTab('reviews')">Reviews (<?php echo count($reviews); ?>)</button>
            </div>

            <div id="description" class="tab-content active">
                <?php echo nl2br(htmlspecialchars($product['long_description'] ?? $product['short_description'])); ?>
            </div>

            <div id="reviews" class="tab-content">
                <?php if (empty($reviews)): ?>
                    <p style="color: var(--grey);">No reviews yet. Be the first to review this product!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                    <div class="stars" style="color: var(--latte); font-size: 14px;">
                                        <?php
                                        for ($i = 0; $i < $review['rating']; $i++) echo '★';
                                        for ($i = $review['rating']; $i < 5; $i++) echo '☆';
                                        ?>
                                    </div>
                                </div>
                                <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                            </div>
                            <?php if ($review['title']): ?>
                                <h4 style="margin-bottom: 8px; font-weight: 600;"><?php echo htmlspecialchars($review['title']); ?></h4>
                            <?php endif; ?>
                            <div class="review-body"><?php echo nl2br(htmlspecialchars($review['body'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($related_products)): ?>
<section class="related-section">
    <h2 class="section-title">You May Also Like</h2>
    <div class="product-grid">
        <?php foreach ($related_products as $related): ?>
            <a href="/pages/product-detail.php?slug=<?php echo $related['slug']; ?>" style="text-decoration: none; color: inherit;">
                <img src="<?php echo $related['image_path'] ? UPLOAD_URL . $related['image_path'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=400'; ?>"
                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                     style="width: 100%; aspect-ratio: 3/4; object-fit: cover; margin-bottom: 16px; background: var(--cream);">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 18px; margin-bottom: 8px;"><?php echo htmlspecialchars($related['name']); ?></h3>
                <div style="font-size: 16px; font-weight: 600;"><?php echo formatPrice($related['price']); ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
    const variants = <?php echo json_encode($variants); ?>;
    let selectedColor = null;
    let selectedSize = null;

    function changeMainImage(thumbnail, src) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        thumbnail.classList.add('active');
    }

    function changeQty(delta) {
        const input = document.getElementById('qtyInput');
        const newValue = parseInt(input.value) + delta;
        if (newValue >= 1 && newValue <= 10) {
            input.value = newValue;
        }
    }

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        event.target.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    document.querySelectorAll('.variant-option').forEach(option => {
        option.addEventListener('click', function() {
            const variantType = this.dataset.variant;
            const value = this.dataset.value;

            this.parentElement.querySelectorAll('.variant-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');

            if (variantType === 'color') {
                selectedColor = value;
            } else if (variantType === 'size') {
                selectedSize = value;
            }

            updateVariantId();
        });
    });

    function updateVariantId() {
        if (!selectedColor && !selectedSize) return;

        const matchingVariant = variants.find(v => {
            return (!selectedColor || v.color === selectedColor) &&
                   (!selectedSize || v.size === selectedSize);
        });

        if (matchingVariant) {
            document.getElementById('variantIdInput').value = matchingVariant.id;
        }
    }

    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        <?php if (!empty($colors) || !empty($sizes)): ?>
        if (!document.getElementById('variantIdInput').value) {
            e.preventDefault();
            alert('Please select <?php echo !empty($colors) ? "a color" : ""; ?><?php echo !empty($colors) && !empty($sizes) ? " and " : ""; ?><?php echo !empty($sizes) ? "a size" : ""; ?>');
        }
        <?php endif; ?>
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
