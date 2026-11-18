# Woodex OS Implementation: Staging Blackibex Net (Hostinger)

## 1. Implementation Overview
Following the approval, this document outlines the **actual implementation steps** to resolve the **403 Forbidden** error and complete the deployment on `https://staging.blackibex.net/` using the **Hostinger Business Shared Plan** and **GitHub Connection**.

## 2. Step-by-Step Implementation (SSH & File Manager)

### A. Resolve 403 Forbidden (File Manager / SSH)
1. **Access File Manager** (staging.blackibex.net):
   - Navigate to `domains/staging.blackibex.net/public_html/`.
   - **Create/Edit .htaccess** (for React Frontend):
     ```apache
     <IfModule mod_rewrite.c>
         RewriteEngine On
         RewriteBase /
         RewriteCond %{REQUEST_FILENAME} !-f
         RewriteCond %{REQUEST_FILENAME} !-d
         RewriteRule . /index.html [L]
     </IfModule>
     ```
   - **Check Permissions**: Ensure `public_html/` is `755` and files are `644`.

2. **Backend Public Root Link (SSH)**:
   ```bash
   ssh user@srv2119.hstgr.io "
     # Ensure laravel/public is linked to public_html/ for API subdomain or path
     ln -sf /domains/staging.blackibex.net/laravel/public /domains/staging.blackibex.net/public_html/api
     # Create .htaccess in public_html/api/
     cat << 'EOF' > /domains/staging.blackibex.net/public_html/api/.htaccess
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
     EOF
   "
   ```

### B. Frontend Implementation (React + Tailwind v4)
1. **Build Locally (in workspace)**:
   ```bash
   cd frontend
   npm install
   npm run build  # Generates dist/
   ```
2. **Upload to Hostinger (SCP)**:
   ```bash
   scp -r frontend/dist/* user@srv2119.hstgr.io:/domains/staging.blackibex.net/public_html/
   ```
3. **Environment Configuration**:
   - Ensure `VITE_API_URL=https://api.staging.blackibex.net` is set in `frontend/dist/.env.production` (built into JS).

### C. Backend Implementation (Laravel + MariaDB 10.5)
1. **Upload Code (SCP)**:
   ```bash
   scp -r backend/ user@srv2119.hstgr.io:/domains/staging.blackibex.net/laravel/
   ```
2. **SSH Commands (Composer & Artisan)**:
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
3. **Database Import (MariaDB 10.5)**:
   - Host: `srv2119.hstgr.io`, Port: `3306`
   - Source: `synthetic-mariadb-10-5-29-schema` (from GitHub Actions Run 44159442772)
   ```bash
   ssh user@srv2119.hstgr.io "
     mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < /path/to/synthetic-mariadb-10-5-29-schema.sql
   "
   ```

## 3. Verification of Implementation
- **Frontend**: Access `https://staging.blackibex.net/` (should show React Leads Index page from `frontend/src/pages/LeadsIndex.tsx`).
- **Backend API**: Access `https://api.staging.blackibex.net/api/v1/leads` (should return JSON from `backend/app/Http/Controllers/Api/ProductController.php` / `LeadsIndex.php`).
- **CORS**: Ensure `https://staging.blackibex.net` is allowed in Laravel `config/cors.php`.

## 4. Status Confirmation
- **Branch**: `arena/8aef18d3-furniture-os` (Active)
- **Target**: `https://staging.blackibex.net/` (Corrected)
- **Deployment Status**: **Implementation Started** (Commands provided above)
- **Database Status**: **MariaDB 10.5 Schema Ready** (from GitHub Actions Run 44159442772)

## 5. Commands Summary for Immediate Execution
```bash
# 1. Frontend Build (Local Workspace)
cd /home/user/furniture-os/frontend
npm install
npm run build

# 2. Upload & Link (SSH to srv2119.hstgr.io)
# (Replace user, password, and paths with actual Hostinger credentials)
scp -r /home/user/furniture-os/frontend/dist/* user@srv2119.hstgr.io:/domains/staging.blackibex.net/public_html/
scp -r /home/user/furniture-os/backend/ user@srv2119.hstgr.io:/domains/staging.blackibex.net/laravel/

ssh user@srv2119.hstgr.io "
  # Fix 403: Link public root and create .htaccess
  ln -sf /domains/staging.blackibex.net/laravel/public /domains/staging.blackibex.net/public_html/api
  cat << 'EOF' > /domains/staging.blackibex.net/public_html/api/.htaccess
  <IfModule mod_rewrite.c>
      <IfModule mod_negotiation.c>Options -MultiViews -Indexes</IfModule>
      RewriteEngine On
      RewriteCond %{HTTP:Authorization} .
      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:AUTHORIZATION}]
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^ index.php [L]
  </IfModule>
  EOF

  # Backend Setup
  cd /domains/staging.blackibex.net/laravel
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan storage:link
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  # Database Import (MariaDB 10.5)
  mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < /path/to/synthetic-mariadb-10-5-29-schema.sql
"
```

## 6. Final Status
- **Branch**: `arena/8aef18d3-furniture-os` (Active)
- **Target**: `https://staging.blackibex.net/` (Implementation Started)
- **Deployment Status**: **Commands Provided & Ready for Execution**
- **Database Status**: **MariaDB 10.5 Schema Ready**

---
*Implementation plan is now complete and ready for execution on the Hostinger server via SSH/File Manager. The 403 error will be resolved by the .htaccess and document root linking steps.*
