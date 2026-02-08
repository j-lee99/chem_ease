<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ChemEase - Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="signin.css">

    <!-- Favicon and icons -->
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">
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
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">

    <style>
        :root {
            --primary-blue: #17a2b8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f9ff 100%);
            color: var(--dark-text);
            overflow-x: hidden;
        }

        .form-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(23,162,184,0.12);
            border: 1px solid rgba(255,255,255,0.4);
            width: 100%;
            max-width: 420px;
            padding: 1.75rem 1.25rem;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            margin-bottom: 0.75rem;
        }

        .form-title {
            font-size: clamp(1.5rem, 6vw, 1.9rem);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .form-subtitle {
            color: #6c757d;
            font-size: clamp(0.9rem, 4vw, 1rem);
        }

        .step-title {
            font-size: clamp(1.25rem, 5vw, 1.4rem);
            font-weight: 600;
            margin: 1.5rem 0 1rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.45rem;
            display: block;
        }

        .form-input {
            width: 100%;
            padding: 0.85rem 2.8rem 0.85rem 2.8rem; /* space for left + right icons */
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255,255,255,0.7);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(23,162,184,0.15);
            background: white;
        }

        .input-container {
            position: relative;
        }

        /* Left icon (envelope, key) */
        .field-icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            font-size: 1.1rem;
            z-index: 2;
        }

        /* Right icon (eye toggle) */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-blue);
        }

        .submit-btn {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary-blue), #0891b2);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(23,162,184,0.25);
            transition: all 0.25s ease;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(23,162,184,0.35);
        }

        .submit-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .loading .spinner {
            width: 22px;
            height: 22px;
            border: 3px solid transparent;
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .step-container { display: none; }
        .step-container.active { display: block; }

        .resend-link {
            color: var(--primary-blue);
            cursor: pointer;
            font-weight: 500;
        }
        .resend-link:hover { text-decoration: underline; }

        .alert-container {
            margin: 1rem 0;
            display: none;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            text-align: center;
        }

        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        /* Password strength */
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .strength-bar {
            flex: 1;
            height: 5px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }

        .strength-text {
            font-weight: 600;
            min-width: 65px;
            text-align: right;
        }

        .strength-weak .strength-bar-fill { width: 33%; background: #ef4444; }
        .strength-medium .strength-bar-fill { width: 66%; background: #f59e0b; }
        .strength-strong .strength-bar-fill { width: 100%; background: #10b981; }

        .strength-weak .strength-text { color: #ef4444; }
        .strength-medium .strength-text { color: #f59e0b; }
        .strength-strong .strength-text { color: #10b981; }

        .error-message {
            color: #ef4444;
            font-size: 0.82rem;
            margin-top: 0.35rem;
            display: none;
        }

        .error-message.show { display: block; }

        /* Floating elements - reduced on mobile */
        .floating-icon {
            position: absolute;
            font-size: 1.6rem;
            opacity: 0.35;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--primary-blue);
            border-radius: 50%;
            opacity: 0;
            z-index: 0;
        }

        /* ── Mobile-first responsive ── */
        @media (max-width: 360px) {
            .form-card { padding: 1.25rem 1rem; border-radius: 12px; }
            .logo-icon { width: 60px; height: 60px; }
            .form-title { font-size: 1.55rem; }
            .step-title { font-size: 1.25rem; margin: 1.25rem 0 0.75rem; }
            .submit-btn { padding: 0.9rem; font-size: 0.95rem; }
            .floating-icon { font-size: 1.3rem; opacity: 0.25; }
        }

        @media (min-width: 361px) and (max-width: 575px) {
            .form-card { padding: 1.75rem 1.25rem; }
            .logo-icon { width: 70px; height: 70px; }
        }

        @media (min-width: 576px) {
            .form-card { padding: 2.25rem 2rem; max-width: 460px; }
            .logo-icon { width: 80px; height: 80px; }
            .floating-icon { font-size: 2rem; opacity: 0.5; }
            .particle { width: 4px; height: 4px; }
        }

        @media (min-width: 992px) {
            .form-card { max-width: 500px; padding: 2.5rem 2.25rem; }
            .floating-icon { opacity: 0.65; }
        }

        @media (max-width: 400px) {
            .floating-icon:nth-child(n+4) { display: none; }
            .particle { display: none; }
        }
    </style>
</head>
<body>

    <!-- Floating icons -->
    <div class="floating-icon h2o-1">H₂O</div>
    <div class="floating-icon co2">CO₂</div>
    <div class="floating-icon molecule"><i class="fas fa-atom"></i></div>
    <div class="floating-icon atom"><i class="fas fa-flask"></i></div>
    <div class="floating-icon nacl">NaCl</div>
    <div class="floating-icon h2so4">H₂SO₄</div>

    <!-- Particles -->
    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 6s;"></div>
    <div class="particle" style="left: 90%; animation-delay: 8s;"></div>

    <div class="form-container">
        <div class="form-card">
            <div class="logo-container">
                <img src="images/logo.png" alt="ChemEase Logo" class="logo-icon">
                <h1 class="form-title">Reset Password</h1>
                <p class="form-subtitle">We'll help you get back into your account</p>
            </div>

            <div class="alert-container" id="alertContainer">
                <div class="alert" id="alertMessage"></div>
            </div>

            <!-- Step 1 -->
            <div class="step-container active" id="step1">
                <h2 class="step-title">Enter your email</h2>
                <form id="emailForm">
                    <div class="form-group">
                        <label class="form-label" for="resetEmail">Email Address</label>
                        <div class="input-container">
                            <i class="fas fa-envelope field-icon-left"></i>
                            <input
                                type="email"
                                id="resetEmail"
                                class="form-input"
                                placeholder="Enter your registered email"
                                required
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    <button type="submit" class="submit-btn" id="sendOtpBtn" data-original-text="Send OTP">
                        <span id="sendOtpText">Send OTP</span>
                    </button>
                </form>
            </div>

            <!-- Step 2 -->
            <div class="step-container" id="step2">
                <h2 class="step-title">Enter OTP</h2>
                <p style="margin-bottom: 1.25rem; color: #555; font-size: 0.92rem; text-align: center;">
                    We sent a 6-digit code to <strong id="sentToEmail"></strong>
                </p>
                <form id="otpForm">
                    <div class="form-group">
                        <label class="form-label" for="otpCode">Verification Code</label>
                        <div class="input-container">
                            <i class="fas fa-key field-icon-left"></i>
                            <input
                                type="text"
                                id="otpCode"
                                class="form-input"
                                placeholder="Enter 6-digit code"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                required
                                inputmode="numeric"
                                autocomplete="one-time-code"
                            >
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="verifyOtpBtn" data-original-text="Verify OTP">
                        <span id="verifyOtpText">Verify OTP</span>
                    </button>

                    <div style="text-align: center; margin-top: 1.1rem; font-size: 0.92rem;">
                        Didn't receive code? <span class="resend-link" id="resendOtp">Resend OTP</span>
                    </div>
                </form>
            </div>

            <!-- Step 3 -->
            <div class="step-container" id="step3">
                <h2 class="step-title">Create new password</h2>
                <form id="passwordForm">
                    <input type="hidden" id="hiddenEmail" name="email">

                    <div class="form-group">
                        <label class="form-label" for="newPassword">New Password</label>
                        <div class="input-container password-container">
                            <i class="fas fa-lock field-icon-left"></i>
                            <input
                                type="password"
                                id="newPassword"
                                class="form-input"
                                placeholder="Enter new password"
                                required
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                <i class="fas fa-eye" id="newPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength" style="display: none;">
                            <div class="strength-bar">
                                <div class="strength-bar-fill"></div>
                            </div>
                            <span class="strength-text"></span>
                        </div>
                        <div class="error-message" id="newPasswordError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm Password</label>
                        <div class="input-container password-container">
                            <i class="fas fa-lock field-icon-left"></i>
                            <input
                                type="password"
                                id="confirmPassword"
                                class="form-input"
                                placeholder="Confirm new password"
                                required
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>

                    <button type="submit" class="submit-btn" id="resetPassBtn" data-original-text="Reset Password">
                        <span id="resetPassText">Reset Password</span>
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.92rem;">
                <a href="index.php" style="color: var(--primary-blue); text-decoration: none;">← Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Helpers ────────────────────────────────────────────────────────
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
            setTimeout(() => container.style.display = 'none', 5000);
        }

        function hideAlert() {
            document.getElementById('alertContainer').style.display = 'none';
        }

        function setLoading(btnId, isLoading) {
            const btn = document.getElementById(btnId);
            if (!btn) return;

            const originalText = btn.getAttribute('data-original-text') || btn.textContent.trim();

            if (isLoading) {
                btn.disabled = true;
                btn.classList.add('loading');
                btn.innerHTML = '<div class="spinner"></div>';
            } else {
                btn.disabled = false;
                btn.classList.remove('loading');
                btn.innerHTML = `<span id="${btnId}Text">${originalText}</span>`;
            }
        }

        // Password strength & validation
        function calculatePasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            if (score <= 2) return { level: 'weak', text: 'Weak' };
            if (score <= 4) return { level: 'medium', text: 'Medium' };
            return { level: 'strong', text: 'Strong' };
        }

        function isStrongPassword(password) {
            return (
                password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password) &&
                /[^a-zA-Z0-9]/.test(password)
            );
        }

        function showError(id, msg) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = msg;
                el.classList.add('show');
            }
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.classList.remove('show');
            });
        }

        // ── Step 1: Email ──────────────────────────────────────────────────
        document.getElementById('emailForm')?.addEventListener('submit', async e => {
            e.preventDefault();
            hideAlert();
            const email = document.getElementById('resetEmail').value.trim();
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showAlert('Please enter a valid email address.', 'danger');
                return;
            }
            setLoading('sendOtpBtn', true);
            try {
                const res = await fetch('partial/forget_pass_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'send_otp', email })
                });
                const data = await res.json();
                setLoading('sendOtpBtn', false);
                if (data.status === 'success') {
                    document.getElementById('sentToEmail').textContent = email;
                    document.getElementById('hiddenEmail').value = email;
                    document.getElementById('step1').classList.remove('active');
                    document.getElementById('step2').classList.add('active');
                    setTimeout(() => document.getElementById('otpCode')?.focus(), 150);
                    showAlert('OTP sent! Check your email.', 'success');
                } else {
                    showAlert(data.message || 'Failed to send OTP.', 'danger');
                }
            } catch {
                setLoading('sendOtpBtn', false);
                showAlert('Connection error. Please check your internet.', 'danger');
            }
        });

        // ── Step 2: OTP ────────────────────────────────────────────────────
        document.getElementById('otpForm')?.addEventListener('submit', async e => {
            e.preventDefault();
            hideAlert();
            const otp = document.getElementById('otpCode').value.trim();
            const email = document.getElementById('hiddenEmail').value;
            if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
                showAlert('Please enter a valid 6-digit code.', 'danger');
                return;
            }
            setLoading('verifyOtpBtn', true);
            try {
                const res = await fetch('partial/forget_pass_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'verify_otp', email, otp })
                });
                const data = await res.json();
                setLoading('verifyOtpBtn', false);
                if (data.status === 'success') {
                    document.getElementById('step2').classList.remove('active');
                    document.getElementById('step3').classList.add('active');
                    showAlert('OTP verified successfully!', 'success');
                } else {
                    showAlert(data.message || 'Invalid OTP.', 'danger');
                }
            } catch {
                setLoading('verifyOtpBtn', false);
                showAlert('Connection error. Please try again.', 'danger');
            }
        });

        // ── Step 3: Password reset ────────────────────────────────────────
        const newPass = document.getElementById('newPassword');
        const confirmPass = document.getElementById('confirmPassword');
        const strengthDiv = document.getElementById('passwordStrength');

        newPass?.addEventListener('input', () => {
            const val = newPass.value;
            if (!val) {
                strengthDiv.style.display = 'none';
                return;
            }
            strengthDiv.style.display = 'flex';
            const s = calculatePasswordStrength(val);
            strengthDiv.className = `password-strength strength-${s.level}`;
            strengthDiv.querySelector('.strength-text').textContent = s.text;
        });

        confirmPass?.addEventListener('input', () => {
            const err = document.getElementById('confirmPasswordError');
            if (confirmPass.value && newPass.value !== confirmPass.value) {
                err.textContent = "Passwords don't match";
                err.classList.add('show');
            } else {
                err.textContent = '';
                err.classList.remove('show');
            }
        });

        document.getElementById('passwordForm')?.addEventListener('submit', async e => {
            e.preventDefault();
            hideAlert();
            clearErrors();

            const email = document.getElementById('hiddenEmail').value;
            const pass = newPass.value;
            const confirm = confirmPass.value;

            let valid = true;

            if (!pass) {
                showError('newPasswordError', 'Please enter a password');
                valid = false;
            } else if (pass.length < 8) {
                showError('newPasswordError', 'Password must be at least 8 characters');
                valid = false;
            } else if (!isStrongPassword(pass)) {
                showError('newPasswordError', 'Must contain uppercase, lowercase, number & special character');
                valid = false;
            }

            if (!confirm) {
                showError('confirmPasswordError', 'Please confirm password');
                valid = false;
            } else if (pass !== confirm) {
                showError('confirmPasswordError', 'Passwords do not match');
                valid = false;
            }

            if (!valid) return;

            setLoading('resetPassBtn', true);

            try {
                const res = await fetch('partial/forget_pass_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'reset_password', email, password: pass })
                });
                const data = await res.json();
                setLoading('resetPassBtn', false);

                if (data.status === 'success') {
                    showAlert('Password reset successfully!', 'success');
                    setTimeout(() => location.replace('index.php'), 1600);
                } else {
                    showAlert(data.message || 'Failed to reset password.', 'danger');
                }
            } catch {
                setLoading('resetPassBtn', false);
                showAlert('Connection error. Please try again.', 'danger');
            }
        });

        // Resend OTP
        document.getElementById('resendOtp')?.addEventListener('click', async () => {
            const email = document.getElementById('hiddenEmail').value;
            if (!email) return;
            try {
                const res = await fetch('partial/forget_pass_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'send_otp', email, is_resend: '1' })
                });
                const data = await res.json();
                showAlert(
                    data.status === 'success' ? 'New OTP sent!' : (data.message || 'Failed to resend.'),
                    data.status === 'success' ? 'success' : 'danger'
                );
            } catch {
                showAlert('Connection error.', 'danger');
            }
        });

        // Floating icons + particles
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.floating-icon').forEach(el => {
                el.addEventListener('mouseenter', () => {
                    el.style.animationPlayState = 'paused';
                    el.style.transform = 'scale(1.25) rotate(12deg)';
                    el.style.filter = 'drop-shadow(0 0 12px rgba(23,162,184,0.6))';
                });
                el.addEventListener('mouseleave', () => {
                    el.style.animationPlayState = 'running';
                    el.style.transform = '';
                    el.style.filter = '';
                });
            });

            const createParticle = () => {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDuration = (6 + Math.random() * 8) + 's';
                document.body.appendChild(p);
                setTimeout(() => p.remove(), 14000);
            };
            setInterval(createParticle, 3200);
        });
    </script>
</body>
</html>