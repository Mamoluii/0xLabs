<?php
include('db.php');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$username_esc = mysqli_real_escape_string($conn, $_SESSION['user']);
$order_res = mysqli_query($conn, "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id AND u.username = '$username_esc'");
$order = mysqli_fetch_assoc($order_res);

// IDOR fix: order doesn't exist or doesn't belong to this user
if (!$order) {
    header("Location: products.php");
    exit();
}

$items_res = mysqli_query($conn, "SELECT oi.*, p.name, p.image_path FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
$items = [];
while ($row = mysqli_fetch_assoc($items_res)) {
    $items[] = $row;
}

// Get user balance
$user_res = mysqli_query($conn, "SELECT balance FROM users WHERE username = '$username_esc'");
$user = mysqli_fetch_assoc($user_res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ByteStore | Order Confirmed</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; min-height: 100vh; }
        header { background: #1e293b; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .logo { font-size: 24px; font-weight: bold; color: #38bdf8; letter-spacing: 2px; }
        .nav-links a { color: #94a3b8; text-decoration: none; margin-left: 20px; font-size: 14px; }
        .nav-links .logout { color: #f87171; }
        .container { max-width: 700px; margin: 60px auto; padding: 0 20px; text-align: center; }
        .success-icon { font-size: 80px; margin-bottom: 20px; animation: bounce 1s ease; }
        @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-20px)} }
        h1 { font-size: 32px; color: #4ade80; margin-bottom: 10px; }
        .subtitle { color: #94a3b8; margin-bottom: 40px; font-size: 15px; }
        .order-card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 30px; text-align: left; margin-bottom: 25px; }
        .order-card h3 { color: #38bdf8; margin-bottom: 20px; font-size: 18px; }
        .order-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #334155; }
        .order-item:last-child { border: none; }
        .order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .item-name { flex: 1; font-size: 14px; }
        .item-subtotal { color: #38bdf8; font-weight: bold; }
        .total-row { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px solid #38bdf8; }
        .total-label { font-size: 18px; font-weight: bold; }
        .total-amount { font-size: 22px; color: #38bdf8; font-weight: bold; }
        .balance-row { display: flex; justify-content: space-between; margin-top: 8px; color: #94a3b8; font-size: 13px; }
        .btn { display: inline-block; padding: 14px 40px; background: #38bdf8; color: #0f172a; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 15px; transition: .3s; }
        .btn:hover { background: #0ea5e9; transform: translateY(-2px); }
        .order-num { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 8px 16px; display: inline-block; font-size: 13px; color: #94a3b8; margin-bottom: 30px; }
    </style>
</head>
<body>
<header>
    <div class="logo">BYTESTORE</div>
    <div class="nav-links">
        <a href="products.php">Shop</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</header>

<div class="container">
    <div class="success-icon">🎉</div>
    <h1>Order Confirmed!</h1>
    <p class="subtitle">Thank you for your purchase, <?php echo htmlspecialchars($_SESSION['user']); ?>!</p>
    <div class="order-num">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>

    <div class="order-card">
        <h3>📦 Order Summary</h3>
        <?php foreach ($items as $item): ?>
        <div class="order-item">
            <img src="images/<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>">
            <div class="item-name">
                <?php echo $item['name']; ?>
                <div style="color:#94a3b8;font-size:12px;">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></div>
            </div>
            <div class="item-subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
        </div>
        <?php endforeach; ?>
        <div class="total-row">
            <span class="total-label">Total Charged</span>
            <span class="total-amount">$<?php echo number_format($order['total'], 2); ?></span>
        </div>
        <div class="balance-row">
            <span>Remaining Balance</span>
            <span>$<?php echo number_format($user['balance'], 2); ?></span>
        </div>
    </div>

    <a href="products.php" class="btn">Continue Shopping →</a>
</div>
</body>
</html>
