<?php
include('db.php');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username_esc = mysqli_real_escape_string($conn, $_SESSION['user']);

// --- Add to cart (VULNERABLE: trusts the price from POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];
    $price      = (float)$_POST['price']; // ⚠️ VULNERABILITY: price comes from client!

    // Get user id
    $user_res = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_esc'");
    $user     = mysqli_fetch_assoc($user_res);
    $user_id  = $user['id'];

    // Check if already in cart
    $check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $new_qty = $row['quantity'] + $quantity;
        mysqli_query($conn, "UPDATE cart SET quantity = $new_qty WHERE id = " . $row['id']);
    } else {
        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity, price) VALUES ($user_id, $product_id, $quantity, $price)");
    }
    header("Location: cart.php");
    exit();
}

// --- Remove from cart ---
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $owner_res = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_esc'");
    $owner     = mysqli_fetch_assoc($owner_res);
    // IDOR fix: only delete the row if it belongs to the logged-in user
    mysqli_query($conn, "DELETE FROM cart WHERE id = $cart_id AND user_id = " . (int)$owner['id']);
    header("Location: cart.php");
    exit();
}

// --- Checkout (VULNERABLE: uses price stored from client) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $user_res = mysqli_query($conn, "SELECT id, balance FROM users WHERE username = '$username_esc'");
    $user     = mysqli_fetch_assoc($user_res);
    $user_id  = $user['id'];
    $balance  = $user['balance'];

    $cart_res = mysqli_query($conn, "SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
    $total    = 0;
    $items    = [];
    while ($row = mysqli_fetch_assoc($cart_res)) {
        $total += $row['price'] * $row['quantity'];
        $items[] = $row;
    }

    if ($total <= $balance) {
        // Create order
        mysqli_query($conn, "INSERT INTO orders (user_id, total) VALUES ($user_id, $total)");
        $order_id = mysqli_insert_id($conn);
        foreach ($items as $item) {
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})");
        }
        // Deduct balance
        $new_balance = $balance - $total;
        mysqli_query($conn, "UPDATE users SET balance = $new_balance WHERE id = $user_id");
        // Clear cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
        header("Location: order_success.php?order_id=" . $order_id);
        exit();
    } else {
        $error = "Insufficient balance! Your balance is $" . number_format($balance, 2);
    }
}

// --- Load cart ---
$user_res = mysqli_query($conn, "SELECT id, balance FROM users WHERE username = '$username_esc'");
$user     = mysqli_fetch_assoc($user_res);
$user_id  = $user['id'];
$balance  = $user['balance'];

$cart_res = mysqli_query($conn, "SELECT c.*, p.name, p.image_path FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
$cart_items = [];
$total = 0;
while ($row = mysqli_fetch_assoc($cart_res)) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ByteStore | Cart</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; min-height: 100vh; }
        header { background: #1e293b; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .logo { font-size: 24px; font-weight: bold; color: #38bdf8; letter-spacing: 2px; }
        .nav-links a { color: #94a3b8; text-decoration: none; margin-left: 20px; font-size: 14px; transition: color .3s; }
        .nav-links a:hover { color: #38bdf8; }
        .nav-links .logout { color: #f87171; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h2 { font-size: 28px; color: #38bdf8; margin-bottom: 30px; }
        .empty-cart { text-align: center; padding: 80px 20px; color: #94a3b8; }
        .empty-cart a { color: #38bdf8; text-decoration: none; font-weight: bold; }
        .cart-item { background: #1e293b; border: 1px solid #334155; border-radius: 15px; display: flex; align-items: center; gap: 20px; padding: 20px; margin-bottom: 15px; }
        .cart-item img { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; }
        .item-info { flex: 1; }
        .item-info h3 { font-size: 16px; margin-bottom: 6px; }
        .item-price { color: #38bdf8; font-weight: bold; font-size: 18px; }
        .item-qty { color: #94a3b8; font-size: 13px; margin-top: 4px; }
        .remove-btn { color: #f87171; text-decoration: none; font-size: 13px; padding: 6px 14px; border: 1px solid #f87171; border-radius: 8px; transition: .3s; }
        .remove-btn:hover { background: #f87171; color: white; }
        .summary { background: #1e293b; border: 1px solid #334155; border-radius: 15px; padding: 25px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #334155; font-size: 15px; }
        .summary-row:last-child { border: none; }
        .summary-total { color: #38bdf8; font-weight: bold; font-size: 20px; }
        .balance-info { color: #94a3b8; font-size: 13px; margin-top: 5px; }
        .checkout-btn { width: 100%; padding: 15px; background: #38bdf8; color: #0f172a; border: none; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; margin-top: 20px; transition: .3s; }
        .checkout-btn:hover { background: #0ea5e9; transform: translateY(-2px); }
        .error { background: #450a0a; border: 1px solid #f87171; color: #f87171; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
<header>
    <div class="logo">BYTESTORE</div>
    <div class="nav-links">
        <a href="products.php">Shop</a>
        <a href="cart.php">🛒 Cart (<?php echo count($cart_items); ?>)</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</header>

<div class="container">
    <h2>🛒 Shopping Cart</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <p style="font-size:48px;margin-bottom:20px;">🛒</p>
            <p style="font-size:20px;margin-bottom:10px;">Your cart is empty</p>
            <a href="products.php">Continue Shopping →</a>
        </div>
    <?php else: ?>
        <?php foreach ($cart_items as $item): ?>
        <div class="cart-item">
            <img src="images/<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>">
            <div class="item-info">
                <h3><?php echo $item['name']; ?></h3>
                <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                <div class="item-qty">Qty: <?php echo $item['quantity']; ?></div>
            </div>
            <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn">Remove</a>
        </div>
        <?php endforeach; ?>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span style="color:#4ade80;">FREE</span>
            </div>
            <div class="summary-row">
                <span class="summary-total">Total</span>
                <span class="summary-total">$<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="balance-info">Your balance: $<?php echo number_format($balance, 2); ?></div>
            <form method="POST">
                <input type="hidden" name="action" value="checkout">
                <button type="submit" class="checkout-btn">Place Order →</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
