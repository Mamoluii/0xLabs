# 🛍️ 0xLabs — Web Security Project

## Project Structure

```
bytestore/
├── images/           ← Product images (copy your images here)
├── db.php            ← Database connection
├── login.php         ← Login page
├── logout.php        ← Logout
├── products.php      ← Product catalog (requires login)
├── product_details.php ← Single product view
├── cart.php          ← Shopping cart (⚠️ vulnerable: trusts client price)
├── order_success.php ← Order confirmation
├── admin.php         ← Admin dashboard (admin role only)
└── database.sql      ← DB setup script
```

## Setup Instructions

### 1. Database Setup
```sql
-- Run this in phpMyAdmin or MySQL CLI:
source database.sql
```

### 2. Server Setup
- Place the project folder inside: `htdocs/` (XAMPP) or `www/` (WAMP)
- Make sure the `images/` folder contains all product images

### 3. Login Credentials
| Username      | Password | Role    | Balance  |
|---------------|----------|---------|----------|
| administrator | admin123 | Admin   | $9999.99 |
| leath    | 123456   | User    | $1500.00 |
| wiener   | peter    | User    | $1500.00 |
| carlos   | montoya  | User    | $1500.00 |

---

## ⚠️ Security Vulnerabilities (Intentional — For Lab)

### 1. Business Logic — Excessive Trust in Client-Side Controls
**File:** `cart.php` & `product_details.php`

The product price is sent as a hidden field from the browser:
```html
<input type="hidden" name="price" value="1800.00">
```
An attacker can intercept this request (e.g., with Burp Suite) and change the price to `1`.

**How to exploit:**
1. Login as `leath`
2. Click "Add to Cart" on any product
3. Intercept the POST request with Burp Suite
4. Change `price=1800.00` → `price=1`
5. Forward — item is now in cart for $1!

### 2. SQL Injection — Login Bypass
**File:** `login.php`

The query is built by concatenating user input directly:
```php
$query = "SELECT * FROM users WHERE username='$user' AND password='$pass'";
```
**Payload:** Username: `admin' -- ` | Password: anything

### 3. No Admin Route Protection on Admin Page
**File:** `admin.php`

Admin check IS implemented in this version — as a secure comparison.
To see the vulnerable version: remove the role check at the top.

---

## Pages Flow
```
login.php → products.php → product_details.php → cart.php → order_success.php
                                                         ↑
                                               admin.php (admin only)
```
