<?php
global $pdo;
session_start();
$is_authenticated = isset($_SESSION['user']);

// Include database connection
require 'db.php';

// Only fetch musician data if user is authenticated
$musicians = [];
if ($is_authenticated) {
    try {
        $stmt = $pdo->query("SELECT * FROM musicians");
        $musicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching musicians: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musician Database</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="src/JQuery.js"></script>
    <script>
        function onRecaptchaLoad() {
            window.recaptchaLoaded = true;
        }
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>
    <style>
        body {
            margin: 0;
            font-family: 'Calibri', serif;
            color: #fff;
            background: linear-gradient(135deg, #2e0267, #0b1a59);
            overflow: hidden;
            height: 100vh;
            will-change: transform;
            -webkit-font-smoothing: antialiased;
        }

        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        /* Blob elements - shared styles */
        .background::before,
        .background::after,
        .blob1,
        .blob2 {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.7;
        }

        /* Individual blob styles */
        .background::before {
            background: #ff0099;
            animation: moveBlob1 20s ease-in-out infinite,
            colorChange1 15s infinite alternate;
        }

        .background::after {
            background: #00d4ff;
            animation: moveBlob2 25s ease-in-out infinite,
            colorChange2 18s infinite alternate;
        }

        .blob1 {
            background: #9900ff;
            animation: moveBlob3 22s ease-in-out infinite,
            colorChange3 12s infinite alternate;
        }

        .blob2 {
            background: #00ff99;
            animation: moveBlob4 28s ease-in-out infinite,
            colorChange4 20s infinite alternate;
        }

        @keyframes moveBlob1 {
            0% { transform: translate(0%, 0%); }
            25% { transform: translate(50%, 50%); }
            50% { transform: translate(100%, -20%); }
            75% { transform: translate(20%, 80%); }
            100% { transform: translate(0%, 0%); }
        }

        @keyframes moveBlob2 {
            0% { transform: translate(100%, 100%); }
            25% { transform: translate(-50%, 20%); }
            50% { transform: translate(0%, 50%); }
            75% { transform: translate(80%, -40%); }
            100% { transform: translate(100%, 100%); }
        }

        @keyframes moveBlob3 {
            0% { transform: translate(-50%, -50%); }
            25% { transform: translate(70%, 30%); }
            50% { transform: translate(30%, 70%); }
            75% { transform: translate(-20%, 20%); }
            100% { transform: translate(-50%, -50%); }
        }

        @keyframes moveBlob4 {
            0% { transform: translate(100%, -100%); }
            25% { transform: translate(0%, 50%); }
            50% { transform: translate(-50%, -30%); }
            75% { transform: translate(30%, 0%); }
            100% { transform: translate(100%, -100%); }
        }

        @keyframes colorChange1 {
            0% { background: #ff0099; }
            33% { background: #ff6600; }
            66% { background: #ff3366; }
            100% { background: #cc00ff; }
        }

        @keyframes colorChange2 {
            0% { background: #00d4ff; }
            33% { background: #00ffcc; }
            66% { background: #33ccff; }
            100% { background: #0066ff; }
        }

        @keyframes colorChange3 {
            0% { background: #9900ff; }
            33% { background: #ff00cc; }
            66% { background: #cc33ff; }
            100% { background: #6600ff; }
        }

        @keyframes colorChange4 {
            0% { background: #00ff99; }
            33% { background: #66ff33; }
            66% { background: #33ff66; }
            100% { background: #00ff66; }
        }

        /* Navbar Styling */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff99cc;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            position: fixed;
            right: 20px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-size: 1.2rem;
            transition: color 0.3s, transform 0.2s;
        }

        .navbar .nav-links a:hover {
            color: #ff99cc;
            transform: scale(1.1);
        }

        .navbar .nav-links a i {
            font-size: 1.5rem;
        }

        /* Main Content Styling */
        .container {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding-top: 60px; /* For Navbar Offset */
        }

        .content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ff99cc;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            background: #ff33cc;
            padding: 10px 20px;
            font-size: 1rem;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .btn:hover {
            background: #ff66ff;
            transform: scale(1.1);
        }

        /* Locked Table Placeholder */
        .table-preview {
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-style: italic;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            animation: scaleUp 0.5s ease-out;
        }

        @keyframes scaleUp {
            from {
                transform: scale(0.8);
            }
            to {
                transform: scale(1);
            }
        }

        .modal-header {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #ff99cc;
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #ff99cc;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .modal-close:hover {
            transform: scale(1.2);
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group label {
            font-size: 0.95rem;
            margin-bottom: 10px;
            display: block;
            color: rgba(255, 255, 255, 0.9);
            text-align: left;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Left padding for icon space */
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            position: relative;
        }

        .form-group input:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-modal {
            width: 70%;
            padding: 10px;
            background: #ff33cc;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-modal:hover {
            background: #ff66ff;
            transform: scale(1.05);
        }

        .popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            color: #fff;
            font-size: 1.2rem;
            animation: fadeIn 0.3s ease-in-out;
            z-index: 2000;
        }

        .popup.success {
            background: #4caf50; /* Green */
        }

        .popup.error {
            background: #f44336; /* Red */
        }

        /* Styles for the unlocked table */
        .table-container {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        th {
            background: rgba(0, 0, 0, 0.5);
            font-weight: bold;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        img {
            width: 50px;
            height: 50px;
            border-radius: 5px;
        }

        .locked-content {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .locked-content p {
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .action-buttons {
            margin-top: 20px;
        }

        .action-buttons .btn {
            margin: 0 10px;
        }

        .preview-content {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .preview-content ul {
            list-style-type: none;
            padding: 0;
        }

        .preview-content li {
            margin: 10px 0;
            font-size: 1.1rem;
        }

        .welcome-badge {
            background: linear-gradient(135deg, #ff33cc, #ff99cc);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            color: white;
            box-shadow: 0 2px 10px rgba(255, 153, 204, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: glow 2s ease-in-out infinite;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes glow {
            0% { box-shadow: 0 2px 10px rgba(255, 153, 204, 0.3); }
            50% { box-shadow: 0 2px 20px rgba(255, 153, 204, 0.5); }
            100% { box-shadow: 0 2px 10px rgba(255, 153, 204, 0.3); }
        }

        .welcome-badge i {
            font-size: 1.2rem;
            color: white;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            font-size: 2.2rem;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #ff33cc, #ff99cc);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(255, 153, 204, 0.3);
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group label {
            font-size: 0.95rem;
            margin-bottom: 10px;
            display: block;
            color: rgba(255, 255, 255, 0.9);
            text-align: left;
            transition: all 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px 15px 45px; /* Added left padding for icons */
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ff99cc;
            box-shadow: 0 0 15px rgba(255, 153, 204, 0.3);
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Add icons to input fields */
        .form-group::before {
            font-family: "Font Awesome 5 Free", sans-serif;
            position: absolute;
            left: 15px;
            top: 47px; /* Adjusted to align with input */
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 900;
            z-index: 1;
        }

        .form-group.username::before {
            content: "\f007"; /* user icon */
        }

        .form-group.email::before {
            content: "\f0e0"; /* envelope icon */
        }

        .form-group.password::before {
            content: "\f023"; /* lock icon */
        }

        .form-group.country::before {
            content: "\f0ac"; /* globe icon */
        }

        .form-group.phone::before {
            content: "\f095"; /* phone icon */
        }

        .btn-modal {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff33cc, #ff99cc);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 51, 204, 0.3);
        }

        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 51, 204, 0.4);
            background: linear-gradient(135deg, #ff66ff, #ffb3d9);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        /* Form switch link */
        .form-switch {
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .form-switch a {
            color: #ff99cc;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-switch a:hover {
            color: #ff66ff;
            text-shadow: 0 0 10px rgba(255, 153, 204, 0.5);
        }

        .error-message {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .btn-modal:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
            background: linear-gradient(135deg, #999, #666);
        }

        .popup {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        .popup.success {
            background-color: #4CAF50;
            color: white;
        }

        .popup.error {
            background-color: #f44336;
            color: white;
        }

        .error-message {
            color: #f44336;
            margin-top: 5px;
            display: none;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert {
            position: fixed;
            top: 80px; /* Below your navbar */
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .alert.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }

        .alert.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #f44336;
            color: #f44336;
        }

        .forgot-link {
            color: #ff99cc;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 10px;
            display: inline-block;
        }

        .forgot-link:hover {
            color: #ff66ff;
            text-decoration: underline;
        }

        #reset-pin {
            letter-spacing: 8px;
            font-size: 1.2rem;
            text-align: center;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #666, #999) !important;
            margin-top: 10px;
        }

        .btn-secondary:not(:disabled):hover {
            background: linear-gradient(135deg, #777, #aaa) !important;
        }

        .btn-secondary:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .pin-timer {
            color: #ff99cc;
            font-size: 0.9rem;
            margin-top: 5px;
            text-align: center;
        }

        #recaptcha-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }

        .g-recaptcha {
            transform-origin: center;
            -webkit-transform-origin: center;
        }

        #pin-countdown, #resend-countdown {
            font-weight: bold;
            color: #ff33cc;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .timer-warning {
            animation: pulse 1s infinite;
            color: #ff3366;
        }

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

        @keyframes slideOut {
            from {
                transform: translate(-50%, 0);
                opacity: 1;
            }
            to {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
        }

    </style>
</head>
<body>
<div class="background"></div>
<div class="blob1"></div>
<div class="blob2"></div>
<div class="navbar">
    <div class="logo">🎵 Musician Database</div>
    <div class="nav-links">
        <?php if (isset($_SESSION['user'])): ?>
            <span class="welcome-badge">
            <i class="fas fa-user-circle"></i>
            <?= htmlspecialchars($_SESSION['user']); ?>
        </span>
            <a href="logout.php" class="btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="#" class="btn" title="Home"><i class="fas fa-home"></i></a>
            <a href="#" class="btn" title="Login" onclick="openModal('login')"><i class="fas fa-sign-in-alt"></i></a>
            <a href="#" class="btn" title="Sign Up" onclick="openModal('signup')"><i class="fas fa-user-plus"></i></a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['verify_message'])): ?>
    <div class="alert <?php echo $_SESSION['verify_message']['type']; ?>">
        <?php
        echo $_SESSION['verify_message']['text'];
        unset($_SESSION['verify_message']); // Clear the message after displaying
        ?>
    </div>
<?php endif; ?>

<div class="container">
    <div class="content">
        <h1>Welcome to Musician Database</h1>
        <?php if ($is_authenticated): ?>
            <p>Below is the curated table of electronic artists:</p>
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Genre</th>
                        <th>Description</th>
                        <th>Image</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($musicians as $musician): ?>
                        <tr>
                            <td><?= htmlspecialchars($musician['name']); ?></td>
                            <td><?= htmlspecialchars($musician['genre']); ?></td>
                            <td><?= htmlspecialchars($musician['description']); ?></td>
                            <td>
                                <?php if ($musician['image_url']): ?>
                                    <img src="<?= htmlspecialchars($musician['image_url']); ?>"
                                         alt="<?= htmlspecialchars($musician['name']); ?>">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="locked-content">
                <p>🔒 This content is locked</p>
                <p>Please log in or sign up to view the complete musician database</p>
                <div class="action-buttons">
                    <!-- <button class="btn" onclick="openModal('login')">Login</button> -->
                    <!-- <button class="btn" onclick="openModal('signup')">Sign Up</button> -->
                </div>
            </div>
            <!-- Add some preview or teaser content here -->
            <div class="preview-content">
                <h3>Preview of our Database</h3>
                <p>Get access to information about:</p>
                <ul>
                    <li>Top Electronic Artists</li>
                    <li>Different Genres</li>
                    <li>Detailed Descriptions</li>
                    <li>Artist Images</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Structure -->
<div id="modal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>

        <!-- Login Form -->
        <div id="login-form" style="display: none;">
            <div class="modal-header">Welcome Back</div>
            <div class="error-message"></div>
            <form action="login.php" method="POST">
                <div class="form-group email">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group password">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-modal">Login</button>
            </form>
            <div class="form-switch">
                Don't have an account? <a href="#" onclick="openModal('signup'); return false;">Sign Up</a>
                <br>
                <a href="#" onclick="openModal('forgot'); return false;" class="forgot-link">Forgot Password?</a>
            </div>
        </div>

        <!-- Add new forgot password modal -->
        <div id="forgot-form" style="display: none;">
            <div class="modal-header">Reset Password</div>
            <div class="error-message"></div>

            <!-- Step 1: Email Form -->
            <form id="forgot-email-form" style="display: block;">
                <div class="form-group email">
                    <label for="forgot-email">Email</label>
                    <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required>
                </div>
                <div id="recaptcha-container"></div>
                <button type="submit" class="btn-modal">Send Reset Code</button>
            </form>

            <!-- Step 2: PIN Verification Form -->
            <form id="verify-pin-form" style="display: none;">
                <div class="form-group">
                    <label for="reset-pin">Enter 6-Digit PIN</label>
                    <input type="text" id="reset-pin" name="pin" placeholder="Enter PIN"
                           maxlength="6" pattern="\d{6}" required>
                    <div class="pin-timer">PIN expires in: <span id="pin-countdown">60</span>s</div>
                </div>
                <button type="submit" class="btn-modal">Verify PIN</button>
                <button type="button" id="resend-pin" class="btn-modal btn-secondary" disabled>
                    Request New PIN (<span id="resend-countdown">60</span>s)
                </button>
            </form>

            <form id="reset-password-form" style="display: none;">
                <div class="form-group password">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new_password"
                           placeholder="Enter new password" required minlength="8">
                </div>
                <div class="form-group password">
                    <label for="confirm-new-password">Confirm New Password</label>
                    <input type="password" id="confirm-new-password" name="confirm_new_password"
                           placeholder="Confirm new password" required>
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
                <div class="error-message"></div>
                <div class="form-group username">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="username" placeholder="Enter your username" required minlength="3" maxlength="50">
                </div>
                <div class="form-group email">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group password">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" placeholder="Enter your password" required minlength="8">
                </div>
                <div class="form-group password">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                <div class="form-group country">
                    <label for="signup-country">Country</label>
                    <input type="text" id="signup-country" name="country" placeholder="Enter your country" maxlength="100">
                </div>
                <div class="form-group phone">
                    <label for="signup-phone">Phone Number</label>
                    <input type="tel" id="signup-phone" name="phone" placeholder="Enter your phone number" pattern="[0-9+\-\s()]*">
                </div>
                <button type="submit" class="btn-modal">Sign Up</button>
            </form>
            <div class="form-switch">
                Already have an account? <a href="#" onclick="openModal('login'); return false;">Login</a>
            </div>
        </div>
    </div>
</div>

<script>
    let pinTimer = null;
    let resendTimer = null;
    let canResendPin = false;
    let resetEmail = '';

    function isRecaptchaLoaded() {
        return typeof grecaptcha !== 'undefined' && window.recaptchaLoaded;
    }

    function initRecaptcha() {
        if (typeof grecaptcha === 'undefined') {
            console.log('Waiting for reCAPTCHA to load...');
            setTimeout(initRecaptcha, 100);
            return;
        }

        try {
            grecaptcha.render('recaptcha-container', {
                'sitekey': '6Lcbf7oqAAAAAD6SdlbYuMU19-wDDCkbuI0r1tYq', // Replace with your site key
                'callback': function(response) {
                    console.log('reCAPTCHA verified');
                }
            });
        } catch (e) {
            console.error('reCAPTCHA initialization error:', e);
        }
    }

    function waitForRecaptcha() {
        return new Promise((resolve) => {
            if (isRecaptchaLoaded()) {
                resolve();
            } else {
                const checkRecaptcha = setInterval(() => {
                    if (isRecaptchaLoaded()) {
                        clearInterval(checkRecaptcha);
                        resolve();
                    }
                }, 100);
            }
        });
    }

    function startPinTimer() {
        let timeLeft = 60;
        const pinCountdown = $('#pin-countdown');

        clearInterval(pinTimer);
        pinCountdown.text(timeLeft).removeClass('timer-warning');

        pinTimer = setInterval(() => {
            timeLeft--;
            pinCountdown.text(timeLeft);

            if (timeLeft <= 10) {
                pinCountdown.addClass('timer-warning');
            }

            if (timeLeft <= 0) {
                clearInterval(pinTimer);
                showFormError('forgot-form', 'PIN has expired. Please request a new one.');
                $('#reset-pin').val('').prop('disabled', true);
                $('#verify-pin-form button[type="submit"]').prop('disabled', true);
            }
        }, 1000);
    }

    function startResendTimer() {
        let timeLeft = 60;
        const resendBtn = $('#resend-pin');
        const resendCountdown = $('#resend-countdown');

        canResendPin = false;
        clearInterval(resendTimer);
        resendBtn.prop('disabled', true);
        resendCountdown.text(timeLeft);

        resendTimer = setInterval(() => {
            timeLeft--;
            resendCountdown.text(timeLeft);
            resendBtn.text(`Request New PIN (${timeLeft}s)`);

            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                resendBtn.prop('disabled', false);
                resendBtn.text('Request New PIN');
                canResendPin = true;
            }
        }, 1000);
    }

    // Global functions
    function openModal(type) {
        const modal = document.getElementById('modal');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const forgotForm = document.getElementById('forgot-form');

        modal.classList.add('active');
        loginForm["style"].display = 'none';
        signupForm["style"].display = 'none';
        if (forgotForm) forgotForm["style"].display = 'none';

        // Clean up any existing reCAPTCHA
        $('#recaptcha-container').empty();

        if (type === 'login') {
            loginForm["style"].display = 'block';
        } else if (type === 'signup') {
            signupForm["style"].display = 'block';
        } else if (type === 'forgot') {
            forgotForm["style"].display = 'block';
            $('#forgot-email-form').show();
            $('#verify-pin-form').hide();
            $('#reset-password-form').hide();

            // Only initialize reCAPTCHA if we're showing the email form
            if ($('#forgot-email-form').is(':visible')) {
                setTimeout(() => {
                    initRecaptcha();
                }, 100);
            }
        }

        $('.error-message').hide();
    }

    function cleanupModal() {
        // Clear all timers
        clearInterval(pinTimer);
        clearInterval(resendTimer);

        // Reset forms
        $('form').trigger('reset');

        // Hide all error messages
        $('.error-message').hide();

        // Clean up reCAPTCHA
        $('#recaptcha-container').empty();
        if (typeof grecaptcha !== 'undefined') {
            try {
                grecaptcha.reset();
            } catch (e) {
                console.log('reCAPTCHA cleanup error:', e);
            }
        }
    }

    // Update closeModal to use cleanup function
    function closeModal() {
        const modal = document.getElementById('modal');
        modal.classList.remove('active');
        cleanupModal();
    }

    function validateSignupForm() {
        const username = $('#signup-username').val().trim();
        const email = $('#signup-email').val().trim();
        const password = $('#signup-password').val();
        const confirmPassword = $('#signup-confirm-password').val();

        // Clear previous error messages
        $('.error-message').hide();

        if (username.length < 3) {
            showFormError('signup-form-element', 'Username must be at least 3 characters long');
            return false;
        }

        if (!email) {
            showFormError('signup-form-element', 'Email is required');
            return false;
        }

        if (password.length < 8) {
            showFormError('signup-form-element', 'Password must be at least 8 characters long');
            return false;
        }

        if (password !== confirmPassword) {
            showFormError('signup-form-element', 'Passwords do not match');
            return false;
        }

        return true;
    }

    function showFormError(formId, message) {
        console.log('Showing error:', message);
        const errorElement = $(`#${formId} .error-message`);
        errorElement.text(message).slideDown();

        // Auto-hide error message after 3 seconds
        setTimeout(() => {
            errorElement.slideUp();
        }, 3000);
    }

    function showPopup(type, message) {
        // Remove any existing popups
        $('.popup').remove();

        const popup = $('<div>')
            .addClass(`popup ${type}`)
            .text(message);
        $('body').append(popup);

        // Auto-remove popup after 3 seconds
        setTimeout(() => {
            popup.fadeOut(500, () => popup.remove());
        }, 3000);
    }

    // Main initialization function
    function initializeEventHandlers() {
        // Clear any existing handlers
        $(document).off('submit', '#signup-form-element');
        $(document).off('submit', '#login-form form');

        // Handle signup form submission
        $('#signup-form-element').on('submit', function(e) {
            e.preventDefault();
            console.log('Signup form submitted');

            if (!validateSignupForm()) {
                return false;
            }

            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Creating Account...');

            const formData = $(this).serialize();
            console.log('Form data:', formData);

            $.ajax({
                url: 'signup.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('Server response:', response);
                    if (response.success) {
                        showPopup('success', response.message);
                        closeModal(); // Close modal before reload
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showFormError('signup-form-element', response.message || 'Registration failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    showFormError('signup-form-element', 'An error occurred during registration');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Sign Up');
                }
            });
        });

        // Handle login form submission
        // Handle login form submission
        $('#login-form form').on('submit', function(e) {
            e.preventDefault();

            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Logging in...');

            const formData = $(this).serialize();

            $.ajax({
                url: 'login.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showPopup('success', response.message);
                        closeModal(); // Close modal before redirect
                        setTimeout(() => {
                            // Check if there's a redirect URL in the response
                            if (response.redirect) {
                                window.location.href = response.redirect; // Redirect to specified URL
                            } else {
                                window.location.reload(); // Fallback to reload if no redirect specified
                            }
                        }, 2000);
                    } else {
                        showFormError('login-form', response.message || 'Login failed');
                        $('.error-message').css('color', '#dc3545'); // Error color
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Login error:', status, error);
                    showFormError('login-form', 'Please check your email for verification.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Login');
                }
            });
        });

        $('#forgot-email-form').off('submit').on('submit', async function(e) {
            e.preventDefault();
            const email = $('#forgot-email').val();
            resetEmail = email;

            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Sending...');

            try {
                // Wait for reCAPTCHA to load
                await waitForRecaptcha();

                const recaptchaResponse = grecaptcha.getResponse();
                if (!recaptchaResponse) {
                    showFormError('forgot-form', 'Please complete the reCAPTCHA verification');
                    submitButton.prop('disabled', false).text('Send Reset Code');
                    return;
                }

                $.ajax({
                    url: 'forgot_password.php',
                    type: 'POST',
                    data: {
                        email: email,
                        'g-recaptcha-response': recaptchaResponse
                    },
                    dataType: 'json', // Specify expected data type
                    success: function(response) {
                        console.log('Success response:', response); // Debug log
                        if (response.success) {
                            showPopup('success', response.message);
                            $('#forgot-email-form').hide();
                            $('#verify-pin-form').show();
                            $('#reset-pin').val('').prop('disabled', false);
                            $('#verify-pin-form button[type="submit"]').prop('disabled', false);
                            startPinTimer();
                            startResendTimer();
                        } else {
                            showFormError('forgot-form', response.message || 'Failed to send reset code');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        let errorMessage = 'An error occurred. Please try again.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        showFormError('forgot-form', errorMessage);
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).text('Send Reset Code');
                        try {
                            grecaptcha.reset();
                        } catch (e) {
                            console.error('Error resetting reCAPTCHA:', e);
                        }
                    }
                });
            } catch (error) {
                console.error('reCAPTCHA error:', error);
                showFormError('forgot-form', 'reCAPTCHA loading failed. Please refresh the page.');
                submitButton.prop('disabled', false).text('Send Reset Code');
            }
        });

        // PIN resend button handler
        $('#resend-pin').off('click').on('click', function() {
            if (!canResendPin) return;

            const resendButton = $(this);
            resendButton.prop('disabled', true).text('Sending...');

            $.ajax({
                url: 'forgot_password.php',
                type: 'POST',
                data: {
                    email: resetEmail,
                    resend: true
                },
                success: function(response) {
                    if (response.success) {
                        showPopup('success', 'A new PIN has been sent to your email');
                        $('#reset-pin').val('').prop('disabled', false);
                        $('#verify-pin-form button[type="submit"]').prop('disabled', false);
                        startPinTimer();
                        startResendTimer();

                        // Update button text with countdown
                        resendButton.text('Request New PIN (60s)');
                    } else {
                        showFormError('forgot-form', response.message);
                        resendButton.prop('disabled', false).text('Request New PIN');
                    }
                },
                error: function() {
                    showFormError('forgot-form', 'Failed to send new PIN. Please try again.');
                    resendButton.prop('disabled', false).text('Request New PIN');
                },
                complete: function() {
                    // This ensures the button text is always reset if something goes wrong
                    if (resendButton.text() === 'Sending...') {
                        resendButton.prop('disabled', false).text('Request New PIN');
                    }
                }
            });
        });

        // PIN verification form submission
        $('#verify-pin-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            const pin = $('#reset-pin').val();
            const submitButton = $(this).find('button[type="submit"]');

            submitButton.prop('disabled', true).text('Verifying...');

            $.ajax({
                url: 'forgot_password.php',
                type: 'POST',
                data: {
                    email: resetEmail,
                    pin: pin
                },
                success: function(response) {
                    if (response.success) {
                        showPopup('success', 'PIN verified successfully!');
                        clearInterval(pinTimer);
                        clearInterval(resendTimer);

                        // Clean up reCAPTCHA
                        $('#recaptcha-container').empty();
                        if (typeof grecaptcha !== 'undefined') {
                            try {
                                grecaptcha.reset();
                            } catch (e) {
                                console.log('reCAPTCHA cleanup error:', e);
                            }
                        }

                        // Show password reset form
                        $('#verify-pin-form').hide();
                        $('#reset-password-form').show();
                    } else {
                        showFormError('forgot-form', response.message);
                    }
                },
                error: function() {
                    showFormError('forgot-form', 'Verification failed. Please try again.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Verify PIN');
                }
            });
        });

        $('#reset-password-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            const newPassword = $('#new-password').val();
            const confirmPassword = $('#confirm-new-password').val();
            const submitButton = $(this).find('button[type="submit"]');

            if (newPassword.length < 8) {
                showFormError('forgot-form', 'Password must be at least 8 characters long');
                return;
            }

            if (newPassword !== confirmPassword) {
                showFormError('forgot-form', 'Passwords do not match');
                return;
            }

            submitButton.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: 'forgot_password.php',
                type: 'POST',
                data: {
                    email: resetEmail,
                    new_password: newPassword,
                    action: 'reset_password'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showPopup('success', response.message);
                        setTimeout(() => {
                            closeModal();
                            openModal('login');
                        }, 2000);
                    } else {
                        showFormError('forgot-form', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Password reset error:', error);
                    showFormError('forgot-form', 'Password reset failed. Please try again.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Reset Password');
                }
            });
        });
    }

    // Initialize everything when document is ready
    $(document).ready(function() {
        initializeEventHandlers();

        // Handle alert animations
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(function() {
                alert.style.animation = 'slideOut 0.5s ease-out forwards';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 3000);
        }
    });
</script>
</body>
</html>
