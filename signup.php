<?php
global $pdo;
require_once 'db.php';
require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';
require_once 'src/Exception.php';

ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Random\RandomException;

const PHONE_PATTERNS = [
    'PH' => [
        'pattern' => '/^(\+63|0)([0-9]{10})$/',
        'format' => '+63 or 0 followed by 10 digits',
        'example' => '+639123456789 or 09123456789',
        'min_length' => 11,
        'max_length' => 13
    ]
];

/**
 * Generate a secure token for verification
 * @throws RandomException
 */
function generateToken(): string {
    try {
        return bin2hex(random_bytes(32));
    } catch (RandomException $e) {
        error_log("Failed to generate secure token: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Validate email domain and check email existence
 * @throws Exception
 */
function validateEmailDomain(string $email): void {
    $domainConfigs = [
        'gmail.com' => [
            'pattern' => '/^[a-zA-Z0-9.]+@gmail\.com$/',
            'message' => 'Gmail addresses should only contain letters, numbers, and dots'
        ],
        'yahoo.com' => [
            'pattern' => '/^[a-zA-Z0-9._-]+@yahoo\.com$/',
            'message' => 'Yahoo addresses should only contain letters, numbers, dots, underscores, and hyphens'
        ],
        'outlook.com' => [
            'pattern' => '/^[a-zA-Z0-9._-]+@outlook\.com$/',
            'message' => 'Outlook addresses should only contain letters, numbers, dots, underscores, and hyphens'
        ],
        'hotmail.com' => [
            'pattern' => '/^[a-zA-Z0-9._-]+@hotmail\.com$/',
            'message' => 'Hotmail addresses should only contain letters, numbers, dots, underscores, and hyphens'
        ]
    ];

    // Get the domain part of the email
    $domain = strtolower(substr($email, strpos($email, '@') + 1));

    // Check if domain is allowed
    if (!isset($domainConfigs[$domain])) {
        throw new Exception(
            'This email domain is not allowed. Please use one of the following: ' .
            implode(', ', array_keys($domainConfigs))
        );
    }

    // Check if email matches the pattern for its domain
    if (!preg_match($domainConfigs[$domain]['pattern'], $email)) {
        throw new Exception($domainConfigs[$domain]['message']);
    }
}

/**
 * Validate and check phone number for duplicates
 * @throws Exception
 */
function validateAndCheckPhone(string $phone, PDO $pdo): void {
    // Remove any whitespace
    $phone = preg_replace('/\s+/', '', $phone);

    // Check if phone exists in database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('This phone number is already registered with another account.');
    }

    $pattern = PHONE_PATTERNS['PH'];
    if (!preg_match($pattern['pattern'], $phone)) {
        throw new Exception(
            "Invalid Philippine phone number format. " .
            "Number should start with +63 or 0, followed by 10 digits. " .
            "Example: " . $pattern['example']
        );
    }

    if (str_starts_with($phone, '+63')) {
        if (strlen($phone) !== 13) {
            throw new Exception('Philippine phone numbers with +63 prefix must be 13 characters long.');
        }
    } elseif (str_starts_with($phone, '0')) {
        if (strlen($phone) !== 11) {
            throw new Exception('Philippine phone numbers starting with 0 must be 11 characters long.');
        }
    }

    $numberWithoutPrefix = str_starts_with($phone, '+63') ? substr($phone, 3) : substr($phone, 1);
    if (!str_starts_with($numberWithoutPrefix, '9')) {
        throw new Exception('Philippine mobile numbers must start with 9 after the prefix (+63 or 0).');
    }
}

/**
 * Validate country
 * @throws Exception
 */
function validateCountry(string $country): void {
    if ($country !== 'PH') {
        throw new Exception('Only Philippines (PH) is accepted as a valid country.');
    }
}

/**
 * Send verification email to user
 */
function sendVerificationEmail(string $email, string $token, string $username): bool {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aaronsaplala7@gmail.com';
        $mail->Password   = 'cqdu cjqg fjbs yxii';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('aaronsaplala7@gmail.com', 'Denji-Bot');
        $mail->addAddress($email, $username);

        // Create verification link for localhost
        $verificationLink = "https://localhost/musicianhub/verify.php?token=" . $token;

        $mail->isHTML();
        $mail->Subject = 'Verify Your Email - Musician Database';

        // HTML Email body
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff33cc;'>Welcome to Musician Database!</h2>
                <p>Hello $username,</p>
                <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
                <p style='text-align: center;'>
                    <a href='$verificationLink' 
                       style='background-color: #ff33cc; 
                              color: white; 
                              padding: 12px 30px; 
                              text-decoration: none; 
                              border-radius: 5px; 
                              display: inline-block;
                              margin: 20px 0;'>
                        Verify Email
                    </a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>$verificationLink</p>
                <p><strong>Note:</strong> This link will expire in 24 hours.</p>
                <p><small>Registration Date (UTC): " . gmdate('Y-m-d H:i:s') . "</small></p>
                <hr style='border: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>
                    If you didn't create an account with Musician Database, please ignore this email.
                </p>
            </div>";

        $mail->AltBody = "Welcome to Musician Database!\n\n" .
            "Please verify your email by clicking this link: $verificationLink\n\n" .
            "This link will expire in 24 hours.\n\n" .
            "Registration Date (UTC): " . gmdate('Y-m-d H:i:s');

        $mail->send();
        error_log("Email sent successfully to: $email");
        return true;
    } catch (Exception) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any previous output
    ob_clean();

    header('Content-Type: application/json');

    try {
        // Get and sanitize input
        $username = trim($_POST['username'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $country = 'PH';
        $phone = trim($_POST['phone'] ?? '');

        // Current UTC timestamp
        $currentDateTime = gmdate('Y-m-d H:i:s');

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            throw new Exception('All required fields must be filled out.');
        }

        // Username validation
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            throw new Exception('Username must be between 3-50 characters and can only contain letters, numbers, dots, underscores, and hyphens.');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Validate email domain and existence
        validateEmailDomain($email);

        // Country validation
        validateCountry($country);

        // Phone number validation if provided
        if (!empty($phone)) {
            validateAndCheckPhone($phone, $pdo);
        }

        // Password validation
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception('Password must contain at least one uppercase letter.');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception('Password must contain at least one lowercase letter.');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception('Password must contain at least one number.');
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            throw new Exception('Password must contain at least one special character.');
        }

        // Generate verification token
        $verificationToken = generateToken();
        $tokenExpires = gmdate('Y-m-d H:i:s', strtotime('+24 hours'));

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();

        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email is already registered.');
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username is already taken.');
        }

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username,
                email,
                password,
                country,
                phone,
                verification_token,
                verification_expires,
                email_verified,
                created_at,
                created_by,
                updated_at,
                updated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $email,
            $hashedPassword,
            'PH',
            $phone ?: null,
            $verificationToken,
            $tokenExpires,
            $currentDateTime,
            $username,
            $currentDateTime,
            $username
        ]);

        // Send verification email
        if (!sendVerificationEmail($email, $verificationToken, $username)) {
            throw new Exception('Failed to send verification email.');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! Please check your email to verify your account.'
        ]);

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } catch (RandomException $e) {
    }
    exit;
}