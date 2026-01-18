<?php
/**
 * ============================================
 * FILE: cari-produk.php
 * PURPOSE: Product Search & Filter Page
 * LOCATION: MobileNest/produk/cari-produk.php
 * ============================================
 */

// Include config database
require_once '../config.php';
require_once '../includes/upload-handler.php';

// Mulai session
session_start();

// ========================================
// 1️⃣ AMBIL PARAMETER PENCARIAN
// ========================================
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_harga_min = isset($_GET['harga_min']) ? (int)$_GET['harga_min'] : 0;
$filter_harga_max = isset($_GET['harga_max']) ? (int)$_GET['harga_max'] : 999999999;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// ========================================
// 2️⃣ AMBIL DAFTAR KATEGORI
// ========================================
$sql_kategori = "SELECT DISTINCT kategori FROM produk ORDER BY kategori ASC";
$result_kategori = $conn->query($sql_kategori);
$kategori_list = [];
while ($row = $result_kategori->fetch_assoc()) {
    $kategori_list[] = $row;
}

// ========================================
// 3️⃣ BUILD QUERY PENCARIAN
// ========================================
$where_conditions = [];
$where_conditions[] = "produk.stok > 0"; // Hanya produk yang masih ada stok

// Kondisi pencarian
if (!empty($search_query)) {
    $search_safe = $conn->real_escape_string($search_query);
    $where_conditions[] = "(produk.nama_produk LIKE '%$search_safe%' OR produk.deskripsi LIKE '%$search_safe%')";
}

// Kondisi kategori
if (!empty($filter_kategori)) {
    $kategori_safe = $conn->real_escape_string($filter_kategori);
    $where_conditions[] = "produk.kategori = '$kategori_safe'";
}

// Kondisi harga
$where_conditions[] = "produk.harga >= $filter_harga_min AND produk.harga <= $filter_harga_max";

$where_clause = implode(' AND ', $where_conditions);

// ========================================
// 4️⃣ DETERMINE SORT ORDER
// ========================================
$order_by = "produk.tanggal_ditambahkan DESC"; // Default: terbaru

switch ($sort_by) {
    case 'harga_rendah':
        $order_by = "produk.harga ASC";
        break;
    case 'harga_tinggi':
        $order_by = "produk.harga DESC";
        break;
    case 'nama_a_z':
        $order_by = "produk.nama_produk ASC";
        break;
    case 'nama_z_a':
        $order_by = "produk.nama_produk DESC";
        break;
    case 'terpopuler':
        $order_by = "produk.terjual DESC";
        break;
    case 'terbaru':
    default:
        $order_by = "produk.tanggal_ditambahkan DESC";
        break;
}

// ========================================
// 5️⃣ QUERY PRODUK
// ========================================
$sql_produk = "SELECT produk.id_produk, produk.nama_produk, produk.deskripsi, 
               produk.harga, produk.stok, produk.gambar, produk.kategori,
               produk.terjual, produk.rating
               FROM produk
               WHERE $where_clause
               ORDER BY $order_by";

$result_produk = $conn->query($sql_produk);

if (!$result_produk) {
    die("Query Error: " . $conn->error);
}

$produk_list = [];
while ($row = $result_produk->fetch_assoc()) {
    $produk_list[] = $row;
}

$total_produk = count($produk_list);

