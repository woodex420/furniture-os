# Complete Deployment Plan for staging.blackibex.net (Woodex OS)

## 1. Correction: Target Domain is staging.blackibex.net
- **Correction**: Deployment is **NOT** for `woodex.com.pk` at this stage.
- **Target**: `https://staging.blackibex.net/` (Hostinger Business Shared Plan, connected via GitHub Actions).
- **Status**: Currently **403 Forbidden** (Access Denied).
- **Goal**: Resolve 403, complete deployment of Frontend (React + Tailwind v4) and Backend (Laravel + MariaDB 10.5) on `staging.blackibex.net`.

## 2. Resolving 403 Forbidden on staging.blackibex.net
The 403 error indicates the document root or `.htaccess` is misconfigured for the staging subdomain.

### A. Document Root Configuration
- **Frontend Location**: `domains/staging.blackibex.net/public_html/` (for React `dist/`)
- **Backend Location**: `domains/staging.blackibex.net/laravel/` (App) & `public_html/` (Public Root linked to `laravel/public`)

### B. .htaccess Fixes (Critical for 403)
1. **For React Frontend (if staging is frontend)**:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.html [L]
   </IfModule>
   ```
2. **For Laravel Backend (if staging is API)**:
   ```apache
   <IfModule mod_rewrite.c>
       <IfModule mod_negotiation.c>
           Options -MultiViews -Indexes
       </IfModule>
       RewriteEngine On
       RewriteCond %{HTTP:Authorization} .
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:AUTHORIZATION}]
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule ^ index.php [L]
   </IfModule>
   ```

## 3. Complete Deployment Plan (SSH Commands)

### Step 1: Frontend Build & Upload (staging.blackibex.net/frontend)
1. **Build Locally**:
   ```bash
   cd frontend
   npm install
   npm run build  # Generates dist/
   ```
2. **Upload to Hostinger**:
   ```bash
   # Assuming staging.blackibex.net points to domains/staging.blackibex.net/public_html/
   scp -r frontend/dist/* user@srv2119.hstgr.io:/domains/staging.blackibex.net/public_html/
   ```
3. **Configure .env.production**:
   ```env
   VITE_API_URL=https://api.staging.blackibex.net
   ```

### Step 2: Backend Deployment (staging.blackibex.net/api)
1. **Upload Code**:
   ```bash
   scp -r backend/ user@srv2119.hstgr.io:/domains/staging.blackibex.net/laravel/
   # Link public_html/ to laravel/public
   ssh user@srv2119.hstgr.io "ln -s /domains/staging.blackibex.net/laravel/public /domains/staging.blackibex.net/public_html"
   ```
2. **SSH Commands (MariaDB 10.5)**:
   ```bash
   ssh user@srv2119.hstgr.io "
     cd /domains/staging.blackibex.net/laravel
     composer install --no-dev --optimize-autoloader
     php artisan migrate --force
     php artisan storage:link
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
   "
   ```
3. **Database Import**:
   ```bash
   ssh user@srv2119.hstgr.io "
     mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < /path/to/synthetic-mariadb-10-5-29-schema.sql
   "
   ```

## 4. Verification of staging.blackibex.net
- **Frontend**: Access `https://staging.blackibex.net/` (should show React Leads Index).
- **Backend API**: Access `https://api.staging.blackibex.net/api/v1/leads` (should return JSON).
- **CORS**: Ensure `https://staging.blackibex.net` is allowed in Laravel config.

## 5. Commands to Start Complete Deployment (Summary)
```bash
# 1. Frontend Build
cd frontend && npm install && npm run build

# 2. Upload & Link (SSH)
scp -r frontend/dist/* user@srv2119.hstgr.io:/domains/staging.blackibex.net/public_html/
scp -r backend/ user@srv2119.hstgr.io:/domains/staging.blackibex.net/laravel/
ssh user@srv2119.hstgr.io "ln -s /domains/staging.blackibex.net/laravel/public /domains/staging.blackibex.net/public_html"

# 3. Backend Setup (SSH)
ssh user@srv2119.hstgr.io "
  cd /domains/staging.blackibex.net/laravel
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan storage:link
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
"

# 4. Database Import (SSH)
ssh user@srv2119.hstgr.io "
  mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < /path/to/synthetic-mariadb-10-5-29-schema.sql
"
```

## 6. Status Confirmation
- **Branch**: `arena/8aef18d3-furniture-os` (Active)
- **Target**: `https://staging.blackibex.net/` (Corrected from woodex.com.pk)
- **Deployment Status**: **Ready for SSH Execution** (403 fix included)
- **Database Status**: **MariaDB 10.5 Schema Ready** (from GitHub Actions Run 44159442772)

---
*Note: The deployment plan is now focused exclusively on `staging.blackibex.net`. The 403 error will be resolved by applying the .htaccess fixes and linking the public root correctly during the SSH upload phase.*
