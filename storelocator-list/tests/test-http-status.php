<?php
/**
 * Test HTTP Status and Headers
 * 
 * This script tests the HTTP status and headers for our custom pages
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-http-status.php
 */

// WordPress environment
$wp_load_paths = [
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded || !defined('ABSPATH')) {
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p>');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('<h1>Access Denied</h1><p>You must be an administrator to run this script. Please <a href="' . wp_login_url() . '">login</a> first.</p>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test HTTP Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #46b450; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3232; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h1>Test HTTP Status and Headers</h1>

<div class="test-section">
    <h2>🌐 Live URL Testing</h2>
    <p>Click the buttons below to test the HTTP status of our custom pages:</p>
    
    <table>
        <tr>
            <th>URL</th>
            <th>Expected Status</th>
            <th>Test</th>
            <th>Result</th>
        </tr>
        <tr>
            <td><code>/store-manager/</code></td>
            <td>200 OK</td>
            <td><button onclick="testUrl('<?php echo home_url('/store-manager/'); ?>', 'store-manager')" class="button">Test</button></td>
            <td id="result-store-manager">Not tested</td>
        </tr>
        <tr>
            <td><code>/update-store/</code></td>
            <td>200 OK</td>
            <td><button onclick="testUrl('<?php echo home_url('/update-store/'); ?>', 'update-store')" class="button">Test</button></td>
            <td id="result-update-store">Not tested</td>
        </tr>
        <tr>
            <td><code>/?sllist_page=store_manager</code></td>
            <td>200 OK</td>
            <td><button onclick="testUrl('<?php echo home_url('/?sllist_page=store_manager'); ?>', 'fallback-manager')" class="button">Test</button></td>
            <td id="result-fallback-manager">Not tested</td>
        </tr>
        <tr>
            <td><code>/?sllist_page=store_update</code></td>
            <td>200 OK</td>
            <td><button onclick="testUrl('<?php echo home_url('/?sllist_page=store_update'); ?>', 'fallback-update')" class="button">Test</button></td>
            <td id="result-fallback-update">Not tested</td>
        </tr>
    </table>
    
    <div id="detailed-results" style="margin-top: 20px;">
        <h3>Detailed Results</h3>
        <div id="detailed-output"></div>
    </div>
</div>

<div class="test-section">
    <h2>📋 Manual Test Instructions</h2>
    <p>Manually test these URLs and check:</p>
    <ul>
        <li><strong>Page Title:</strong> Should show "Store Manager" or "Update Store Details", not "Page Not Found"</li>
        <li><strong>Browser Dev Tools:</strong> Network tab should show 200 status, not 404</li>
        <li><strong>Content:</strong> Should display the actual plugin content</li>
    </ul>
    
    <h3>Test URLs:</h3>
    <ul>
        <li><a href="<?php echo home_url('/store-manager/'); ?>" target="_blank"><?php echo home_url('/store-manager/'); ?></a></li>
        <li><a href="<?php echo home_url('/update-store/'); ?>" target="_blank"><?php echo home_url('/update-store/'); ?></a></li>
        <li><a href="<?php echo home_url('/?sllist_page=store_manager'); ?>" target="_blank"><?php echo home_url('/?sllist_page=store_manager'); ?></a></li>
        <li><a href="<?php echo home_url('/?sllist_page=store_update'); ?>" target="_blank"><?php echo home_url('/?sllist_page=store_update'); ?></a></li>
    </ul>
</div>

<script>
function testUrl(url, resultId) {
    const resultElement = document.getElementById('result-' + resultId);
    const detailedOutput = document.getElementById('detailed-output');
    
    resultElement.innerHTML = '⏳ Testing...';
    
    // Use fetch to test the URL and get detailed information
    fetch(url, {
        method: 'GET',
        cache: 'no-cache'
    })
    .then(response => {
        const status = response.status;
        const statusText = response.statusText;
        
        let resultText = '';
        let resultClass = '';
        
        if (status === 200) {
            resultText = '✅ 200 OK';
            resultClass = 'success';
        } else if (status === 404) {
            resultText = '❌ 404 Not Found';
            resultClass = 'error';
        } else {
            resultText = `⚠️ ${status} ${statusText}`;
            resultClass = 'warning';
        }
        
        resultElement.innerHTML = resultText;
        resultElement.className = resultClass;
        
        // Add detailed info
        const detailDiv = document.createElement('div');
        detailDiv.className = 'info';
        detailDiv.innerHTML = `
            <h4>${url}</h4>
            <p><strong>Status:</strong> ${status} ${statusText}</p>
            <p><strong>Headers:</strong></p>
            <pre>${Array.from(response.headers.entries()).map(([key, value]) => `${key}: ${value}`).join('\n')}</pre>
        `;
        
        detailedOutput.appendChild(detailDiv);
        
        return response.text();
    })
    .then(html => {
        // Check if the page title contains our expected content
        const titleMatch = html.match(/<title>(.*?)<\/title>/i);
        if (titleMatch) {
            const title = titleMatch[1];
            const lastDetailDiv = detailedOutput.lastElementChild;
            
            let titleStatus = '';
            if (title.toLowerCase().includes('store manager') || title.toLowerCase().includes('store update')) {
                titleStatus = '✅ Title looks good: ' + title;
            } else if (title.toLowerCase().includes('not found') || title.toLowerCase().includes('404')) {
                titleStatus = '❌ Title shows error: ' + title;
            } else {
                titleStatus = '⚠️ Title: ' + title;
            }
            
            lastDetailDiv.innerHTML += `<p><strong>Page Title:</strong> ${titleStatus}</p>`;
        }
    })
    .catch(error => {
        resultElement.innerHTML = '❌ Error: ' + error.message;
        resultElement.className = 'error';
        
        console.error('Test failed for ' + url + ':', error);
    });
}

// Auto-test all URLs on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        testUrl('<?php echo home_url('/store-manager/'); ?>', 'store-manager');
        setTimeout(() => testUrl('<?php echo home_url('/update-store/'); ?>', 'update-store'), 500);
        setTimeout(() => testUrl('<?php echo home_url('/?sllist_page=store_manager'); ?>', 'fallback-manager'), 1000);
        setTimeout(() => testUrl('<?php echo home_url('/?sllist_page=store_update'); ?>', 'fallback-update'), 1500);
    }, 1000);
});
</script>

</body>
</html>
