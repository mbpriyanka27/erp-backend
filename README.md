# ERP Event Management System — Day 1

Fresh MySQL schema + seed data + token-based PHP REST API auth endpoints.
(Option B: PHP REST API backend, Flutter-only frontend, no sessions/HTML pages.)

## 1. Folder structure
```
erp_backend/
  config/
    database.php        # PDO connection
  helpers/
    response.php         # JSON response envelope + CORS
    auth.php              # token issue/verify
  api/
    auth/
      login.php           # POST /api/auth/login
      me.php              # GET  /api/auth/me
  database/
    schema.sql            # 10-table schema
    seed.php              # CLI seeder (uses password_hash())
  README.md
```

## 2. Setup with XAMPP/WAMP
1. Copy the `erp_backend` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start Apache + MySQL from the XAMPP/WAMP control panel.
3. Import the schema:
   ```
   mysql -u root -p < database/schema.sql
   ```
   (no password by default on a stock XAMPP install — just press Enter)
4. Seed demo data. This must be run with the PHP CLI (not the browser),
   because it uses `password_hash()` to generate real bcrypt hashes:
   ```
   php database/seed.php
   ```
   Every seeded user shares the password `Password@123`. Login emails:
   `student@erp.test`, `faculty@erp.test`, `coordinator@erp.test`,
   `hod@erp.test`, `principal@erp.test`, `director@erp.test`,
   `vc@erp.test`, `admin@erp.test`.

## 3. Test the endpoints

**Login:**
```
curl -X POST http://localhost/erp_backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"hod@erp.test","password":"Password@123"}'
```
Returns a `token` — copy it for the next call.

**Get current user (token-protected):**
```
curl http://localhost/erp_backend/api/auth/me.php \
  -H "Authorization: Bearer <paste token here>"
```

## 4. Notes / decisions baked into Day 1
- Auth is **token-based only** — no `session_start()`, no cookies. Tokens
  live in the `auth_tokens` table with a 7-day expiry (`helpers/auth.php`).
- `login.php` returns the same error for "no such email" and "wrong
  password" so the API doesn't leak which accounts exist.
- `event_requests` uses the single-table + `current_status` /
  `current_approver_role_id` design (no separate workflow-engine tables),
  per the locked architecture decision. The two approval chains
  (department vs. university) will be hardcoded as PHP arrays in Day 4's
  approval engine — not part of the schema.
- `audit_logs` already gets a row on every login; the same pattern will
  extend to submit/approve/reject actions from Day 3 onward.
- All endpoints are plain `.php` files under `api/...` — no router/
  `.htaccess` rewriting yet, so Flutter will call
  `http://<host>/erp_backend/api/auth/login.php` directly. If you'd
  rather have clean URLs like `/api/auth/login` without the `.php`,
  say so and I'll add an `.htaccess` rewrite rule next.

## 5. Next up — Day 2
Flutter project setup: folder structure, HTTP service layer, `User` /
`EventRequest` / `ApprovalHistory` models, login screen, and role-based
home routing that calls these two endpoints.
