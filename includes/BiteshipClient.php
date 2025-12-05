<?php
/**
 * Biteship API Client
 * Handles all communication with Biteship API
 */

require_once __DIR__ . '/BiteshipConfig.php';

class BiteshipClient {
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        $config = BiteshipConfig::load();
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
        
        if (empty($this->apiKey)) {
            throw new Exception('Biteship API key not configured');
        }
    }
    
    /**
     * Make HTTP request to Biteship API
     */
    private function request($method, $path, $payload = null) {
        $url = $this->baseUrl . $path;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        } elseif ($method === 'GET' && $payload) {
            $url .= '?' . http_build_query($payload);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $data,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'error' => $data['error'] ?? $data['message'] ?? 'API Error',
                'data' => $data,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Get shipping rates
     * https://biteship.com/en/docs/api/rates
     */
    public function getRates($origin, $destination, $items, $courierCodes = null) {
        $payload = [
            'origin_area_id' => $origin['area_id'] ?? null,
            'origin_latitude' => $origin['latitude'] ?? null,
            'origin_longitude' => $origin['longitude'] ?? null,
            'origin_postal_code' => $origin['postal_code'] ?? null,
            
            'destination_area_id' => $destination['area_id'] ?? null,
            'destination_latitude' => $destination['latitude'] ?? null,
            'destination_longitude' => $destination['longitude'] ?? null,
            'destination_postal_code' => $destination['postal_code'] ?? null,
            
            'couriers' => $courierCodes ?? 'jne,jnt,sicepat,anteraja,idexpress',
            'items' => $items
        ];
        
        // Remove null values
        $payload = array_filter($payload, function($v) { return $v !== null; });
        
        return $this->request('POST', '/rates/couriers', $payload);
    }
    
    /**
     * Create order
     * https://biteship.com/en/docs/api/order
     */
    public function createOrder($orderData) {
        return $this->request('POST', '/orders', $orderData);
    }
    
    /**
     * Get order details
     */
    public function getOrder($biteshipOrderId) {
        return $this->request('GET', '/orders/' . $biteshipOrderId);
    }
    
    /**
     * Track order
     * https://biteship.com/en/docs/api/tracking
     */
    public function trackOrder($waybillId, $courierCode) {
        return $this->request('GET', '/trackings/' . $waybillId . '/couriers/' . $courierCode);
    }
    
    /**
     * Get area by name (for postal code lookup)
     */
    public function getAreas($searchQuery) {
        return $this->request('GET', '/maps/areas', ['countries' => 'ID', 'input' => $searchQuery, 'type' => 'single']);
    }
}
