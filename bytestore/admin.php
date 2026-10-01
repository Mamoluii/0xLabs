<?php
include('db.php');
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php" . (isset($_SESSION["active_lab"]) ? "?lab=" . urlencode($_SESSION["active_lab"]) : "")); exit(); }

// Check admin role
$username_esc = mysqli_real_escape_string($conn, $_SESSION['user']);
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_esc'");
$user = mysqli_fetch_assoc($user_res);
if ($user['role'] !== 'admin') {
    die("<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title>
    <style>body{font-family:Poppins,sans-serif;background:#0f172a;color:white;display:flex;justify-content:center;align-items:center;height:100vh;flex-direction:column;gap:20px;}
    h1{color:#f87171;font-size:48px;} p{color:#94a3b8;} a{color:#38bdf8;text-decoration:none;}</style></head>
    <body><h1>403</h1><p>Access Denied — Admins Only</p><a href='products.php'>← Go Back</a></body></html>");
}

// Stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as s FROM orders"))['s'] ?? 0;
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];

$users_res = mysqli_query($conn, "SELECT id, username, role, balance FROM users ORDER BY id");
$orders_res = mysqli_query($conn, "SELECT o.id, u.username, o.total, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ByteStore | Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; min-height: 100vh; }
        header { background: #1e293b; padding: 18px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .logo { font-size: 22px; font-weight: bold; color: #38bdf8; }
        .admin-tag { background: #fbbf24; color: #0f172a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .nav-right a { color: #94a3b8; text-decoration: none; font-size: 14px; margin-left: 20px; }
        .nav-right .logout { color: #f87171; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 25px; }
        h2 { font-size: 26px; color: #f8fafc; margin-bottom: 25px; }
        h2 span { color: #38bdf8; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 25px; text-align: center; transition: .3s; }
        .stat-card:hover { border-color: #38bdf8; }
        .stat-icon { font-size: 36px; margin-bottom: 10px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #38bdf8; }
        .stat-label { color: #64748b; font-size: 13px; margin-top: 5px; }
        .section { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 25px; margin-bottom: 30px; }
        .section h3 { font-size: 17px; color: #38bdf8; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 14px; color: #64748b; font-weight: 600; border-bottom: 1px solid #334155; }
        td { padding: 12px 14px; border-bottom: 1px solid #1e293b; }
        tr:last-child td { border: none; }
        tr:hover td { background: rgba(56,189,248,0.05); }
        .role-admin { background: #fbbf24; color: #0f172a; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .role-user { background: #334155; color: #94a3b8; padding: 3px 10px; border-radius: 20px; font-size: 11px; }
        .status-done { background: #052e16; color: #4ade80; padding: 3px 10px; border-radius: 20px; font-size: 11px; }
        .back-btn { display: inline-block; color: #38bdf8; text-decoration: none; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
<header>
    <div class="logo">BYTESTORE <span class="admin-tag">ADMIN</span></div>
    <div class="nav-right">
        <a href="products.php">← Store</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Admin <span>Dashboard</span></h2>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?php echo $total_orders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">$<?php echo number_format($total_revenue, 0); ?></div>
            <div class="stat-label">Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛍️</div>
            <div class="stat-value"><?php echo $total_products; ?></div>
            <div class="stat-label">Products</div>
        </div>
    </div>

    <div class="section">
        <h3>👥 Users</h3>
        <table>
            <tr><th>#</th><th>Username</th><th>Role</th><th>Balance</th></tr>
            <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><span class="role-<?php echo $u['role']; ?>"><?php echo strtoupper($u['role']); ?></span></td>
                <td style="color:#4ade80;">$<?php echo number_format($u['balance'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="section">
        <h3>📋 Recent Orders</h3>
        <?php if (mysqli_num_rows($orders_res) === 0): ?>
            <p style="color:#64748b;text-align:center;padding:20px;">No orders yet.</p>
        <?php else: ?>
        <table>
            <tr><th>Order #</th><th>User</th><th>Total</th><th>Status</th><th>Date</th></tr>
            <?php while ($o = mysqli_fetch_assoc($orders_res)): ?>
            <tr>
                <td>#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($o['username']); ?></td>
                <td style="color:#38bdf8;font-weight:bold;">$<?php echo number_format($o['total'], 2); ?></td>
                <td><span class="status-done"><?php echo strtoupper($o['status']); ?></span></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $o['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
