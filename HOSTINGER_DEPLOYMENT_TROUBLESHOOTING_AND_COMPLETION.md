# Hostinger Deployment Troubleshooting & Completion Guide (Woodex OS)

## 1. Issue Analysis: 403 Forbidden on staging.blackibex.net
The **403 Forbidden** error on `https://staging.blackibex.net/` indicates that the server is denying access to the resource. This is common on Hostinger Shared Plans when:
- **Document Root Mismatch**: The domain points to a directory without an `index.php` or `index.html`, or the directory permissions are incorrect.
- **Missing .htaccess**: The Laravel public folder or React dist folder lacks the necessary rewrite rules.
- **Private App Path**: The Laravel application is outside the public root, but the public root is not correctly linked or the `.htaccess` in `public_html/` is missing.
- **Directory Indexing Disabled**: The server is configured to block directory listings.

### 2. Immediate Fix for Staging (blackibex.net)
Based on the **GitHub Actions Run 44159442772** and **Hostinger Business Plan**:
- **Frontend (woodex.com.pk)**: Ensure `frontend/dist/index.html` is uploaded to `domains/woodex.com.pk/public_html/`.
- **Backend (api.woodex.com.pk)**: Ensure `backend/public/index.php` is the entry point for `domains/api.woodex.com.pk/public_html/`.
- **.htaccess for Laravel (public_html/)**:
  ```apache
  <IfModule mod_rewrite.c>
      <IfModule mod_negotiation.c>
          Options -MultiViews -Indexes
      </IfModule>

      RewriteEngine On

      # Handle Authorization Header
      RewriteCond %{HTTP:Authorization} .
      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:AUTHORIZATION}]

      # Redirect Trailing Slashes If Not A Folder...
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^ index.php [L]
  </IfModule>
  ```
- **.htaccess for React (public_html/)**:
  ```apache
  <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /

      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.html [L]
  </IfModule>
  ```

### 3. Complete Deployment Steps (Hostinger Business Plan)

#### Step 1: Frontend Build & Upload (woodex.com.pk)
1. **Build**:
   ```bash
   cd frontend
   npm install
   npm run build  # Generates dist/
   ```
2. **Upload**:
   - Source: `frontend/dist/`
   - Destination: `domains/woodex.com.pk/public_html/`
3. **Configuration**:
   - `.env.production`: `VITE_API_URL=https://api.woodex.com.pk`
   - `.htaccess`: React Router support (already defined above)

#### Step 2: Backend Deployment (api.woodex.com.pk)
1. **Location**:
   - App: `domains/api.woodex.com.pk/laravel/`
   - Public Root: `domains/api.woodex.com.pk/public_html/` (pointing to `laravel/public`)
2. **Database (MariaDB 10.5)**:
   - Host: `srv2119.hstgr.io`
   - Port: `3306`
   - Driver: `mysql`
   - `.env`: Configured with database credentials.
3. **SSH Commands**:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. **CORS Configuration**:
   - Allow `https://woodex.com.pk` (or `https://app.woodex.com.pk`) in Laravel config.

#### Step 3: Database Import (MariaDB 10.5)
- **Source**: GitHub Actions Artifact `synthetic-mariadb-10-5-29-schema` (3.45 KB) or production SQL.
- **Destination**: `srv2119.hstgr.io:3306` -> `your_hostinger_database_name`.
- **Command (SSH)**:
  ```bash
  mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < synthetic-mariadb-10-5-29-schema.sql
  ```

### 4. Verification & Completion
- **Test Connectivity**: Ensure `https://woodex.com.pk` can reach `https://api.woodex.com.pk`.
- **Check Status**: The **403 Forbidden** should be resolved once the `.htaccess` and document root are correctly configured.
- **Final Report**: Update `COMPLETE_DEPLOYMENT_AND_DATABASE_REPORT.md` with the successful deployment status.

## 5. Commands to Start Complete Deployment (SSH)
```bash
# 1. Frontend Build (Local)
cd frontend
npm install
npm run build

# 2. Upload Frontend (SCP/FTP)
scp -r frontend/dist/* user@srv2119.hstgr.io:/domains/woodex.com.pk/public_html/

# 3. Upload Backend (SCP/FTP)
scp -r backend/ user@srv2119.hstgr.io:/domains/api.woodex.com.pk/laravel/
# Link public_html/ to laravel/public (via SSH)
ln -s /domains/api.woodex.com.pk/laravel/public /domains/api.woodex.com.pk/public_html

# 4. SSH Execution
ssh user@srv2119.hstgr.io "
  cd /domains/api.woodex.com.pk/laravel
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan storage:link
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
"

# 5. Database Import
ssh user@srv2119.hstgr.io "
  mysql -h srv2119.hstgr.io -u your_hostinger_database_user -p your_database_password < /path/to/synthetic-mariadb-10-5-29-schema.sql
"
```

## 6. Status Confirmation
- **Branch**: `arena/8aef18d3-furniture-os` (Active)
- **Commits**: 4 (Initial, Structure, Report, Frontend Implementation)
- **Deployment Status**: **Pending Final SSH Upload & .htaccess Fix**
- **Database Status**: **Schema Ready** (MariaDB 10.5)

---
*Note: The 403 Forbidden error on staging.blackibex.net is expected until the .htaccess and document root are correctly configured for the Hostinger Business plan. The steps above will resolve this and complete the deployment for woodex.com.pk and api.woodex.com.pk.*
