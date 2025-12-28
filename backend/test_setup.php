<?php
/**
 * Quick Setup Test Page
 * Visit: http://localhost:8001/test_setup.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>SSO Setup Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h1 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Sabancı Internship Portal - Setup Test</h1>
    
    <h2>1. PHP Configuration</h2>
    <div class="test success">
        ✅ PHP Version: <?php echo phpversion(); ?>
    </div>
    
    <h2>2. SimpleSAMLPHP Library</h2>
    <?php
    $samlLibPath = __DIR__ . '/../simplesamlphp-2.4.1/vendor/autoload.php';
    if (file_exists($samlLibPath)) {
        echo '<div class="test success">✅ SimpleSAMLPHP library found at: ' . realpath($samlLibPath) . '</div>';
    } else {
        echo '<div class="test error">❌ SimpleSAMLPHP library NOT found! Expected at: ' . $samlLibPath . '</div>';
    }
    ?>
    
    <h2>3. SAML Configuration Files</h2>
    <?php
    $configFiles = [
        'Config (Local)' => __DIR__ . '/../simplesamlphp-2.4.1/config/config-local.php',
        'IDP Metadata (Local)' => __DIR__ . '/../simplesamlphp-2.4.1/metadata/saml20-idp-remote-local.php',
    ];
    
    foreach ($configFiles as $name => $path) {
        if (file_exists($path)) {
            echo '<div class="test success">✅ ' . $name . ' exists</div>';
        } else {
            echo '<div class="test error">❌ ' . $name . ' missing: ' . $path . '</div>';
        }
    }
    ?>
    
    <h2>4. SAML Authentication Files</h2>
    <?php
    $authFiles = [
        'SAML Auth Handler' => __DIR__ . '/auth/saml_auth.php',
        'SAML Login Endpoint' => __DIR__ . '/auth/saml_login_endpoint.php',
    ];
    
    foreach ($authFiles as $name => $path) {
        if (file_exists($path)) {
            echo '<div class="test success">✅ ' . $name . ' exists</div>';
        } else {
            echo '<div class="test error">❌ ' . $name . ' missing: ' . $path . '</div>';
        }
    }
    ?>
    
    <h2>5. Database Connection</h2>
    <?php
    require_once __DIR__ . '/config/database.php';
    try {
        $db = getDB();
        echo '<div class="test success">✅ Database connection successful!</div>';
        
        // Test a simple query
        $stmt = $db->query("SELECT DATABASE() as dbname");
        $result = $stmt->fetch();
        echo '<div class="test info">📊 Connected to database: <strong>' . htmlspecialchars($result['dbname']) . '</strong></div>';
        
    } catch (Exception $e) {
        echo '<div class="test error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    
    <h2>6. Current Configuration</h2>
    <div class="test info">
        <strong>Application URL (Production):</strong> https://pro2-dev.sabanciuniv.edu/shadowing<br>
        <strong>Reply URL (Azure):</strong> https://pro2-dev.sabanciuniv.edu/shadowing/saml/module.php/saml/sp/saml2-acs.php/default-sp<br>
        <strong>Local Frontend:</strong> http://localhost:8000<br>
        <strong>Local Backend:</strong> http://localhost:8001<br>
    </div>
    
    <h2>7. Next Steps</h2>
    <div class="test info">
        <strong>✅ To Test Locally (Limited):</strong><br>
        1. Visit: <a href="http://localhost:8000/index.html">http://localhost:8000/index.html</a><br>
        2. Click "Sign in with Sabancı SSO"<br>
        3. You should see SimpleSAMLPHP interface (not a file download!)<br>
        <br>
        <strong>⚠️ Full SSO Testing:</strong><br>
        - Requires SSL certificate on production server<br>
        - IT is setting this up: <code>pro2-dev.sabanciuniv.edu/shadowing</code><br>
        - Azure will only accept the registered Reply URL (https://pro2-dev...)<br>
        <br>
        <strong>🚀 Once SSL is ready:</strong><br>
        1. Deploy your code to production server<br>
        2. Update <code>index.html</code> SSO button URLs from <code>http://localhost:8001</code> to production URLs<br>
        3. Test full SSO flow with real Sabancı credentials<br>
    </div>
    
    <h2>8. Test SSO Login (Local)</h2>
    <div class="test">
        <a href="/auth/saml_login_endpoint.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
            🔐 Try SSO Login Now
        </a>
        <p style="margin-top: 10px; font-size: 14px; color: #666;">
            Note: This will fail with Azure because you're on localhost, but it will show if PHP/SimpleSAMLPHP is working!
        </p>
    </div>
    
</body>
</html>

