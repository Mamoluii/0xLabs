<?php
include('lab-config.php'); // handles session_start()
include('db.php');
if (!isset($_SESSION['user'])) { header("Location: login.php" . (isset($_SESSION["active_lab"]) ? "?lab=" . urlencode($_SESSION["active_lab"]) : "")); exit(); }

$user_res = mysqli_query($conn, "SELECT id, COALESCE(balance, 0) as balance FROM users WHERE username = '" . mysqli_real_escape_string($conn, $_SESSION['user']) . "'");
$user = mysqli_fetch_assoc($user_res);
$balance = $user['balance'];

$cart_count_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = " . $user['id']);
$cart_count_row = mysqli_fetch_assoc($cart_count_res);
$cart_count = $cart_count_row['total'] ?? 0;

// SQL-09: blind SQLi via TrackingId cookie
if ($current_lab && $current_lab['id'] === 'SQL — 09') {
    if (!isset($_COOKIE['TrackingId'])) {
        $new_id = 'TRK-' . bin2hex(random_bytes(8));
        mysqli_query($conn, "INSERT INTO tracking (id) VALUES ('" . mysqli_real_escape_string($conn, $new_id) . "')");
        setcookie('TrackingId', $new_id, time() + 3600, '/');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
    $tracking_id = $_COOKIE['TrackingId'];

    if ($current_lab['type'] === 'vulnerable') {
        // VULNERABLE: cookie value concatenated directly into the query
        $q = "SELECT * FROM tracking WHERE id = '$tracking_id'";
        $result = @mysqli_query($conn, $q);
        if ($result === false) {
            http_response_code(500);
            echo "<pre style='background:#0f172a;color:#f87171;font-family:monospace;padding:40px;'>Internal Server Error\n" . htmlspecialchars(mysqli_error($conn)) . "</pre>";
            exit();
        }
    } else if ($current_lab['type'] === 'secure') {
        // SECURE: prepared statement — the cookie is always a literal value
        $stmt = $conn->prepare("SELECT * FROM tracking WHERE id = ?");
        $stmt->bind_param("s", $tracking_id);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $found = $result && mysqli_num_rows($result) > 0;
    if ($found && strpos($tracking_id, "'") !== false) mark_lab_solved();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Tracking</title></head>";
    echo "<body style='background:#0f172a;color:#e2e8f0;font-family:Poppins,sans-serif;margin:0;'>";
    render_lab_banner();
    echo "<div style='max-width:700px;margin:60px auto;padding:0 24px;text-align:center;'>";
    if ($found) {
        echo "<h2 style='color:#4ade80;'>✓ Welcome back</h2><p style='color:#94a3b8;'>Your tracking ID was recognized.</p>";
    } else {
        echo "<h2 style='color:#f87171;'>Tracking ID not recognized</h2><p style='color:#94a3b8;'>No matching session found.</p>";
    }
    echo "</div></body></html>";
    exit();
}

// SQL-02+: WHERE clause injection via category param
$category = $_GET['category'] ?? '';
if ($current_lab && in_array($current_lab['id'], ['SQL — 02','SQL — 03','SQL — 04','SQL — 05','SQL — 06','SQL — 07','SQL — 08'])) {
    if ($current_lab['type'] === 'secure') {
        // SECURE: prepared statement, only visible products
        if ($category !== '') {
            $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND visible = 1");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $products_result = $stmt->get_result();
        } else {
            $products_result = mysqli_query($conn, "SELECT * FROM products WHERE visible = 1");
        }
    } else {
        // VULNERABLE: category injected directly — visible=1 filter can be bypassed
        if ($category !== '') {
            $products_result = @mysqli_query($conn, "SELECT * FROM products WHERE visible = 1 AND category = '$category'");
        } else {
            $products_result = mysqli_query($conn, "SELECT * FROM products WHERE visible = 1");
        }
    }
} else {
    $products_result = mysqli_query($conn, "SELECT * FROM products WHERE visible = 1");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ByteStore | Catalog</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#0f172a;color:white;min-height:100vh;}
        header{background:#1e293b;padding:20px 50px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #334155;}
        .logo{font-size:24px;font-weight:bold;color:#38bdf8;letter-spacing:2px;}
        .nav-right{display:flex;align-items:center;gap:20px;}
        .balance-badge{background:#0f172a;border:1px solid #334155;border-radius:20px;padding:6px 16px;font-size:13px;color:#4ade80;}
        .cart-link{color:#94a3b8;text-decoration:none;font-size:14px;transition:color .3s;}
        .cart-link:hover{color:#38bdf8;}
        .cart-badge{background:#38bdf8;color:#0f172a;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:bold;display:inline-flex;align-items:center;justify-content:center;margin-left:4px;}
        .logout{color:#f87171;text-decoration:none;font-size:14px;}
        .hero{text-align:center;padding:50px 20px 30px;}
        .hero h1{font-size:36px;font-weight:700;color:#f8fafc;}
        .hero h1 span{color:#38bdf8;}
        .hero p{color:#94a3b8;margin-top:10px;font-size:15px;}
        .container{max-width:1200px;margin:20px auto;padding:0 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:25px;padding-bottom:50px;}
        .card{background:#1e293b;border-radius:16px;overflow:hidden;transition:.3s;border:1px solid #334155;display:flex;flex-direction:column;}
        .card:hover{transform:translateY(-8px);border-color:#38bdf8;box-shadow:0 20px 40px rgba(56,189,248,.1);}
        .card img{width:100%;height:220px;object-fit:cover;}
        .info{padding:20px;flex:1;display:flex;flex-direction:column;}
        .info h3{margin:0 0 8px;font-size:17px;color:#f8fafc;}
        .info p{color:#64748b;font-size:13px;line-height:1.5;flex:1;margin-bottom:15px;}
        .price-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
        .price{color:#38bdf8;font-size:22px;font-weight:bold;}
        .stock{color:#4ade80;font-size:12px;}
        .btn-group{display:flex;gap:8px;}
        .btn-details{flex:1;padding:10px;background:transparent;color:#38bdf8;border:1px solid #38bdf8;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:600;transition:.3s;display:flex;align-items:center;justify-content:center;}
        .btn-details:hover{background:#38bdf8;color:#0f172a;}
        .btn-cart{flex:1;padding:10px;background:#38bdf8;color:#0f172a;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.3s;width:100%;}
        .btn-cart:hover{background:#0ea5e9;}
        .category-bar{margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
        .cat-btn{padding:8px 20px;border-radius:20px;border:1px solid #334155;color:#94a3b8;text-decoration:none;font-size:13px;transition:.3s;}
        .cat-btn:hover,.cat-btn.active{background:#38bdf8;color:#0f172a;border-color:#38bdf8;font-weight:600;}
        .toast{position:fixed;bottom:30px;right:30px;background:#1e293b;border:1px solid #4ade80;color:#4ade80;padding:14px 24px;border-radius:12px;font-size:14px;opacity:0;transition:opacity .3s;z-index:999;pointer-events:none;}
        .toast.show{opacity:1;}
        /* Injected rows from UNION attacks get highlighted */
        .card.injected{border-color:#ff3e6c;box-shadow:0 0 20px rgba(255,62,108,.2);}
    </style>
</head>
<body>
<?php render_lab_banner(); ?>
<header>
    <div class="logo">BYTESTORE</div>
    <div class="nav-right">
        <span class="balance-badge">💰 $<?php echo number_format($balance, 2); ?></span>
        <a href="cart.php" class="cart-link">🛒 Cart <span class="cart-badge"><?php echo $cart_count; ?></span></a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</header>
<div class="hero">
    <h1>Welcome back, <span><?php echo htmlspecialchars($_SESSION['user']); ?></span> 👋</h1>
    <p>Discover premium products curated just for you</p>
    <?php if ($current_lab && in_array($current_lab['id'], ['SQL — 02','SQL — 03','SQL — 04','SQL — 05','SQL — 06','SQL — 07','SQL — 08'])): ?>
    <div class="category-bar">
        <?php
        $cats = ['Electronics','Accessories','Fashion','Gifts'];
        $active = $_GET['category'] ?? '';
        $base_url = '?lab=' . urlencode($_SESSION['active_lab'] ?? '');
        ?>
        <a href="<?= $base_url ?>" class="cat-btn<?= $active==='' ? ' active' : '' ?>">All</a>
        <?php foreach($cats as $c): ?>
        <a href="<?= $base_url ?>&category=<?= urlencode($c) ?>" class="cat-btn<?= $active===$c ? ' active' : '' ?>"><?= $c ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<div class="container">
    <?php
    // Mark lab solved if UNION injection detected (extra rows without stock column)
    $injected_count = 0;
    $all_rows = [];
    if ($products_result && mysqli_num_rows($products_result) > 0) {
        while($r = mysqli_fetch_assoc($products_result)) $all_rows[] = $r;
        foreach($all_rows as $r) { if(!isset($r['stock'])) $injected_count++; }
        if ($injected_count > 0) mark_lab_solved();
        // Also mark solved for SQL-02 if OR 1=1 returned all products (more than 8 = injected)
        if ($current_lab && $current_lab['id'] === 'SQL — 02' && count($all_rows) > 8 && $category !== '') mark_lab_solved();
    }
    $products_array_index = 0;
    if (count($all_rows) > 0):
        foreach($all_rows as $row):
            $is_injected = !isset($row['stock']);
    ?>
    <div class="card<?php echo $is_injected ? ' injected' : ''; ?>">
        <?php if(isset($row['image_path']) && $row['image_path']): ?>
        <img src="images/<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name'] ?? ''); ?>" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="info">
            <h3><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></h3>
            <p><?php $desc = $row['description'] ?? (implode(' | ', array_filter($row))); echo htmlspecialchars(function_exists('mb_substr') ? mb_substr($desc, 0, 120) : substr($desc, 0, 120)) . '...'; ?></p>
            <?php if(isset($row['price'])): ?>
            <div class="price-row">
                <span class="price">$<?php echo number_format($row['price'], 2); ?></span>
                <span class="stock">✓ In Stock</span>
            </div>
            <div class="btn-group">
                <a href="product_details.php?id=<?php echo (int)($row['id'] ?? 0); ?>" class="btn-details">Details</a>
                <form method="POST" action="cart.php" style="flex:1;display:flex;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                    <button type="submit" class="btn-cart" onclick="showToast()">🛒 Add to Cart</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; else: ?>
    <div style="grid-column:1/-1;text-align:center;padding:60px;color:#64748b;font-size:14px;">No products found.</div>
    <?php endif; ?>
</div>
<div class="toast" id="toast">✓ Added to cart!</div>
<script>
function showToast(){const t=document.getElementById('toast');t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);}
</script>
</body>
</html>
