<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Restricted | BH Skin System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff6b9d, #c44dd8);
            --warning-gradient: linear-gradient(135deg, #ffd93d, #ff6b9d);
            --danger-gradient: linear-gradient(135deg, #ff6b6b, #ee5a24);
            --shadow-light: 0 8px 32px rgba(255, 107, 157, 0.1);
            --shadow-dark: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-danger: 0 8px 32px rgba(255, 107, 107, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Animated background elements */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 120px;
            height: 120px;
            top: 15%;
            left: 8%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 12%;
            animation-delay: -7s;
        }
        
        .shape-3 {
            width: 90px;
            height: 90px;
            bottom: 25%;
            left: 15%;
            animation-delay: -14s;
        }
        
        .warning-shape {
            background: rgba(255, 107, 157, 0.1);
            border: 2px solid rgba(255, 107, 157, 0.2);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.3;
            }
            25% { 
                transform: translateY(-25px) rotate(90deg) scale(1.1);
                opacity: 0.6;
            }
            50% { 
                transform: translateY(-15px) rotate(180deg) scale(0.9);
                opacity: 0.4;
            }
            75% { 
                transform: translateY(-30px) rotate(270deg) scale(1.05);
                opacity: 0.7;
            }
        }
        
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--shadow-dark);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 60px 40px;
            max-width: 650px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--danger-gradient);
        }
        
        .logo {
            max-width: 280px;
            height: auto;
            margin-bottom: 40px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
        }
        
        .lock-icon {
            font-size: 6rem;
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            display: block;
            position: relative;
            animation: lockPulse 2s infinite ease-in-out;
        }
        
        @keyframes lockPulse {
            0%, 100% { 
                transform: scale(1);
                filter: drop-shadow(0 0 20px rgba(255, 107, 107, 0.3));
            }
            50% { 
                transform: scale(1.05);
                filter: drop-shadow(0 0 30px rgba(255, 107, 107, 0.5));
            }
        }
        
        .error-number {
            font-size: 5rem;
            font-weight: 900;
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 2.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }
        
        .error-message {
            font-size: 1.3rem;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .user-info {
            background: rgba(255, 107, 157, 0.1);
            border: 2px solid rgba(255, 107, 157, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 40px;
            position: relative;
        }
        
        .user-info::before {
            content: '👤';
            position: absolute;
            top: -12px;
            left: 20px;
            background: white;
            padding: 0 8px;
            font-size: 1.2rem;
        }
        
        .user-info h6 {
            color: #c44dd8;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .user-info p {
            color: #4a5568;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .restriction-details {
            background: rgba(255, 235, 59, 0.1);
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 40px;
            position: relative;
        }
        
        .restriction-details::before {
            content: '⚠️';
            position: absolute;
            top: -12px;
            left: 20px;
            background: white;
            padding: 0 8px;
            font-size: 1.2rem;
        }
        
        .restriction-details h6 {
            color: #f57c00;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .restriction-details ul {
            text-align: left;
            color: #4a5568;
            margin: 0;
            padding-left: 20px;
        }
        
        .restriction-details li {
            margin-bottom: 6px;
            font-size: 0.95rem;
        }
        
        .btn-group {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .btn-custom {
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-light);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(255, 107, 157, 0.3);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #4a5568;
            border: 2px solid rgba(255, 107, 157, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 107, 157, 0.1);
            transform: translateY(-2px);
            color: #2d3748;
        }
        
        .btn-warning {
            background: var(--warning-gradient);
            color: #2d3748;
            box-shadow: 0 8px 32px rgba(255, 217, 61, 0.2);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(255, 217, 61, 0.3);
            color: #2d3748;
        }
        
        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(255, 107, 107, 0.2);
            animation: iconFloat 12s infinite ease-in-out;
        }
        
        .icon-1 {
            font-size: 2.5rem;
            top: 12%;
            left: 12%;
            animation-delay: 0s;
        }
        
        .icon-2 {
            font-size: 1.8rem;
            top: 20%;
            right: 18%;
            animation-delay: -4s;
        }
        
        .icon-3 {
            font-size: 2.2rem;
            bottom: 18%;
            left: 18%;
            animation-delay: -8s;
        }
        
        .icon-4 {
            font-size: 2rem;
            bottom: 25%;
            right: 12%;
            animation-delay: -12s;
        }
        
        @keyframes iconFloat {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
                opacity: 0.2;
            }
            25% { 
                transform: translateY(-20px) rotate(90deg);
                opacity: 0.4;
            }
            50% { 
                transform: translateY(-10px) rotate(180deg);
                opacity: 0.2;
            }
            75% { 
                transform: translateY(-25px) rotate(270deg);
                opacity: 0.3;
            }
        }
        
        .help-links {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 107, 157, 0.2);
        }
        
        .help-links h6 {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }
        
        .help-links .btn-link {
            color: #ff6b9d;
            text-decoration: none;
            font-weight: 500;
            margin: 0 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .help-links .btn-link:hover {
            color: #c44dd8;
            transform: translateY(-1px);
        }
        
        .security-notice {
            background: rgba(52, 152, 219, 0.1);
            border: 2px solid rgba(52, 152, 219, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #2980b9;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .error-card {
                padding: 40px 20px;
                margin: 20px;
            }
            
            .lock-icon {
                font-size: 4.5rem;
            }
            
            .error-number {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 2.2rem;
            }
            
            .error-message {
                font-size: 1.1rem;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-custom {
                width: 100%;
                max-width: 280px;
            }
            
            .logo {
                max-width: 220px;
            }
            
            .user-info, .restriction-details {
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .lock-icon {
                font-size: 3.5rem;
            }
            
            .error-number {
                font-size: 3rem;
            }
            
            .error-title {
                font-size: 1.8rem;
            }
            
            .floating-icon {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shape shape-1 warning-shape"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3 warning-shape"></div>
    </div>

    <div class="error-container">
        <div class="error-card">
            <!-- Floating Icons -->
            <div class="floating-icons">
                <i class="bi bi-shield-exclamation floating-icon icon-1"></i>
                <i class="bi bi-lock floating-icon icon-2"></i>
                <i class="bi bi-person-x floating-icon icon-3"></i>
                <i class="bi bi-exclamation-triangle floating-icon icon-4"></i>
            </div>
            
            <!-- Logo -->
            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjgwIiB2aWV3Qm94PSIwIDAgMjgwIDgwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8ZGVmcz4KPHN0eWxlPgouYnJhbmQtdGV4dCB7IGZvbnQtZmFtaWx5OiAtYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsICdTZWdvZSBVSScsIFJvYm90bywgc2Fucy1zZXJpZjsgZm9udC13ZWlnaHQ6IDcwMDsgZm9udC1zaXplOiAyNHB4OyBmaWxsOiAjMmQzNzQ4OyB9Cjwvc3R5bGU+CjxsaW5lYXJHcmFkaWVudCBpZD0iYnV0dGVyZmx5LWdyYWRpZW50IiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6I2ZmNmI5ZDtzdG9wLW9wYWNpdHk6MSIgLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojYzQ0ZGQ4O3N0b3Atb3BhY2l0eToxIiAvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+CjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwLCAxMCkiPgo8IS0tIEJ1dHRlcmZseSBTaGFwZSAtLT4KPHBhdGggZD0iTTIwIDVDMjUgMCAzMCA1IDMwIDEwQzMwIDE1IDI1IDIwIDIwIDIwQzE1IDIwIDEwIDE1IDEwIDEwQzEwIDUgMTUgMCAyMCA1WiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjgiLz4KPHBhdGggZD0iTTUwIDVDNTUgMCA2MCA1IDYwIDEwQzYwIDE1IDU1IDIwIDUwIDIwQzQ1IDIwIDQwIDE1IDQwIDEwQzQwIDUgNDUgMCA1MCA1WiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjgiLz4KPHBhdGggZD0iTTIwIDMwQzI1IDI1IDMwIDI1IDMwIDMwQzMwIDM1IDI1IDQwIDIwIDQwQzE1IDQwIDEwIDM1IDEwIDMwQzEwIDI1IDE1IDI1IDIwIDMwWiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjciLz4KPHBhdGggZD0iTTUwIDMwQzU1IDI1IDYwIDI1IDYwIDMwQzYwIDM1IDU1IDQwIDUwIDQwQzQ1IDQwIDQwIDM1IDQwIDMwQzQwIDI1IDQ1IDI1IDUwIDMwWiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjciLz4KPGNpcmNsZSBjeD0iMzUiIGN5PSIyNSIgcj0iMyIgZmlsbD0iIzJkMzc0OCIvPgo8L2c+CjwhLS0gVGV4dCAtLT4KPHRleHQgeD0iOTAiIHk9IjQ1IiBjbGFzcz0iYnJhbmQtdGV4dCI+Ymhza2luc3lzdGVtPC90ZXh0Pgo8L3N2Zz4K" 
                 alt="BH Skin System" class="logo">
            
            <!-- Lock Icon -->
            <i class="bi bi-shield-lock lock-icon"></i>
            
            <!-- Error Content -->
            <div class="error-number">403</div>
            <h1 class="error-title">Access Restricted</h1>
            <p class="error-message">
                You don't have permission to access this resource. 
                Your current role doesn't allow you to view this page.
            </p>
            
            <!-- User Information -->
            <?php if (isset($_SESSION['user'])): ?>
            <div class="user-info">
                <h6>Current User Information</h6>
                <p><strong>User:</strong> <?php echo $_SESSION['user']['name'] ?? 'Unknown User'; ?></p>
                <p><strong>Role:</strong> 
                    <?php 
                    $role_names = [
                        '1' => 'Super Admin',
                        '2' => 'Admin', 
                        '7' => 'HR Manager',
                        'default' => 'Employee'
                    ];
                    echo $role_names[$_SESSION['user']['role']] ?? $role_names['default'];
                    ?>
                </p>
                <p><strong>Last Login:</strong> <?php echo date('F j, Y g:i A', strtotime($_SESSION['user']['updated_at'] ?? 'now')); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Restriction Details -->
            <div class="restriction-details">
                <h6>Why am I seeing this?</h6>
                <ul>
                    <li>This page requires specific permissions that your role doesn't have</li>
                    <li>You may need to be assigned to a different role or position</li>
                    <li>Some features are restricted to administrators only</li>
                    <li>Your session may have expired - try logging in again</li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div class="btn-group">
                <a href="javascript:history.back()" class="btn-custom btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Go Back
                </a>
                <a href="<?php echo base_url(); ?>dashboard" class="btn-custom btn-primary">
                    <i class="bi bi-speedometer2"></i>
                    Go to Dashboard
                </a>
                <a href="mailto:admin@acnenosystem.com?subject=Access Request&body=Hi, I need access to a restricted page. My current role is: <?php echo $_SESSION['user']['role'] ?? 'Unknown'; ?>" class="btn-custom btn-warning">
                    <i class="bi bi-envelope"></i>
                    Request Access
                </a>
            </div>
            
            <!-- Help Links -->
            <div class="help-links">
                <h6>Need Help?</h6>
                <a href="<?php echo base_url(); ?>profile" class="btn-link">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
                <a href="<?php echo base_url(); ?>quest" class="btn-link">
                    <i class="bi bi-trophy"></i> My Quests
                </a>
                <a href="mailto:support@acnenosystem.com" class="btn-link">
                    <i class="bi bi-headset"></i> Contact Support
                </a>
            </div>
            
            <!-- Security Notice -->
            <div class="security-notice">
                <i class="bi bi-info-circle"></i>
                This access attempt has been logged for security purposes. 
                If you believe this is an error, please contact your system administrator.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add interactive effects
        document.querySelectorAll('.btn-custom').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Animate elements on load
        window.addEventListener('load', function() {
            const lockIcon = document.querySelector('.lock-icon');
            const errorNumber = document.querySelector('.error-number');
            
            // Animate lock icon
            lockIcon.style.opacity = '0';
            lockIcon.style.transform = 'scale(0.5) rotate(-10deg)';
            
            setTimeout(() => {
                lockIcon.style.transition = 'all 1s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                lockIcon.style.opacity = '1';
                lockIcon.style.transform = 'scale(1) rotate(0deg)';
            }, 200);
            
            // Animate error number
            setTimeout(() => {
                errorNumber.style.opacity = '0';
                errorNumber.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    errorNumber.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    errorNumber.style.opacity = '1';
                    errorNumber.style.transform = 'scale(1)';
                }, 100);
            }, 500);
        });
        
        // Add some security-themed interactions
        let clickCount = 0;
        document.querySelector('.lock-icon').addEventListener('click', function() {
            clickCount++;
            if (clickCount >= 5) {
                this.style.animation = 'lockPulse 0.3s infinite';
                setTimeout(() => {
                    this.style.animation = 'lockPulse 2s infinite ease-in-out';
                    clickCount = 0;
                }, 1500);
            }
        });
    </script>
</body>
</html>