<?php
/**
 * Biteship Configuration Helper
 * Loads settings from database
 */

class BiteshipConfig {
    private static $config = null;
    
    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        global $pdo;
        
        // Check which column name is used
        try {
            $checkStmt = $pdo->query("DESCRIBE settings");
            $columns = array_column($checkStmt->fetchAll(), 'Field');
            $valueColumn = in_array('setting_value', $columns) ? 'setting_value' : 'value';
        } catch (Exception $e) {
            $valueColumn = 'setting_value'; // default
        }
        
        $stmt = $pdo->query("SELECT setting_key, $valueColumn as setting_value FROM settings WHERE setting_key LIKE 'biteship_%' OR setting_key LIKE 'store_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        self::$config = [
            'api_key' => $settings['biteship_api_key'] ?? '',
            'environment' => $settings['biteship_environment'] ?? 'sandbox',
            'webhook_secret' => $settings['biteship_webhook_secret'] ?? '',
            'base_url' => ($settings['biteship_environment'] ?? 'sandbox') === 'production' 
                ? 'https://api.biteship.com/v1'
                : 'https://api-sandbox.biteship.com/v1',
            'store' => [
                'name' => $settings['store_name'] ?? 'Dorve.id Official Store',
                'phone' => $settings['store_phone'] ?? '+62-813-7737-8859',
                'address' => $settings['store_address'] ?? '',
                'city' => $settings['store_city'] ?? '',
                'province' => $settings['store_province'] ?? '',
                'postal_code' => $settings['store_postal_code'] ?? '',
                'country' => $settings['store_country'] ?? 'ID'
            ]
        ];
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        $config = self::load();
        return $config[$key] ?? $default;
    }
    
    public static function getStore($key, $default = null) {
        $config = self::load();
        return $config['store'][$key] ?? $default;
    }
}
