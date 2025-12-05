<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();

// Get referral statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_referrals,
           SUM(CASE WHEN rr.status = 'completed' THEN rr.reward_value ELSE 0 END) as total_commission,
           SUM(CASE WHEN rr.status = 'pending' THEN rr.reward_value ELSE 0 END) as pending_commission
    FROM referral_rewards rr
    WHERE rr.referrer_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Get list of referred users with their details
$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.created_at,
           rr.reward_value, rr.status, rr.completed_at,
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count
    FROM users u
    LEFT JOIN referral_rewards rr ON rr.referred_id = u.id AND rr.referrer_id = ?
    WHERE u.referred_by = ?
    ORDER BY u.created_at DESC
");
$stmt->execute([$user['id'], $user['id']]);
$referred_users = $stmt->fetchAll();

// Get referral vouchers (created when someone signs up with referral code)
$stmt = $pdo->prepare("
    SELECT v.*, rr.referred_id, u.name as referred_user_name
    FROM vouchers v
    LEFT JOIN referral_rewards rr ON v.code = CONCAT('REF', rr.id)
    LEFT JOIN users u ON rr.referred_id = u.id
    WHERE v.code LIKE 'REF%'
    AND rr.referrer_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$user['id']]);
$referral_vouchers = $stmt->fetchAll();

