// ✅ FIX: Fetch transaction items with JOIN to produk for images
$query_items = "SELECT 
                dt.id_detail,
                dt.id_produk,
                dt.nama_produk,
                dt.harga_satuan,
                dt.jumlah,
                dt.subtotal,
                p.gambar_produk
                FROM detail_transaksi dt
                LEFT JOIN produk p ON dt.id_produk = p.id_produk
                WHERE dt.id_transaksi = ?";
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param('i', $id_transaksi);
$stmt_items->execute();
$resultItems = $stmt_items->get_result();
$cart_items = $resultItems->fetch_all(MYSQLI_ASSOC);