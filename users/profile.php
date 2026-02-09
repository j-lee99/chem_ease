<?php
// profile_settings.php
require_once '../partial/db_conn.php';
// session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT full_name, email, mobile, birthday, address, profile_image FROM users WHERE id = ? AND is_deleted = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: ../signin.php');
    exit;
}

$full_name = $user['full_name'];
$email = $user['email'];
$profile_image = $user['profile_image'] ?? '';
$mobile = $user['mobile'] ?? 'N/A';
$address = $user['address'] ?? 'N/A';
$birthday = $user['birthday'] ?? 'N/A';

// Get initials for fallback avatar
$initials = '';
$name_parts = explode(' ', trim($full_name));
foreach ($name_parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($initials) >= 2) break;
}
if (empty($initials)) $initials = 'U';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Profile Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
            --success-color: #28A745;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --gradient-start: #17a2b8;
            --gradient-end: #20c5d4;
        }

        body {
            background: linear-gradient(135deg, var(--light-blue) 0%, var(--light-gray) 50%, #ffffff 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            color: var(--dark-text);
        }

        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            margin-bottom: 3rem;
            text-align: center;
        }

        .settings-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-text);
        }

        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(23, 162, 184, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(23, 162, 184, 0.1);
            margin-bottom: 1.5rem;
        }

        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.15);
        }

        .card-header {
            padding: 1.5rem 1.5rem 0;
            background: transparent;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-text);
        }

        .edit-btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
        }

        .edit-btn:hover {
            background: var(--primary-blue);
            color: white;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.2);
        }

        .default-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.2);
        }

        .profile-field {
            margin-bottom: 1rem;
        }

        .field-label {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-value {
            font-size: 1.1rem;
            color: var(--dark-text);
            font-weight: 500;
        }

        .danger-card {
            border-color: rgba(220, 53, 69, 0.3);
            background: rgba(220, 53, 69, 0.02);
        }

        .danger-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .danger-description {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }

        #imagePreview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
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
            z-index: 5;
        }

        .password-toggle:hover {
            color: var(--primary-blue);
        }

        .otp-container {
            margin: 2rem 0;
            text-align: center;
        }

        .otp-input {
            font-family: monospace;
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 1.2rem;
            text-align: center;
            height: 90px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 0.5rem;
            border: 3px solid var(--primary-blue);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.35rem rgba(23, 162, 184, 0.25);
            outline: none;
        }

        .password-strength {
            margin-top: 0.5rem;
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .strength-bar {
            flex: 1;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0;
        }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 60px;
        }

        /* MOBILE RESPONSIVENESS */
        @media (max-width: 767px) {
            .settings-container {
                padding: 1.5rem 0.9rem;
            }

            .settings-title {
                font-size: 2.1rem;
            }

            .profile-info {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }

            .profile-avatar,
            .default-avatar {
                width: 110px;
                height: 110px;
                font-size: 2.6rem;
            }

            .danger-item {
                flex-direction: column;
                align-items: stretch;
                gap: 1.2rem;
            }

            .danger-item button {
                width: 100%;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-lg {
                max-width: 96%;
            }

            .otp-input {
                font-size: 2.5rem;
                letter-spacing: 0.9rem;
                height: 80px;
            }
        }

        @media (max-width: 576px) {
            .settings-title {
                font-size: 1.8rem;
            }

            .profile-avatar,
            .default-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.4rem;
            }

            .section-title {
                font-size: 1.15rem;
            }
        }
    </style>
</head>

