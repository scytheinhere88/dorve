<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    redirect('/admin/login.php');
}

// Get all referral activities
$stmt = $pdo->query("
    SELECT
        rr.*,
        u1.name as referrer_name,
        u1.email as referrer_email,
        u1.tier as referrer_tier,
        u2.name as referred_name,
        u2.email as referred_email,
        u2.tier as referred_tier,
        (SELECT SUM(amount) FROM topups WHERE user_id = rr.referred_id AND status = 'completed') as total_topup
    FROM referral_rewards rr
    JOIN users u1 ON rr.referrer_id = u1.id
    JOIN users u2 ON rr.referred_id = u2.id
    ORDER BY rr.created_at DESC
");
$referrals = $stmt->fetchAll();

// Get commission settings
$stmt = $pdo->query("SELECT * FROM commission_tiers ORDER BY min_topup ASC");
$commission_tiers = $stmt->fetchAll();

// Get referral statistics
$stats = $pdo->query("
    SELECT
        COUNT(*) as total_referrals,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_referrals,
        SUM(CASE WHEN status = 'completed' THEN reward_value ELSE 0 END) as total_commission_paid
    FROM referral_rewards
")->fetch();

include __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .admin-container {
        padding: 40px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-size: 32px;
        margin-bottom: 8px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 24px;
        border-radius: 8px;
        border: 1px solid #E5E5E5;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .stat-label {
        color: #666;
        font-size: 14px;
    }

    .section-card {
        background: white;
        border-radius: 8px;
        border: 1px solid #E5E5E5;
        margin-bottom: 30px;
        overflow: hidden;
    }

    .section-header {
        padding: 20px 24px;
        border-bottom: 1px solid #E5E5E5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-header h2 {
        font-size: 20px;
        font-weight: 600;
    }

    .section-body {
        padding: 24px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        padding: 12px;
        background: #F9FAFB;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #374151;
    }

    td {
        padding: 16px 12px;
        border-bottom: 1px solid #F3F4F6;
    }

    tr:hover {
        background: #F9FAFB;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-completed {
        background: #D1FAE5;
        color: #065F46;
    }

    .badge-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-bronze {
        background: #CD7F32;
        color: white;
    }

    .badge-silver {
        background: #C0C0C0;
        color: #333;
    }

    .badge-gold {
        background: #FFD700;
        color: #333;
    }

    .badge-platinum {
        background: #E5E4E2;
        color: #333;
    }

    .badge-vvip {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #1F2937;
        color: white;
    }

    .btn-primary:hover {
        background: #111827;
    }

    .btn-secondary {
        background: #E5E7EB;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #D1D5DB;
    }

    .commission-table {
        margin-top: 20px;
    }

    .commission-table input {
        width: 100%;
        padding: 8px;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
    }

    .tier-info {
        background: #F3F4F6;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .tier-info h4 {
        margin-bottom: 12px;
        font-size: 16px;
    }

    .tier-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .tier-item {
        background: white;
        padding: 12px;
        border-radius: 6px;
        border: 2px solid #E5E7EB;
    }

    .tier-item strong {
        display: block;
        margin-bottom: 4px;
    }
</style>

<div class="admin-container">
    <div class="page-header">
        <h1>🎁 Referral Management</h1>
        <p>Manage referral program, commissions, and customer tiers</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['total_referrals']); ?></div>
            <div class="stat-label">Total Referrals</div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['completed_referrals']); ?></div>
            <div class="stat-label">Completed Referrals</div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['pending_referrals']); ?></div>
            <div class="stat-label">Pending Referrals</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">Rp <?php echo number_format($stats['total_commission_paid'], 0, ',', '.'); ?></div>
            <div class="stat-label">Total Commission Paid</div>
        </div>
    </div>

    <!-- Commission Settings -->
    <div class="section-card">
        <div class="section-header">
            <h2>⚙️ Commission Tiers Settings</h2>
            <button class="btn btn-primary" onclick="showAddTier()">+ Add Tier</button>
        </div>
        <div class="section-body">
            <div class="tier-info">
                <h4>📊 How Commission Tiers Work:</h4>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Commission is calculated based on referred user's FIRST topup amount</li>
                    <li>Referrer gets commission % + free shipping vouchers</li>
                    <li>Only paid when referred user completes their first topup</li>
                    <li>Just registering = NO reward (must topup!)</li>
                </ul>
            </div>

            <table class="commission-table">
                <thead>
                    <tr>
                        <th>Tier Name</th>
                        <th>Min Topup</th>
                        <th>Max Topup</th>
                        <th>Commission %</th>
                        <th>Free Ship Vouchers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($commission_tiers) > 0): ?>
                        <?php foreach ($commission_tiers as $tier): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tier['name']); ?></strong></td>
                            <td>Rp <?php echo number_format($tier['min_topup'], 0, ',', '.'); ?></td>
                            <td><?php echo $tier['max_topup'] ? 'Rp ' . number_format($tier['max_topup'], 0, ',', '.') : 'No limit'; ?></td>
                            <td><?php echo $tier['commission_percent']; ?>%</td>
                            <td><?php echo $tier['free_shipping_vouchers']; ?> voucher(s)</td>
                            <td>
                                <button class="btn btn-secondary" onclick="editTier(<?php echo $tier['id']; ?>)">Edit</button>
                                <button class="btn btn-secondary" onclick="deleteTier(<?php echo $tier['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                No commission tiers set. Click "Add Tier" to create default tiers.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; padding: 16px; background: #FEF3C7; border-radius: 6px;">
                <strong>💡 Recommended Default Tiers:</strong>
                <div class="tier-list" style="margin-top: 12px;">
                    <div class="tier-item">
                        <strong>Tier 1: Under 500K</strong>
                        <div>Commission: 3%</div>
                        <div>Vouchers: 1 free ship</div>
                    </div>
                    <div class="tier-item">
                        <strong>Tier 2: 500K - 1M</strong>
                        <div>Commission: 4%</div>
                        <div>Vouchers: 2 free ship</div>
                    </div>
                    <div class="tier-item">
                        <strong>Tier 3: 1M - 5M</strong>
                        <div>Commission: 5%</div>
                        <div>Vouchers: 2 free ship</div>
                    </div>
                    <div class="tier-item">
                        <strong>Tier 4: 5M+</strong>
                        <div>Commission: 6%</div>
                        <div>Vouchers: 3 free ship</div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="createDefaultTiers()" style="margin-top: 12px;">Create Default Tiers</button>
            </div>
        </div>
    </div>

    <!-- Customer Tiers -->
    <div class="section-card">
        <div class="section-header">
            <h2>🏆 Customer Tiers</h2>
        </div>
        <div class="section-body">
            <div class="tier-info">
                <h4>🎯 Customer Tier System:</h4>
                <p style="margin-bottom: 12px;">Customers are automatically upgraded based on their TOTAL topup amount.</p>
                <div class="tier-list">
                    <div class="tier-item" style="border-color: #CD7F32;">
                        <span class="badge badge-bronze">BRONZE</span>
                        <div style="margin-top: 8px;">Under Rp 1M</div>
                    </div>
                    <div class="tier-item" style="border-color: #C0C0C0;">
                        <span class="badge badge-silver">SILVER</span>
                        <div style="margin-top: 8px;">Rp 1M - 3M</div>
                    </div>
                    <div class="tier-item" style="border-color: #FFD700;">
                        <span class="badge badge-gold">GOLD</span>
                        <div style="margin-top: 8px;">Rp 3M - 10M</div>
                    </div>
                    <div class="tier-item" style="border-color: #E5E4E2;">
                        <span class="badge badge-platinum">PLATINUM</span>
                        <div style="margin-top: 8px;">Rp 10M - 20M</div>
                    </div>
                    <div class="tier-item" style="border-color: #667eea;">
                        <span class="badge badge-vvip">VVIP</span>
                        <div style="margin-top: 8px;">Rp 20M+</div>
                    </div>
                </div>
            </div>

            <p style="margin-top: 16px; color: #666;">
                <strong>Note:</strong> Customer tiers are automatically calculated and updated when they complete topups.
                You can assign tier-specific vouchers from the Voucher Management page.
            </p>
        </div>
    </div>

    <!-- Referral List -->
    <div class="section-card">
        <div class="section-header">
            <h2>📋 All Referrals</h2>
        </div>
        <div class="section-body" style="padding: 0; overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Referrer (Source)</th>
                        <th>Referrer Tier</th>
                        <th>Referred User</th>
                        <th>Referred Tier</th>
                        <th>Total Topup</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $ref): ?>
                    <tr>
                        <td>#<?php echo $ref['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($ref['referrer_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($ref['referrer_email']); ?></small>
                        </td>
                        <td>
                            <?php
                            $tier = strtoupper($ref['referrer_tier'] ?? 'bronze');
                            echo "<span class='badge badge-" . strtolower($tier) . "'>" . $tier . "</span>";
                            ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($ref['referred_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($ref['referred_email']); ?></small>
                        </td>
                        <td>
                            <?php
                            $tier = strtoupper($ref['referred_tier'] ?? 'bronze');
                            echo "<span class='badge badge-" . strtolower($tier) . "'>" . $tier . "</span>";
                            ?>
                        </td>
                        <td>Rp <?php echo number_format($ref['total_topup'] ?? 0, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($ref['reward_value'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($ref['status'] === 'completed'): ?>
                                <span class="badge badge-completed">✓ Paid</span>
                            <?php else: ?>
                                <span class="badge badge-pending">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($ref['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showAddTier() {
    window.location.href = '/admin/referrals/add-tier.php';
}

function editTier(id) {
    window.location.href = '/admin/referrals/edit-tier.php?id=' + id;
}

function deleteTier(id) {
    if (confirm('Are you sure you want to delete this commission tier?')) {
        window.location.href = '/admin/referrals/delete-tier.php?id=' + id;
    }
}

function createDefaultTiers() {
    if (confirm('This will create 4 default commission tiers. Continue?')) {
        window.location.href = '/admin/referrals/create-defaults.php';
    }
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
