<?php
require_once __DIR__ . '/../config.php';

$stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE slug = 'faq' AND is_published = 1 LIMIT 1");
$stmt->execute();
$page = $stmt->fetch();

$page_title = $page['meta_title'] ?? $page['title'] ?? 'FAQ - Panduan Belanja Baju Online Dorve House | Pertanyaan Lengkap';
$page_description = $page['meta_description'] ?? 'Temukan jawaban lengkap seputar cara belanja, pengiriman, pembayaran, dan kebijakan return di Dorve House. Panduan lengkap belanja baju wanita online dengan aman dan mudah.';
$page_keywords = 'faq belanja online, cara belanja online, panduan belanja, tanya jawab baju online, kebijakan toko online, pengiriman baju, return baju online';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .faq-container {
        max-width: 900px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .faq-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .faq-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        margin-bottom: 16px;
    }

    .faq-header p {
        font-size: 16px;
        color: var(--grey);
    }

    .faq-categories {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-bottom: 60px;
        flex-wrap: wrap;
    }

    .category-btn {
        padding: 12px 24px;
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.15);
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .category-btn:hover,
    .category-btn.active {
        background: var(--charcoal);
        color: var(--white);
        border-color: var(--charcoal);
    }

    .faq-section {
        margin-bottom: 60px;
    }

    .faq-section-title {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin-bottom: 30px;
        padding-bottom: 16px;
        border-bottom: 2px solid var(--latte);
    }

    .faq-item {
        margin-bottom: 16px;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 4px;
        overflow: hidden;
    }

    .faq-question {
        width: 100%;
        padding: 20px 24px;
        background: var(--white);
        border: none;
        text-align: left;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.3s;
    }

    .faq-question:hover {
        background: var(--cream);
    }

    .faq-question.active {
        background: var(--cream);
    }

    .faq-arrow {
        transition: transform 0.3s;
        font-size: 20px;
    }

    .faq-question.active .faq-arrow {
        transform: rotate(180deg);
    }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: var(--white);
    }

    .faq-answer.active {
        max-height: 500px;
    }

    .faq-answer-content {
        padding: 24px;
        line-height: 1.8;
        color: var(--grey);
        border-top: 1px solid rgba(0,0,0,0.05);
    }

    @media (max-width: 768px) {
        .faq-container {
            padding: 0 24px;
        }

        .faq-header h1 {
            font-size: 36px;
        }

        .category-btn {
            padding: 10px 16px;
            font-size: 12px;
        }
    }
</style>

<div class="faq-container">
    <div class="faq-header">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about Dorve</p>
    </div>

    <div class="faq-categories">
        <button class="category-btn active" onclick="filterCategory('all')">All</button>
        <button class="category-btn" onclick="filterCategory('shipping')">Shipping</button>
        <button class="category-btn" onclick="filterCategory('payment')">Payment</button>
        <button class="category-btn" onclick="filterCategory('products')">Products</button>
        <button class="category-btn" onclick="filterCategory('returns')">Returns</button>
        <button class="category-btn" onclick="filterCategory('size')">Size Guide</button>
    </div>

    <div class="faq-section" data-category="shipping">
        <h2 class="faq-section-title">Shipping & Delivery</h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>How long does shipping take?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Standard shipping typically takes 3-5 business days within Jakarta and surrounding areas,
                    and 5-7 business days for other cities in Indonesia. Express shipping options are available
                    for faster delivery (1-2 business days).
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Do you offer free shipping?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Yes! We offer free standard shipping on all orders over Rp 500.000. Orders below this amount
                    will have a flat shipping fee of Rp 25.000.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Can I track my order?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Absolutely! Once your order ships, you'll receive a tracking number via email. You can also
                    track your order status in your account dashboard under "My Orders".
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section" data-category="payment">
        <h2 class="faq-section-title">Payment Methods</h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>What payment methods do you accept?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    We accept various payment methods including credit/debit cards (Visa, Mastercard, AMEX),
                    bank transfers, QRIS, PayPal, and cash on delivery (COD) for selected areas.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Is my payment information secure?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Yes, absolutely. All payment transactions are processed through secure, encrypted payment
                    gateways. We never store your complete credit card information on our servers.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Can I use multiple payment methods?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Currently, each order can only be paid using one payment method. However, you can use
                    voucher codes in combination with any payment method.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section" data-category="products">
        <h2 class="faq-section-title">Products & Quality</h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Are all products authentic?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Yes! All Dorve House products are 100% authentic and come with our quality guarantee. Each piece
                    is carefully crafted and inspected before shipping to ensure it meets our high standards.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>How do I care for my Dorve House pieces?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Care instructions are included with each item. Generally, we recommend hand washing or
                    gentle machine wash in cold water, and air drying to maintain the quality and longevity
                    of your garments. Specific care instructions vary by fabric.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Do you restock sold-out items?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Popular items are often restocked, but availability varies. You can sign up for restock
                    notifications on product pages, and we'll email you when the item becomes available again.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section" data-category="returns">
        <h2 class="faq-section-title">Returns & Exchanges</h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>What is your return policy?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    We offer a 14-day return policy from the date of delivery. Items must be unworn, unwashed,
                    with all original tags attached. Returns are free for defective items; customers cover
                    return shipping for change of mind returns.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Can I exchange items?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Yes! We offer exchanges for different sizes or colors within 14 days. Contact our customer
                    service team to arrange an exchange, and we'll help you find the perfect fit.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>How do I initiate a return?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Log into your account, go to "My Orders", select the order you want to return, and click
                    "Request Return". Follow the instructions, and you'll receive a return label and further
                    instructions via email.
                </div>
            </div>
        </div>
    </div>

    <div class="faq-section" data-category="size">
        <h2 class="faq-section-title">Size Guide</h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>How do I find my size?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    We recommend checking our detailed size guide available on each product page. Measurements
                    include bust, waist, hip, and length. If you're between sizes, we generally recommend
                    sizing up for a more comfortable fit.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>Do Dorve House clothes run true to size?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Most of our pieces run true to size, but fit can vary by style. We provide detailed
                    measurements and fit notes on each product page. Customer reviews often include fit
                    feedback that can be helpful.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span>What if I ordered the wrong size?</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    No problem! You can exchange for a different size within 14 days. Simply contact our
                    customer service team, and we'll arrange an exchange for you at no additional cost.
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 80px; padding: 60px 40px; background: var(--cream); border-radius: 8px;">
        <h3 style="font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 16px;">Still have questions?</h3>
        <p style="color: var(--grey); margin-bottom: 30px;">Our customer service team is here to help</p>
        <a href="mailto:support@dorve.co" style="display: inline-block; padding: 16px 40px; background: var(--charcoal); color: var(--white); text-decoration: none; font-size: 14px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase;">Contact Us</a>
    </div>
</div>

<script>
    function toggleFaq(button) {
        const answer = button.nextElementSibling;
        const isActive = button.classList.contains('active');

        document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));

        if (!isActive) {
            button.classList.add('active');
            answer.classList.add('active');
        }
    }

    function filterCategory(category) {
        document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        document.querySelectorAll('.faq-section').forEach(section => {
            if (category === 'all' || section.dataset.category === category) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