<body>
    <div class="settings-container">
        <div class="page-header text-center mb-5">
            <h1 class="settings-title">Profile Settings</h1>
            <p class="text-muted">Manage your account information and preferences</p>
        </div>

        <div class="settings-card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title mb-0">Profile Information</h3>
                    <button class="btn btn-outline-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        Edit Profile
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="profile-info">
                    <div class="profile-avatar-container">
                        <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>" alt="Profile" class="profile-avatar" id="mainProfileImage">
                        <?php else: ?>
                            <div class="default-avatar" id="mainProfileAvatar"><?php echo htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-details">
                        <div class="profile-field">
                            <label class="field-label">Full Name</label>
                            <div class="field-value" id="displayFullName"><?php echo htmlspecialchars($full_name); ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="field-label">Email Address</label>
                            <div class="field-value" id="displayEmail"><?php echo htmlspecialchars($email); ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="field-label">Mobile Number</label>
                            <div class="field-value" id="displayMobile"><?php echo htmlspecialchars($mobile); ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="field-label">Birthday</label>
                            <div class="field-value" id="displayBirthday"><?php echo htmlspecialchars($birthday); ?></div>
                        </div>
                        <div class="profile-field">
                            <label class="field-label">Address</label>
                            <div class="field-value" id="displayAddress"><?php echo htmlspecialchars($address); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title mb-0">Account Security</h3>
                    <button class="btn btn-outline-primary edit-btn" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        Change Password
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">Your password is encrypted and secure.</p>
            </div>
        </div>

        <div class="settings-card danger-card">
            <div class="card-header">
                <h3 class="section-title mb-0 text-danger">Danger Zone</h3>
            </div>
            <div class="card-body">
                <div class="danger-item">
                    <div class="danger-info">
                        <label class="field-label text-danger">Delete Account</label>
                        <p class="danger-description">Permanently delete your account and all associated data. This cannot be undone.</p>
                    </div>
                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-blue); color: white;">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                                    <img src="../<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>" alt="Current" id="currentImagePreview" class="profile-avatar">
                                <?php else: ?>
                                    <div class="default-avatar" id="currentImagePreview"><?php echo htmlspecialchars($initials); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3">
                                <label for="profile_image" class="btn btn-outline-primary btn-sm">Change Photo</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;">
                                <img id="imagePreview" src="" alt="Preview" class="mt-2">
                                <small class="text-muted d-block mt-2">JPG, JPEG, PNG, GIF only (max 5MB)</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editFirstName" value="<?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" value="<?php echo htmlspecialchars(count(explode(' ', $full_name)) > 1 ? explode(' ', $full_name)[1] : ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="editMobile" value="<?php echo htmlspecialchars($mobile); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthday</label>
                            <input type="date" class="form-control" id="editBirthday" value="<?php echo htmlspecialchars($birthday); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="editAddress" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary"
                        style="background-color: var(--primary-blue); border-color: var(--primary-blue);"
                        onclick="updateProfile()">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-blue); color: white;">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Current Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="currentPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                    <i class="fas fa-eye" id="currentPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="newPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye" id="newPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrengthContainer">
                                <div class="strength-bar">
                                    <div class="strength-bar-fill" id="strengthBar"></div>
                                </div>
                                <span class="strength-text" id="strengthText"></span>
                            </div>
                            <div class="invalid-feedback d-block" id="newPasswordFeedback"></div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirmPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback d-block" id="confirmFeedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" style="background-color: var(--primary-blue); border-color: var(--primary-blue);" onclick="preparePasswordChange()">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal for Password Change -->
    <div class="modal fade" id="otpPasswordModal" tabindex="-1" aria-labelledby="otpPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-blue); color: white;">
                    <h5 class="modal-title" id="otpPasswordModalLabel">Verify Password Change</h5>
                </div>
                <div class="modal-body">
                    <p class="text-center mb-4">We sent a 6-digit code to your email.</p>
                    <div class="otp-container">
                        <input type="text"
                            class="form-control otp-input"
                            id="otpPasswordInput"
                            maxlength="6"
                            placeholder="------"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            required>
                    </div>
                    <small class="text-muted d-block text-center mt-3">
                        Enter the 6-digit code (check spam/junk folder if needed)
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-5" style="background-color: var(--primary-blue); border-color: var(--primary-blue);" onclick="verifyPasswordOtp()">
                        Verify & Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--danger-color); color: white;">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>Warning!</strong> This action is permanent and cannot be undone.
                    </div>
                    <p>Please enter your <strong>current password</strong> to confirm:</p>
                    <input type="password" class="form-control" id="deletePasswordConfirm" placeholder="Your password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="deleteAccount()">
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top:20px; right:20px; z-index:9999; min-width:320px; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.2);';
            toast.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':type==='danger'?'exclamation-circle':'exclamation-triangle'} me-2"></i>${message}<button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 6000);
        }

        // Password visibility toggle
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

        // Password strength calculator
        function calculatePasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            if (score <= 2) return {
                level: 'weak',
                text: 'Weak',
                color: '#dc3545',
                width: '33%'
            };
            if (score <= 4) return {
                level: 'medium',
                text: 'Medium',
                color: '#ffc107',
                width: '66%'
            };
            return {
                level: 'strong',
                text: 'Strong',
                color: '#28a745',
                width: '100%'
            };
        }

        // DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            const newPass = document.getElementById('newPassword');
            const confirmPass = document.getElementById('confirmPassword');
            const strengthContainer = document.getElementById('passwordStrengthContainer');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const newFeedback = document.getElementById('newPasswordFeedback');
            const confirmFeedback = document.getElementById('confirmFeedback');

            if (newPass && confirmPass) {
                newPass.addEventListener('input', function() {
                    const val = this.value;
                    newFeedback.textContent = '';
                    if (!val) {
                        strengthContainer.style.display = 'none';
                        return;
                    }
                    strengthContainer.style.display = 'flex';
                    const st = calculatePasswordStrength(val);
                    strengthBar.style.width = st.width;
                    strengthBar.style.backgroundColor = st.color;
                    strengthText.textContent = st.text;
                    if (val.length < 8) {
                        newFeedback.textContent = 'At least 8 characters required';
                    } else if (!/[A-Z]/.test(val) || !/[a-z]/.test(val) || !/[0-9]/.test(val) || !/[^a-zA-Z0-9]/.test(val)) {
                        newFeedback.textContent = 'Needs uppercase, lowercase, number, special character';
                    }
                });

                confirmPass.addEventListener('input', function() {
                    confirmFeedback.textContent = '';
                    if (this.value && newPass.value !== this.value) {
                        confirmFeedback.textContent = 'Passwords do not match';
                    }
                });
            }

            // Enable delete button only when password entered
            document.getElementById('deletePasswordConfirm')?.addEventListener('input', function() {
                document.getElementById('confirmDeleteBtn').disabled = !this.value.trim();
            });

            // Profile image preview
            document.getElementById('profile_image')?.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('imagePreview');
                if (!file) {
                    preview.style.display = 'none';
                    return;
                }
                const ext = file.name.split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    showToast('Only JPG, JPEG, PNG, GIF allowed', 'danger');
                    this.value = '';
                    preview.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = ev => {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });

            // OTP input - only numbers
            const otpInput = document.getElementById('otpInput');
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }

            const otpPasswordInput = document.getElementById('otpPasswordInput');
            if (otpPasswordInput) {
                otpPasswordInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }
        });

        async function updateProfile() {
            const firstName = document.getElementById('editFirstName').value.trim();
            const lastName = document.getElementById('editLastName').value.trim();
            const email = document.getElementById('editEmail').value.trim();
            const mobile = document.getElementById('editMobile').value.trim();
            const birthday = document.getElementById('editBirthday').value;
            const address = document.getElementById('editAddress').value.trim();
            const fileInput = document.getElementById('profile_image');

            if (!firstName || !email) {
                showToast('First name and email are required', 'warning');
                return;
            }

            const fullName = firstName + (lastName ? ' ' + lastName : '');

            const formData = new FormData();
            formData.append('action', 'prepare_profile_update');
            formData.append('full_name', fullName);
            formData.append('email', email);
            formData.append('mobile', mobile);
            formData.append('birthday', birthday);
            formData.append('address', address);
            if (fileInput.files?.[0]) formData.append('profile_image', fileInput.files[0]);

            try {
                showToast('Processing...', 'info');
                const res = await fetch('../partial/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
                    showToast(data.message || 'Update failed', 'danger');
                    return;
                }

                showToast('Profile updated successfully', 'success');

                // Update displayed values in main profile
                document.getElementById('displayFullName').textContent = fullName;
                document.getElementById('displayEmail').textContent = email;
                document.getElementById('displayMobile').textContent = mobile;
                document.getElementById('displayBirthday').textContent = birthday;
                document.getElementById('displayAddress').textContent = address;

                if (data.updated_image) {
                    const container = document.querySelector('.profile-avatar-container');
                    container.innerHTML = `<img src="${data.updated_image}" alt="Profile" class="profile-avatar" id="mainProfileImage">`;
                }

                setTimeout(() => location.reload(), 1000);
            } catch (err) {
                showToast('Network error – please try again', 'danger');
                console.error(err);
            }
        }


        // Verify OTP for email change
        async function verifyNewEmailOtp() {
            const otp = document.getElementById('otpInput').value.trim();
            if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
                showToast('Enter a valid 6-digit code', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'confirm_email_change');
            formData.append('otp', otp);

            try {
                const res = await fetch('../partial/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('otpVerifyModal'))?.hide();
                    setTimeout(() => location.reload(), 1600);
                }
            } catch (err) {
                showToast('Network error – please try again', 'danger');
            }
        }

        // Prepare password change → send OTP
        async function preparePasswordChange() {
            const current = document.getElementById('currentPassword').value;
            const newP = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (!current || !newP || !confirm) return showToast('All fields required', 'warning');
            if (newP.length < 8) return showToast('New password too short (min 8)', 'warning');
            if (newP !== confirm) return showToast('Passwords do not match', 'danger');

            const formData = new FormData();
            formData.append('action', 'prepare_password_change');
            formData.append('current_password', current);
            formData.append('new_password', newP);
            formData.append('confirm_password', confirm);

            try {
                showToast('Verifying...', 'info');
                const res = await fetch('../partial/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
                    showToast(data.message || 'Verification failed', 'danger');
                    return;
                }

                if (data.password_change_pending) {
                    showToast('Verification code sent to your email', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'))?.hide();
                    setTimeout(() => {
                        new bootstrap.Modal(document.getElementById('otpPasswordModal')).show();
                    }, 350);
                }
            } catch (err) {
                showToast('Network error – please try again', 'danger');
            }
        }

        // Verify OTP and update password
        async function verifyPasswordOtp() {
            const otp = document.getElementById('otpPasswordInput').value.trim();
            if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
                showToast('Enter a valid 6-digit code', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'confirm_password_change');
            formData.append('otp', otp);

            try {
                const res = await fetch('../partial/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('otpPasswordModal'))?.hide();
                    setTimeout(() => location.reload(), 1600);
                }
            } catch (err) {
                showToast('Network error – please try again', 'danger');
            }
        }

        // Delete Account
        async function deleteAccount() {
            const pass = document.getElementById('deletePasswordConfirm').value.trim();
            if (!pass) return showToast('Password required', 'warning');

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('password', pass);

            try {
                const res = await fetch('../partial/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    setTimeout(() => window.location.href = '../partial/logout.php', 2200);
                }
            } catch (err) {
                showToast('Network error', 'danger');
            }
        }
    </script>
</body>

</html>