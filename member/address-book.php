<?php
/**
 * MEMBER - Address Book dengan Google Maps Integration
 * User bisa add address dengan pilih lokasi di map
 */
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$userId = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, 
                                           latitude, longitude, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $_POST['label'],
                $_POST['recipient_name'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['latitude'] ?? null,
                $_POST['longitude'] ?? null,
                isset($_POST['is_default']) ? 1 : 0
            ]);
            
            // If set as default, unset others
            if (isset($_POST['is_default'])) {
                $pdo->exec("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId AND id != LAST_INSERT_ID()");
            }
            
            $success = 'Address added successfully!';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['address_id'], $userId]);
            $success = 'Address deleted!';
        } elseif ($action === 'set_default') {
            $pdo->exec("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId");
            $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['address_id'], $userId]);
            $success = 'Default address updated!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get addresses
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Book - Dorve.id</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            color: #6B7280; text-decoration: none; margin-bottom: 20px;
            font-weight: 500;
        }
        .header { margin-bottom: 32px; }
        .header h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        
        .btn-add {
            padding: 14px 28px; 
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white; border: none; border-radius: 10px;
            font-weight: 600; cursor: pointer; margin-bottom: 24px;
        }
        
        .address-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .address-card {
            background: white; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: 2px solid #E5E7EB; position: relative;
        }
        .address-card.default {
            border-color: #10B981; background: linear-gradient(to bottom, #ECFDF5 0%, white 30%);
        }
        .default-badge {
            position: absolute; top: 16px; right: 16px;
            background: #10B981; color: white;
            padding: 6px 12px; border-radius: 6px;
            font-size: 12px; font-weight: 700;
        }
        .address-label {
            font-size: 20px; font-weight: 700; margin-bottom: 12px;
        }
        .address-details {
            font-size: 14px; color: #374151; line-height: 1.8;
        }
        .address-details > div {
            margin-bottom: 8px;
        }
        .address-actions {
            display: flex; gap: 8px; margin-top: 16px;
            padding-top: 16px; border-top: 1px solid #E5E7EB;
        }
        .btn {
            padding: 8px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 600;
            cursor: pointer; border: none;
        }
        .btn-default { background: #10B981; color: white; }
        .btn-delete { background: #EF4444; color: white; }
        
        /* Modal */
        .modal {
            display: none; position: fixed; z-index: 9999;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
            overflow-y: auto;
        }
        .modal-content {
            background: white; border-radius: 20px;
            max-width: 900px; width: 90%; margin: 40px auto;
            padding: 32px;
        }
        .modal h2 {
            font-size: 28px; font-weight: 700; margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block; margin-bottom: 8px;
            font-weight: 600; color: #374151;
        }
        .form-group input, .form-group textarea {
            width: 100%; padding: 12px 16px;
            border: 2px solid #E5E7EB; border-radius: 10px;
            font-size: 15px;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #667EEA;
        }
        textarea { min-height: 100px; resize: vertical; }
        
        #map {
            width: 100%; height: 400px;
            border-radius: 12px; border: 2px solid #E5E7EB;
            margin: 16px 0;
        }
        .map-hint {
            font-size: 13px; color: #6B7280;
            padding: 12px; background: #F9FAFB;
            border-radius: 8px; margin-bottom: 16px;
        }
        .btn-submit {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: 700; cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/member/dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <h1>📍 Address Book</h1>
            <p style="color: #6B7280;">Manage your shipping addresses</p>
        </div>

        <?php if (isset($success)): ?>
            <div style="padding: 16px; background: #D1FAE5; color: #065F46; border-radius: 8px; margin-bottom: 24px;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <button onclick="openModal()" class="btn-add">
            ➕ Add New Address
        </button>

        <div class="address-grid">
            <?php if (empty($addresses)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px;">
                    <div style="font-size: 64px; margin-bottom: 16px;">📍</div>
                    <h3>No Addresses Yet</h3>
                    <p style="color: #6B7280; margin-top: 8px;">Add your first address to get started</p>
                </div>
            <?php else: ?>
                <?php foreach ($addresses as $addr): ?>
                <div class="address-card <?= $addr['is_default'] ? 'default' : '' ?>">
                    <?php if ($addr['is_default']): ?>
                        <div class="default-badge">✓ Default</div>
                    <?php endif; ?>
                    
                    <div class="address-label">
                        <?= htmlspecialchars($addr['label']) ?>
                    </div>
                    
                    <div class="address-details">
                        <div><strong><?= htmlspecialchars($addr['recipient_name']) ?></strong></div>
                        <div>📱 <?= htmlspecialchars($addr['phone']) ?></div>
                        <div>📍 <?= nl2br(htmlspecialchars($addr['address'])) ?></div>
                        <?php if ($addr['latitude'] && $addr['longitude']): ?>
                            <div style="font-size: 12px; color: #6B7280;">
                                📌 Lat: <?= $addr['latitude'] ?>, Long: <?= $addr['longitude'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="address-actions">
                        <?php if (!$addr['is_default']): ?>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                            <button type="submit" class="btn btn-default" style="width: 100%;">
                                Set as Default
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" onsubmit="return confirm('Delete this address?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                            <button type="submit" class="btn btn-delete">
                                🗑️ Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal" id="addressModal">
        <div class="modal-content">
            <h2>📍 Add New Address</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                
                <div class="form-group">
                    <label>Address Label *</label>
                    <input type="text" name="label" placeholder="e.g., Home, Office, Mom's House" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Recipient Name *</label>
                        <input type="text" name="recipient_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>
                
                <div class="map-hint">
                    📍 <strong>Click on the map</strong> to select your exact location. Drag the marker to adjust.
                </div>
                
                <div id="map"></div>
                
                <div class="form-group">
                    <label>Full Address *</label>
                    <textarea name="address" id="addressField" placeholder="Complete address with street, area, postal code..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_default" style="width: auto;">
                        Set as default address
                    </label>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn-submit" style="flex: 1;">
                        ✓ Save Address
                    </button>
                    <button type="button" onclick="closeModal()" class="btn-submit" style="background: #6B7280;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>
    <script>
    let map, marker, geocoder;

    function openModal() {
        document.getElementById('addressModal').style.display = 'block';
        setTimeout(initMap, 100); // Initialize map after modal opens
    }

    function closeModal() {
        document.getElementById('addressModal').style.display = 'none';
    }

    function initMap() {
        // Default to Jakarta
        const defaultPos = { lat: -6.2088, lng: 106.8456 };
        
        map = new google.maps.Map(document.getElementById('map'), {
            center: defaultPos,
            zoom: 15
        });
        
        geocoder = new google.maps.Geocoder();
        
        marker = new google.maps.Marker({
            position: defaultPos,
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP
        });
        
        // Click map to set location
        map.addListener('click', (e) => {
            placeMarker(e.latLng);
        });
        
        // Drag marker
        marker.addListener('dragend', () => {
            updateAddress(marker.getPosition());
        });
        
        // Try to get user's current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(pos);
                marker.setPosition(pos);
                updateAddress(pos);
            });
        }
    }

    function placeMarker(location) {
        marker.setPosition(location);
        map.panTo(location);
        updateAddress(location);
    }

    function updateAddress(location) {
        document.getElementById('latitude').value = location.lat();
        document.getElementById('longitude').value = location.lng();
        
        // Reverse geocode to get address
        geocoder.geocode({ location: location }, (results, status) => {
            if (status === 'OK' && results[0]) {
                document.getElementById('addressField').value = results[0].formatted_address;
            }
        });
    }

    // Close modal on outside click
    document.getElementById('addressModal').addEventListener('click', (e) => {
        if (e.target.id === 'addressModal') closeModal();
    });
    </script>
</body>
</html>
