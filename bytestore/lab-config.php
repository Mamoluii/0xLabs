<?php
/**
 * Lab Config — reads ?lab=XXX from URL and stores in session
 * Every ByteStore page includes this file first.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ── Lab isolation: reset session when switching labs ─────────────────────────
if (isset($_GET['lab'])) {
    $incoming_lab = $_GET['lab'];
    $prev_lab = $_SESSION['active_lab'] ?? null;

    if ($incoming_lab !== $prev_lab) {
        // New lab started — destroy old session and start fresh
        session_destroy();
        session_start();
        $_SESSION['active_lab'] = $incoming_lab;
        $_SESSION['lab_fresh']  = true; // signal: needs login
    }
}

$lab_id = $_SESSION['active_lab'] ?? null;

// ── Lab definitions ─────────────────────────────────────────────────────────
$labs = [

    // SQL INJECTION
    'sql-01' => [
        'id'       => 'SQL — 01',
        'title'    => 'Login Bypass',
        'goal'     => "Log in as <code>administrator</code> without knowing the password using SQL injection.",
        'hint'     => "Try: <code>administrator'-- </code> in the username field. (note the space after --)",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'login',
    ],
    'sql-01-secure' => [
        'id'       => 'SQL — 01',
        'title'    => 'Login Bypass (Secure)',
        'goal'     => "This version uses prepared statements. SQL injection in the login form is blocked.",
        'hint'     => "Notice how <code>administrator'-- </code> no longer works.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'login',
    ],

    'sql-02' => [
        'id'       => 'SQL — 02',
        'title'    => 'WHERE Clause — Hidden Data',
        'goal'     => "Retrieve all products including hidden/unreleased ones using SQL injection in the category filter.",
        'hint'     => "Try: <code>?category=Gifts'+OR+1=1#</code> in the URL.",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-02-secure' => [
        'id'       => 'SQL — 02',
        'title'    => 'WHERE Clause — Hidden Data (Secure)',
        'goal'     => "This version uses prepared statements. Category injection is blocked.",
        'hint'     => "The OR 1=1 trick no longer reveals hidden products.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-03' => [
        'id'       => 'SQL — 03',
        'title'    => 'UNION — Number of Columns',
        'goal'     => "Determine the number of columns returned by the product query using UNION NULL probing.",
        'hint'     => "Try: <code>?category=Gifts' UNION SELECT NULL#</code> and add NULLs until no error.",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-03-secure' => [
        'id'       => 'SQL — 03',
        'title'    => 'UNION — Number of Columns (Secure)',
        'goal'     => "This version uses prepared statements. Column-count probing via UNION is blocked.",
        'hint'     => "The UNION SELECT NULL trick no longer works — the query is parameterized.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-04' => [
        'id'       => 'SQL — 04',
        'title'    => 'UNION — Find Text Column',
        'goal'     => "Identify which column accepts string data by injecting text values via UNION.",
        'hint'     => "Try: <code>?category=Gifts' UNION SELECT 'a',NULL,NULL#</code> and swap the 'a' position.",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-04-secure' => [
        'id'       => 'SQL — 04',
        'title'    => 'UNION — Find Text Column (Secure)',
        'goal'     => "This version uses prepared statements. UNION injection is blocked.",
        'hint'     => "Injecting text via UNION no longer reaches the database.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-05' => [
        'id'       => 'SQL — 05',
        'title'    => 'DB Type & Version — MySQL',
        'goal'     => "Retrieve the MySQL database version using UNION injection.",
        'hint'     => "Try: <code>?category=Gifts' UNION SELECT @@version,NULL,NULL#</code>",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-05-secure' => [
        'id'       => 'SQL — 05',
        'title'    => 'DB Type & Version — MySQL (Secure)',
        'goal'     => "This version uses prepared statements. @@version can no longer be extracted.",
        'hint'     => "UNION SELECT @@version no longer reaches the database.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-06' => [
        'id'       => 'SQL — 06',
        'title'    => 'UNION — Retrieve From Other Tables',
        'goal'     => "Extract usernames and passwords from the <code>users</code> table using UNION injection.",
        'hint'     => "Try: <code>?category=Gifts' UNION SELECT username,password,NULL FROM users#</code>",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-06-secure' => [
        'id'       => 'SQL — 06',
        'title'    => 'UNION — Retrieve From Other Tables (Secure)',
        'goal'     => "This version uses prepared statements. Cross-table UNION extraction is blocked.",
        'hint'     => "UNION SELECT username,password FROM users no longer reaches the database.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-07' => [
        'id'       => 'SQL — 07',
        'title'    => 'UNION — Multiple Values in Single Column',
        'goal'     => "Retrieve multiple values by concatenating them into a single column using UNION injection.",
        'hint'     => "Try: <code>?category=Gifts' UNION SELECT CONCAT(username,':',password),NULL,NULL FROM users#</code>",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-07-secure' => [
        'id'       => 'SQL — 07',
        'title'    => 'UNION — Multiple Values in Single Column (Secure)',
        'goal'     => "This version uses prepared statements. CONCAT-based UNION extraction is blocked.",
        'hint'     => "The CONCAT(username,':',password) trick no longer reaches the database.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-08' => [
        'id'       => 'SQL — 08',
        'title'    => 'Listing DB Contents — Non-Oracle',
        'goal'     => "Enumerate all table names then extract credentials from the users table.",
        'hint'     => "Start: <code>?category=Gifts' UNION SELECT table_name,NULL,NULL FROM information_schema.tables#</code>",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-08-secure' => [
        'id'       => 'SQL — 08',
        'title'    => 'Listing DB Contents — Non-Oracle (Secure)',
        'goal'     => "This version uses prepared statements. information_schema enumeration via UNION is blocked.",
        'hint'     => "UNION SELECT table_name FROM information_schema.tables no longer reaches the database.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'sql-09' => [
        'id'       => 'SQL — 09',
        'title'    => 'Blind SQLi — Conditional Responses',
        'goal'     => "Determine if the administrator password starts with 'a' using a cookie-based blind injection.",
        'hint'     => "The tracking cookie is vulnerable. Try: <code>TrackingId=&lt;your-id&gt;' AND (SELECT SUBSTRING(password,1,1) FROM users WHERE username='administrator')='a'-- </code>",
        'type'     => 'vulnerable',
        'color'    => '#ff3e6c',
        'page'     => 'products',
    ],
    'sql-09-secure' => [
        'id'       => 'SQL — 09',
        'title'    => 'Blind SQLi — Conditional Responses (Secure)',
        'goal'     => "This version uses a prepared statement for the tracking cookie lookup. Blind injection is blocked.",
        'hint'     => "Modifying the TrackingId cookie no longer changes the query logic — it's treated as a literal value.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    // PATH TRAVERSAL
    'path-01' => [
        'id'       => 'PATH — 01',
        'title'    => 'File Path Traversal — Simple Case',
        'goal'     => "Read the server's <code>config.txt</code> file by manipulating the image filename parameter.",
        'hint'     => "Try: <code>?file=../../../config.txt</code> on the image endpoint.",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
    'path-01-secure' => [
        'id'       => 'PATH — 01',
        'title'    => 'File Path Traversal — Simple Case (Secure)',
        'goal'     => "This version uses basename() to strip traversal sequences.",
        'hint'     => "Notice how ../ sequences are removed and only the filename is used.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'path-02' => [
        'id'       => 'PATH — 02',
        'title'    => 'Traversal — Absolute Path Bypass',
        'goal'     => "Bypass traversal blocking by supplying an absolute path like <code>/etc/passwd</code>.",
        'hint'     => "Try: <code>?file=/etc/passwd</code> directly instead of using ../",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
    'path-02-secure' => [
        'id'       => 'PATH — 02',
        'title'    => 'Traversal — Absolute Path Bypass (Secure)',
        'goal'     => "This version rejects both '..' sequences and any input starting with '/'.",
        'hint'     => "Notice how an absolute path is now rejected outright, not just '../' sequences.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'path-03' => [
        'id'       => 'PATH — 03',
        'title'    => 'Traversal — Stripped Non-Recursively',
        'goal'     => "Bypass a filter that strips ../ once by nesting sequences like <code>....//</code>.",
        'hint'     => "Try: <code>?file=....//....//....//config.txt</code>",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
    'path-03-secure' => [
        'id'       => 'PATH — 03',
        'title'    => 'Traversal — Stripped Non-Recursively (Secure)',
        'goal'     => "This version strips ../ in a loop until no more remain, so nested sequences can't survive.",
        'hint'     => "The ....// trick no longer reconstructs a ../ sequence.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'path-04' => [
        'id'       => 'PATH — 04',
        'title'    => 'Traversal — Superfluous URL-Decode',
        'goal'     => "Bypass a filter using double URL encoding so <code>%252e%252e%252f</code> decodes to <code>../</code> after the check.",
        'hint'     => "Try: <code>?file=%252e%252e%252f%252e%252e%252fconfig.txt</code>",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
    'path-04-secure' => [
        'id'       => 'PATH — 04',
        'title'    => 'Traversal — Superfluous URL-Decode (Secure)',
        'goal'     => "This version validates the value exactly as received, with no extra decode pass.",
        'hint'     => "Double-encoding no longer reveals a hidden ../ after validation.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'path-05' => [
        'id'       => 'PATH — 05',
        'title'    => 'Traversal — Validation of Start of Path',
        'goal'     => "Bypass a check that validates the path starts with <code>/var/www/images/</code>.",
        'hint'     => "Try: <code>?file=/var/www/images/../../../config.txt</code>",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
    'path-05-secure' => [
        'id'       => 'PATH — 05',
        'title'    => 'Traversal — Validation of Start of Path (Secure)',
        'goal'     => "This version validates the prefix AND rejects any remaining '..' after stripping it.",
        'hint'     => "The prefix check alone isn't enough — leftover ../ sequences are now also rejected.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'path-06' => [
        'id'       => 'PATH — 06',
        'title'    => 'Traversal — Null Byte File Extension Bypass',
        'goal'     => "Bypass a check that requires the filename to end with <code>.jpg</code> by injecting a null byte <code>%00</code> to truncate the extension check.",
        'hint'     => "Try: <code>?file=../../../config.txt%00.jpg</code> — the null byte tricks the validation but PHP reads only up to the null byte.",
        'type'     => 'vulnerable',
        'color'    => '#00ff88',
        'page'     => 'image',
    ],
    'path-06-secure' => [
        'id'       => 'PATH — 06',
        'title'    => 'Traversal — Null Byte Bypass (Secure)',
        'goal'     => "This version sanitizes null bytes and validates the real extension after stripping them. The bypass no longer works.",
        'hint'     => "Notice how <code>config.txt%00.jpg</code> is rejected — the server strips null bytes first then re-checks the extension.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'image',
    ],

    // INFO DISCLOSURE
    'info-01' => [
        'id'       => 'INFO — 01',
        'title'    => 'Information Disclosure — Error Messages',
        'goal'     => "Trigger a detailed error message that leaks the database structure and server info.",
        'hint'     => "Try entering a single quote <code>'</code> in the product ID parameter.",
        'type'     => 'vulnerable',
        'color'    => '#ffd60a',
        'page'     => 'product_details',
    ],
    'info-01-secure' => [
        'id'       => 'INFO — 01',
        'title'    => 'Information Disclosure — Error Messages (Secure)',
        'goal'     => "This version suppresses errors and shows a generic message instead.",
        'hint'     => "Notice how errors no longer reveal database or server details.",
        'type'     => 'secure',
        'color'    => '#ffd60a',
        'page'     => 'product_details',
    ],

    'info-02' => [
        'id'       => 'INFO — 02',
        'title'    => 'Information Disclosure — Debug Page',
        'goal'     => "Find an exposed debug page that leaks environment variables and credentials.",
        'hint'     => "Try visiting <code>/bytestore/phpinfo.php</code> or <code>/debug.php</code>",
        'type'     => 'vulnerable',
        'color'    => '#ffd60a',
        'page'     => 'products',
    ],
    'info-02-secure' => [
        'id'       => 'INFO — 02',
        'title'    => 'Information Disclosure — Debug Page (Secure)',
        'goal'     => "This version has the debug page removed from production entirely.",
        'hint'     => "Visiting <code>/debug.php</code> now returns a plain 404, regardless of the URL you try.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],

    'info-03' => [
        'id'       => 'INFO — 03',
        'title'    => 'Information Disclosure — Backup Files',
        'goal'     => "Find backup files accidentally left on the server that expose PHP source code.",
        'hint'     => "Try: <code>login.php.bak</code> or <code>db.php.old</code>",
        'type'     => 'vulnerable',
        'color'    => '#ffd60a',
        'page'     => 'products',
    ],
    'info-03-secure' => [
        'id'       => 'INFO — 03',
        'title'    => 'Information Disclosure — Backup Files (Secure)',
        'goal'     => "This is a deployment/config issue, not a code issue — the fix is process, not PHP.",
        'hint'     => "In a real deployment, .bak/.old files are excluded via .gitignore and a web-server rule (e.g. Apache's <code>&lt;FilesMatch \"\\.(bak|old)$\"&gt;deny&lt;/FilesMatch&gt;</code>) so they're never shipped or served — there's no per-request toggle to demo here.",
        'type'     => 'secure',
        'color'    => '#00ff88',
        'page'     => 'products',
    ],
];

// Current lab data
$current_lab = isset($lab_id) ? ($labs[$lab_id] ?? null) : null;

/**
 * Renders the lab banner shown at the top of every ByteStore page
 */
