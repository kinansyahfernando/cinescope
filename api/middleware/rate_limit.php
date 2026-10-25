<?php
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    $cacheFile = __DIR__ . '/../../cache/rate_limit_' . md5($identifier) . '.txt';
    
    // Buat folder cache jika belum ada
    $cacheDir = __DIR__ . '/../../cache';
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $now = time();
    $requests = [];
    
    // Load existing requests
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?: [];
    }
    
    // Filter requests within time window
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($requests) >= $maxRequests) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $timeWindow
        ]);
        exit();
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
    
    return true;
}

// Example usage in endpoints:
// $ip = $_SERVER['REMOTE_ADDR'];
// checkRateLimit($ip, 100, 3600); // 100 requests per hour
?>