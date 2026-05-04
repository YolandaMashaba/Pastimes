<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Password Hash Generator</h1>";

echo "<h2>Generate Bcrypt Hash:</h2>";
echo "<form method='POST'>";
echo "<p>Password: <input type='password' name='password' required></p>";
echo "<button type='submit'>Generate Hash</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h3>Generated Hash:</h3>";
    echo "<input type='text' value='" . htmlspecialchars($hash) . "' size='80' readonly>";
    echo "<button onclick='copyHash()'>Copy</button>";
    
    echo "<h3>Verification Test:</h3>";
    $verify = password_verify($password, $hash);
    echo "<p>Verification: " . ($verify ? "✓ Success" : "✗ Failed") . "</p>";
    
    echo "<script>
    function copyHash() {
        navigator.clipboard.writeText(document.querySelector('input[type=\"text\"]').value);
        alert('Hash copied to clipboard!');
    }
    </script>";
}

echo "<h2>Common Password Hashes:</h2>";
echo "<table border='1'>";
echo "<tr><th>Password</th><th>Hash</th></tr>";

$commonPasswords = [
    'admin123',
    'password123',
    '123456',
    'admin',
    'password'
];

foreach ($commonPasswords as $pwd) {
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    echo "<tr>";
    echo "<td><code>$pwd</code></td>";
    echo "<td><input type='text' value='" . htmlspecialchars($hash) . "' size='60' readonly></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Verify Existing Hash:</h2>";
echo "<form method='POST'>";
echo "<p>Password: <input type='password' name='verify_password'></p>";
echo "<p>Hash: <input type='text' name='verify_hash' size='80'></p>";
echo "<button type='submit'>Verify</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password']) && isset($_POST['verify_hash'])) {
    $password = $_POST['verify_password'];
    $hash = $_POST['verify_hash'];
    $verify = password_verify($password, $hash);
    
    echo "<h3>Verification Result:</h3>";
    echo "<p style='color: " . ($verify ? "green" : "red") . ";'>";
    echo $verify ? "✓ Password matches hash" : "✗ Password does not match hash";
    echo "</p>";
}

echo "<h2>Hash Information:</h2>";
echo "<ul>";
echo "<li><strong>Algorithm:</strong> Bcrypt (PASSWORD_DEFAULT)</li>";
echo "<li><strong>Cost:</strong> 10 (default)</li>";
echo "<li><strong>Format:</strong> $2y$10$... (60 characters)</li>";
echo "<li><strong>Security:</strong> Includes salt automatically</li>";
echo "</ul>";
?>
