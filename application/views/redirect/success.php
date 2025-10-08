<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Berhasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        .container-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.6s ease-out;
            text-align: center;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out 0.3s both;
            position: relative;
        }

        .success-icon::before {
            content: '';
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.5;
            }
        }

        .success-icon i {
            font-size: 60px;
            color: white;
            animation: checkmark 0.8s ease-out 0.5s both;
            position: relative;
            z-index: 1;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(0deg);
            }
            50% {
                transform: scale(1.2) rotate(180deg);
            }
            100% {
                transform: scale(1) rotate(360deg);
            }
        }

        .success-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            animation: fadeIn 0.6s ease-out 0.8s both;
        }

        .success-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            animation: fadeIn 0.6s ease-out 1s both;
            line-height: 1.6;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .redirect-info {
            font-size: 16px;
            color: #999;
            margin-bottom: 25px;
            animation: fadeIn 0.6s ease-out 1.2s both;
        }

        .countdown-number {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            min-width: 30px;
        }

        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 3px;
            animation: progress 3s linear;
        }

        @keyframes progress {
            from {
                width: 0%;
            }
            to {
                width: 100%;
            }
        }

        .btn-close-now {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 45px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeIn 0.6s ease-out 1.4s both;
        }

        .btn-close-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-close-now:active {
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div class="particles" id="particles"></div>

    <!-- Main Content -->
    <div class="container-wrapper">
        <div class="success-card">
            <div class="success-icon">
                <img src="<?= base_url('assets/img/icon/success-icon.png') ?>" alt="success" style="width:70px; height:70px; z-index:1; position:relative;">
            </div>

            
            <h1 class="success-title">Login Berhasil!</h1>
            <p class="success-message">
                Akun Google Anda telah berhasil terhubung dengan sistem kami.<br>
                Terima kasih telah melakukan autentikasi.
            </p>
            
            <p class="redirect-info">
                <i class="fas fa-times-circle"></i> Tab ini akan tertutup otomatis dalam 
                <span class="countdown-number" id="countdown">3</span> detik
            </p>
            
            <div class="progress-bar-container">
                <div class="progress-bar-fill"></div>
            </div>
            
            <button class="btn-close-now" onclick="closeTab()">
                <i class="fas fa-times"></i> Tutup Sekarang
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Close tab function
        function closeTab() {
            // Try to close the window
            window.close();
            
            // If window.close() doesn't work (some browsers block it), 
            // show alternative message
            setTimeout(() => {
                document.querySelector('.success-card').innerHTML = `
                    <div class="success-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h1 class="success-title">Login Berhasil!</h1>
                    <p class="success-message">
                        Silakan tutup tab ini secara manual.<br>
                        Anda sudah dapat kembali ke aplikasi utama.
                    </p>
                `;
            }, 500);
        }

        // Countdown and auto close
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                closeTab();
            }
        }

        // Initialize
        createParticles();
        
        // Start countdown after 1 second
        setTimeout(() => {
            const countdownInterval = setInterval(() => {
                updateCountdown();
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }, 1000);
    </script>
</body>
</html>