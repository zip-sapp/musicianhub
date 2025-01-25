<?php
global $pdo;
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';
require_once 'signup.php'; // To reuse validation functions

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please log in to edit your profile']);
    exit;
}

error_log("Profile update request received: " . print_r($_POST, true));
if (!empty($_FILES)) {
    error_log("Files received: " . print_r($_FILES, true));
}

$username = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $pdo->beginTransaction();

        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

        $updateFields = [];
        $updateValues = [];

        // Handle profile picture upload
        if (isset($_FILES['profile_picture'])) {
            $file = $_FILES['profile_picture'];

            // Validate file
            if ($file['error'] === 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes)) {
                    throw new Exception('Only JPG, PNG and GIF files are allowed');
                }

                // Check file size (500KB max)
                if ($file['size'] > 500 * 1024) {
                    throw new Exception('File size must be less than 500KB');
                }

                // Create uploads directory if it doesn't exist
                $uploadDir = 'uploads/profile_pictures/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate unique filename
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = uniqid($username . '_') . '.' . $fileExt;
                $targetPath = $uploadDir . $fileName;

                // Process image
                list($width, $height) = getimagesize($file['tmp_name']);
                if ($width > 500 || $height > 500) {
                    // Resize image
                    $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
                    $newImage = imagecreatetruecolor(500, 500);
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, 500, 500, $width, $height);

                    switch($fileExt) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($newImage, $targetPath, 90);
                            break;
                        case 'png':
                            imagepng($newImage, $targetPath, 9);
                            break;
                        case 'gif':
                            imagegif($newImage, $targetPath);
                            break;
                    }

                    imagedestroy($image);
                    imagedestroy($newImage);
                } else {
                    // Move original file if it's already the right size
                    move_uploaded_file($file['tmp_name'], $targetPath);
                }

                // Delete old profile picture if exists
                if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }

                $updateFields[] = 'profile_picture = ?';
                $updateValues[] = $targetPath;
            }
        }

        // Handle other field updates
        if (isset($_POST['new_username']) && $_POST['new_username'] !== $user['username']) {
            // Validate new username
            $newUsername = trim($_POST['new_username']);
            if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $newUsername)) {
                throw new Exception('Username must be between 3-50 characters and can only contain letters, numbers, dots, underscores, and hyphens.');
            }

            // Check if username is taken
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$newUsername, $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username is already taken.');
            }

            $updateFields[] = 'username = ?';
            $updateValues[] = $newUsername;
            $_SESSION['user'] = $newUsername; // Update session
        }

        if (isset($_POST['new_phone']) && $_POST['new_phone'] !== $user['phone']) {
            $newPhone = trim($_POST['new_phone']);
            if (!empty($newPhone)) {
                validateAndCheckPhone($newPhone, $pdo);
                $updateFields[] = 'phone = ?';
                $updateValues[] = $newPhone;
            }
        }

        // Handle password update
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception('Current password is required to change password');
            }

            // Verify current password
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            $newPassword = $_POST['new_password'];

            // Password validation
            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }
            if (!preg_match('/[A-Z]/', $newPassword)) {
                throw new Exception('Password must contain at least one uppercase letter.');
            }
            if (!preg_match('/[a-z]/', $newPassword)) {
                throw new Exception('Password must contain at least one lowercase letter.');
            }
            if (!preg_match('/[0-9]/', $newPassword)) {
                throw new Exception('Password must contain at least one number.');
            }
            if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $newPassword)) {
                throw new Exception('Password must contain at least one special character.');
            }

            $updateFields[] = 'password = ?';
            $updateValues[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if (!empty($updateFields)) {
            // Add metadata
            $updateFields[] = 'updated_at = ?';
            $updateValues[] = gmdate('Y-m-d H:i:s');
            $updateFields[] = 'updated_by = ?';
            $updateValues[] = $username;

            // Build and execute update query
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE username = ?";
            $updateValues[] = $username;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// If it's a GET request, return the current user data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->prepare("SELECT username, email, country, phone, profile_picture, created_at, last_login FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            echo json_encode([
                'success' => true,
                'data' => $userData
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}