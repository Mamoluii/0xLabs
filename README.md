# 🛍️ 0xLabs — Web Security Labs

A deliberately vulnerable e-commerce web app (**ByteStore**) built to practice web security concepts hands-on. Every lab ships **two versions side by side** — a `vulnerable` one and a `secure` one — so you can run the same exploit against both and see exactly what the fix changes.


---

## 🚀 Setup

1. Install [XAMPP](https://www.apachefriends.org/) (or any PHP + MySQL stack). Make sure the **mbstring** PHP extension is enabled.
2. Copy the whole `bytestore/` folder into `htdocs/` (XAMPP) or `www/` (WAMP).
3. Start **Apache** and **MySQL** from the XAMPP control panel.
4. Open `http://localhost/bytestore/login.php` in your browser.

That's it — `db.php` creates the database, tables, and default users automatically on first load. You do **not** need to import `database.sql` manually (it's kept only as a reference schema).

### Login credentials

| Username      | Password | Role  | Balance  |
|---------------|----------|-------|----------|
| administrator | admin123 | Admin | $9999.99 |
| wiener        | peter    | User  | $1500.00 |
| carlos        | montoya  | User  | $1500.00 |
| leath         | 123456   | User  | $1500.00 |

---

## 🧪 How labs work

Every page checks a `?lab=` query parameter the first time you load it and stores it in your session — after that, the whole app behaves according to that lab until you switch. Open `index.html` for a full menu of links, or jump straight to a lab:

```
http://localhost/bytestore/login.php?lab=sql-01
http://localhost/bytestore/login.php?lab=sql-01-secure
```

Each lab page shows a banner with the lab name, its goal, and an expandable hint with the exact payload to try.

---

## 📋 Lab catalog (18 labs × vulnerable/secure = 36 scenarios)

### SQL Injection
| Lab | Title | Where |
|---|---|---|
| `sql-01` | Login Bypass | `login.php` |
| `sql-02` | WHERE Clause — Hidden Data | `products.php` (`category`) |
| `sql-03` | UNION — Number of Columns | `products.php` (`category`) |
| `sql-04` | UNION — Find Text Column | `products.php` (`category`) |
| `sql-05` | DB Type & Version — MySQL | `products.php` (`category`) |
| `sql-06` | UNION — Retrieve From Other Tables | `products.php` (`category`) |
| `sql-07` | UNION — Multiple Values in Single Column | `products.php` (`category`) |
| `sql-08` | Listing DB Contents — Non-Oracle | `products.php` (`category`) |
| `sql-09` | Blind SQLi — Conditional Responses | `products.php` (`TrackingId` cookie) |

Append `-secure` to any of these (e.g. `sql-06-secure`) for the prepared-statement version.

### Path Traversal
| Lab | Title | Technique |
|---|---|---|
| `path-01` | File Path Traversal — Simple Case | No filtering at all |
| `path-02` | Traversal — Absolute Path Bypass | Filter blocks `../` but trusts absolute paths |
| `path-03` | Traversal — Stripped Non-Recursively | `../` stripped only once, bypass with `....//` |
| `path-04` | Traversal — Superfluous URL-Decode | Input decoded twice, double-encoded payload slips through |
| `path-05` | Traversal — Validation of Start of Path | Only checks the path *starts with* an allowed prefix |
| `path-06` | Traversal — Null Byte File Extension Bypass | `%00` truncates the filename before the extension check |

All on `image.php?file=...`. `-secure` variants exist for every one of these.

### Information Disclosure
| Lab | Title | Where |
|---|---|---|
| `info-01` | Verbose Error Messages | `product_details.php?id=` |
| `info-02` | Exposed Debug Page | `/debug.php` |
| `info-03` | Leftover Backup Files | `login.php.bak`, `db.php.old` |

`-secure` variants exist for `info-01` and `info-02`. `info-03-secure` has no live demo — it's explained in the hint, since removing leaked backup files is a deployment/`.gitignore` fix, not something a session flag can toggle.

---

## 🗂️ Project structure

```
0xLabs-work/
├── index.html              ← Lab menu — links to every lab above
└── bytestore/
    ├── lab-config.php       ← Defines every lab (id, title, hint, type)
    ├── db.php                ← Creates DB/tables, seeds default users on first run
    ├── login.php             ← Login (SQL-01)
    ├── products.php          ← Catalog + category/cookie SQLi labs (SQL-02→09)
    ├── product_details.php   ← Single product view (INFO-01)
    ├── image.php             ← Product image endpoint (PATH-01→06)
    ├── debug.php              ← Leaked debug page (INFO-02)
    ├── login.php.bak, db.php.old  ← Leftover backup files (INFO-03)
    ├── cart.php               ← Shopping cart — trusts the client-submitted price
    ├── admin.php               ← Admin dashboard (admin role only)
    ├── order_success.php      ← Order confirmation
    ├── logout.php
    ├── images/                 ← Product photos
    └── config.txt              ← Fake secrets file — the target of the PATH-0x labs
```

---

## ⚠️ Other intentional weaknesses (not gated by `?lab=`)

These run the same way regardless of which lab you're in, since they're part of the base app:

- **`cart.php` — client-trusted price.** The price is sent from the browser as a hidden field; intercepting and changing it (e.g. with Burp Suite) lets you check out at any price you want.
- **Plaintext passwords.** Even the "secure" login (`sql-01-secure` onward) compares passwords in plaintext — there's no `password_hash()`/`password_verify()` anywhere. A real app should never store passwords this way.

---

## 🔄 Page flow

```
login.php → products.php → product_details.php → cart.php → order_success.php
                                                          ↑
                                                admin.php (admin role only)
```
