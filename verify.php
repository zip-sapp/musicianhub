<?php
global $pdo;
require_once 'db.php';
session_start();

try {
    // Get token from URL
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        throw new Exception('Verification token is missing.');
    }

    // Set UTC timezone
    date_default_timezone_set('UTC');
    $currentDateTime = gmdate('Y-m-d H:i:s'); // Current UTC time

    // Prepare and execute query to find user with token
    $stmt = $pdo->prepare("
        SELECT id, username, email, verification_expires, created_by 
        FROM users 
        WHERE verification_token = ? 
        AND email_verified = 0
    ");

    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid verification token or account already verified.');
    }

    // Check if token has expired
    if (strtotime($user['verification_expires']) < strtotime($currentDateTime)) {
        throw new Exception('Verification link has expired. Please request a new one.');
    }

    // Use the user's own username for the update
    $userLogin = $user['created_by'];

    // Update user as verified
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET email_verified = 1,
            verification_token = NULL,
            updated_at = ?,
            updated_by = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $currentDateTime,
        $userLogin, // Using the user's own username
        $user['id']
    ]);

    // Set success message
    $_SESSION['verify_message'] = [
        'type' => 'success',
        'text' => 'Email verified successfully! You can now log in.'
    ];

} catch (Exception $e) {
    // Set error message
    $_SESSION['verify_message'] = [
        'type' => 'error',
        'text' => $e->getMessage()
    ];
}

// Redirect to home.php
header('Location: home.php');
exit;
