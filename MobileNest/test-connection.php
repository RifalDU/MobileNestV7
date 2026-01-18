<?php
// Test Connection File - For Debugging Only
echo "<h2>MobileNest Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check if config.php exists
echo "<p><strong>Test 1: Check config.php</strong></p>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
} else {
    echo "❌ config.php NOT found!<br>";
    die('Please create config.php in MobileNest folder');
}

// Test 2: Include config
echo "<p><strong>Test 2: Include config.php</strong></p>";
try {
    require_once 'config.php';
    echo "✅ config.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error including config.php: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check connection
echo "<p><strong>Test 3: Check Database Connection</strong></p>";
if ($conn->connect_error) {
    echo "❌ Connection Error: " . $conn->connect_error . "<br>";
    echo "<strong>Fix:</strong><br>";
    echo "1. Make sure XAMPP MySQL is running<br>";
    echo "2. Check database name in config.php<br>";
    echo "3. Check username/password<br>";
} else {
    echo "✅ Connected to database successfully<br>";
    echo "Database: " . htmlspecialchars($conn->stat()) . "<br>";
}

// Test 4: List tables
echo "<p><strong>Test 4: Database Tables</strong></p>";
$tables_result = $conn->query("SHOW TABLES");
if ($tables_result) {
    echo "✅ Tables found:<br>";
    echo "<ul>";
    while ($row = $tables_result->fetch_row()) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "❌ Could not fetch tables: " . $conn->error . "<br>";
}

// Test 5: Session test
echo "<p><strong>Test 5: Session Test</strong></p>";
session_start();
$_SESSION['test'] = 'Session works!';
if (isset($_SESSION['test'])) {
    echo "✅ Session working correctly<br>";
    unset($_SESSION['test']);
} else {
    echo "❌ Session not working<br>";
}

// Test 6: Check crucial tables
echo "<p><strong>Test 6: Check Required Tables</strong></p>";
$required_tables = ['users', 'produk', 'keranjang', 'transaksi', 'detail_transaksi', 'pengiriman'];
foreach ($required_tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' NOT found<br>";
    }
}

// Test 7: Verify column names in users table
echo "<p><strong>Test 7: Verify 'users' Table Columns</strong></p>";
$columns_check = $conn->query("SHOW COLUMNS FROM users");
if ($columns_check && $columns_check->num_rows > 0) {
    echo "✅ 'users' table columns:<br>";
    echo "<ul>";
    $has_nama_lengkap = false;
    $has_no_telepon = false;
    $has_email = false;
    while ($row = $columns_check->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['Field']) . " (" . htmlspecialchars($row['Type']) . ")</li>";
        if ($row['Field'] === 'nama_lengkap') $has_nama_lengkap = true;
        if ($row['Field'] === 'no_telepon') $has_no_telepon = true;
        if ($row['Field'] === 'email') $has_email = true;
    }
    echo "</ul>";
    
    if ($has_nama_lengkap && $has_no_telepon && $has_email) {
        echo "✅ All required columns found in users table<br>";
    } else {
        echo "❌ Missing columns in users table:<br>";
        if (!$has_nama_lengkap) echo "  - nama_lengkap<br>";
        if (!$has_no_telepon) echo "  - no_telepon<br>";
        if (!$has_email) echo "  - email<br>";
    }
} else {
    echo "❌ Could not fetch users table columns<br>";
}

// Test 8: Verify column names in keranjang table
echo "<p><strong>Test 8: Verify 'keranjang' Table Columns</strong></p>";
$cart_columns = $conn->query("SHOW COLUMNS FROM keranjang");
if ($cart_columns && $cart_columns->num_rows > 0) {
    echo "✅ 'keranjang' table columns:<br>";
    echo "<ul>";
    $has_jumlah = false;
    while ($row = $cart_columns->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['Field']) . " (" . htmlspecialchars($row['Type']) . ")</li>";
        if ($row['Field'] === 'jumlah') $has_jumlah = true;
    }
    echo "</ul>";
    
    if ($has_jumlah) {
        echo "✅ Column 'jumlah' found (NOT 'qty')<br>";
    } else {
        echo "❌ Column 'jumlah' NOT found - Check if using 'qty' instead<br>";
    }
} else {
    echo "❌ Could not fetch keranjang table columns<br>";
}

// Test 9: Verify pengiriman table columns
echo "<p><strong>Test 9: Verify 'pengiriman' Table Columns</strong></p>";
$shipping_columns = $conn->query("SHOW COLUMNS FROM pengiriman");
if ($shipping_columns && $shipping_columns->num_rows > 0) {
    echo "✅ 'pengiriman' table columns:<br>";
    echo "<ul>";
    $has_nama_penerima = false;
    while ($row = $shipping_columns->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['Field']) . " (" . htmlspecialchars($row['Type']) . ")</li>";
        if ($row['Field'] === 'nama_penerima') $has_nama_penerima = true;
    }
    echo "</ul>";
    
    if ($has_nama_penerima) {
        echo "✅ Column 'nama_penerima' found (NOT 'nama_pengguna')<br>";
    } else {
        echo "❌ Column 'nama_penerima' NOT found<br>";
    }
} else {
    echo "❌ Could not fetch pengiriman table columns<br>";
}

// Test 10: Sample query test
echo "<p><strong>Test 10: Sample Queries</strong></p>";
$user_test = $conn->query("SELECT id_user, nama_lengkap, no_telepon, email FROM users LIMIT 1");
if ($user_test && $user_test->num_rows > 0) {
    echo "✅ Sample users query works:<br>";
    $user = $user_test->fetch_assoc();
    echo "<pre>";
    echo "ID: " . htmlspecialchars($user['id_user']) . "\n";
    echo "Nama: " . htmlspecialchars($user['nama_lengkap']) . "\n";
    echo "Telepon: " . htmlspecialchars($user['no_telepon']) . "\n";
    echo "Email: " . htmlspecialchars($user['email']) . "\n";
    echo "</pre>";
} else {
    echo "✅ Sample users query prepared (no data yet)<br>";
}

echo "<hr>";
echo "<p><strong style='color: green;'>If all tests are green ✅, your database is ready!</strong></p>";
echo "<p><strong style='color: red;'>If you see red errors ❌, check the fix suggestions above.</strong></p>";
echo "<p><em>For detailed database schema, see: DATABASE_SCHEMA.md</em></p>";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h2 {
            color: #333;
        }
        p {
            color: #666;
        }
        ul {
            margin-top: 10px;
        }
        hr {
            border: 1px solid #ddd;
            margin: 20px 0;
        }
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <!-- Content from PHP above -->
</body>
</html>