<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NESRAH GROUP Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .card-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .left-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 600px;
        }
        
        .right-section {
            padding: 60px 40px;
            background: white;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        
        .company-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .company-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .features-list i {
            margin-right: 15px;
            font-size: 1.3rem;
            color: #4ade80;
        }
        
        .login-section {
            text-align: center;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #6b7280;
            margin-bottom: 40px;
        }
        
        .demo-credentials {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .demo-title {
            color: #0c4a6e;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .demo-info {
            color: #0c4a6e;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.3);
        }
        
        .back-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 20px;
            display: inline-block;
        }
        
        .back-link:hover {
            color: #374151;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .checkbox-group label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .left-section {
                min-height: 400px;
                padding: 40px 20px;
            }
            
            .right-section {
                padding: 40px 20px;
            }
            
            .company-name {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container-fluid">
            <div class="card-container">
                <div class="row g-0">
                    <!-- Left Section - Branding & Features -->
                    <div class="col-lg-6">
                        <div class="left-section">
                            <div class="logo-section">
                                <div class="logo-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h1 class="company-name">NESRAH GROUP</h1>
                                <p class="company-subtitle">Management System</p>
                            </div>
                            
                            <ul class="features-list">
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    Complete Employee Management
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    Inventory & Stock Control
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    Sales & Revenue Tracking
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    Task & Project Management
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    Real-time Analytics & Reports
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Right Section - Login Form -->
                    <div class="col-lg-6">
                        <div class="right-section">
                            <div class="login-section">
                                <h2 class="login-title">Admin Login</h2>
                                <p class="login-subtitle">Sign in to access the management dashboard</p>
                                
                                <div class="demo-credentials">
                                    <div class="demo-title">Demo Credentials</div>
                                    <div class="demo-info">
                                        <strong>Admin:</strong> admin / password<br>
                                        <strong>Employee:</strong> john_doe / password123
                                    </div>
                                </div>
                                
                                <form action="auth/login.php" method="POST">
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="login_as" class="form-label">Login As</label>
                                        <select name="login_as" id="login_as" class="form-control" required>
                                            <option value="">Select Role</option>
                                            <option value="admin">Administrator</option>
                                            <option value="employee">Employee</option>
                                        </select>
                                    </div>
                                    
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="remember" name="remember">
                                        <label for="remember">Remember me</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-login">
                                        <i class="fas fa-arrow-right"></i> Sign In
                                    </button>
                                    
                                    <!-- Back to Website link removed as requested -->
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>