$page_title = 'My Referrals - Earn Rewards | Dorve House';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .referral-container {
        max-width: 1200px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .referral-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .referral-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 16px;
    }

    .referral-header p {
        font-size: 18px;
        color: var(--grey);
    }

    /* Referral Code Card */
    .referral-code-card {
        background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 40px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .code-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin: 30px 0;
    }

    .code-box {
        background: rgba(255,255,255,0.1);
        padding: 20px 40px;
        border-radius: 8px;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 4px;
        border: 2px dashed rgba(255,255,255,0.3);
    }

    .copy-btn {
        background: white;
        color: #1A1A1A;
        border: none;
        padding: 14px 28px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .copy-btn:hover {
        background: #F0F0F0;
        transform: translateY(-2px);
    }

    .share-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-top: 30px;
    }

    .share-btn {
        padding: 12px 24px;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.1);
        color: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .share-btn:hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-2px);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: var(--cream);
        padding: 30px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-number {
        font-size: 36px;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
        margin-bottom: 8px;
        color: var(--charcoal);
    }

    .stat-label {
        font-size: 14px;
        color: var(--grey);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Sections */
    .section {
        background: white;
        padding: 40px;
        border-radius: 8px;
        margin-bottom: 30px;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .section-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Table Styles */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        padding: 16px;
        background: var(--cream);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }

    td {
        padding: 20px 16px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    tr:hover {
        background: rgba(0,0,0,0.02);
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-completed {
        background: #D4EDDA;
        color: #155724;
    }

    .status-pending {
        background: #FFF3CD;
        color: #856404;
    }

    .voucher-code {
        font-family: monospace;
        background: var(--cream);
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--grey);
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    @media (max-width: 768px) {
        .referral-container {
            padding: 0 20px;
        }

        .code-display {
            flex-direction: column;
        }

        .code-box {
            font-size: 24px;
            padding: 16px 24px;
        }

        .share-buttons {
            flex-direction: column;
        }

        .section {
            padding: 24px;
        }

        table {
            font-size: 14px;
        }

        th, td {
            padding: 12px 8px;
        }
    }
</style>

<div class="referral-container">
    <div class="referral-header">
        <h1>🎁 My Referral Program</h1>
        <p>Invite friends and earn Rp 50,000 for each successful referral!</p>
    </div>

    <!-- Referral Code Card -->
    <div class="referral-code-card">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size: 24px; margin-bottom: 8px;">Your Referral Code</h2>
            <p style="opacity: 0.8; font-size: 15px;">Share this code with your friends</p>
        </div>

        <div class="code-display">
            <div class="code-box" id="referralCode"><?php echo htmlspecialchars($user['referral_code']); ?></div>
            <button class="copy-btn" onclick="copyReferralCode()">📋 Copy Code</button>
        </div>

        <div class="share-buttons">
            <button class="share-btn" onclick="shareWhatsApp()">
                💬 Share via WhatsApp
            </button>
            <button class="share-btn" onclick="shareFacebook()">
                📘 Share via Facebook
            </button>
            <button class="share-btn" onclick="shareTwitter()">
                🐦 Share via Twitter
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_referrals'] ?? 0; ?></div>
            <div class="stat-label">Total Referrals</div>
        </div>

        <div class="stat-card">
            <div class="stat-number">Rp <?php echo number_format($stats['total_commission'] ?? 0, 0, ',', '.'); ?></div>
            <div class="stat-label">Total Commission Earned</div>
        </div>

        <div class="stat-card">
            <div class="stat-number">Rp <?php echo number_format($stats['pending_commission'] ?? 0, 0, ',', '.'); ?></div>
            <div class="stat-label">Pending Commission</div>
        </div>
    </div>

    <!-- Referral Vouchers Section -->
    <div class="section">
        <h2 class="section-title">🎫 My Referral Vouchers</h2>

        <?php if (count($referral_vouchers) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Voucher Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>From Referral</th>
                    <th>Expires</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referral_vouchers as $voucher): ?>
                <tr>
                    <td>
                        <span class="voucher-code"><?php echo htmlspecialchars($voucher['code']); ?></span>
                    </td>
                    <td>
                        <?php
                        if ($voucher['type'] === 'percentage') {
                            echo 'Percentage';
                        } elseif ($voucher['type'] === 'fixed') {
                            echo 'Fixed Amount';
                        } else {
                            echo 'Free Shipping';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($voucher['type'] === 'percentage') {
                            echo $voucher['value'] . '%';
                        } elseif ($voucher['type'] === 'fixed') {
                            echo 'Rp ' . number_format($voucher['value'], 0, ',', '.');
                        } else {
                            echo 'Free';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($voucher['referred_user_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php
                        if ($voucher['valid_until']) {
                            $expiry = new DateTime($voucher['valid_until']);
                            $now = new DateTime();
                            $diff = $now->diff($expiry);

                            if ($expiry < $now) {
                                echo '<span style="color: red;">Expired</span>';
                            } else {
                                echo $diff->days . ' days left';
                            }
                        } else {
                            echo 'No expiry';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($voucher['is_active']): ?>
                            <span class="status-badge status-completed">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-pending">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎫</div>
            <p>No referral vouchers yet</p>
            <small>Vouchers will appear here when you refer new users</small>
        </div>
        <?php endif; ?>
    </div>

    <!-- Referred Users Section -->
    <div class="section">
        <h2 class="section-title">👥 Referred Users</h2>

        <?php if (count($referred_users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined Date</th>
                    <th>Orders</th>
                    <th>Commission</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referred_users as $referred): ?>
                <tr>
                    <td><?php echo htmlspecialchars($referred['name']); ?></td>
                    <td><?php echo htmlspecialchars($referred['email']); ?></td>
                    <td><?php echo date('d M Y', strtotime($referred['created_at'])); ?></td>
                    <td><?php echo $referred['order_count']; ?> order(s)</td>
                    <td>Rp <?php echo number_format($referred['reward_value'] ?? 0, 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($referred['status'] === 'completed'): ?>
                            <span class="status-badge status-completed">✓ Paid</span>
                        <?php elseif ($referred['status'] === 'pending'): ?>
                            <span class="status-badge status-pending">⏳ Pending</span>
                        <?php else: ?>
                            <span class="status-badge">Not Started</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <p>No referrals yet</p>
            <small>Start sharing your referral code to earn rewards!</small>
        </div>
        <?php endif; ?>
    </div>

    <!-- How It Works -->
    <div class="section">
        <h2 class="section-title">ℹ️ How Referral Program Works</h2>
        <div style="line-height: 1.8; color: var(--grey);">
            <p style="margin-bottom: 16px;"><strong>Step 1:</strong> Share your unique referral code with friends and family.</p>
            <p style="margin-bottom: 16px;"><strong>Step 2:</strong> They register using your code and get special welcome benefits.</p>
            <p style="margin-bottom: 16px;"><strong>Step 3:</strong> When they complete their first order, you earn Rp 50,000 commission!</p>
            <p style="margin-bottom: 16px;"><strong>Step 4:</strong> Commission is added to your wallet and can be used for shopping.</p>

            <div style="background: var(--cream); padding: 20px; border-radius: 8px; margin-top: 24px;">
                <strong style="color: var(--charcoal);">💰 Earning Potential:</strong>
                <ul style="margin-top: 12px; padding-left: 24px;">
                    <li>5 referrals = Rp 250,000</li>
                    <li>10 referrals = Rp 500,000</li>
                    <li>20 referrals = Rp 1,000,000</li>
                    <li>50 referrals = Rp 2,500,000</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralCode() {
    const code = '<?php echo $user['referral_code']; ?>';

    // Modern clipboard API
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            alert('✓ Referral code copied: ' + code);
        });
    } else {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = code;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✓ Referral code copied: ' + code);
    }
}

function shareWhatsApp() {
    const code = '<?php echo $user['referral_code']; ?>';
    const registerUrl = '<?php echo SITE_URL; ?>auth/register.php?ref=' + code;
    const text = `🎁 Belanja di Dorve House sekarang! Pakai kode referral: ${code}\n\nDapatkan bonus spesial dan gratis ongkir!\n\nDaftar di: ${registerUrl}`;
    const url = 'https://wa.me/?text=' + encodeURIComponent(text);
    window.open(url, '_blank');
}

function shareFacebook() {
    const url = '<?php echo SITE_URL; ?>auth/register.php?ref=<?php echo $user['referral_code']; ?>';
    const shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

function shareTwitter() {
    const code = '<?php echo $user['referral_code']; ?>';
    const text = `Join Dorve House with my referral code ${code} and get special bonuses! 🎁`;
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent('<?php echo SITE_URL; ?>');
    window.open(url, '_blank', 'width=600,height=400');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
