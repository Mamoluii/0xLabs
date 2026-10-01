<?php
include('lab-config.php');
include('db.php');

$file = $_GET['file'] ?? '';

// Shared leaked-content shown when a PATH-0X traversal bypass succeeds
$LEAKED_CONFIG = "# /var/www/config.txt\nDB_HOST     = localhost\nDB_NAME     = 0xlabs_db\nDB_USER     = root\nDB_PASS     = r00tP@ssw0rd!\nSECRET_KEY  = 8f3a9c2d1e7b...\nAPP_ENV     = production\nADMIN_TOKEN = eyJhbGci...truncated";

function path_lab_page($title, $body_html) {
    ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title><?= htmlspecialchars($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Poppins:wght@400;600&display=swap" rel="stylesheet"></head>
<body style="background:#0f172a;color:#e2e8f0;font-family:'Poppins',sans-serif;margin:0;padding:0;">
<?php render_lab_banner(); ?>
<div style="max-width:860px;margin:40px auto;padding:0 24px;"><?= $body_html ?></div>
</body></html><?php
    exit();
}

function path_lab_landing($lab_name, $file_param) {
    path_lab_page("$lab_name | Image Viewer", "
    <div style=\"background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px;\">
      <h2 style=\"font-size:18px;color:#f8fafc;margin-bottom:14px;\">🖼️ Product Image Viewer</h2>
      <img src=\"images/headphones.jpg\" style=\"width:100%;max-height:300px;object-fit:cover;border-radius:10px;border:1px solid #334155;display:block;margin-bottom:20px;\">
      <div style=\"background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px 16px;font-family:'Share Tech Mono',monospace;font-size:13px;color:#94a3b8;\">Current: image.php?lab=$file_param&file=<strong>headphones.jpg</strong></div>
    </div>");
}

function path_lab_leaked($LEAKED_CONFIG, $explanation) {
    mark_lab_solved();
    path_lab_page("File Read", "
    <h2 style=\"color:#ffd60a;margin-bottom:16px;\">📄 File Read via Traversal Bypass</h2>
    <pre style=\"background:#1e293b;padding:24px;border-radius:12px;border:1px solid #334155;color:#4ade80;line-height:1.8;white-space:pre-wrap;\">" . htmlspecialchars($LEAKED_CONFIG) . "</pre>
    <p style=\"color:#94a3b8;margin-top:20px;font-size:13px;line-height:1.7;\">$explanation</p>");
}

function path_lab_blocked($reason) {
    path_lab_page("Blocked", "
    <h2 style=\"color:#f87171;margin-bottom:16px;\">⛔ Blocked</h2>
    <p style=\"color:#94a3b8;font-size:13px;line-height:1.7;\">$reason</p>");
}

// ─── PATH-01 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 01' && $current_lab['type'] === 'vulnerable') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-01', 'path-01');
    // VULNERABLE: no validation at all — raw concatenation
    $target = realpath(__DIR__ . '/images/' . $file);
    if ($target && basename($target) === 'config.txt' && file_exists($target)) {
        path_lab_leaked(file_get_contents($target), "No filtering at all was applied to <code>file</code> — the raw value was concatenated straight into the filesystem path.");
    } elseif ($target && strpos($target, realpath(__DIR__ . '/images')) === 0 && in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif'])) {
        header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit();
    } else {
        path_lab_blocked("File not found.");
    }
}
// ─── PATH-01 SECURE ─────────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 01' && $current_lab['type'] === 'secure') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-01 Secure', 'path-01-secure');
    // SECURE: basename() strips any directory component
    $safe = basename($file);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target) && in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif'])) {
        header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit();
    } else {
        path_lab_blocked("basename() strips any '../' or directory component, so only files directly inside <code>images/</code> are ever reachable. <code>config.txt</code> lives one level up and can't be reached this way.");
    }
}

// ─── PATH-02 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 02' && $current_lab['type'] === 'vulnerable') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-02', 'path-02');
    // VULNERABLE: blocks '..' but trusts absolute paths outright
    if (strpos($file, '..') !== false) { path_lab_blocked("Rejected — input contains '..'"); }
    if (substr($file, 0, 1) === '/') {
        path_lab_leaked($GLOBALS['LEAKED_CONFIG'], "The filter only blocked <code>..</code> sequences. Because the input started with <code>/</code>, the app trusted it as an absolute path and read it directly, ignoring the intended <code>images/</code> base directory.");
    }
    $safe = basename($file);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}
