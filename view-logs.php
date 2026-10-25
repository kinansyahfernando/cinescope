<?php
// File: view-logs.php
// Simpan di: C:/xampp/htdocs/movie-review/view-logs.php
// Akses: http://localhost/movie-review/view-logs.php

header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Debug Logs</title>
    <style>
        body {
            font-family: monospace;
            background: #0a0a0a;
            color: #0f0;
            padding: 20px;
            margin: 0;
        }
        h1 { color: #e50914; }
        .log-container {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .log-line {
            padding: 5px;
            margin: 2px 0;
            border-left: 3px solid #333;
            padding-left: 10px;
        }
        .log-success { border-left-color: #28a745; color: #0f0; }
        .log-error { border-left-color: #dc3545; color: #f00; }
        .log-warning { border-left-color: #ffc107; color: #ff0; }
        .log-info { border-left-color: #667eea; color: #9f9; }
        .log-start { 
            border-left-color: #e50914; 
            color: #e50914; 
            font-weight: bold; 
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
        button {
            background: #e50914;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover { background: #f00; }
        .stats {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-box {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .stat-label { color: #999; font-size: 0.9em; }
        .stat-value { color: #fff; font-size: 1.5em; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📋 Debug Logs Viewer</h1>
    
    <div>
        <button onclick="location.reload()">🔄 Refresh</button>
        <button onclick="clearLogs()">🗑️ Clear Logs</button>
        <button onclick="window.open('debug-submit.html')">🧪 Open Debug Tool</button>
        <button onclick="window.open('test_connection.php')">🔍 Test Connection</button>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value" id="totalRequests">-</div>
        </div>
        <div class="stat-box" style="border-left-color: #28a745;">
            <div class="stat-label">Successful</div>
            <div class="stat-value" id="successCount" style="color: #0f0;">-</div>
        </div>
        <div class="stat-box" style="border-left-color: #dc3545;">
            <div class="stat-label">Failed</div>
            <div class="stat-value" id="failCount" style="color: #f00;">-</div>
        </div>
        <div class="stat-box" style="border-left-color: #ffc107;">
            <div class="stat-label">Last Update</div>
            <div class="stat-value" id="lastUpdate" style="font-size: 1em;">-</div>
        </div>
    </div>

    <div class="log-container">
        <h2>📄 Submit Review Logs</h2>
        <div id="logs">
            <?php
            $logFile = __DIR__ . '/api/submit_debug.log';
            
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                if (empty(trim($logs))) {
                    echo '<p style="color:#999;">Log file is empty. No requests yet.</p>';
                } else {
                    $lines = explode("\n", $logs);
                    $lines = array_reverse(array_filter($lines)); // Terbaru di atas
                    
                    $totalRequests = 0;
                    $successCount = 0;
                    $failCount = 0;
                    
                    foreach ($lines as $line) {
                        if (empty(trim($line))) continue;
                        
                        $class = 'log-info';
                        
                        if (strpos($line, '=== NEW REQUEST START ===') !== false) {
                            $class = 'log-start';
                            $totalRequests++;
                        } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, 'successfully') !== false) {
                            $class = 'log-success';
                            if (strpos($line, 'Review inserted successfully') !== false) {
                                $successCount++;
                            }
                        } elseif (strpos($line, 'FAILED') !== false || strpos($line, 'error') !== false || strpos($line, 'Exception') !== false) {
                            $class = 'log-error';
                            if (strpos($line, 'Insert FAILED') !== false || strpos($line, 'Exception') !== false) {
                                $failCount++;
                            }
                        } elseif (strpos($line, 'NOT FOUND') !== false) {
                            $class = 'log-warning';
                        }
                        
                        echo "<div class='log-line $class'>" . htmlspecialchars($line) . "</div>";
                    }
                    
                    // Update stats via JavaScript
                    echo "<script>
                        document.getElementById('totalRequests').textContent = '$totalRequests';
                        document.getElementById('successCount').textContent = '$successCount';
                        document.getElementById('failCount').textContent = '$failCount';
                        document.getElementById('lastUpdate').textContent = '" . date('H:i:s') . "';
                    </script>";
                }
            } else {
                echo '<p style="color:#f00;">❌ Log file not found: ' . $logFile . '</p>';
                echo '<p style="color:#999;">The log file will be created automatically when the first request is made.</p>';
                echo '<p style="color:#999;">Make sure the api/ folder has write permissions.</p>';
            }
            ?>
        </div>
    </div>

    <div class="log-container">
        <h2>📁 File Status</h2>
        <?php
        $files = [
            'api/submit_review.php' => 'Submit Review API',
            'api/get_reviews.php' => 'Get Reviews API',
            'api/submit_debug.log' => 'Debug Log File',
            'debug-submit.html' => 'Debug Tool',
            'test_connection.php' => 'Connection Test'
        ];
        
        echo '<table style="width:100%; color:#fff;">';
        echo '<tr><th style="text-align:left; padding:10px; background:#2d2d2d;">File</th><th style="text-align:left; padding:10px; background:#2d2d2d;">Status</th><th style="text-align:left; padding:10px; background:#2d2d2d;">Size</th></tr>';
        
        foreach ($files as $file => $desc) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $size = $exists ? filesize(__DIR__ . '/' . $file) : 0;
            $status = $exists ? '<span style="color:#0f0;">✅ Exists</span>' : '<span style="color:#f00;">❌ Not Found</span>';
            $sizeText = $exists ? number_format($size) . ' bytes' : '-';
            
            echo "<tr>";
            echo "<td style='padding:10px;'>$desc<br><small style='color:#999;'>$file</small></td>";
            echo "<td style='padding:10px;'>$status</td>";
            echo "<td style='padding:10px;'>$sizeText</td>";
            echo "</tr>";
        }
        
        echo '</table>';
        ?>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Clear all logs? This cannot be undone.')) {
                fetch('view-logs.php?action=clear')
                    .then(() => {
                        alert('Logs cleared!');
                        location.reload();
                    });
            }
        }

        // Auto-refresh every 5 seconds
        setInterval(() => {
            location.reload();
        }, 5000);
    </script>

    <?php
    // Handle clear action
    if (isset($_GET['action']) && $_GET['action'] === 'clear') {
        $logFile = __DIR__ . '/api/submit_debug.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        exit('OK');
    }
    ?>
</body>
</html>