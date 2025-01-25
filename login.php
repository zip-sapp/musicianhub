<?php
global $pdo;

use Random\RandomException;

require 'db.php';
header('Content-Type: application/json');

// Create admin account if it doesn't exist
try {
    $adminEmail = 'denji@musiciandb.co';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
    $stmt->execute([$adminEmail]);

    if ($stmt->fetchColumn() == 0) {
        // Admin doesn't exist, create it
        $adminPassword = 'admin123';
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO admins (
                username, 
                email, 
                password,
                is_online,
                created_at,
                updated_at
            ) VALUES (
                'Denji',
                ?,
                ?,
                FALSE,
                UTC_TIMESTAMP(),
                UTC_TIMESTAMP()
            )
        ");
        $stmt->execute([$adminEmail, $hashedPassword]);
    }
} catch (PDOException $e) {
    error_log("Admin creation error: " . $e->getMessage());
}

// Rest of your login code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    try {
        // First check if it's an admin
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login successful
            session_start();
            $_SESSION['user'] = $admin['username'];
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['is_admin'] = true;

            // Update admin's online status and last login
            $updateStmt = $pdo->prepare("
                UPDATE admins 
                SET is_online = TRUE,
                    last_login = UTC_TIMESTAMP()
                WHERE id = ?
            ");
            $updateStmt->execute([$admin['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Welcome back, Admin ' . $admin['username'] . '!',
                'redirect' => 'admin_dashboard.php'
            ]);
            exit;
        }

        // If not admin, check regular users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // First check if email is verified
            if (!$user['email_verified']) {
                // Your existing email verification code...
                $tokenExpiry = new DateTime($user['verification_token_expires']);
                $now = new DateTime();

                if ($now > $tokenExpiry) {
                    // Token has expired, generate new one
                    try {
                        $verificationToken = bin2hex(random_bytes(32));
                    } catch (RandomException $e) {

                    }
                    $tokenExpires = gmdate('Y-m-d H:i:s', strtotime('+24 hours'));

                    $updateStmt = $pdo->prepare("
                        UPDATE users 
                        SET verification_token = ?,
                            verification_token_expires = ?,
                            updated_at = ?,
                            updated_by = ?
                        WHERE email = ?
                    ");

                    $updateStmt->execute([
                        $verificationToken,
                        $tokenExpires,
                        gmdate('Y-m-d H:i:s'),
                        $user['username'],
                        $email
                    ]);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Your email is not verified. A new verification link has been sent to your email.',
                        'requires_verification' => true
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Your email is not verified. Please check your email for the verification link.',
                        'requires_verification' => true
                    ]);
                }
                exit;
            }

            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = false;

                // Update user's online status and last login
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET is_online = TRUE,
                        last_login = UTC_TIMESTAMP()
                    WHERE id = ?
                ");
                $updateStmt->execute([$user['id']]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Welcome back, ' . $user['username'] . '!',
                    'redirect' => 'home.php'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    } catch (DateMalformedStringException $e) {
    }
}