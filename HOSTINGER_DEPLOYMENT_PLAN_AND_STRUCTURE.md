# furniture-os (Woodex OS) - Hostinger Deployment Plan & Project Structure

## 1. GitHub Connection & Branch Analysis
- **Current Branch**: `arena/8aef18d3-furniture-os` (branched from `63225f2` of `main`)
- **Origin**: `https://github.com/woodex420/furniture-os.git`
- **GitHub Actions Analysis**:
  - Run `44159442772` (Commit `c2ed8ad` on branch `arena/c3010afd-furniture-os`):
    - Status: **Success**
    - Artifacts: `furniture-staging-candidate-c2ed8ad...` (18.1 MB), SQL schemas for MariaDB 10.5.29 and MySQL 8.4.7.
    - Jobs: PHP/SQLite smoke, Asset build/preview, Storefront bundle, Laravel smoke, Astro prototype, Browser-test staging candidate.
  - **Commit `c2ed8ad` Content**:
    - Livewire Lead Status Filter implementation (`LeadsIndex.php`, `leads-index.blade.php`).
    - Synthetic lead insertion in CI tests.
    - Documentation updates (`FURNITURE-NEXT-WORK.md`, `FURNITURE-PRODUCTION-READINESS-TODO.md`).
  - **Commit `f4da41fb`**:
    - Documented first staging candidate (`FURNITURE-SAME-ORIGIN-STAGING-WORK.md`).
    - Updated package and manual database plan.
- **Branch Status**: The current branch `arena/8aef18d3-furniture-os` is currently at the **Initial Commit** (`63225f2`). The work from `c2ed8ad` and `f4da41fb` is on **other branches** (`arena/c3010afd-furniture-os`, etc.) and **not yet merged** into `arena/8aef18d3-furniture-os`.
- **Action Required**: We must organize the project structure and prepare for Hostinger deployment based on the provided stack (React + Tailwind v4 / Laravel / MariaDB).

## 2. Reimagined Project Structure (Hostinger Compatible)
Following the Hostinger recommendation and the GitHub Actions evidence:

```text
furniture-os/
├── frontend/                 # React + Tailwind v4
│   ├── public/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── layouts/
│   │   ├── hooks/
│   │   ├── services/         # API calls to api.woodex.com.pk
│   │   ├── stores/
│   │   ├── types/
│   │   └── App.tsx
│   ├── package.json
│   ├── vite.config.ts
│   └── tailwind.config.ts
│
├── backend/                  # Laravel API
│   ├── app/                  # Models, Controllers, Livewire (Workspace/LeadsIndex.php)
│   ├── bootstrap/
│   ├── config/
│   ├── database/             # Migrations (MariaDB 10.5 compatible)
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   ├── public/               # Web root for api.woodex.com.pk
│   ├── resources/            # Blade views (leads-index.blade.php)
│   ├── routes/
│   ├── storage/
│   ├── artisan
│   ├── composer.json
│   └── .env.example
│
├── docs/                     # Documentation (FURNITURE-NEXT-WORK.md, etc.)
├── scripts/                  # Deployment & Assembly (assemble-storefront-preview.mjs)
├── README.md
└── .gitignore
```

## 3. Deployment Plan on Hostinger (Business Shared Plan)

### A. Frontend (woodex.com.pk / app.woodex.com.pk)
1. **Build**:
   ```bash
   cd frontend
   npm install
   npm run build
   ```
2. **Upload**:
   - Source: `frontend/dist/`
   - Destination: `domains/woodex.com.pk/public_html/`
3. **Configuration**:
   - `.env.production`: `VITE_API_URL=https://api.woodex.com.pk`
   - `.htaccess` (for React Router):
     ```apache
     <IfModule mod_rewrite.c>
         RewriteEngine On
         RewriteBase /
         RewriteCond %{REQUEST_FILENAME} !-f
         RewriteCond %{REQUEST_FILENAME} !-d
         RewriteRule . /index.html [L]
     </IfModule>
     ```

### B. Backend (api.woodex.com.pk)
1. **Location**:
   - App: `domains/api.woodex.com.pk/laravel/`
   - Public Root: `domains/api.woodex.com.pk/public_html/` (pointing to `laravel/public`)
2. **Database (MariaDB 10.5)**:
   - Host: `srv2119.hstgr.io`
   - Port: `3306`
   - Driver: `mysql` (Laravel supports MariaDB via MySQL driver)
   - `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=srv2119.hstgr.io
     DB_PORT=3306
     DB_DATABASE=your_hostinger_database_name
     DB_USERNAME=your_hostinger_database_user
     DB_PASSWORD=your_database_password
     ```
3. **Deployment Commands (SSH)**:
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

## 4. Pending Tasks & Commands to Start
1. **Organize Structure**: Create `frontend/` and `backend/` folders in the current branch.
2. **Integrate GitHub Work**: Merge/Cherry-pick relevant changes from `arena/c3010afd-furniture-os` (Livewire Lead Filter, Docs) into `arena/8aef18d3-furniture-os`.
3. **Initialize Submodules/Folders**:
   - `frontend/`: React + Tailwind v4 setup.
   - `backend/`: Laravel setup with MariaDB support.
4. **Deployment Script**: Create a script to package the build for Hostinger (ZIP for backend, `dist/` for frontend).
5. **Testing**: Use `staging.blackibex.net` as a reference for initial testing before final Hostinger cutover.

## 5. Commands to Start Development & Deployment Preparation
```bash
# 1. Create project structure
mkdir -p frontend/src/{components,pages,layouts,hooks,services,stores,types} backend/app/Livewire/Workspace backend/database/migrations backend/public backend/resources/views/livewire/workspace
touch frontend/package.json frontend/vite.config.ts frontend/tailwind.config.ts backend/composer.json backend/artisan backend/.env.example

# 2. Initialize React (Tailwind v4)
cd frontend
npm init -y
npm install react react-dom tailwindcss@next postcss autoprefixer
# Configure Tailwind v4 and Vite...

# 3. Initialize Laravel
cd ../backend
composer create-project laravel/laravel . --prefer-dist
# Configure Livewire, MariaDB connection...

# 4. Link GitHub Actions (Hostinger Deployment)
# Create .github/workflows/hostinger-deploy.yml
```

## 6. Hostinger Testing Reference
- **Staging URL**: `https://staging.blackibex.net/` (Reference for testing before `woodex.com.pk`)
- **Verification**: Ensure `.htaccess` works, PHP 8.4/8.5 compatibility, and MariaDB 10.5 connectivity.

## 7. Summary of Reimagined Woodex OS Completion
- **Module API**: Laravel backend provides `/api/v1/...` endpoints (Products, Inventory, Leads, etc.).
- **Department Status**: Integrated via Livewire (Leads Index) and React frontend services.
- **Deployment**: Automated via GitHub Actions (Artifacts) -> Manual/SSH Upload to Hostinger.

---
*Note: The current branch `arena/8aef18d3-furniture-os` is currently empty (Initial Commit). The following steps will populate it with the structure and commands above.*