// ─── PATH-02 SECURE ─────────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 02' && $current_lab['type'] === 'secure') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-02 Secure', 'path-02-secure');
    if (strpos($file, '..') !== false || substr($file, 0, 1) === '/') {
        path_lab_blocked("Rejected — both '..' sequences AND absolute paths (starting with '/') are now blocked.");
    }
    $safe = basename($file);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}

// ─── PATH-03 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 03' && $current_lab['type'] === 'vulnerable') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-03', 'path-03');
    // VULNERABLE: single-pass, non-recursive strip of '../'
    $stripped = str_replace('../', '', $file);
    if (strpos($stripped, '../') !== false) {
        path_lab_leaked($GLOBALS['LEAKED_CONFIG'], "The filter removed <code>../</code> in a single pass. Nesting it as <code>....//</code> means removing the middle <code>../</code> once reconstructs a real <code>../</code>, since the result is never re-checked.");
    }
    $safe = basename($stripped);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}
// ─── PATH-03 SECURE ─────────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 03' && $current_lab['type'] === 'secure') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-03 Secure', 'path-03-secure');
    // SECURE: strip in a loop until stable (recursive)
    $stripped = $file;
    do { $before = $stripped; $stripped = str_replace('../', '', $stripped); } while ($stripped !== $before);
    $safe = basename($stripped);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("The strip now runs in a loop until nothing changes, so nested sequences like <code>....//</code> can no longer reconstruct <code>../</code>.");
}

// ─── PATH-04 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 04' && $current_lab['type'] === 'vulnerable') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-04', 'path-04');
    // VULNERABLE: checks the once-decoded value, then decodes AGAIN before use
    $raw = $file; // PHP already decoded this once from the query string
    if (strpos($raw, '..') !== false) { path_lab_blocked("Rejected — input contains '..'"); }
    $decoded_again = urldecode($raw); // superfluous second decode
    if (strpos($decoded_again, '../') !== false) {
        path_lab_leaked($GLOBALS['LEAKED_CONFIG'], "PHP already URL-decodes <code>\$_GET</code> once. The app then called <code>urldecode()</code> a second time, so <code>%252e%252e%252f</code> (which passed the '..' check as-is) turned into <code>../</code> right before the file was read.");
    }
    $safe = basename($decoded_again);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}
// ─── PATH-04 SECURE ─────────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 04' && $current_lab['type'] === 'secure') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-04 Secure', 'path-04-secure');
    // SECURE: validate the value exactly as received — no extra decode pass
    if (strpos($file, '..') !== false) { path_lab_blocked("Rejected — input contains '..'"); }
    $safe = basename($file);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("No second decode pass happens, so a double-encoded payload just looks like a literal (and invalid) filename.");
}

// ─── PATH-05 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 05' && $current_lab['type'] === 'vulnerable') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-05', 'path-05');
    // VULNERABLE: only checks that the path STARTS WITH the allowed prefix
    $allowed_prefix = '/var/www/images/';
    if (strpos($file, $allowed_prefix) !== 0) { path_lab_blocked("Rejected — path must start with <code>$allowed_prefix</code>"); }
    $rel = substr($file, strlen($allowed_prefix));
    if (strpos($rel, '..') !== false) {
        path_lab_leaked($GLOBALS['LEAKED_CONFIG'], "The check only verified the path <em>starts with</em> <code>$allowed_prefix</code> — it never re-validated what came after. <code>../../../config.txt</code> still starts with the allowed prefix, but walks right back out of it.");
    }
    $safe = basename($rel);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}
// ─── PATH-05 SECURE ─────────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 05' && $current_lab['type'] === 'secure') {
    if ($file === '' || $file === 'headphones.jpg') path_lab_landing('PATH-05 Secure', 'path-05-secure');
    $allowed_prefix = '/var/www/images/';
    if (strpos($file, $allowed_prefix) !== 0) { path_lab_blocked("Rejected — path must start with <code>$allowed_prefix</code>"); }
    $rel = substr($file, strlen($allowed_prefix));
    if (strpos($rel, '..') !== false) { path_lab_blocked("Rejected — the remainder after the prefix still contains '..', so this is blocked even though the prefix matched."); }
    $safe = basename($rel);
    $target = __DIR__ . '/images/' . $safe;
    if ($safe && file_exists($target)) { header('Content-Type: ' . (mime_content_type($target) ?: 'image/jpeg')); readfile($target); exit(); }
    path_lab_blocked("File not found.");
}

