<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | BH Skin System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff6b9d, #c44dd8);
            --secondary-gradient: linear-gradient(135deg, #667eea, #764ba2);
            --shadow-light: 0 8px 32px rgba(255, 107, 157, 0.1);
            --shadow-dark: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: -5s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(90deg); }
            50% { transform: translateY(0px) rotate(180deg); }
            75% { transform: translateY(-15px) rotate(270deg); }
        }
        
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: var(--shadow-dark);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 60px 40px;
            max-width: 600px;
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
            background: var(--primary-gradient);
        }
        
        .logo {
            max-width: 280px;
            height: auto;
            margin-bottom: 40px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
        }
        
        .error-number {
            font-size: 8rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 4px 12px rgba(255, 107, 157, 0.3);
        }
        
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .search-container {
            margin-bottom: 40px;
        }
        
        .search-input {
            border: 2px solid rgba(255, 107, 157, 0.2);
            border-radius: 50px;
            padding: 15px 25px;
            font-size: 1rem;
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #ff6b9d;
            box-shadow: 0 0 0 4px rgba(255, 107, 157, 0.1);
            background: white;
        }
        
        .btn-group {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
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
        
        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(255, 107, 157, 0.3);
            animation: iconFloat 10s infinite ease-in-out;
        }
        
        .icon-1 {
            font-size: 2rem;
            top: 15%;
            left: 15%;
            animation-delay: 0s;
        }
        
        .icon-2 {
            font-size: 1.5rem;
            top: 25%;
            right: 20%;
            animation-delay: -3s;
        }
        
        .icon-3 {
            font-size: 2.5rem;
            bottom: 20%;
            left: 20%;
            animation-delay: -6s;
        }
        
        .icon-4 {
            font-size: 1.8rem;
            bottom: 30%;
            right: 15%;
            animation-delay: -9s;
        }
        
        @keyframes iconFloat {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
                opacity: 0.3;
            }
            25% { 
                transform: translateY(-15px) rotate(90deg);
                opacity: 0.6;
            }
            50% { 
                transform: translateY(-10px) rotate(180deg);
                opacity: 0.3;
            }
            75% { 
                transform: translateY(-20px) rotate(270deg);
                opacity: 0.5;
            }
        }
        
        .help-links {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 107, 157, 0.2);
        }
        
        .help-links h6 {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .help-links .btn-link {
            color: #ff6b9d;
            text-decoration: none;
            font-weight: 500;
            margin: 0 8px;
            transition: all 0.3s ease;
        }
        
        .help-links .btn-link:hover {
            color: #c44dd8;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .error-card {
                padding: 40px 20px;
                margin: 20px;
            }
            
            .error-number {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 2rem;
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
        }
        
        @media (max-width: 480px) {
            .error-number {
                font-size: 4.5rem;
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
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
    </div>

    <div class="error-container">
        <div class="error-card">
            <!-- Floating Icons -->
            <div class="floating-icons">
                <i class="bi bi-search floating-icon icon-1"></i>
                <i class="bi bi-house floating-icon icon-2"></i>
                <i class="bi bi-exclamation-triangle floating-icon icon-3"></i>
                <i class="bi bi-arrow-left floating-icon icon-4"></i>
            </div>
            
            <!-- Logo -->
            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjgwIiB2aWV3Qm94PSIwIDAgMjgwIDgwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8ZGVmcz4KPHN0eWxlPgouYnJhbmQtdGV4dCB7IGZvbnQtZmFtaWx5OiAtYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsICdTZWdvZSBVSScsIFJvYm90bywgc2Fucy1zZXJpZjsgZm9udC13ZWlnaHQ6IDcwMDsgZm9udC1zaXplOiAyNHB4OyBmaWxsOiAjMmQzNzQ4OyB9Cjwvc3R5bGU+CjxsaW5lYXJHcmFkaWVudCBpZD0iYnV0dGVyZmx5LWdyYWRpZW50IiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6I2ZmNmI5ZDtzdG9wLW9wYWNpdHk6MSIgLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojYzQ0ZGQ4O3N0b3Atb3BhY2l0eToxIiAvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+CjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwLCAxMCkiPgo8IS0tIEJ1dHRlcmZseSBTaGFwZSAtLT4KPHBhdGggZD0iTTIwIDVDMjUgMCAzMCA1IDMwIDEwQzMwIDE1IDI1IDIwIDIwIDIwQzE1IDIwIDEwIDE1IDEwIDEwQzEwIDUgMTUgMCAyMCA1WiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjgiLz4KPHBhdGggZD0iTTUwIDVDNTUgMCA2MCA1IDYwIDEwQzYwIDE1IDU1IDIwIDUwIDIwQzQ1IDIwIDQwIDE1IDQwIDEwQzQwIDUgNDUgMCA1MCA1WiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjgiLz4KPHBhdGggZD0iTTIwIDMwQzI1IDI1IDMwIDI1IDMwIDMwQzMwIDM1IDI1IDQwIDIwIDQwQzE1IDQwIDEwIDM1IDEwIDMwQzEwIDI1IDE1IDI1IDIwIDMwWiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjciLz4KPHBhdGggZD0iTTUwIDMwQzU1IDI1IDYwIDI1IDYwIDMwQzYwIDM1IDU1IDQwIDUwIDQwQzQ1IDQwIDQwIDM1IDQwIDMwQzQwIDI1IDQ1IDI1IDUwIDMwWiIgZmlsbD0idXJsKCNidXR0ZXJmbHktZ3JhZGllbnQpIiBvcGFjaXR5PSIwLjciLz4KPGNpcmNsZSBjeD0iMzUiIGN5PSIyNSIgcj0iMyIgZmlsbD0iIzJkMzc0OCIvPgo8L2c+CjwhLS0gVGV4dCAtLT4KPHRleHQgeD0iOTAiIHk9IjQ1IiBjbGFzcz0iYnJhbmQtdGV4dCI+Ymhza2luc3lzdGVtPC90ZXh0Pgo8L3N2Zz4K" 
                 alt="BH Skin System" class="logo">
            
            <!-- Error Content -->
            <div class="error-number">404</div>
            <h1 class="error-title">Oops! Page Not Found</h1>
            <p class="error-message">
                The page you're looking for doesn't exist or has been moved. 
                Don't worry, it happens to the best of us!
            </p>
            
            <!-- Search -->
            <div class="search-container">
                <input type="text" class="form-control search-input" placeholder="🔍 Search for what you need..." id="searchInput">
            </div>
            
            <!-- Action Buttons -->
            <div class="btn-group">
                <a href="javascript:history.back()" class="btn-custom btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Go Back
                </a>
                <a href="<?php echo base_url(); ?>" class="btn-custom btn-primary">
                    <i class="bi bi-house"></i>
                    Go Home
                </a>
            </div>
            
            <!-- Help Links -->
            <div class="help-links">
                <h6>Need Help?</h6>
                <a href="<?php echo base_url(); ?>dashboard" class="btn-link">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?php echo base_url(); ?>profile" class="btn-link">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
                <a href="mailto:support@acnenosystem.com" class="btn-link">
                    <i class="bi bi-envelope"></i> Contact Support
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    // Redirect to dashboard with search query
                    window.location.href = '<?php echo base_url(); ?>dashboard?search=' + encodeURIComponent(query);
                }
            }
        });
        
        // Add some interactive effects
        document.querySelectorAll('.btn-custom').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Animate error number on load
        window.addEventListener('load', function() {
            const errorNumber = document.querySelector('.error-number');
            errorNumber.style.opacity = '0';
            errorNumber.style.transform = 'scale(0.5)';
            
            setTimeout(() => {
                errorNumber.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                errorNumber.style.opacity = '1';
                errorNumber.style.transform = 'scale(1)';
            }, 300);
        });
    </script>
</body>
</html>