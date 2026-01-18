<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth-check.php';
require_user_login();

$page_title = "Keranjang Belanja";

$user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? 0;

if ($user_id === 0) {
    header('Location: ../login.php');
    exit();
}

$cart_items = [];
$error_message = '';

// Get all cart items - JOIN dengan produk untuk ambil gambar
try {
    $sql = "SELECT 
                c.id_keranjang,
                c.id_produk,
                c.jumlah,
                c.added_at,
                p.nama_produk,
                p.harga,
                p.gambar_produk,
                p.stok,
                (c.jumlah * p.harga) as subtotal
            FROM keranjang c
            JOIN produk p ON c.id_produk = p.id_produk
            WHERE c.id_user = ?
            ORDER BY c.added_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Keranjang query error: " . $e->getMessage());
    $error_message = "Gagal memuat keranjang. Silakan coba lagi.";
}

include '../includes/header.php';
?>

<style>
body { background: #f5f7fa; }
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Cart Items */
.cart-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.cart-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.product-image {
    width: 120px;
    height: 120px;
    border-radius: 10px;
    object-fit: cover;
    background: #f0f0f0;
    flex-shrink: 0;
}

.product-details {
    flex: 1;
}

.product-name {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.product-price {
    font-size: 14px;
    color: #667eea;
    font-weight: 600;
    margin-bottom: 15px;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 10px;
    width: fit-content;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: white;
    color: #2c3e50;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.qty-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.qty-input {
    width: 60px;
    height: 30px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    padding: 0;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-small {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-remove {
    background: #f8d7da;
    color: #842029;
}

.btn-remove:hover {
    background: #f5c6cb;
}

.subtotal {
    font-size: 16px;
    font-weight: 700;
    color: #667eea;
    min-width: 120px;
    text-align: right;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.empty-state-icon {
    font-size: 100px;
    color: #e0e0e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 10px;
}

.empty-state p {
    color: #7f8c8d;
    margin-bottom: 30px;
}

/* Summary */
.summary-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    position: sticky;
    top: 100px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 14px;
}

.summary-row.total {
    border-top: 2px solid #e0e0e0;
    border-bottom: none;
    padding-top: 12px;
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

.btn-checkout {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    margin-top: 20px;
    transition: all 0.3s ease;
}

.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-checkout:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-shop {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-shop:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    color: white;
}

@media (max-width: 768px) {
    .cart-item {
        flex-direction: column;
    }

    .product-image {
        width: 100%;
        height: 200px;
    }

    .subtotal {
        text-align: left;
        min-width: auto;
    }
}
</style>

<div class="container py-5">
    <div class="cart-container">
        <h1 class="mb-4" style="font-weight: 700; color: #2c3e50;">
            <i class="bi bi-cart3"></i> Keranjang Belanja
        </h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <?php if (count($cart_items) > 0): ?>
                    <?php foreach ($cart_items as $item): 
                        $gambar = !empty($item['gambar_produk']) 
                            ? '../assets/images/produk/' . htmlspecialchars($item['gambar_produk'])
                            : '../assets/images/placeholder.png';
                        $id_keranjang = $item['id_keranjang'];
                    ?>
                    <div class="cart-item">
                        <!-- Product Image -->
                        <img src="<?php echo $gambar; ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" class="product-image" onerror="this.src='../assets/images/placeholder.png';">
                        
                        <!-- Product Details -->
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="product-price">Rp <?php echo number_format(intval($item['harga']), 0, ',', '.'); ?></div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 12px; color: #7f8c8d; margin-bottom: 8px;">Stok tersedia: <strong><?php echo intval($item['stok']); ?></strong></div>
                                <div class="quantity-control">
                                    <button class="qty-btn" onclick="changeQuantity(<?php echo $id_keranjang; ?>, -1)">−</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $id_keranjang; ?>" value="<?php echo intval($item['jumlah']); ?>" min="1" max="<?php echo intval($item['stok']); ?>" onchange="changeQuantity(<?php echo $id_keranjang; ?>, 0)">
                                    <button class="qty-btn" onclick="changeQuantity(<?php echo $id_keranjang; ?>, 1)">+</button>
                                </div>
                            </div>
                            
                            <div class="product-actions">
                                <button class="btn-small btn-remove" onclick="removeFromCart(<?php echo $id_keranjang; ?>)">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subtotal -->
                        <div class="subtotal">
                            <div style="font-size: 12px; color: #7f8c8d; margin-bottom: 5px;">Subtotal</div>
                            Rp <?php echo number_format(intval($item['subtotal']), 0, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-bag-x"></i>
                        </div>
                        <h3>Keranjang Belanja Kosong</h3>
                        <p>Belum ada produk di keranjang Anda.</p>
                        <a href="../produk/list-produk.php" class="btn-shop">
                            <i class="bi bi-shop"></i> Mulai Belanja
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Summary -->
            <?php if (count($cart_items) > 0): ?>
            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 style="font-weight: 700; margin-bottom: 20px;">Ringkasan Pesanan</h5>
                    
                    <?php 
                    $total_items = 0;
                    $total_price = 0;
                    foreach ($cart_items as $item) {
                        $total_items += intval($item['jumlah']);
                        $total_price += intval($item['subtotal']);
                    }
                    ?>
                    
                    <div class="summary-row">
                        <span>Total Produk</span>
                        <span><?php echo $total_items; ?> pcs</span>
                    </div>
                    <div class="summary-row">
                        <span>Jumlah Item</span>
                        <span><?php echo count($cart_items); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total Belanja</span>
                        <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                    </div>
                    
                    <form action="checkout.php" method="POST">
                        <button type="submit" class="btn-checkout">
                            <i class="bi bi-arrow-right"></i> Lanjut ke Pengiriman
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeQuantity(id, delta) {
    const input = document.getElementById('qty_' + id);
    let newQty = parseInt(input.value) + delta;
    const maxStok = parseInt(input.max);
    
    if (newQty < 1) newQty = 1;
    if (newQty > maxStok) newQty = maxStok;
    
    input.value = newQty;
    updateCart(id, newQty);
}

function updateCart(id, quantity) {
    fetch('update-keranjang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_keranjang: id, jumlah: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            location.reload();
        } else {
            location.reload();
        }
    });
}

function removeFromCart(id) {
    if (confirm('Hapus produk dari keranjang?')) {
        fetch('delete-keranjang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_keranjang: id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