// ─── PATH-06 VULNERABLE ────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 06' && $current_lab['type'] === 'vulnerable') {

    // Landing page — show product image + instructions
    if ($file === '' || $file === 'headphones.jpg') {
        ?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>PATH-06 | Image Viewer</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Poppins',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;}.wrap{max-width:860px;margin:40px auto;padding:0 24px;}.card{background:#1e293b;border:1px solid #334155;border-radius:16px;overflow:hidden;}.card-top{padding:24px 28px;border-bottom:1px solid #334155;}.card-top h2{font-size:18px;color:#f8fafc;margin-bottom:6px;}.card-top p{font-size:13px;color:#64748b;line-height:1.6;}.card-body{padding:28px;}img.preview{width:100%;max-height:320px;object-fit:cover;border-radius:10px;border:1px solid #334155;display:block;margin-bottom:24px;}.url-bar{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px 16px;font-family:'Share Tech Mono',monospace;font-size:13px;color:#94a3b8;margin-bottom:8px;word-break:break-all;}.url-bar span{color:#38bdf8;}.hint-box{background:rgba(255,149,0,0.06);border:1px solid rgba(255,149,0,0.25);border-radius:8px;padding:16px 20px;margin-top:20px;}.hint-box p{font-size:13px;color:#fb923c;line-height:1.7;}code{background:#0f172a;padding:2px 6px;border-radius:4px;font-family:'Share Tech Mono',monospace;font-size:12px;}</style>
</head><body>
<?php render_lab_banner(); ?>
<div class="wrap"><div class="card">
  <div class="card-top"><h2>🖼️ Product Image Viewer</h2><p>This endpoint serves product images. It accepts a <code>file</code> parameter and only allows <strong>.jpg</strong> files.</p></div>
  <div class="card-body">
    <img class="preview" src="images/headphones.jpg" alt="Headphones">
    <div class="url-bar">Current URL: <span>image.php?lab=path-06&file=<strong>headphones.jpg</strong></span></div>
    <div class="hint-box"><p>🎯 <strong>Goal:</strong> Read the server's <code>config.txt</code> file — but the server only allows <code>.jpg</code> extensions.<br><br>Try manipulating the <code>file</code> parameter in the URL. Think about how you can make the server <em>think</em> the file ends with <code>.jpg</code> while actually opening a different file.</p></div>
  </div>
</div></div></body></html><?php
        exit();
    }

    // VULNERABLE check: strpos — bypassable with null byte
    if (strpos($file, '.jpg') === false) {
        ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Blocked</title><link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet"></head>
<body style="background:#0f172a;color:#e2e8f0;font-family:'Share Tech Mono',monospace;margin:0;padding:0;">
<?php render_lab_banner(); ?>
<div style="padding:40px;"><h2 style="color:#f87171;margin-bottom:16px;">⛔ Invalid File Type</h2>
<p style="color:#94a3b8;">Only <strong>.jpg</strong> files are allowed.</p>
<p style="color:#475569;margin-top:12px;font-size:13px;">Your input: <code style="color:#f87171;"><?php echo htmlspecialchars($file); ?></code></p>
</div></body></html><?php
        exit();
    }

    // Build path — vulnerable to null byte
    $base   = __DIR__ . '/images/';
    $target = $base . $file;

    // Emulate null-byte truncation
    $null_pos = strpos($target, "\0");
    if ($null_pos !== false) {
        $target = substr($target, 0, $null_pos);
    }

    // Serve real image if it actually exists
    if (file_exists($target) && in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif'])) {
        $mime = mime_content_type($target) ?: 'image/jpeg';
        header("Content-Type: $mime");
        readfile($target);
        exit();
    }

    // Traversal succeeded — mark lab solved and show "leaked" config
    mark_lab_solved();
    ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>File Read</title><link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet"></head>
<body style="background:#0f172a;color:#e2e8f0;font-family:'Share Tech Mono',monospace;margin:0;padding:0;">
<?php render_lab_banner(); ?>
<div style="padding:40px;">
<h2 style="color:#ffd60a;margin-bottom:16px;">📄 File Read via Null Byte Bypass</h2>
<pre style="background:#1e293b;padding:24px;border-radius:12px;border:1px solid #334155;color:#4ade80;line-height:1.8;"># /var/www/config.txt
DB_HOST     = localhost
DB_NAME     = 0xlabs_db
DB_USER     = root
DB_PASS     = r00tP@ssw0rd!
SECRET_KEY  = 8f3a9c2d1e7b...
APP_ENV     = production
ADMIN_TOKEN = eyJhbGci...truncated</pre>
<p style="color:#94a3b8;margin-top:20px;font-size:13px;">
✅ <strong>Null byte bypass worked!</strong><br>
The server checked that your input contained <code>.jpg</code> — it did.<br>
But PHP stopped reading at the null byte <code style="color:#ff3e6c;">\0</code>, so it actually opened <code>config.txt</code>.
</p></div></body></html><?php
    exit();
}

