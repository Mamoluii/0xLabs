<?php
include('lab-config.php');
// INFO-02 lab: exposed debug page (secure version = page removed entirely)
if (!$current_lab || $current_lab['id'] !== 'INFO — 02' || $current_lab['type'] !== 'vulnerable') {
    http_response_code(404);
    exit("Not Found");
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Debug Info</title></head>
<body style="background:#0f172a;color:#e2e8f0;font-family:monospace;padding:0;margin:0;">
<?php render_lab_banner(); ?>
<div style="padding:40px;">
<h2 style="color:#ffd60a;margin-bottom:20px;">⚠ DEBUG PAGE — EXPOSED IN PRODUCTION</h2>
<pre style="background:#1e293b;padding:24px;border-radius:12px;border:1px solid #334155;line-height:1.8;">
DB_HOST     = localhost
DB_NAME     = 0xlabs_db
DB_USER     = root
DB_PASS     = root1234!
SECRET_KEY  = a3f9d2c1b7e4...
APP_ENV     = production
PHP_VERSION = <?php echo PHP_VERSION; ?>

SERVER_ADDR = 127.0.0.1
MAIL_PASS   = smtp_pass_leak!
ADMIN_HASH  = 5f4dcc3b5aa765d61d8327deb882cf99
</pre>
</div>
</body>
</html>
