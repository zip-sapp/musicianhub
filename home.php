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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script src="src/JQuery.js"></script>
    <script>
        function onRecaptchaLoad() {
            window.recaptchaLoaded = true;
        }
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>
    <link rel="stylesheet" href="src/styles.css">
    <script src="src/scripts.js"></script>
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
        <?php
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE username = ?");
        $stmt->execute([$_SESSION['user']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['profile_picture']): ?>
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>"
                 alt="Profile"
                 class="profile-picture-small">
        <?php else: ?>
            <i class="fas fa-user-circle"></i>
        <?php endif; ?>
                <?= htmlspecialchars($_SESSION['user']); ?>
    </span>
            <a href="#" class="btn" title="Edit Profile" onclick="openProfileModal()">
                <i class="fas fa-user-edit"></i>
            </a>
            <a href="logout.php" class="btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
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

<!-- Profile Modal -->
<div id="profile-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeProfileModal()">&times;</button>
        <div class="modal-header">Edit Profile</div>

        <form id="profile-form" enctype="multipart/form-data">
            <div class="error-message" style="display: none;"></div>

            <!-- Profile Picture Section -->
            <div class="profile-section">
                <h3>Profile Picture</h3>
                <div class="profile-upload-wrapper">
                    <?php if ($user && $user['profile_picture']): ?>
                        <img src="<?= htmlspecialchars($user['profile_picture']) ?>"
                             alt="Profile"
                             class="profile-picture-large"
                             id="current-profile-picture">
                    <?php else: ?>
                        <div class="default-profile-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <img src=""
                             alt="Profile"
                             class="profile-picture-large"
                             id="current-profile-picture"
                             style="display: none;">
                    <?php endif; ?>
                    <button type="button" class="btn-upload" onclick="document.getElementById('profile-picture-input').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" style="display: none;">
                </div>
            </div>

            <!-- Account Information Section -->
            <div class="profile-section">
                <h3>Account Information</h3>
                <div class="form-group username">
                    <label for="new-username">Username</label>
                    <div class="input-group">
                        <input type="text" id="new-username" name="new_username" placeholder="Keep current username" disabled>
                        <button type="button" class="btn-edit" onclick="toggleEdit('new-username')">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group phone">
                    <label for="new-phone">Phone Number</label>
                    <div class="input-group">
                        <input type="tel" id="new-phone" name="new_phone" placeholder="Keep current phone number" disabled>
                        <button type="button" class="btn-edit" onclick="toggleEdit('new-phone')">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="profile-section">
                <h3>Change Password</h3>
                <button type="button" class="btn-change-password" onclick="togglePasswordFields()">
                    Change Password
                </button>
                <div id="password-fields" style="display: none;">
                    <div class="form-group password">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current_password" placeholder="Enter current password">
                    </div>
                    <div class="form-group password">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password" placeholder="Enter new password">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-modal">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>