function mark_lab_solved() {
    $_SESSION['lab_solved'] = true;
}

function render_lab_banner() {
    global $current_lab, $lab_id;
    if (!$current_lab) return;

    $color    = $current_lab['color'];
    $type_label = $current_lab['type'] === 'secure' ? 'SECURE VERSION' : 'VULNERABLE VERSION';
    $type_bg    = $current_lab['type'] === 'secure'
        ? 'rgba(0,255,136,0.08)' : 'rgba(255,62,108,0.08)';
    $type_border = $current_lab['type'] === 'secure'
        ? 'rgba(0,255,136,0.3)' : 'rgba(255,62,108,0.3)';
    ?>
    <div id="lab-banner" style="
        background:#0a0d14;
        border-bottom:2px solid <?= $color ?>;
        padding:0;
        font-family:'Share Tech Mono',monospace;
        position:sticky;top:0;z-index:9999;
        box-shadow:0 4px 30px <?= $color ?>22;
    ">
        <!-- Top bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 28px;gap:16px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;">
                <a href="../index.html" style="color:#64748b;text-decoration:none;font-size:11px;letter-spacing:1px;">← 0xLabs</a>
                <span style="color:#1a2332;">|</span>
                <span style="font-size:11px;color:#64748b;letter-spacing:2px;"><?= htmlspecialchars($current_lab['id']) ?></span>
                <span style="font-size:13px;color:#e2e8f0;font-weight:600;letter-spacing:1px;"><?= htmlspecialchars($current_lab['title']) ?></span>
                <span style="font-size:9px;padding:2px 8px;border-radius:3px;letter-spacing:2px;background:<?= $type_bg ?>;color:<?= $color ?>;border:1px solid <?= $type_border ?>;"><?= $type_label ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <button onclick="document.getElementById('lab-details').style.display=document.getElementById('lab-details').style.display==='none'?'flex':'none'"
                    style="background:transparent;border:1px solid #1a2332;color:#64748b;font-size:11px;padding:5px 14px;border-radius:4px;cursor:pointer;font-family:'Share Tech Mono',monospace;letter-spacing:1px;">
                    HINT ▾
                </button>
                <a href="../index.html" style="background:<?= $color ?>;color:#000;font-size:11px;padding:5px 16px;border-radius:4px;text-decoration:none;letter-spacing:1px;font-weight:700;">
                    ← LABS
                </a>
            </div>
        </div>
        <!-- Expandable hint -->
        <div id="lab-details" style="display:none;padding:12px 28px 14px;border-top:1px solid #1a2332;gap:40px;flex-wrap:wrap;background:#070b14;">
            <div>
                <div style="font-size:9px;color:#64748b;letter-spacing:3px;margin-bottom:6px;">OBJECTIVE</div>
                <div style="font-size:12px;color:#e2e8f0;line-height:1.7;"><?= $current_lab['goal'] ?></div>
            </div>
            <div>
                <div style="font-size:9px;color:#64748b;letter-spacing:3px;margin-bottom:6px;">HINT</div>
                <div style="font-size:12px;color:#94a3b8;line-height:1.7;"><?= $current_lab['hint'] ?></div>
            </div>
        </div>
    </div>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <?php if (!empty($_SESSION['lab_solved'])): unset($_SESSION['lab_solved']); ?>
    <div onclick="this.remove()" style="
        position:fixed;top:0;left:0;right:0;bottom:0;
        background:rgba(0,0,0,0.75);
        display:flex;align-items:center;justify-content:center;
        z-index:999999;font-family:'Share Tech Mono',monospace;
        cursor:pointer;
    ">
        <div style="
            background:#0a1a0e;
            border:2px solid #00ff88;
            border-radius:20px;
            padding:48px 64px;
            text-align:center;
            box-shadow:0 0 60px rgba(0,255,136,0.3);
            max-width:480px;
        ">
            <div style="font-size:52px;margin-bottom:16px;">🎉</div>
            <div style="font-size:22px;color:#00ff88;font-weight:700;letter-spacing:3px;margin-bottom:12px;">LAB SOLVED</div>
            <div style="font-size:13px;color:#64748b;margin-bottom:28px;">Congratulations! You successfully exploited the vulnerability.</div>
            <div style="
                display:inline-block;
                background:rgba(0,255,136,0.1);
                border:1px solid rgba(0,255,136,0.3);
                color:#00ff88;
                font-size:11px;
                padding:8px 20px;
                border-radius:8px;
                letter-spacing:2px;
            ">CLICK ANYWHERE TO CONTINUE</div>
        </div>
    </div>
    <?php endif; ?>
    <?php
}
