## Set up your local development environment using Laravel Sail:

### 1. Clone the repository
```
git clone https://github.com/lowildlr10/pims-api
cd pims-api
```

### 2. Install dependencies
```
composer install
```

### 3. Configure Environment & Generate App Key
```
cp .env.example .env
php artisan key:generate
```

### 4. Launch Laravel Sail (Docker)
```
./vendor/bin/sail up -d
```

### 5. Run Database Migrations & Seeders
```
./vendor/bin/sail artisan migrate:fresh --seed
```

### 6. Cache Configuration
```
./vendor/bin/sail artisan config:cache
```

### 7. Install and Configure Supervisor for Required Queue Workers
Supervisor keeps your Laravel queue for `default` and `notification` workers running automatically.

...or simply run these commands in a separate terminal

```
./vendor/bin/sail artisan queue:work --tries=3
./vendor/bin/sail artisan queue:work --queue=notification --tries=3
```
