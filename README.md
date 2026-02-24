# Local Development Installation
This is a Laravel project that can be set up using various local development environments.

## Option 1: Laravel Sail (Docker) - Recommended:
Laravel Sail provides a Docker-powered development environment with all dependencies included.

### Prerequisites:
- **Docker Desktop** and **Git** installed and running  
-  or alternatively use **WSL** with the following:  
    - PHP (with required extensions to run Laravel Artisan commands)  
    - Composer  
    - Git  
    - Docker

### 1. Clone the repository
```bash
git clone https://github.com/lowildlr10/pims-api
cd pims-api
```

### 2. Install dependencies
```bash
composer install
```

### 3. Configure Environment & Generate App Key
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Launch Laravel Sail (Docker)
```bash
./vendor/bin/sail up -d
```

### 5. Run Database Migrations & Seeders
```
./vendor/bin/sail artisan migrate:fresh --seed
```
or import the existing database

### 6. Cache Configuration
```
./vendor/bin/sail artisan config:cache
```

### 7. Access the application

Application: http://localhost:8000
Database: localhost:5432 (if PostgreSQL is configured in docker-compose.yml)

### Useful Sail Commands
```bash
# Stop containers
./vendor/bin/sail down

# View logs
./vendor/bin/sail logs

# Run artisan commands
./vendor/bin/sail artisan [command]

# Run composer commands
./vendor/bin/sail composer [command]
```

## Option 2: Laravel Herd (macOS/Windows)
Laravel Herd is a native Laravel development environment that requires minimal configuration.

### Prerequisites:
- Laravel Herd installed (herd.laravel.com)
- Composer installed globally

### 1. Clone the repository
```bash
git clone https://github.com/lowildlr10/pims-api
cd pims-api
```
### 2. Install dependencies
```bash
composer install
```
### 3. Configure Environment & Generate App Key
```bash
cp .env.example .env
php artisan key:generate
```
### 4. Configure Database
Update your .env file with database credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432        # Update if your PostgreSQL server uses a custom port
DB_DATABASE=pims_db # Replace with the name of your database
DB_USERNAME=root    # Replace with your database username
DB_PASSWORD=password # Replace with your database password
```
### 5. Create Database
Open Herd and create a new database named pims_api, or
Use your preferred database management tool
### 6. Run Database Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```
or import the existing database
### 7. Cache Configuration
```bash
php artisan config:cache
```
### 8. Access the application

Herd automatically serves your project at: http://pims-api.test

## Option 3: Use Laragon, XAMPP, WAMP, and other similar applications
### 1. Clone the repository
### 2. Install dependencies
### 3. Configure Environment & Generate App Key
### 4. Configure Database
### 5. Create Database
### 6. Run Database Migrations & Seeders or Import the Existing Database
### 7. Cache Configuration
### 8. Access the application
```example
http://localhost/pims-api/public
```

## Recommended Tools
- **Database Management:** TablePlus, DBeaver.
- **API Testing:** Postman, Insomnia, Bruno
- **Code Editor:** VS Code, PhpStorm
