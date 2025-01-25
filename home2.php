<?php
declare(strict_types=1);

// Initialize session if not already started
use Random\RandomException;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Initialize variables
$pdo = null;
$musicians = [];
$is_authenticated = isset($_SESSION['user']);
$errors = [];

// Include database connection with error handling
try {
    require_once 'db.php';

    // Fetch musician data for authenticated users
    if ($is_authenticated && isset($pdo)) {
        $stmt = $pdo->prepare("SELECT name, genre, description, image_url FROM musicians");
        $stmt->execute();
        $musicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again later.";
    $musicians = [];
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $errors[] = "An unexpected error occurred. Please try again later.";
    $musicians = [];
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (RandomException $e) {

    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Musician Database - Your source for electronic music artists">
    <meta name="author" content="MusicianHub">
    <meta name="robots" content="index, follow">
    <title>Musician Database</title>

    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://www.google.com">

    <!-- External Resources with Integrity Checks -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
          integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"
            defer></script>

    <!-- reCAPTCHA -->
    <script>
        window.recaptchaLoaded = false;
        function onRecaptchaLoad() {
            window.recaptchaLoaded = true;
        }
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit"
            async
            defer></script>

    <style>
        /* CSS Variables for consistent theming */
        :root {
            --primary-color: #ff99cc;
            --primary-dark: #ff33cc;
            --primary-light: #ffb3d9;
            --background-dark: #2e0267;
            --background-light: #0b1a59;
            --text-color: #ffffff;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --shadow-color: rgba(0, 0, 0, 0.2);
            --overlay-color: rgba(0, 0, 0, 0.7);
            --blur-effect: blur(10px);
            --transition-speed: 0.3s;
        }

        /* Reset and Base Styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Base Element Styles */
        body {
            margin: 0;
            font-family: 'Calibri', system-ui, -apple-system, sans-serif;
            color: var(--text-color);
            background: linear-gradient(135deg, var(--background-dark), var(--background-light));
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Background Effects */
        .background {
            position: fixed;
            inset: 0;
            z-index: -1;
            overflow: hidden;
        }

        /* Optimized Blob Styles */
        .blob-base {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.7;
            pointer-events: none;
        }

        .blob1 {
            composes: blob-base;
            background: var(--primary-dark);
            animation:
                    moveBlob1 20s ease-in-out infinite,
                    colorChange1 15s infinite alternate;
        }

        .blob2 {
            composes: blob-base;
            background: var(--primary-light);
            animation:
                    moveBlob2 25s ease-in-out infinite,
                    colorChange2 18s infinite alternate;
        }

        /* Optimized Animation Keyframes */
        @keyframes moveBlob1 {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(50%, 50%); }
            50% { transform: translate(100%, -20%); }
            75% { transform: translate(20%, 80%); }
        }

        @keyframes moveBlob2 {
            0%, 100% { transform: translate(100%, 100%); }
            25% { transform: translate(-50%, 20%); }
            50% { transform: translate(0, 50%); }
            75% { transform: translate(80%, -40%); }
        }

        @keyframes colorChange1 {
            0% { background: var(--primary-dark); }
            50% { background: var(--primary-color); }
            100% { background: var(--primary-light); }
        }

        @keyframes colorChange2 {
            0% { background: var(--primary-light); }
            50% { background: var(--primary-color); }
            100% { background: var(--primary-dark); }
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: var(--blur-effect);
            -webkit-backdrop-filter: var(--blur-effect);
            z-index: 100;
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .nav-links {
            display: flex;
            gap: 1.25rem;
            align-items: center;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.313rem;
            background: var(--primary-dark);
            color: var(--text-color);
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }

        .btn:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--overlay-color);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: var(--blur-effect);
            -webkit-backdrop-filter: var(--blur-effect);
            padding: 2rem;
            border-radius: 1.25rem;
            width: 90%;
            max-width: 28.125rem;
            position: relative;
            transform: scale(0.8);
            transition: transform var(--transition-speed) ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        /* Form Styles */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            color: var(--text-color);
            font-size: 1rem;
            transition: all var(--transition-speed) ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 2px rgba(255, 153, 204, 0.3);
        }

        /* Table Styles */
        .table-container {
            margin-top: 1.25rem;
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.625rem;
            backdrop-filter: var(--blur-effect);
            -webkit-backdrop-filter: var(--blur-effect);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Alert Styles */
        .alert {
            position: fixed;
            top: 5rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 1rem 1.5rem;
            border-radius: 0.313rem;
            background: rgba(0, 0, 0, 0.8);
            color: var(--text-color);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .alert.success {
            background: rgba(76, 175, 80, 0.9);
        }

        .alert.error {
            background: rgba(244, 67, 54, 0.9);
        }

        /* Welcome Badge */
        .welcome-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            border-radius: 1.563rem;
            color: var(--text-color);
            font-weight: bold;
        }

        /* Utility Classes */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }

            .nav-links {
                gap: 0.75rem;
            }

            .welcome-badge {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .table-container {
                margin: 1rem -1rem;
                border-radius: 0;
            }
        }

        /* Print Styles */
        @media print {
            .modal,
            .btn,
            .background,
            .blob1,
            .blob2 {
                display: none !important;
            }

            body {
                background: none;
                color: #000;
            }

            .table-container {
                background: none;
                box-shadow: none;
            }

            th, td {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
<!-- Background Elements -->
<div class="background" aria-hidden="true">
    <div class="blob1"></div>
    <div class="blob2"></div>
</div>

<!-- Navigation -->
<nav class="navbar" role="navigation">
    <div class="logo">🎵 Musician Database</div>
    <div class="nav-links">
        <?php if ($is_authenticated): ?>
            <span class="welcome-badge">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($_SESSION['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <a href="logout.php"
               class="btn"
               title="Logout"
               data-csrf="<?= $_SESSION['csrf_token'] ?>">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span class="sr-only">Logout</span>
            </a>
        <?php else: ?>
            <a href="#" class="btn" title="Home">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span class="sr-only">Home</span>
            </a>
            <button class="btn"
                    onclick="openModal('login')"
                    title="Login">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                <span class="sr-only">Login</span>
            </button>
            <button class="btn"
                    onclick="openModal('signup')"
                    title="Sign Up">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
                <span class="sr-only">Sign Up</span>
            </button>
        <?php endif; ?>
    </div>
</nav>

<!-- Alert Messages -->
<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($_SESSION['verify_message'])): ?>
    <div class="alert <?= htmlspecialchars($_SESSION['verify_message']['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         role="alert">
        <?= htmlspecialchars($_SESSION['verify_message']['text'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['verify_message']); ?>
<?php endif; ?>

<!-- Main Content -->
<main class="container" role="main">
    <div class="content">
        <h1>Welcome to Musician Database</h1>

        <?php if ($is_authenticated): ?>
            <!-- Authenticated User Content -->
            <section class="musician-list">
                <h2>Electronic Artists Directory</h2>
                <div class="table-container">
                    <table role="grid">
                        <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Genre</th>
                            <th scope="col">Description</th>
                            <th scope="col">Image</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($musicians as $musician): ?>
                            <tr>
                                <td><?= htmlspecialchars($musician['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($musician['genre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($musician['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($musician['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($musician['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                             alt="<?= htmlspecialchars($musician['name'] ?? 'Musician', ENT_QUOTES, 'UTF-8') ?>"
                                             loading="lazy"
                                             width="50"
                                             height="50">
                                    <?php else: ?>
                                        <span class="no-image">No Image</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php else: ?>
            <!-- Non-authenticated User Content -->
            <section class="locked-content">
                <p>🔒 This content is locked</p>
                <p>Please log in or sign up to view the complete musician database</p>
            </section>

            <section class="preview-content">
                <h2>Preview of our Database</h2>
                <p>Get access to information about:</p>
                <ul>
                    <li>Top Electronic Artists</li>
                    <li>Different Genres</li>
                    <li>Detailed Descriptions</li>
                    <li>Artist Images</li>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Structure -->
<div id="modal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal()">&times;</button>

        <!-- Login Form -->
        <div id="login-form" style="display: none;">
            <div class="modal-header">Welcome Back</div>
            <div class="error-message"></div>
            <form action="login.php" method="POST">
                <?php if (isset($_SESSION['csrf_token'])): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <?php endif; ?>

                <div class="form-group email">
                    <label for="login-email">Email</label>
                    <input type="email"
                           id="login-email"
                           name="email"
                           placeholder="Enter your email"
                           required>
                </div>
                <div class="form-group password">
                    <label for="login-password">Password</label>
                    <input type="password"
                           id="login-password"
                           name="password"
                           placeholder="Enter your password"
                           required>
                </div>
                <button type="submit" class="btn-modal">Login</button>
            </form>
            <div class="form-switch">
                Don't have an account? <a href="#" onclick="openModal('signup'); return false;">Sign Up</a>
                <br>
                <a href="#" onclick="openModal('forgot'); return false;" class="forgot-link">Forgot Password?</a>
            </div>
        </div>

        <!-- Forgot Password Form -->
        <div id="forgot-form" style="display: none;">
            <div class="modal-header">Reset Password</div>
            <div class="error-message"></div>

            <!-- Step 1: Email Form -->
            <form id="forgot-email-form" style="display: block;">
                <?php if (isset($_SESSION['csrf_token'])): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <?php endif; ?>

                <div class="form-group email">
                    <label for="forgot-email">Email</label>
                    <input type="email"
                           id="forgot-email"
                           name="email"
                           placeholder="Enter your email"
                           required>
                </div>
                <div id="recaptcha-container"></div>
                <button type="submit" class="btn-modal">Send Reset Code</button>
            </form>

            <!-- Step 2: PIN Verification -->
            <form id="verify-pin-form" style="display: none;">
                <div class="form-group">
                    <label for="reset-pin">Enter 6-Digit PIN</label>
                    <input type="text"
                           id="reset-pin"
                           name="pin"
                           placeholder="Enter PIN"
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           required>
                    <div class="pin-timer">PIN expires in: <span id="pin-countdown">60</span>s</div>
                </div>
                <button type="submit" class="btn-modal">Verify PIN</button>
                <button type="button"
                        id="resend-pin"
                        class="btn-modal btn-secondary"
                        disabled>
                    Request New PIN (<span id="resend-countdown">60</span>s)
                </button>
            </form>

            <!-- Step 3: New Password -->
            <form id="reset-password-form" style="display: none;">
                <div class="form-group password">
                    <label for="new-password">New Password</label>
                    <input type="password"
                           id="new-password"
                           name="new_password"
                           placeholder="Enter new password"
                           required
                           minlength="8">
                </div>
                <div class="form-group password">
                    <label for="confirm-new-password">Confirm New Password</label>
                    <input type="password"
                           id="confirm-new-password"
                           name="confirm_new_password"
                           placeholder="Confirm new password"
                           required>
                </div>
                <button type="submit" class="btn-modal">Reset Password</button>
            </form>

            <div class="form-switch">
                Remember your password? <a href="#" onclick="openModal('login'); return false;">Login</a>
            </div>
        </div>

        <!-- Signup Form -->
        <div id="signup-form" style="display: none;">
            <div class="modal-header">Create Account</div>
            <form id="signup-form-element" action="signup.php" method="POST">
                <?php if (isset($_SESSION['csrf_token'])): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <?php endif; ?>

                <div class="error-message"></div>
                <div class="form-group username">
                    <label for="signup-username">Username</label>
                    <input type="text"
                           id="signup-username"
                           name="username"
                           placeholder="Enter your username"
                           required
                           minlength="3"
                           maxlength="50">
                </div>
                <div class="form-group email">
                    <label for="signup-email">Email</label>
                    <input type="email"
                           id="signup-email"
                           name="email"
                           placeholder="Enter your email"
                           required>
                </div>
                <div class="form-group password">
                    <label for="signup-password">Password</label>
                    <input type="password"
                           id="signup-password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           minlength="8">
                </div>
                <div class="form-group password">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password"
                           id="signup-confirm-password"
                           name="confirm_password"
                           placeholder="Confirm your password"
                           required>
                </div>
                <div class="form-group country">
                    <label for="signup-country">Country</label>
                    <input type="text"
                           id="signup-country"
                           name="country"
                           placeholder="Enter your country"
                           maxlength="100">
                </div>
                <div class="form-group phone">
                    <label for="signup-phone">Phone Number</label>
                    <input type="tel"
                           id="signup-phone"
                           name="phone"
                           placeholder="Enter your phone number"
                           pattern="[0-9+\-\s()]*">
                </div>
                <button type="submit" class="btn-modal">Sign Up</button>
            </form>
            <div class="form-switch">
                Already have an account? <a href="#" onclick="openModal('login'); return false;">Login</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    'use strict';

    // Constants
    const CONFIG = {
        ANIMATION_DURATION: 500,
        ALERT_DURATION: 3000,
        PIN_TIMEOUT: 60,
        RECAPTCHA_SITE_KEY: '6Lcbf7oqAAAAAD6SdlbYuMU19-wDDCkbuI0r1tYq',
        API_ENDPOINTS: {
            LOGIN: 'login.php',
            SIGNUP: 'signup.php',
            FORGOT_PASSWORD: 'forgot_password.php'
        }
    };

    // State management
    const state = {
        pinTimer: null,
        resendTimer: null,
        canResendPin: false,
        resetEmail: '',
        formSubmitting: false
    };

    // Utility functions
    const utils = {
        safeJSONParse(str) {
            try {
                return JSON.parse(str);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                return null;
            }
        },

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        validatePassword(password) {
            return /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
        }
    };

    // Modal management
    const modalManager = {
        currentModal: null,

        open(type) {
            const modal = document.getElementById('modal');
            const forms = {
                login: document.getElementById('login-form'),
                signup: document.getElementById('signup-form'),
                forgot: document.getElementById('forgot-form')
            };

            // Hide all forms
            Object.values(forms).forEach(form => {
                if (form) form["style"].display = 'none';
            });

            // Clean up
            this.cleanup();

            // Show requested form
            if (forms[type]) {
                forms[type].style.display = 'block';
                this.currentModal = type;
                modal.classList.add('active');

                // Initialize reCAPTCHA if needed
                if (type === 'forgot' && document.getElementById('forgot-email-form')["style"].display !== 'none') {
                    setTimeout(() => reCAPTCHA.init(), 100);
                }
            }
        },

        close() {
            const modal = document.getElementById('modal');
            modal.classList.remove('active');
            this.cleanup();
        },

        cleanup() {
            // Clear timers
            if (state.pinTimer) clearInterval(state.pinTimer);
            if (state.resendTimer) clearInterval(state.resendTimer);

            // Reset forms
            document.querySelectorAll('form').forEach(form => form.reset());

            // Hide error messages
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');

            // Clean up reCAPTCHA
            const recaptchaContainer = document.getElementById('recaptcha-container');
            if (recaptchaContainer) recaptchaContainer.innerHTML = '';
            if (typeof grecaptcha !== 'undefined') {
                try {
                    grecaptcha.reset();
                } catch (e) {
                    console.error('reCAPTCHA cleanup error:', e);
                }
            }
        }
    };

    // Form handling
    const formHandler = {
        async submitForm(formId, endpoint, data) {
            if (state.formSubmitting) return;
            state.formSubmitting = true;

            const submitButton = document.querySelector(`#${formId} button[type="submit"]`);
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(result.message);
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, CONFIG.ANIMATION_DURATION);
                    }
                } else {
                    this.showError(formId, result.message || 'An error occurred');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                this.showError(formId, 'A network error occurred');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                state.formSubmitting = false;
            }
        },

        showSuccess(message) {
            const popup = document.createElement('div');
            popup.className = 'popup success';
            popup.textContent = message;
            document.body.appendChild(popup);

            setTimeout(() => {
                popup["style"].animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => popup.remove(), CONFIG.ANIMATION_DURATION);
            }, CONFIG.ALERT_DURATION);
        },

        showError(formId, message) {
            const errorElement = document.querySelector(`#${formId} .error-message`);
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                setTimeout(() => errorElement.style.display = 'none', CONFIG.ALERT_DURATION);
            }
        }
    };

    // PIN handling
    const pinHandler = {
        startPinTimer() {
            let timeLeft = CONFIG.PIN_TIMEOUT;
            const pinCountdown = document.getElementById('pin-countdown');

            if (state.pinTimer) clearInterval(state.pinTimer);
            pinCountdown.textContent = timeLeft;
            pinCountdown.classList.remove('timer-warning');

            state.pinTimer = setInterval(() => {
                timeLeft--;
                pinCountdown.textContent = timeLeft;

                if (timeLeft <= 10) {
                    pinCountdown.classList.add('timer-warning');
                }

                if (timeLeft <= 0) {
                    clearInterval(state.pinTimer);
                    formHandler.showError('forgot-form', 'PIN has expired. Please request a new one.');
                    document.getElementById('reset-pin').disabled = true;
                    document.querySelector('#verify-pin-form button[type="submit"]').disabled = true;
                }
            }, 1000);
        },
        
        startResendTimer() {
            let timeLeft = CONFIG.PIN_TIMEOUT;
            const resendBtn = document.getElementById('resend-pin');
            const resendCountdown = document.getElementById('resend-countdown');

            state.canResendPin = false;
            if (state.resendTimer) clearInterval(state.resendTimer);

            resendBtn.disabled = true;
            resendCountdown.textContent = timeLeft;

            state.resendTimer = setInterval(() => {
                timeLeft--;
                resendCountdown.textContent = timeLeft;
                resendBtn.textContent = `Request New PIN (${timeLeft}s)`;

                if (timeLeft <= 0) {
                    clearInterval(state.resendTimer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Request New PIN';
                    state.canResendPin = true;
                }
            }, 1000);
        }
    };

    // reCAPTCHA handling
    const reCAPTCHA = {
        isLoaded() {
            return typeof grecaptcha !== 'undefined' && window.recaptchaLoaded;
        },

        init() {
            if (!this.isLoaded()) {
                console.log('Waiting for reCAPTCHA to load...');
                setTimeout(() => this.init(), 100);
                return;
            }

            try {
                grecaptcha.render('recaptcha-container', {
                    sitekey: CONFIG.RECAPTCHA_SITE_KEY,
                    callback: (response) => {
                        console.log('reCAPTCHA verified');
                    }
                });
            } catch (e) {
                console.error('reCAPTCHA initialization error:', e);
            }
        },

        async waitForLoad() {
            return new Promise((resolve) => {
                if (this.isLoaded()) {
                    resolve();
                } else {
                    const checkInterval = setInterval(() => {
                        if (this.isLoaded()) {
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 100);
                }
            });
        }
    };

    // Form validation
    const validator = {
        validateSignupForm() {
            const username = document.getElementById('signup-username')["value"].trim();
            const email = document.getElementById('signup-email')["value"].trim();
            const password = document.getElementById('signup-password')["value"];
            const confirmPassword = document.getElementById('signup-confirm-password')["value"];

            if (username.length < 3) {
                formHandler.showError('signup-form-element', 'Username must be at least 3 characters long');
                return false;
            }

            if (!utils.validateEmail(email)) {
                formHandler.showError('signup-form-element', 'Please enter a valid email address');
                return false;
            }

            if (!utils.validatePassword(password)) {
                formHandler.showError('signup-form-element', 'Password must be at least 8 characters long and include uppercase, lowercase, and numbers');
                return false;
            }

            if (password !== confirmPassword) {
                formHandler.showError('signup-form-element', 'Passwords do not match');
                return false;
            }

            return true;
        },

        validatePasswordReset() {
            const newPassword = document.getElementById('new-password')["value"];
            const confirmPassword = document.getElementById('confirm-new-password')["value"];

            if (!utils.validatePassword(newPassword)) {
                formHandler.showError('reset-password-form', 'Password must meet the requirements');
                return false;
            }

            if (newPassword !== confirmPassword) {
                formHandler.showError('reset-password-form', 'Passwords do not match');
                return false;
            }

            return true;
        }
    };

    // Event handlers
    const eventHandlers = {
        initializeFormHandlers() {
            // Signup form submission
            document.getElementById('signup-form-element')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!validator.validateSignupForm()) return;

                const formData = new FormData(e.target);
                await formHandler.submitForm('signup-form-element', CONFIG.API_ENDPOINTS.SIGNUP, Object.fromEntries(formData));
            });

            // Login form submission
            document.querySelector('#login-form form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                await formHandler.submitForm('login-form', CONFIG.API_ENDPOINTS.LOGIN, Object.fromEntries(formData));
            });

            // Forgot password email form
            document.getElementById('forgot-email-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('forgot-email')["value"];
                state.resetEmail = email;

                try {
                    await reCAPTCHA.waitForLoad();
                    const recaptchaResponse = grecaptcha.getResponse();

                    if (!recaptchaResponse) {
                        formHandler.showError('forgot-form', 'Please complete the reCAPTCHA verification');
                        return;
                    }

                    await formHandler.submitForm('forgot-email-form', CONFIG.API_ENDPOINTS.FORGOT_PASSWORD, {
                        email,
                        'g-recaptcha-response': recaptchaResponse
                    });

                    document.getElementById('forgot-email-form')["style"].display = 'none';
                    document.getElementById('verify-pin-form')["style"].display = 'block';
                    pinHandler.startPinTimer();
                    pinHandler.startResendTimer();
                } catch (error) {
                    formHandler.showError('forgot-form', 'An error occurred. Please try again.');
                }
            });

            // PIN verification form
            document.getElementById('verify-pin-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const pin = document.getElementById('reset-pin')["value"];

                await formHandler.submitForm('verify-pin-form', CONFIG.API_ENDPOINTS.FORGOT_PASSWORD, {
                    email: state.resetEmail,
                    pin
                });

                // Show password reset form on success
                document.getElementById('verify-pin-form')["style"].display = 'none';
                document.getElementById('reset-password-form')["style"].display = 'block';
            });

            // Password reset form
            document.getElementById('reset-password-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!validator.validatePasswordReset()) return;

                const newPassword = document.getElementById('new-password')["value"];
                await formHandler.submitForm('reset-password-form', CONFIG.API_ENDPOINTS.FORGOT_PASSWORD, {
                    email: state.resetEmail,
                    new_password: newPassword,
                    action: 'reset_password'
                });
            });

            // Resend PIN button
            document.getElementById('resend-pin')?.addEventListener('click', async () => {
                if (!state.canResendPin) return;

                await formHandler.submitForm('verify-pin-form', CONFIG.API_ENDPOINTS.FORGOT_PASSWORD, {
                    email: state.resetEmail,
                    resend: true
                });

                pinHandler.startPinTimer();
                pinHandler.startResendTimer();
            });
        },

        initializeAlertAnimations() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.animation = 'slideOut 0.5s ease-out forwards';
                    setTimeout(() => alert.remove(), CONFIG.ANIMATION_DURATION);
                }, CONFIG.ALERT_DURATION);
            }
        }
    };

    // Initialize everything when document is ready
    document.addEventListener('DOMContentLoaded', () => {
        eventHandlers.initializeFormHandlers();
        eventHandlers.initializeAlertAnimations();
    });

    // Export functions for global access
    window.openModal = (type) => modalManager.open(type);
    window.closeModal = () => modalManager.close();
    window.onRecaptchaLoad = () => window.recaptchaLoaded = true;
</script>
</body>
</html>