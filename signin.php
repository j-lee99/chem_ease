<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="signin.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="apple-touch-icon" href="/apple-icon.png">
    <link rel="apple-touch-icon-precomposed" href="/apple-icon-precomposed.png">
    <!-- Android Icons -->
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">
    <style>
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), #0891b2);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(23,162,184,0.25);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            min-height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #138496, #0e7490);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(23,162,184,0.4);
        }
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .loading .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .forgot-password a {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .forgot-password a:hover {
            color: #138496;
            text-decoration: underline;
        }
        .alert-container {
            margin-bottom: 1.5rem;
            display: none;
        }
        .alert {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* Success Checkmark Animation */
        .success-animation {
            text-align: center;
            padding: 2rem 0;
        }
        .success-checkmark {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: #28a745;
            stroke-miterlimit: 10;
            box-shadow: inset 0 0 0 #28a745;
            animation: fill-success 0.4s ease-in-out 0.4s forwards, scale-success 0.3s ease-in-out 0.9s both;
            position: relative;
        }
        .success-checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: #28a745;
            fill: #f0fff4;
            animation: stroke-anim 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .success-checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke: #28a745;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            animation: stroke-anim 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        @keyframes stroke-anim {
            100% {
                stroke-dashoffset: 0;
            }
        }
        @keyframes scale-success {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        @keyframes fill-success {
            100% {
                box-shadow: inset 0 0 0 30px #28a745;
            }
        }
        .success-message {
            text-align: center;
            padding: 2rem 0;
        }
        .success-message h3 {
            color: #28a745;
            margin-top: 1rem;
            font-weight: 600;
            animation: fadeInUp 0.5s ease 0.8s both;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Floating Chemical Icons -->
    <!-- <div class="floating-icon h2o-1">H₂O</div>
    <div class="floating-icon co2">CO₂</div>
    <div class="floating-icon molecule"><i class="fas fa-atom"></i></div>
    <div class="floating-icon atom"><i class="fas fa-flask"></i></div>
    <div class="floating-icon nacl">NaCl</div>
    <div class="floating-icon h2so4">H₂SO₄</div> -->
  
    <!-- Particles -->
    <!-- <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 6s;"></div>
    <div class="particle" style="left: 90%; animation-delay: 8s;"></div> -->
  
    <div class="form-container">
        <div class="form-card">
            <div class="logo-container">
                <img src="images/logo.png" alt="ChemEase Logo" class="logo-icon">
                <h1 class="form-title">Welcome Back</h1>
                <p class="form-subtitle">Sign in to your ChemEase account</p>
            </div>
            <div class="alert-container" id="alertContainer">
                <div class="alert" id="alertMessage"></div>
            </div>
          
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-container">
                        <input
                            type="email"
                            id="email"
                            class="form-input"
                            placeholder="Enter your email"
                            required
                            autocomplete="email"
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
              
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-container password-container">
                        <input
                            type="password"
                            id="password"
                            class="form-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>
              
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
              
                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="btnText">Sign In</span>
                </button>
            </form>
          
            <div class="signup-link">
                Don't have an account? <a href="signup.php">Create one</a>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + 'Icon');
          
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        function showAlert(message, type = 'danger') {
            const container = document.getElementById('alertContainer');
            const alertEl = document.getElementById('alertMessage');
            alertEl.textContent = message;
            alertEl.className = `alert alert-${type}`;
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        function hideAlert() {
            document.getElementById('alertContainer').style.display = 'none';
        }
        function showSuccessAnimation() {
            const form = document.getElementById('loginForm');
            const successSvg = `
                <div class="success-message">
                    <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="success-checkmark__circle" cx="26" cy="26" r="25"/>
                        <path class="success-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                    <h3>Login Successful!</h3>
                </div>
            `;
          
            form.style.transition = 'opacity 0.4s';
            form.style.opacity = '0';
            setTimeout(() => {
                form.innerHTML = successSvg;
                form.style.opacity = '1';
            }, 400);
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Floating icons hover effect
            document.querySelectorAll('.floating-icon').forEach(icon => {
                icon.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                    this.style.transform = 'scale(1.2) rotate(10deg)';
                    this.style.filter = 'drop-shadow(0 0 15px rgba(23, 162, 184, 0.5))';
                });
                icon.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                    this.style.transform = '';
                    this.style.filter = 'drop-shadow(0 0 8px rgba(23, 162, 184, 0.2))';
                });
            });
            // Input focus & real-time validation
            document.querySelectorAll('.form-input').forEach(input => {
                const container = input.closest('.input-container');
                input.addEventListener('focus', () => {
                    container.style.transform = 'scale(1.02)';
                    input.closest('.form-group').querySelector('.form-label').style.color = 'var(--primary-blue)';
                });
                input.addEventListener('blur', () => {
                    container.style.transform = 'scale(1)';
                    if (!input.value) {
                        input.closest('.form-group').querySelector('.form-label').style.color = 'var(--dark-text)';
                    }
                });
                input.addEventListener('input', () => {
                    if (input.value.length > 0) {
                        if (input.checkValidity()) {
                            input.style.borderColor = '#28a745';
                            input.style.boxShadow = '0 0 0 3px rgba(40,167,69,0.1)';
                        } else {
                            input.style.borderColor = '#dc3545';
                            input.style.boxShadow = '0 0 0 3px rgba(220,53,69,0.1)';
                        }
                    } else {
                        input.style.borderColor = '#e9ecef';
                        input.style.boxShadow = 'none';
                    }
                });
            });
            // Particle generator
            function createParticle() {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDuration = (8 + Math.random() * 5) + 's';
                document.body.appendChild(p);
                setTimeout(() => p.remove(), 15000);
            }
            setInterval(createParticle, 2800);
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                hideAlert();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                if (!email || !password) {
                    showAlert('Please enter both email and password.', 'danger');
                    return;
                }
                if (!email.includes('@') || !email.includes('.')) {
                    showAlert('Please enter a valid email address.', 'danger');
                    return;
                }
                const submitBtn = document.getElementById('submitBtn');
                const btnText = document.getElementById('btnText');
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                btnText.style.display = 'none';
                submitBtn.innerHTML = '<div class="spinner"></div>';
                try {
                    const response = await fetch('partial/login_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ email, password })
                    });
                    const data = await response.json();
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span id="btnText">Sign In</span>';
                    if (data.status === 'success') {
                        showSuccessAnimation();
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1600);
                    } else {
                        showAlert(data.message || 'Login failed. Please try again.', 'danger');
                    }
                } catch (err) {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span id="btnText">Sign In</span>';
                    showAlert('Something went wrong. Please check your connection.', 'danger');
                    console.error(err);
                }
            });
        });
    </script>
</body>
</html>