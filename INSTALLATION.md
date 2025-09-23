# NESRAH GROUP Management System - Installation Guide

## Quick Setup Instructions

### 1. Database Setup
1. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
2. Click on **"Import"** tab
3. Click **"Choose File"** and select: `database/nesrah_database.sql`
4. Click **"Go"** to import the database
5. This will create the `nesrah_group` database with all required tables

### 2. Access the System
1. Open your web browser
2. Navigate to: `http://localhost/nesrah`
3. You will be redirected to the login page

### 3. Default Login Credentials

#### Administrator Account
- **Username:** `admin`
- **Email:** `admin@nesrahgroup.com`
- **Password:** `password`
- **Role:** Administrator

### 4. First Time Setup
1. Login with the admin credentials above
2. Go to **Settings** to update company information
3. Add inventory items in **Inventory** section
4. Approve employee registrations in **Employees** section
5. Allocate stock to employees in **Stock Allocations**

## Troubleshooting

### If you get path errors:
- Make sure you're accessing via `http://localhost/nesrah` (not file://)
- Ensure XAMPP is running (Apache and MySQL)
- Check that all files are in the correct directory structure

### If database connection fails:
- Verify MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure the `nesrah_group` database exists

### If pages show errors:
- Check PHP error logs in XAMPP
- Ensure all file permissions are correct
- Verify all files are present in the directory

## System Requirements
- **XAMPP** (Apache, MySQL, PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- **Modern web browser**

## File Structure
```
nesrah/
├── auth/           # Login/Register pages
├── config/         # Configuration files
├── database/       # Database setup files
├── includes/       # Common header/footer
├── css/           # Stylesheets
├── js/            # JavaScript files
├── images/        # Images and assets
└── *.php          # Main application files
```

## Support
If you encounter any issues, check the error messages and ensure all requirements are met.
