<?php
// Start with a clean output buffer
global $pdo;
ob_start();
session_start();
require 'db.php';

// Get user info before destroying session
$username = $_SESSION['user'] ?? 'User';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$userId = $_SESSION['user_id'] ?? null;
$currentUTC = gmdate('Y-m-d H:i:s');
$logoutSuccess = false;

try {
    if ($userId) {
        if ($isAdmin) {
            $stmt = $pdo->prepare("
                UPDATE admins 
                SET is_online = FALSE,
                    last_login = ?,
                    updated_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$currentUTC, $currentUTC, $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_online = FALSE,
                    last_login = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$currentUTC, $currentUTC, $username, $userId]);
        }
        $logoutSuccess = true;
    }
} catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
} finally {
    // Clear session
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logging Out - Musician Database</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

            .blob1, .blob2, .background::before, .background::after {
                content: '';
                position: absolute;
                width: 500px;
                height: 500px;
                border-radius: 50%;
                filter: blur(100px);
                opacity: 0.7;
                animation: flyOut 1.5s ease-in forwards;
            }

            .background::before {
                background: #ff0099;
                animation: flyOutTopLeft 1.5s ease-in forwards;
            }

            .background::after {
                background: #00d4ff;
                animation: flyOutTopRight 1.5s ease-in forwards;
            }

            .blob1 {
                background: #9900ff;
                animation: flyOutBottomLeft 1.5s ease-in forwards;
            }

            .blob2 {
                background: #00ff99;
                animation: flyOutBottomRight 1.5s ease-in forwards;
            }

            @keyframes flyOutTopLeft {
                to {
                    transform: translate(-200%, -200%) scale(0);
                    opacity: 0;
                }
            }

            @keyframes flyOutTopRight {
                to {
                    transform: translate(200%, -200%) scale(0);
                    opacity: 0;
                }
            }

            @keyframes flyOutBottomLeft {
                to {
                    transform: translate(-200%, 200%) scale(0);
                    opacity: 0;
                }
            }

            @keyframes flyOutBottomRight {
                to {
                    transform: translate(200%, 200%) scale(0);
                    opacity: 0;
                }
            }

            .logout-message {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 40px;
                border-radius: 20px;
                text-align: center;
                animation: fadeInUp 0.5s ease-out;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                max-width: 500px;
                width: 90%;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .logout-icon {
                font-size: 4rem;
                color: #ff99cc;
                margin-bottom: 20px;
                animation: wave 1s infinite;
            }

            .logout-text {
                font-size: 1.5rem;
                margin: 20px 0;
                color: white;
                line-height: 1.6;
            }

            .logout-details {
                font-size: 1rem;
                color: rgba(255, 255, 255, 0.8);
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
            }

            .session-info {
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.6);
                margin-top: 15px;
                background: rgba(255, 255, 255, 0.1);
                padding: 10px;
                border-radius: 10px;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translate(-50%, -30%);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, -50%);
                }
            }

            @keyframes wave {
                0%, 100% { transform: rotate(0); }
                50% { transform: rotate(20deg); }
            }

            .redirect-text {
                font-size: 0.9rem;
                color: #ff99cc;
                margin-top: 20px;
                animation: pulse 1s infinite;
            }

            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }

            .user-info {
                color: #ff99cc;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
    <div class="background"></div>
    <div class="blob1"></div>
    <div class="blob2"></div>

    <div class="logout-message">
        <i class="fas fa-hand-peace logout-icon"></i>
        <div class="logout-text">
            <?php if ($logoutSuccess): ?>
                <?php if ($isAdmin): ?>
                    <div class="logout-text">
                        Goodbye, Admin <span class="user-info"><?php echo htmlspecialchars($username); ?></span>! 👋<br><br>
                        Thank you for managing the Musician Database today.<br>
                        Your administrative session has ended securely.
                    </div>
                <?php else: ?>
                    <div class="logout-text">
                        Goodbye, <span class="user-info"><?php echo htmlspecialchars($username); ?></span>! 👋<br><br>
                        Thanks for visiting the Musician Database.<br>
                        We hope you enjoyed exploring our collection!
                    </div>
                <?php endif; ?>

                <div class="logout-details">
                    Your session has been successfully ended and your status is now set to offline.
                </div>
                <div class="session-info">
                    Logout Time (UTC): <span class="user-info"><?php echo $currentUTC; ?></span>
                </div>
                <div class="redirect-text">
                    <i class="fas fa-spinner fa-spin"></i> Redirecting to home page...
                </div>
            <?php else: ?>
                <div class="logout-text">
                    Session ended.
                </div>
                <div class="redirect-text">
                    <i class="fas fa-spinner fa-spin"></i> Redirecting...
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = 'home.php';
        }, 6000);
    </script>
    </body>
    </html>
<?php
ob_end_flush();
?>