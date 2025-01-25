<?php
global $pdo;
require_once 'db.php';
require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';
require_once 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Random\RandomException;

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

function verifyRecaptcha($recaptchaResponse) {
    $secretKey = "your-secret-key";
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

/**
 * @throws Exception
 */
function generatePin(): string {
    try {
        return str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
    } catch (RandomException $e) {
        error_log("PIN generation error: " . $e->getMessage());
        throw new Exception('Error generating PIN');
    }
}

function sendResetPin(string $email, string $pin): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aaronsaplala7@gmail.com';
        $mail->Password = 'cqdu cjqg fjbs yxii';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('aaronsaplala7@gmail.com', 'Musician Database');
        $mail->addAddress($email);
        $mail->isHTML();

        $mail->Subject = 'Password Reset PIN - Musician Database';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff33cc;'>Password Reset Request</h2>
                <p>Your password reset PIN is: <strong style='font-size: 24px; color: #ff33cc;'>$pin</strong></p>
                <p>This PIN will expire in 1 minute.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function canResendPin(string $email): bool {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT reset_attempts, last_pin_request 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) return false; // Email doesn't exist

    $attempts = intval($result['reset_attempts']);

    // Immediately return false if attempts are >= 3
    if ($attempts >= 3) {
        // Check if 60 seconds have passed to reset attempts
        if ($result['last_pin_request']) {
            $lastRequest = strtotime($result['last_pin_request']);
            $currentTime = strtotime(gmdate('Y-m-d H:i:s'));

            if (($currentTime - $lastRequest) >= 60) {
                // Reset attempts after 60 seconds
                $resetStmt = $pdo->prepare("
                    UPDATE users 
                    SET reset_attempts = 0,
                        last_pin_request = NULL
                    WHERE email = ?
                ");
                $resetStmt->execute([$email]);
                return true;
            }
        }
        return false; // Still within 60 seconds window with >= 3 attempts
    }

    // If there's no previous request, allow it
    if (!$result['last_pin_request']) return true;

    $lastRequest = strtotime($result['last_pin_request']);
    $currentTime = strtotime(gmdate('Y-m-d H:i:s'));

    // Check if 60 seconds have passed since last request
    return ($currentTime - $lastRequest) >= 60;
}

function incrementResetAttempts(string $email): void {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE users 
        SET reset_attempts = reset_attempts + 1 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
}

function checkAndResetAttempts(string $email): void {
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE users 
        SET reset_attempts = CASE 
            WHEN TIMESTAMPDIFF(SECOND, last_pin_request, UTC_TIMESTAMP()) >= 60 
            THEN 0
            ELSE reset_attempts 
            END
        WHERE email = ?
    ");
    $stmt->execute([$email]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        session_start();
        $currentUser = $_SESSION['user'] ?? 'unknown user';
        $currentTime = gmdate('Y-m-d H:i:s');

        if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
            // Password reset - NO reCAPTCHA needed here
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $newPassword = $_POST['new_password'];

            // Validate password
            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }

            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password and clear reset fields
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?,
                    reset_pin = NULL,
                    reset_pin_expires = NULL,
                    reset_attempts = 0
                WHERE email = ?
            ");
            $stmt->execute([$hashedPassword, $email]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Password reset failed. Please try again.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully!'
            ]);
            exit;

        } elseif (isset($_POST['email']) && !isset($_POST['resend']) && !isset($_POST['pin'])) {
            // Initial PIN request
            if (!isset($_POST['g-recaptcha-response'])) {
                throw new Exception('Please complete the reCAPTCHA verification.');
            }

            if (!verifyRecaptcha($_POST['g-recaptcha-response'])) {
                throw new Exception('reCAPTCHA verification failed.');
            }

            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

            // Check if email exists and get current attempts
            $stmt = $pdo->prepare("
                SELECT id, reset_attempts, last_pin_request 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Email not found.');
            }

            // Check if user can request a PIN
            if (!canResendPin($email)) {
                throw new Exception('Too many attempts. Please wait 60 seconds before trying again.');
            }

            // Generate and save reset PIN
            $pin = generatePin();
            $expires = gmdate('Y-m-d H:i:s', strtotime('+1 minute'));
            $currentTime = gmdate('Y-m-d H:i:s'); // Using UTC time

            $stmt = $pdo->prepare("
                UPDATE users 
                SET reset_pin = ?, 
                    reset_pin_expires = ?,
                    last_pin_request = ?,
                    reset_attempts = reset_attempts + 1,
                    updated_at = ?,
                    updated_by = ?
                WHERE email = ?
            ");
            $stmt->execute([$pin, $expires, $currentTime, $currentTime, $currentUser, $email]);

            if (!sendResetPin($email, $pin)) {
                throw new Exception('Failed to send reset PIN.');
            }

            // Get updated attempts count
            $stmt = $pdo->prepare("SELECT reset_attempts FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $currentAttempts = $stmt->fetch(PDO::FETCH_ASSOC)['reset_attempts'];

            $remainingAttempts = max(0, 3 - $currentAttempts);

            echo json_encode([
                'success' => true,
                'message' => "Reset PIN has been sent to your email. You have $remainingAttempts attempts remaining.",
                'remainingAttempts' => $remainingAttempts
            ]);
            exit;

        } elseif (isset($_POST['resend'])) {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

            if (!canResendPin($email)) {
                throw new Exception('Please wait 60 seconds before requesting a new PIN.');
            }

            // Get current attempts and last request time
            $stmt = $pdo->prepare("
                SELECT reset_attempts, last_pin_request 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $currentTime = strtotime(gmdate('Y-m-d H:i:s'));
            $lastRequest = $user['last_pin_request'] ? strtotime($user['last_pin_request']) : 0;

            // Calculate new attempts value
            $newAttempts = ($currentTime - $lastRequest >= 60) ? 1 : $user['reset_attempts'] + 1;

            // Update attempts and last request time
            $stmt = $pdo->prepare("
                UPDATE users 
                SET reset_attempts = ?,
                    last_pin_request = UTC_TIMESTAMP()
                WHERE email = ?
            ");
            $stmt->execute([$newAttempts, $email]);

            $pin = generatePin();
            $expires = gmdate('Y-m-d H:i:s', strtotime('+1 minute'));

            $stmt = $pdo->prepare("
                UPDATE users 
                SET reset_pin = ?, 
                    reset_pin_expires = ?
                WHERE email = ?
            ");
            $stmt->execute([$pin, $expires, $email]);

            if (!sendResetPin($email, $pin)) {
                throw new Exception('Failed to send reset PIN.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'A new PIN has been sent to your email address. Please check your inbox.'
            ]);
            exit;

        } elseif (isset($_POST['pin'])) {
            $pin = $_POST['pin'];
            $email = $_POST['email'];

            checkAndResetAttempts($email);

            $stmt = $pdo->prepare("
                SELECT id, reset_attempts FROM users 
                WHERE email = ? 
                AND reset_pin = ? 
                AND reset_pin_expires > UTC_TIMESTAMP()
            ");
            $stmt->execute([$email, $pin]);
            $user = $stmt->fetch();

            if (!$user) {
                incrementResetAttempts($email);
                throw new Exception('Invalid or expired PIN.');
            }

            if ($user['reset_attempts'] >= 3) {
                throw new Exception('Too many attempts. Please try again later.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'PIN verified successfully.'
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}