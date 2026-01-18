<?php
require_once 'config.php';
require_once 'includes/brand-logos.php';

echo "<h1>DEBUG: Brand Logos Configuration</h1>";
echo "<hr>";

// 1. Check all brands in config
echo "<h2>1. Brands Defined in brand-logos.php:</h2>";
$all_brands = get_all_brands();
echo "<pre>";
print_r($all_brands);
echo "</pre>";

echo "<h2>2. Check Realme Logo URLs:</h2>";
$realme_data = get_brand_logo_data('Realme');
echo "<pre>";
print_r($realme_data);
echo "</pre>";

echo "<h2>3. Realme Logo Direct URL:</h2>";
echo "<img src='" . get_brand_logo_url('Realme') . "' width='100' height='100' alt='Realme' onerror=\"console.log('Image failed to load'); this.style.border='2px solid red';\">";
echo "<br>";
echo "URL: " . get_brand_logo_url('Realme');
echo "<hr>";

// 2. Check database brands
echo "<h2>4. Brands in Database (FROM produk table):</h2>";
$sql = "SELECT DISTINCT merek FROM produk WHERE status_produk = 'Tersedia' ORDER BY merek";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='10'><tr><th>Merek</th><th>Logo URL</th><th>Preview</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $brand = $row['merek'];
    $logo_url = get_brand_logo_url($brand);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($brand) . "</td>";
    echo "<td><small>" . htmlspecialchars($logo_url) . "</small></td>";
    echo "<td><img src='" . htmlspecialchars($logo_url) . "' width='50' height='50' style='border: 1px solid #ccc;' alt='" . htmlspecialchars($brand) . "' onerror=\"this.alt='FAILED'; this.style.border='2px solid red';\"></td>";
    echo "</tr>";
}
echo "</table>";
echo "<hr>";

// 3. Count Realme products
echo "<h2>5. Products by Brand Count:</h2>";
$sql = "SELECT merek, COUNT(*) as count FROM produk WHERE status_produk = 'Tersedia' GROUP BY merek ORDER BY count DESC";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='10'><tr><th>Merek</th><th>Product Count</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>" . htmlspecialchars($row['merek']) . "</td><td>" . $row['count'] . "</td></tr>";
}
echo "</table>";

?>
<style>
body { font-family: Arial; margin: 20px; }
img { margin: 5px; }
h2 { color: #333; margin-top: 30px; }
table { border-collapse: collapse; margin: 10px 0; }
</style>