// Helper function untuk build image URL
function getImageUrl($gambar_field) {
    if (empty($gambar_field)) {
        return '../assets/placeholder.png';
    }
    
    // Check if it's a filename (local upload) or URL
    if (strpos($gambar_field, 'http') === false && strpos($gambar_field, '/') === false) {
        // It's a filename - use UploadHandler to build URL
        return UploadHandler::getFileUrl($gambar_field, 'produk');
    } else {
        // It's already a URL
        return $gambar_field;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($search_query) ? "Hasil Pencarian: " . htmlspecialchars($search_query) : "Cari Produk"; ?> - MobileNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f0f0f0;
        }

        .product-body {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.95rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price {
            color: #667eea;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .product-rating {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-stok {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem;
            font-size: 0.9rem;
            border-radius: 5px;
            transition: opacity 0.3s;
            margin-top: auto;
        }

        .btn-add-cart:hover {
            opacity: 0.9;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .badge-kategori {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .results-info {
            color: #666;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f0f0f0;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .search-header {
                padding: 1rem 0;
            }

            .product-image {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/header.php'; ?>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <h2>🔍 Cari Produk</h2>
            <p class="mb-0">Temukan produk mobile terbaik untuk Anda</p>
        </div>
    </div>

    <div class="container py-4">
        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="cari-produk.php">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="q" class="form-control form-control-lg" 
                               placeholder="Cari produk (nama, merk, tipe)..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-md-3">
                <!-- Filter Kategori -->
                <div class="filter-card">
                    <div class="filter-title">📁 Kategori</div>
                    <form method="GET" action="cari-produk.php">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="kategori" id="kat_semua" 
                                   value="" <?php echo empty($filter_kategori) ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label class="form-check-label" for="kat_semua">
                                Semua Kategori
                            </label>
                        </div>

                        <?php foreach ($kategori_list as $kat): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="kategori" 
                                       id="kat_<?php echo htmlspecialchars($kat['kategori']); ?>" 
                                       value="<?php echo htmlspecialchars($kat['kategori']); ?>"
                                       <?php echo $filter_kategori === $kat['kategori'] ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                                <label class="form-check-label" for="kat_<?php echo htmlspecialchars($kat['kategori']); ?>">
                                    <?php echo htmlspecialchars($kat['kategori']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>

                <!-- Filter Harga -->
                <div class="filter-card">
                    <div class="filter-title">💰 Rentang Harga</div>
                    <form method="GET" action="cari-produk.php" id="priceForm">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($filter_kategori); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Min: Rp <?php echo number_format($filter_harga_min, 0, ',', '.'); ?></label>
                            <input type="range" class="form-range" name="harga_min" min="0" max="100000000" 
                                   step="100000" value="<?php echo $filter_harga_min; ?>"
                                   onchange="document.getElementById('priceForm').submit()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max: Rp <?php echo number_format($filter_harga_max, 0, ',', '.'); ?></label>
                            <input type="range" class="form-range" name="harga_max" min="0" max="100000000" 
                                   step="100000" value="<?php echo $filter_harga_max; ?>"
                                   onchange="document.getElementById('priceForm').submit()">
                        </div>
                    </form>
                </div>

                <!-- Sort Options -->
                <div class="filter-card">
                    <div class="filter-title">📊 Urutkan</div>
                    <form method="GET" action="cari-produk.php">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($filter_kategori); ?>">
                        <input type="hidden" name="harga_min" value="<?php echo $filter_harga_min; ?>">
                        <input type="hidden" name="harga_max" value="<?php echo $filter_harga_max; ?>">
                        
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="terbaru" <?php echo $sort_by === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="terpopuler" <?php echo $sort_by === 'terpopuler' ? 'selected' : ''; ?>>Terpopuler</option>
                            <option value="harga_rendah" <?php echo $sort_by === 'harga_rendah' ? 'selected' : ''; ?>>Harga Terendah</option>
                            <option value="harga_tinggi" <?php echo $sort_by === 'harga_tinggi' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                            <option value="nama_a_z" <?php echo $sort_by === 'nama_a_z' ? 'selected' : ''; ?>>Nama A-Z</option>
                            <option value="nama_z_a" <?php echo $sort_by === 'nama_z_a' ? 'selected' : ''; ?>>Nama Z-A</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Product Results -->
            <div class="col-md-9">
                <!-- Results Info -->
                <div class="results-info">
                    <strong><?php echo $total_produk; ?></strong> produk ditemukan
                    <?php if (!empty($search_query)): ?>
                        untuk pencarian "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                    <?php endif; ?>
                </div>

                <!-- Product Grid -->
                <?php if ($total_produk > 0): ?>
                    <div class="row g-3 mb-4">
                        <?php foreach ($produk_list as $produk): ?>
                            <div class="col-md-4 col-sm-6 col-12">
                                <div class="product-card">
                                    <!-- Gambar Produk -->
                                    <div style="position: relative; overflow: hidden;">
                                        <img src="<?php echo htmlspecialchars(getImageUrl($produk['gambar'])); ?>" 
                                             alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>" 
                                             class="product-image">
                                        <span class="badge-kategori"><?php echo htmlspecialchars($produk['kategori']); ?></span>
                                    </div>

                                    <!-- Info Produk -->
                                    <div class="product-body">
                                        <div class="product-name" title="<?php echo htmlspecialchars($produk['nama_produk']); ?>">
                                            <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                        </div>

                                        <div class="product-price">
                                            Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                                        </div>

                                        <?php if (!empty($produk['rating'])): ?>
                                            <div class="product-rating">
                                                <i class="fas fa-star"></i> <?php echo number_format($produk['rating'], 1); ?>
                                                (<?php echo $produk['terjual']; ?> terjual)
                                            </div>
                                        <?php endif; ?>

                                        <div class="product-stok">
                                            <?php if ($produk['stok'] <= 5): ?>
                                                <span class="badge bg-danger">Stok Terbatas: <?php echo $produk['stok']; ?></span>
                                            <?php else: ?>
                                                ✓ Stok Tersedia (<?php echo $produk['stok']; ?>)
                                            <?php endif; ?>
                                        </div>

                                        <!-- Button Actions -->
                                        <div class="d-grid gap-2">
                                            <a href="detail-produk.php?id=<?php echo $produk['id_produk']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                👁️ Lihat Detail
                                            </a>
                                            <?php if (isset($_SESSION['user'])): ?>
                                                <form method="POST" action="../keranjang-aksi.php" style="display: inline; width: 100%;">
                                                    <input type="hidden" name="id_produk" value="<?php echo $produk['id_produk']; ?>">
                                                    <input type="hidden" name="action" value="add">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn btn-add-cart btn-sm w-100">
                                                        🛒 Keranjang
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="../user/login.php" class="btn btn-warning btn-sm">
                                                    🔐 Login Terlebih Dahulu
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- No Results -->
                    <div class="no-results">
                        <h4>😕 Produk Tidak Ditemukan</h4>
                        <p class="text-muted mb-3">
                            Maaf, tidak ada produk yang sesuai dengan kriteria pencarian Anda.
                        </p>
                        <a href="cari-produk.php" class="btn btn-primary">
                            🔄 Coba Pencarian Lagi
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>