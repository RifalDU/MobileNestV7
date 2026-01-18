<?php
/**
 * Database Structure Verification Script
 * Purpose: Verify that database schema matches expected structure
 * Usage: Run via browser at localhost/mobilenest/verify-database-structure.php
 */

// Include config
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Verification - MobileNest</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status-box {
            margin: 20px 0;
            padding: 15px;
            border-left: 5px solid #28a745;
            background: #f0f9f4;
            border-radius: 5px;
        }
        .status-box.error {
            border-left-color: #dc3545;
            background: #f8f5f5;
        }
        .status-box.warning {
            border-left-color: #ffc107;
            background: #fffaf0;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .table-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .table-item {
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .table-item:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .table-item.present {
            border-left: 4px solid #28a745;
        }
        .table-item.missing {
            border-left: 4px solid #dc3545;
            opacity: 0.6;
        }
        .details {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .column {
            padding: 8px;
            background: white;
            margin: 5px 0;
            border-left: 3px solid #667eea;
            border-radius: 3px;
        }
        .column.key {
            border-left-color: #ffc107;
            background: #fffaf0;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-item {
            padding: 15px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 5px;
            border-top: 3px solid #667eea;
        }
        .summary-item.warning { border-top-color: #ffc107; }
        .summary-item.error { border-top-color: #dc3545; }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .summary-item.error .summary-value { color: #dc3545; }
        .summary-item.warning .summary-value { color: #ffc107; }
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .actions {
            margin: 30px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .fk-info {
            background: #e3f2fd;
            padding: 10px;
            border-left: 3px solid #2196F3;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 12px;
        }
        .index-info {
            background: #f3e5f5;
            padding: 10px;
            border-left: 3px solid #9c27b0;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Database Structure Verification</h1>
        <p class="subtitle">MobileNest E-Commerce Platform - Database Integrity Check</p>

        <?php
        // Get connection
        $conn = mysqli_connect($host, $user, $password, $database);

        if (!$conn) {
            echo '<div class="status-box error">';
            echo '<strong>❌ Connection Failed!</strong><br>';
            echo 'Error: ' . mysqli_connect_error();
            echo '</div>';
            exit;
        }

        echo '<div class="status-box">';
        echo '<strong>✅ Database Connected Successfully!</strong><br>';
        echo 'Database: <strong>' . htmlspecialchars($database) . '</strong><br>';
        echo 'Server: <strong>' . htmlspecialchars($host) . '</strong>';
        echo '</div>';

        // Define expected tables and columns
        $expectedTables = array(
            'admin' => array('id_admin', 'username', 'password', 'nama_lengkap', 'email', 'no_telepon', 'tanggal_dibuat'),
            'users' => array('id_user', 'username', 'password', 'nama_lengkap', 'email', 'no_telepon', 'alamat', 'tanggal_daftar', 'status_akun'),
            'produk' => array('id_produk', 'nama_produk', 'merek', 'deskripsi', 'spesifikasi', 'harga', 'stok', 'gambar', 'kategori', 'status_produk', 'tanggal_ditambahkan'),
            'promo' => array('id_promo', 'nama_promo', 'jenis_promo', 'nilai_diskon', 'persentase_diskon', 'tanggal_mulai', 'tanggal_selesai', 'status_promo', 'deskripsi'),
            'transaksi' => array('id_transaksi', 'id_user', 'total_harga', 'status_pesanan', 'metode_pembayaran', 'alamat_pengiriman', 'no_resi', 'tanggal_transaksi', 'tanggal_dikirim', 'kode_transaksi', 'catatan_user', 'bukti_pembayaran'),
            'detail_transaksi' => array('id_detail', 'id_transaksi', 'id_produk', 'jumlah', 'harga_satuan', 'subtotal'),
            'keranjang' => array('id_keranjang', 'id_user', 'id_produk', 'jumlah', 'tanggal_ditambahkan'),
            'ulasan' => array('id_ulasan', 'id_user', 'id_produk', 'rating', 'komentar', 'tanggal_ulasan')
        );

        // Get actual tables
        $result = mysqli_query($conn, "SHOW TABLES");
        $actualTables = array();
        while ($row = mysqli_fetch_row($result)) {
            $actualTables[] = $row[0];
        }

        // Check tables
        $presentCount = 0;
        $missingCount = 0;

        foreach ($expectedTables as $tableName => $columns) {
            if (in_array($tableName, $actualTables)) {
                $presentCount++;
            } else {
                $missingCount++;
            }
        }

        // Summary
        echo '<div class="summary">';
        echo '<div class="summary-item" style="border-top-color: #28a745;">';
        echo '<div class="summary-value" style="color: #28a745;">' . $presentCount . '</div>';
        echo '<div class="summary-label">Tables Present</div>';
        echo '</div>';
        if ($missingCount > 0) {
            echo '<div class="summary-item error">';
            echo '<div class="summary-value">' . $missingCount . '</div>';
            echo '<div class="summary-label">Tables Missing</div>';
            echo '</div>';
        }
        echo '<div class="summary-item" style="border-top-color: #17a2b8;">';
        echo '<div class="summary-value" style="color: #17a2b8;">' . count($expectedTables) . '</div>';
        echo '<div class="summary-label">Total Expected</div>';
        echo '</div>';
        echo '</div>';

        // Table List
        echo '<div class="section">';
        echo '<div class="section-title">📋 Tables Status</div>';
        echo '<div class="table-list">';

        foreach ($expectedTables as $tableName => $columns) {
            $exists = in_array($tableName, $actualTables);
            $class = $exists ? 'present' : 'missing';
            $icon = $exists ? '✅' : '❌';
            echo '<div class="table-item ' . $class . '" onclick="toggleDetails(\'' . $tableName . '\')">'
                . $icon . ' ' . htmlspecialchars($tableName)
                . '</div>';
        }

        echo '</div>';
        echo '</div>';

        // Detailed Analysis
        echo '<div class="section">';
        echo '<div class="section-title">🗒️ Detailed Structure Analysis</div>';

        foreach ($expectedTables as $tableName => $expectedColumns) {
            if (!in_array($tableName, $actualTables)) {
                echo '<div class="status-box error">';
                echo '<strong>❌ Table Missing: ' . htmlspecialchars($tableName) . '</strong><br>';
                echo 'Expected columns: ' . implode(', ', $expectedColumns);
                echo '</div>';
                continue;
            }

            $result = mysqli_query($conn, "DESCRIBE " . $tableName);
            $actualColumns = array();
            $columnDetails = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $actualColumns[] = $row['Field'];
                $columnDetails[$row['Field']] = $row;
            }

            $missingColumns = array_diff($expectedColumns, $actualColumns);
            $extraColumns = array_diff($actualColumns, $expectedColumns);

            echo '<div class="section" style="margin: 15px 0;">';
            echo '<h3 style="color: #667eea; font-size: 16px; margin-bottom: 10px;">';
            echo (empty($missingColumns) && empty($extraColumns) ? '✅' : '⚠️');
            echo ' ' . htmlspecialchars($tableName) . ' (' . count($actualColumns) . ' columns)</h3>';

            // Show all columns
            foreach ($columnDetails as $colName => $colInfo) {
                $class = '';
                if ($colInfo['Key']) {
                    $class = 'key';
                }
                echo '<div class="column ' . $class . '">';
                echo '<strong>' . htmlspecialchars($colName) . '</strong> | '
                    . htmlspecialchars($colInfo['Type'])
                    . ' | ' . ($colInfo['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
                if ($colInfo['Default']) {
                    echo ' | DEFAULT: ' . htmlspecialchars($colInfo['Default']);
                }
                if ($colInfo['Key']) {
                    echo ' | KEY: ' . htmlspecialchars($colInfo['Key']);
                }
                echo '</div>';
            }

            // Show foreign keys
            $fkQuery = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                       WHERE TABLE_NAME = '" . $tableName . "' AND TABLE_SCHEMA = '" . $database . "' 
                       AND REFERENCED_TABLE_NAME IS NOT NULL";
            $fkResult = mysqli_query($conn, $fkQuery);
            if (mysqli_num_rows($fkResult) > 0) {
                while ($fk = mysqli_fetch_assoc($fkResult)) {
                    echo '<div class="fk-info">';
                    echo '<strong>FK:</strong> ' . htmlspecialchars($fk['COLUMN_NAME'])
                        . ' → ' . htmlspecialchars($fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'])
                        . ' (' . htmlspecialchars($fk['CONSTRAINT_NAME']) . ')';
                    echo '</div>';
                }
            }

            // Show indexes
            $indexQuery = "SHOW INDEX FROM " . $tableName;
            $indexResult = mysqli_query($conn, $indexQuery);
            $indexes = array();
            while ($idx = mysqli_fetch_assoc($indexResult)) {
                if ($idx['Key_name'] !== 'PRIMARY') {
                    $indexes[$idx['Key_name']][] = $idx['Column_name'];
                }
            }
            if (!empty($indexes)) {
                foreach ($indexes as $idxName => $idxCols) {
                    echo '<div class="index-info">';
                    echo '<strong>INDEX:</strong> ' . htmlspecialchars($idxName) . ' (' . implode(', ', $idxCols) . ')';
                    echo '</div>';
                }
            }

            // Warnings
            if (!empty($missingColumns)) {
                echo '<div class="status-box warning" style="margin-top: 10px;">';
                echo '<strong>⚠️ Missing Columns:</strong><br>';
                echo implode(', ', array_map('htmlspecialchars', $missingColumns));
                echo '</div>';
            }
            if (!empty($extraColumns)) {
                echo '<div class="status-box warning" style="margin-top: 10px;">';
                echo '<strong>⚠️ Extra Columns (not in schema):</strong><br>';
                echo implode(', ', array_map('htmlspecialchars', $extraColumns));
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';

        // Data Statistics
        echo '<div class="section">';
        echo '<div class="section-title">📈 Data Statistics</div>';
        echo '<div class="summary">';

        foreach ($expectedTables as $tableName => $columns) {
            if (in_array($tableName, $actualTables)) {
                $countResult = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM " . $tableName);
                $countRow = mysqli_fetch_assoc($countResult);
                $count = $countRow['cnt'];

                echo '<div class="summary-item">';
                echo '<div class="summary-value">' . $count . '</div>';
                echo '<div class="summary-label">' . htmlspecialchars($tableName) . '</div>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '</div>';

        // Final Status
        echo '<div class="section">';
        if ($missingCount === 0) {
            $schemaOk = true;
            foreach ($expectedTables as $tableName => $expectedColumns) {
                if (in_array($tableName, $actualTables)) {
                    $result = mysqli_query($conn, "DESCRIBE " . $tableName);
                    $actualColumns = array();
                    while ($row = mysqli_fetch_assoc($result)) {
                        $actualColumns[] = $row['Field'];
                    }
                    if (count(array_diff($expectedColumns, $actualColumns)) > 0) {
                        $schemaOk = false;
                        break;
                    }
                }
            }

            if ($schemaOk) {
                echo '<div class="status-box">';
                echo '<strong>✅ Database Schema is VALID!</strong><br>';
                echo 'All tables and columns match the expected schema. You can proceed with integration.';
                echo '</div>';
            } else {
                echo '<div class="status-box warning">';
                echo '<strong>⚠️ Schema Incomplete</strong><br>';
                echo 'Some columns are missing. Please review the detailed analysis above and use migration scripts.';
                echo '</div>';
            }
        } else {
            echo '<div class="status-box error">';
            echo '<strong>❌ Database Schema is INVALID</strong><br>';
            echo 'Some tables are missing. Please run: <code>mobilenest_schema.sql</code>';
            echo '</div>';
        }

        echo '</div>';

        // Actions
        echo '<div class="actions">';
        echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-primary">Refresh</a>';
        echo '</div>';

        mysqli_close($conn);
        ?>
    </div>

    <script>
        function toggleDetails(tableName) {
            alert('Table: ' + tableName);
        }
    </script>
</body>
</html>