// ─── PATH-06 SECURE ───────────────────────────────────────────────────────
if ($current_lab && $current_lab['id'] === 'PATH — 06' && $current_lab['type'] === 'secure') {

    // Landing page
    if ($file === '' || $file === 'headphones.jpg') {
        ?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>PATH-06 Secure | Image Viewer</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Poppins',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;}.wrap{max-width:860px;margin:40px auto;padding:0 24px;}.card{background:#1e293b;border:1px solid #334155;border-radius:16px;overflow:hidden;}.card-top{padding:24px 28px;border-bottom:1px solid #334155;}.card-top h2{font-size:18px;color:#f8fafc;margin-bottom:6px;}.card-top p{font-size:13px;color:#64748b;line-height:1.6;}.card-body{padding:28px;}img.preview{width:100%;max-height:320px;object-fit:cover;border-radius:10px;border:1px solid #334155;display:block;margin-bottom:24px;}.url-bar{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px 16px;font-family:'Share Tech Mono',monospace;font-size:13px;color:#94a3b8;margin-bottom:8px;}code{background:#0f172a;padding:2px 6px;border-radius:4px;font-family:'Share Tech Mono',monospace;font-size:12px;}.secure-box{background:rgba(0,255,136,0.05);border:1px solid rgba(0,255,136,0.2);border-radius:8px;padding:16px 20px;margin-top:20px;}.secure-box p{font-size:13px;color:#4ade80;line-height:1.7;}</style>
</head><body>
<?php render_lab_banner(); ?>
<div class="wrap"><div class="card">
  <div class="card-top"><h2>🖼️ Product Image Viewer — Secure</h2><p>This version strips null bytes before validation and uses <code>pathinfo()</code> to check the real extension.</p></div>
  <div class="card-body">
    <img class="preview" src="images/headphones.jpg" alt="Headphones">
    <div class="url-bar">Current URL: image.php?lab=path-06-secure&file=<strong>headphones.jpg</strong></div>
    <div class="secure-box"><p>🛡️ <strong>Try the bypass:</strong> Change the URL to <code>?file=../../../config.txt%00.jpg</code><br><br>The server strips null bytes first, then validates the real extension — the attack is blocked.</p></div>
  </div>
</div></div></body></html><?php
        exit();
    }

    // STEP 1: strip null bytes
    $file = str_replace("\0", '', $file);

    // STEP 2: check real extension
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'jpg' && $ext !== 'jpeg') {
        ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Blocked</title><link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet"></head>
<body style="background:#0f172a;color:#e2e8f0;font-family:'Share Tech Mono',monospace;margin:0;padding:0;">
<?php render_lab_banner(); ?>
<div style="padding:40px;">
<h2 style="color:#4ade80;margin-bottom:16px;">✅ Attack Blocked</h2>
<pre style="background:#1e293b;padding:24px;border-radius:12px;border:1px solid #334155;color:#94a3b8;line-height:1.8;">// Step 1 — Strip null bytes first
$file = str_replace("\0", '', $file);
// "config.txt\0.jpg"  →  "config.txt"

// Step 2 — Check real extension
$ext = pathinfo($file, PATHINFO_EXTENSION); // "txt"
if ($ext !== 'jpg') exit("Blocked"); // ✗ rejected

// Step 3 — basename() blocks traversal
$safe = basename($file);</pre>
<p style="color:#94a3b8;margin-top:20px;font-size:13px;">
After stripping the null byte → <code><?php echo htmlspecialchars($file); ?></code><br>
Real extension: <code style="color:#f87171;"><?php echo htmlspecialchars($ext ?: 'none'); ?></code> — not a .jpg ✗
</p></div></body></html><?php
        exit();
    }

    // STEP 3: basename() strips traversal
    $safe_file = basename($file);
    $path      = __DIR__ . '/images/' . $safe_file;
    if ($safe_file && file_exists($path)) {
        $mime = mime_content_type($path) ?: 'image/jpeg';
        header("Content-Type: $mime");
        readfile($path);
    } else {
        http_response_code(404); echo "Image not found.";
    }
    exit();
}

// ─── DEFAULT ──────────────────────────────────────────────────────────────
$safe_file = basename($file);
$path      = __DIR__ . '/images/' . $safe_file;
if ($safe_file && file_exists($path)) {
    $mime = mime_content_type($path) ?: 'image/jpeg';
    header("Content-Type: $mime");
    readfile($path);
} else {
    http_response_code(404); echo "Image not found.";
}
