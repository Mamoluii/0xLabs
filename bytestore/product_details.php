<?php
include('lab-config.php');
include('db.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get user info safely
$username = mysqli_real_escape_string($conn, $_SESSION['user']);
$user_res = mysqli_query($conn, "SELECT id, balance FROM users WHERE username = '$username'");
$user = $user_res ? mysqli_fetch_assoc($user_res) : ['id' => 0, 'balance' => 0];

// INFO-01 vulnerable: show raw DB errors
if ($current_lab && $current_lab['id'] === 'INFO — 01' && $current_lab['type'] === 'vulnerable') {
    $id = $_GET['id'] ?? '';
    // Intentionally expose errors
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    // Mark solved if SQL injection triggered an error
    if (strpos($id, "'") !== false) mark_lab_solved();
    if (!$result) {
        ?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Error</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body style="background:#0f172a;color:#e2e8f0;font-family:'Share Tech Mono',monospace;margin:0;padding:0;">
<?php render_lab_banner(); ?>
<div style="padding:40px;">
<h2 style="color:#f87171;margin-bottom:16px;">⚠ Fatal Error</h2>
<pre style="background:#1e293b;padding:24px;border-radius:12px;border:1px solid #7f1d1d;color:#fca5a5;line-height:1.8;overflow:auto;"><?php
echo htmlspecialchars(
    "mysqli_sql_exception: " . mysqli_error($conn) . "\n" .
    "Query: SELECT * FROM products WHERE id = $id\n\n" .
    "Stack trace:\n" .
    "#0 C:\\xampp\\htdocs\\0xlabsfinal\\bytestore\\product_details.php(6): mysqli_query()\n" .
    "#1 {main} thrown in product_details.php on line 6\n\n" .
    "Server: Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12\n" .
    "Database: MySQL 8.0.33 — 0xlabs_db\n" .
    "PHP: " . PHP_VERSION
);
?></pre>
</div>
</body></html><?php
        exit();
    }
    $product = mysqli_fetch_assoc($result);
} elseif ($current_lab && $current_lab['id'] === 'INFO — 01' && $current_lab['type'] === 'secure') {
    // SECURE: cast to int, suppress errors
    $id = (int)($_GET['id'] ?? 0);
    $result = @mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    $product = $result ? mysqli_fetch_assoc($result) : null;
} else {
    $id = (int)($_GET['id'] ?? 0);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    $product = $result ? mysqli_fetch_assoc($result) : null;
}

if (!$product) {
    header("Location: products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ByteStore | <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#0f172a;color:white;min-height:100vh;display:flex;flex-direction:column;}
        header{background:#1e293b;padding:20px 50px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #334155;}
        .logo{font-size:24px;font-weight:bold;color:#38bdf8;letter-spacing:2px;}
        .nav-right{display:flex;align-items:center;gap:20px;}
        .balance-badge{background:#0f172a;border:1px solid #334155;border-radius:20px;padding:6px 16px;font-size:13px;color:#4ade80;}
        .nav-right a{color:#94a3b8;text-decoration:none;font-size:14px;transition:.2s;}
        .nav-right a:hover{color:#38bdf8;}
        .logout{color:#f87171!important;}
        .wrapper{max-width:1000px;margin:50px auto;padding:0 20px;flex:1;}
        .detail-card{background:#1e293b;display:flex;border-radius:20px;overflow:hidden;border:1px solid #334155;}
        .image-side{flex:1;min-height:400px;overflow:hidden;}
        .image-side img{width:100%;height:100%;object-fit:cover;transition:.4s;}
        .image-side img:hover{transform:scale(1.05);}
        .content-side{flex:1;padding:45px;display:flex;flex-direction:column;justify-content:center;}
        .content-side h1{font-size:26px;color:#f8fafc;margin-bottom:10px;}
        .price{font-size:34px;font-weight:bold;color:#38bdf8;margin-bottom:20px;}
        .desc{color:#94a3b8;line-height:1.7;font-size:14px;margin-bottom:28px;}
        .qty-row{display:flex;align-items:center;gap:15px;margin-bottom:20px;}
        .qty-label{color:#94a3b8;font-size:14px;}
        .qty-input{background:#0f172a;border:1px solid #334155;color:white;padding:8px 14px;border-radius:8px;font-size:15px;width:70px;text-align:center;}
        .btn-add{width:100%;padding:16px;background:#38bdf8;color:#0f172a;border:none;border-radius:12px;font-weight:700;font-size:16px;cursor:pointer;transition:.3s;margin-bottom:12px;}
        .btn-add:hover{background:#0ea5e9;transform:translateY(-2px);}
        .back-btn{display:inline-block;color:#64748b;text-decoration:none;font-size:13px;margin-top:4px;}
        .back-btn:hover{color:#38bdf8;}
        @media(max-width:700px){.detail-card{flex-direction:column;}.image-side{min-height:250px;}}
    </style>
</head>
<body>
<?php render_lab_banner(); ?>
<header>
    <div class="logo">BYTESTORE</div>
    <div class="nav-right">
        <span class="balance-badge">💰 $<?php echo number_format($user['balance'] ?? 0, 2); ?></span>
        <a href="cart.php">🛒 Cart</a>
        <a href="products.php">Shop</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</header>
<div class="wrapper">
    <div class="detail-card">
        <div class="image-side">
            <img src="images/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>"
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 onerror="this.style.background='#0f172a';this.style.display='block';">
        </div>
        <div class="content-side">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
            <p class="desc"><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="qty-row">
                <span class="qty-label">Quantity:</span>
                <input type="number" class="qty-input" id="qty" value="1" min="1" max="10">
            </div>
            <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                <input type="hidden" name="price" value="<?php echo $product['price']; ?>">
                <input type="hidden" name="quantity" id="qty-hidden" value="1">
                <button type="submit" class="btn-add"
                    onclick="document.getElementById('qty-hidden').value=document.getElementById('qty').value">
                    🛒 Add to Cart
                </button>
            </form>
            <a href="products.php" class="back-btn">← Back to Shop</a>
        </div>
    </div>
</div>
</body>
</html>
