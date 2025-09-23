# NESRAH GROUP Management System

A comprehensive web-based business management platform designed to streamline operations for material business companies. The system provides complete solutions for inventory management, employee management, task tracking, attendance monitoring, and sales operations.

## Features

### 🔐 Authentication & User Management
- Secure login system with role-based access
- Employee self-registration with admin approval
- User roles: Admin and Employee
- Account status management (Pending, Active, Inactive)

### 📦 Inventory Management
- Complete item management (Add, Edit, Delete)
- Real-time stock quantity monitoring
- Stock movement tracking with history
- Item categorization and pricing management
- Low stock alerts and reorder levels
- Automatic item code generation

### 👥 Employee Management
- Employee registration and approval workflow
- Status management (Approve, Reject, Activate, Deactivate)
- Profile management and contact details
- Role-based access control

### 📊 Stock Allocation System
- Assign stock to specific employees
- Quantity tracking (Allocated vs Remaining)
- Complete audit trail of stock allocations
- Status management (Active, Completed, Cancelled)

### 📝 Stock Request System
- Employee stock request submission
- Admin approval/rejection workflow
- Detailed reason documentation
- Automatic allocation upon approval

### 💰 Sales Management
- Sales recording from allocated stock
- Customer information tracking
- Complete transaction history
- Revenue monitoring
- Automatic stock deduction

### ✅ Task Management
- Task creation and assignment
- Status tracking (Pending, In-Progress, Completed)
- Due date management
- Progress monitoring

### ⏰ Attendance System
- Check-in/Check-out functionality
- Attendance history records
- Time tracking and status monitoring

### 📈 Reporting & Analytics
- Stock movement reports
- Sales analytics (Employee and company-wide)
- Task completion metrics
- Employee performance tracking
- Comprehensive reporting dashboard

## Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **UI Framework:** Bootstrap 4, Custom CSS
- **Icons:** Font Awesome
- **Server:** XAMPP (Apache, MySQL, PHP)

## Installation

1. **Clone or download the project files**
   ```bash
   # Place files in your web server directory
   # For XAMPP: C:\xampp\htdocs\nesrah
   ```

2. **Database Setup**
   - Open phpMyAdmin or MySQL command line
   - Import the database file: `database/nesrah_database.sql`
   - This will create the `nesrah_group` database with all required tables

3. **Configuration**
   - Update database credentials in `config/database.php` if needed
   - Default credentials (for XAMPP):
     - Host: localhost
     - Database: nesrah_group
     - Username: root
     - Password: (empty)

4. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/nesrah`
   - You will be redirected to the login page

## Default Login Credentials

### Administrator
- **Username:** admin
- **Email:** admin@nesrahgroup.com
- **Password:** password
- **Role:** Administrator

## User Roles

### Administrator
- Complete system oversight and management
- Employee approval and management
- Inventory management and stock control
- Stock allocation to employees
- Stock request approval/rejection
- Task creation and assignment
- Comprehensive reporting and analytics
- System configuration

### Employee
- Personal dashboard management
- Attendance tracking (check-in/check-out)
- Task completion and status updates
- Stock request submission
- Sales recording from allocated stock
- Personal performance monitoring

## System Workflow

### Employee Onboarding
1. Employee registers through the system
2. Account status set to "pending"
3. Admin reviews and approves/rejects registration
4. Approved employees gain access to employee dashboard
5. Admin allocates initial stock to new employees

### Daily Operations
1. Employee checks in at start of day
2. Employee views and works on assigned tasks
3. Employee uses allocated stock for sales
4. Employee records all sales transactions
5. Employee requests additional stock when needed
6. Employee checks out at end of day

### Stock Management
1. Admin adds products to inventory
2. Admin allocates stock to employees
3. Employees sell from allocated stock
4. Employees request more stock when needed
5. Admin approves/rejects stock requests
6. Approved requests automatically create new allocations

## File Structure

```
nesrah/
├── auth/                    # Authentication files
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/                  # Configuration files
│   ├── config.php
│   └── database.php
├── database/               # Database files
│   └── nesrah_database.sql
├── includes/               # Common includes
│   ├── header.php
│   └── footer.php
├── css/                    # Stylesheets
├── js/                     # JavaScript files
├── images/                 # Images and assets
├── dashboard.php           # Main dashboard
├── employees.php           # Employee management
├── inventory.php           # Inventory management
├── tasks.php               # Task management
├── stock_allocations.php   # Stock allocation
├── stock_requests.php      # Stock requests
├── sales.php               # Sales management
├── reports.php             # Reports and analytics
├── attendance.php          # Attendance tracking
├── profile.php             # User profile
├── settings.php            # System settings
└── README.md              # This file
```

## Key Features Implemented

### ✅ Completed Features
- [x] User authentication and authorization
- [x] Employee management system
- [x] Inventory management
- [x] Stock allocation and request system
- [x] Sales management
- [x] Task management
- [x] Attendance tracking
- [x] Reporting and analytics
- [x] Profile management
- [x] System settings

### 🔧 Technical Features
- [x] Responsive design
- [x] Role-based access control
- [x] Data validation and sanitization
- [x] Error handling
- [x] Database transactions
- [x] Audit trails
- [x] Real-time statistics
- [x] Mobile-friendly interface

## Security Features

- Password hashing using PHP's password_hash()
- Input sanitization and validation
- SQL injection prevention with prepared statements
- Session management
- Role-based access control
- CSRF protection (basic)

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Internet Explorer 11+

## Support

For technical support or questions about the system, please contact the development team.

## License

This project is proprietary software developed for NESRAH GROUP.

---

**Version:** 1.0  
**Last Updated:** 2024  
**Developer:** NESRAH GROUP Development Team
