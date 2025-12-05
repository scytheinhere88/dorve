<?php

function updateUserTier($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT current_tier, total_topup FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['error' => 'User not found'];
        }

        $total_topup = floatval($user['total_topup'] ?? 0);
        $current_tier = $user['current_tier'] ?? 'bronze';

        $new_tier = 'bronze';
        if ($total_topup >= 10000000) {
            $new_tier = 'platinum';
        } elseif ($total_topup >= 5000000) {
            $new_tier = 'gold';
        } elseif ($total_topup >= 1000000) {
            $new_tier = 'silver';
        }

        if ($new_tier !== $current_tier) {
            $stmt = $pdo->prepare("UPDATE users SET current_tier = ? WHERE id = ?");
            $stmt->execute([$new_tier, $user_id]);

            return [
                'changed' => true,
                'old_tier' => $current_tier,
                'new_tier' => $new_tier,
                'total_topup' => $total_topup
            ];
        }

        return [
            'changed' => false,
            'tier' => $current_tier,
            'total_topup' => $total_topup
        ];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function getTierInfo($tier) {
    $tiers = [
        'bronze' => [
            'name' => 'Bronze',
            'icon' => '🥉',
            'min' => 0,
            'max' => 999999,
            'discount' => 0,
            'commission' => 3,
            'color' => '#CD7F32'
        ],
        'silver' => [
            'name' => 'Silver',
            'icon' => '🥈',
            'min' => 1000000,
            'max' => 4999999,
            'discount' => 5,
            'commission' => 5,
            'color' => '#C0C0C0'
        ],
        'gold' => [
            'name' => 'Gold',
            'icon' => '🥇',
            'min' => 5000000,
            'max' => 9999999,
            'discount' => 10,
            'commission' => 8,
            'color' => '#FFD700'
        ],
        'platinum' => [
            'name' => 'Platinum',
            'icon' => '💎',
            'min' => 10000000,
            'max' => PHP_INT_MAX,
            'discount' => 15,
            'commission' => 10,
            'color' => '#E5E4E2'
        ]
    ];

    return $tiers[$tier] ?? $tiers['bronze'];
}

function getNextTier($current_tier) {
    $progression = ['bronze' => 'silver', 'silver' => 'gold', 'gold' => 'platinum', 'platinum' => null];
    return $progression[$current_tier] ?? null;
}

function getProgressToNextTier($total_topup) {
    $current_tier = 'bronze';
    if ($total_topup >= 10000000) {
        return ['tier' => 'platinum', 'progress' => 100, 'needed' => 0, 'next_tier' => null];
    } elseif ($total_topup >= 5000000) {
        $current_tier = 'gold';
        $needed = 10000000 - $total_topup;
        $progress = ($total_topup - 5000000) / 5000000 * 100;
        return ['tier' => 'gold', 'progress' => $progress, 'needed' => $needed, 'next_tier' => 'platinum'];
    } elseif ($total_topup >= 1000000) {
        $current_tier = 'silver';
        $needed = 5000000 - $total_topup;
        $progress = ($total_topup - 1000000) / 4000000 * 100;
        return ['tier' => 'silver', 'progress' => $progress, 'needed' => $needed, 'next_tier' => 'gold'];
    } else {
        $needed = 1000000 - $total_topup;
        $progress = $total_topup / 1000000 * 100;
        return ['tier' => 'bronze', 'progress' => $progress, 'needed' => $needed, 'next_tier' => 'silver'];
    }
}

function getTierDiscount($tier) {
    $info = getTierInfo($tier);
    return $info['discount'];
}

function getTierCommission($tier) {
    $info = getTierInfo($tier);
    return $info['commission'];
}
