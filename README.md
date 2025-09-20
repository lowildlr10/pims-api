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
