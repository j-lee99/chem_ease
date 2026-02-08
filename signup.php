<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Create Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        /* === FIX: NO SCROLL, FULL SCREEN === */
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light-blue) 0%, #e3f2fd 50%, #f0f9ff 100%);
            background-attachment: fixed;
            position: relative;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(23, 162, 184, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(14, 165, 233, 0.05) 0%, transparent 50%);
        }

        .form-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            z-index: 10;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 50px rgba(23, 162, 184, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            max-width: 420px;
            width: 100%;
            max-height: 95vh;
            overflow-y: auto;
            animation: slideUp 0.8s ease-out;
            position: relative;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), #06b6d4, #0ea5e9);
            border-radius: 20px 20px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1)
            }

            50% {
                transform: scale(1.05)
            }
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .form-subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease-out both;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(4) {
            animation-delay: 0.2s;
        }

        .form-group:nth-child(5) {
            animation-delay: 0.3s;
        }

        .form-group:nth-child(6) {
            animation-delay: 0.4s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            background: var(--input-bg);
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
            background: white;
            transform: translateY(-2px);
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            color: var(--primary-blue);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-text {
            font-weight: 600;
            min-width: 70px;
        }

        .strength-weak .strength-bar-fill {
            width: 33%;
            background: #dc3545;
        }

        .strength-weak .strength-text {
            color: #dc3545;
        }

        .strength-medium .strength-bar-fill {
            width: 66%;
            background: #ffc107;
        }

        .strength-medium .strength-text {
            color: #ffc107;
        }

        .strength-strong .strength-bar-fill {
            width: 100%;
            background: #28a745;
        }

        .strength-strong .strength-text {
            color: #28a745;
        }

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .custom-checkbox:checked {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .checkbox-label {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
        }

        .checkbox-label a {
            color: var(--primary-blue);
            text-decoration: none;
            cursor: pointer;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

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
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.25);
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
            box-shadow: 0 15px 35px rgba(23, 162, 184, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Floating icons */
        .floating-icon {
            position: absolute;
            animation: float 8s ease-in-out infinite;
            opacity: 0.6;
            z-index: 1;
        }

        .floating-icon.h2o-1 {
            top: 15%;
            right: 10%;
            font-size: 2rem;
            color: var(--primary-blue);
        }

        .floating-icon.co2 {
            bottom: 25%;
            left: 8%;
            font-size: 1.8rem;
            color: #06b6d4;
            animation-delay: 3s;
        }

        .floating-icon.molecule {
            top: 60%;
            right: 15%;
            font-size: 2.2rem;
            color: #0891b2;
            animation-delay: 1.5s;
        }

        .floating-icon.atom {
            top: 25%;
            left: 5%;
            font-size: 2rem;
            color: #0284c7;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0)
            }

            25% {
                transform: translateY(-20px)
            }

            75% {
                transform: translateY(10px)
            }
        }

        /* Particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary-blue);
            border-radius: 50%;
            animation: particle 12s linear infinite;
            opacity: 0;
        }

        @keyframes particle {
            0% {
                opacity: 0;
                transform: translateY(100vh) scale(0);
            }

            10% {
                opacity: 0.6;
            }

            90% {
                opacity: 0.3;
            }

            100% {
                opacity: 0;
                transform: translateY(-10vh) scale(1);
            }
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
            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        @media (max-width: 768px) {
            .form-card {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }

            .floating-icon {
                font-size: 1.5rem !important;
            }

            .logo-icon {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>

<body>
    <!-- Floating Chemical Icons -->
    <!-- <div class="floating-icon h2o-1">H₂O</div>
    <div class="floating-icon co2">CO₂</div>
    <div class="floating-icon molecule"><i class="fas fa-atom"></i></div>
    <div class="floating-icon atom"><i class="fas fa-flask"></i></div> -->
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
                <h1 class="form-title">Create an account</h1>
                <p class="form-subtitle">Enter your details to register for ChemEase</p>
            </div>
            <form id="signupForm">

                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" class="form-input" placeholder="John" required>
                    <div class="error-message" id="firstNameError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" class="form-input" placeholder="Doe" required>
                    <div class="error-message" id="lastNameError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" class="form-input" placeholder="john.doe@gmail.com" required>
                    <div class="error-message" id="emailError"></div>
                </div>

                <!-- Mobile Number -->
                <div class="form-group">
                    <label class="form-label" for="mobile">Mobile Number</label>
                    <input
                        type="tel"
                        id="mobile"
                        class="form-input"
                        placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                        required>
                    <div class="error-message" id="mobileError"></div>
                </div>

                <!-- Birthday -->
                <div class="form-group">
                    <label class="form-label" for="birthday">Birthday</label>
                    <input type="date" id="birthday" class="form-input" required>
                    <div class="error-message" id="birthdayError"></div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" class="form-input" rows="2" placeholder="Complete home address" required></textarea>
                    <div class="error-message" id="addressError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" class="form-input" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-bar-fill"></div>
                        </div>
                        <span class="strength-text"></span>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirmPassword">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirmPassword" class="form-input" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                            <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>

                <div class="checkbox-container">
                    <input type="checkbox" id="terms" class="custom-checkbox" required>
                    <label for="terms" class="checkbox-label">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms of service</a> and
                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">privacy policy</a>
                    </label>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="btnText">Create account</span>
                </button>

            </form>

            <div class="login-link">
                Already have an account? <a href="signin.php">Sign in</a>
            </div>
        </div>
    </div>
    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="font-size: 0.95rem; line-height: 1.6;">
                    <p><strong>Last Updated: February 2025</strong></p>
                    <p>Welcome to <strong>ChemEase</strong>, a web-based chemistry reviewer designed to support learners in understanding and mastering fundamental chemistry concepts. By accessing or using this platform, you agree to comply with and be bound by the following Terms of Service. Please read them carefully.</p>

                    <h6>1. Acceptance of Terms</h6>
                    <p>By creating an account, accessing the platform, or using any feature of ChemEase, you acknowledge that you have read, understood, and agreed to these Terms of Service.</p>

                    <h6>2. User Responsibilities</h6>
                    <p>Users must:</p>
                    <ul>
                        <li>Provide accurate account information during registration.</li>
                        <li>Use the platform only for educational and non-commercial purposes.</li>
                        <li>Maintain confidentiality of their login credentials.</li>
                        <li>Avoid any activity that may disrupt, damage, or compromise system functionality.</li>
                    </ul>
                    <h6>3. Prohibited Activities</h6>
                    <p>Users are not allowed to:</p>
                    <ul>
                        <li>Share or distribute protected reviewer content without permission.</li>
                        <li>Attempt to hack, reverse-engineer, or exploit the system.</li>
                        <li>Upload harmful materials (malware, copyrighted content, offensive content).</li>
                        <li>Impersonate other users or administrators.</li>
                    </ul>
                    <h6>4. Content Ownership</h6>
                    <p>All modules, quizzes, illustrations, system features, and written materials within ChemEase are the intellectual property of the developers or licensed contributors. Unauthorized reproduction, modification, or distribution is strictly prohibited.</p>
                    <h6>5. System Availability</h6>
                    <p>ChemEase may experience scheduled maintenance or unexpected downtime. The developers are not liable for delays, data loss, or service interruptions.</p>
                    <h6>6. Termination of Use</h6>
                    <p>The administrators reserve the right to suspend or terminate user accounts that violate system policies.</p>
                    <h6>7. Limitation of Liability</h6>
                    <p>ChemEase is provided as an educational aid. The platform does not guarantee exam results or licensure eligibility.</p>
                    <h6>8. Modifications to Terms</h6>
                    <p>The developers may update these Terms of Service anytime. Continued use signifies acceptance of revisions.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="font-size: 0.95rem; line-height: 1.6;">
                    <p><strong>Last Updated: February 2025</strong></p>
                    <p><strong>ChemEase</strong> is committed to protecting your privacy. This Privacy Policy explains how we collect, use, store, and safeguard your personal information.</p>
                    <h6>1. Information We Collect</h6>
                    <p>ChemEase may collect:</p>
                    <ul>
                        <li>Personal Information: Name, email address, school, account credentials.</li>
                        <li>Usage Data: Module access history, quiz performance, analytics, login activity.</li>
                        <li>Device Information: Browser type, IP address, device type (for security and analytics).</li>
                    </ul>
                    <h6>2. How Your Information Is Used</h6>
                    <p>Your data is used to:</p>
                    <ul>
                        <li>Manage your account and verify identity.</li>
                        <li>Track your learning progress.</li>
                        <li>Maintain security through activity monitoring.</li>
                        <li>Improve system performance and user experience.</li>
                    </ul>
                    <h6>3. Data Protection and Security</h6>
                    <p>ChemEase uses encrypted database storage, Role-Based Access Control (RBAC), and restricted administrative access.</p>
                    <h6>4. Data Sharing and Disclosure</h6>
                    <p>ChemEase does not sell or share personal data with advertisers or external parties.</p>
                    <h6>5. User Rights</h6>
                    <p>Users may request to access, update, or delete their data by contacting the administrator.</p>
                    <h6>6. Data Retention</h6>
                    <p>User data is stored only as long as necessary for educational use.</p>
                    <h6>7. Cookies and Tracking</h6>
                    <p>ChemEase may use cookies for session management and analytics. You may disable them, but some features may not work.</p>
                    <h6>8. Changes to the Privacy Policy</h6>
                    <p>Continued use after updates means acceptance of the new policy.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const icons = document.querySelectorAll('.floating-icon');
            icons.forEach((icon, index) => {
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

            function createParticle() {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDelay = Math.random() * 12 + 's';
                document.body.appendChild(p);
                setTimeout(() => p.remove(), 12000);
            }
            setInterval(createParticle, 3000);

            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthBar = passwordStrength.querySelector('.strength-bar-fill');
            const strengthText = passwordStrength.querySelector('.strength-text');
            const mobileInput = document.getElementById("mobile");
            const mobileError = document.getElementById("mobileError");

            mobileInput.addEventListener("input", () => {
                const value = mobileInput.value.trim();
                const phMobileRegex = /^(09|\+639)\d{9}$/;

                if (!value) {
                    mobileError.textContent = "";
                    return;
                }

                if (!phMobileRegex.test(value)) {
                    mobileError.textContent = "Enter a valid PH mobile number (09XXXXXXXXX or +639XXXXXXXXX)";
                    mobileInput.classList.add("input-error");
                } else {
                    mobileError.textContent = "";
                    mobileInput.classList.remove("input-error");
                }
            });

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                if (password.length === 0) {
                    passwordStrength.style.display = 'none';
                    return;
                }
                passwordStrength.style.display = 'flex';
                const strength = calculatePasswordStrength(password);
                passwordStrength.className = 'password-strength strength-' + strength.level;
                strengthText.textContent = strength.text;
            });

            function calculatePasswordStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^a-zA-Z0-9]/.test(password)) score++;

                if (score <= 2) {
                    return {
                        level: 'weak',
                        text: 'Weak'
                    };
                } else if (score <= 4) {
                    return {
                        level: 'medium',
                        text: 'Medium'
                    };
                } else {
                    return {
                        level: 'strong',
                        text: 'Strong'
                    };
                }
            }

            const form = document.getElementById('signupForm');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                clearErrors();

                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const fullName = firstName + " " + lastName;
                const email = document.getElementById('email').value.trim();
                const mobile = document.getElementById('mobile').value.trim();
                const birthday = document.getElementById('birthday').value;
                const address = document.getElementById('address').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const terms = document.getElementById('terms').checked;

                let hasError = false;

                /* ---------- Name ---------- */
                if (!firstName || !lastName) {
                    showError('fullNameError', 'Please enter your full name');
                    hasError = true;
                }

                /* ---------- Email ---------- */
                if (!email) {
                    showError('emailError', 'Please enter your email');
                    hasError = true;
                } else if (!isValidEmail(email)) {
                    showError('emailError', 'Only @gmail.com and @bicol-u.edu.ph emails are allowed');
                    hasError = true;
                }

                /* ---------- Mobile ---------- */
                const phMobileRegex = /^(09|\+639)\d{9}$/;
                if (!mobile) {
                    showError('mobileError', 'Please enter your mobile number');
                    hasError = true;
                } else if (!phMobileRegex.test(mobile)) {
                    showError('mobileError', 'Enter valid PH mobile (09XXXXXXXXX or +639XXXXXXXXX)');
                    hasError = true;
                }

                /* ---------- Birthday ---------- */
                if (!birthday) {
                    showError('birthdayError', 'Please select your birthday');
                    hasError = true;
                } else if (!isValidAge(birthday, 13)) {
                    showError('birthdayError', 'You must be at least 13 years old');
                    hasError = true;
                }

                /* ---------- Address ---------- */
                if (!address) {
                    showError('addressError', 'Please enter your address');
                    hasError = true;
                }

                /* ---------- Password ---------- */
                if (!password) {
                    showError('passwordError', 'Please enter a password');
                    hasError = true;
                } else if (password.length < 8) {
                    showError('passwordError', 'Password must be at least 8 characters');
                    hasError = true;
                } else if (!isStrongPassword(password)) {
                    showError('passwordError', 'Password must contain uppercase, lowercase, number, and special character');
                    hasError = true;
                }

                /* ---------- Confirm Password ---------- */
                if (!confirmPassword) {
                    showError('confirmPasswordError', 'Please confirm your password');
                    hasError = true;
                } else if (password !== confirmPassword) {
                    showError('confirmPasswordError', 'Passwords do not match');
                    hasError = true;
                }

                /* ---------- Terms ---------- */
                if (!terms) {
                    alert('You must agree to the terms and privacy policy');
                    hasError = true;
                }

                if (hasError) return;

                /* ---------- Button UI ---------- */
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.classList.add('loading');
                btn.innerHTML = '<div class="spinner"></div>';

                try {
                    const res = await fetch('partial/signup_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            fullName,
                            email,
                            mobile,
                            birthday,
                            address,
                            password,
                            confirmPassword,
                            terms: '1'
                        })
                    });

                    const data = await res.json();

                    btn.classList.remove('loading');
                    btn.disabled = false;
                    btn.innerHTML = '<span id="btnText">Create account</span>';

                    if (data.status === 'success') {
                        showAlert(data.message + ' Welcome email sent!', 'success');
                        setTimeout(() => location.href = 'signin.php', 2500);
                    } else {
                        showAlert(data.message, 'error');
                    }

                } catch (error) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    btn.innerHTML = '<span id="btnText">Create account</span>';
                    showAlert('Network error. Try again.', 'error');
                }
            });

            function isValidEmail(email) {
                const lowerEmail = email.toLowerCase();
                return lowerEmail.endsWith('@gmail.com') || lowerEmail.endsWith('@bicol-u.edu.ph');
            }

            function isStrongPassword(password) {
                const hasUpperCase = /[A-Z]/.test(password);
                const hasLowerCase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecialChar = /[^a-zA-Z0-9]/.test(password);
                return hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar;
            }

            function isValidAge(dateString, minAge) {
                const today = new Date();
                const birthDate = new Date(dateString);
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();

                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= minAge;
            }

            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.classList.add('show');
            }

            function clearErrors() {
                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(error => {
                    error.textContent = '';
                    error.classList.remove('show');
                });
            }
        });

        function togglePassword(id) {
            const field = document.getElementById(id);
            const icon = document.getElementById(id + 'Icon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function showAlert(msg, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type==='error'?'danger':'success'} position-fixed`;
            alert.style.cssText = 'top:20px; right:20px; z-index:9999; opacity:0; transform:translateX(100%); transition:all 0.3s; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1);';
            alert.innerHTML = `<i class="fas fa-${type==='error'?'exclamation':'check'}-circle me-2"></i>${msg}`;
            document.body.appendChild(alert);
            setTimeout(() => {
                alert.style.opacity = '1';
                alert.style.transform = 'translateX(0)';
            }, 100);
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }
    </script>

</body>

</html>