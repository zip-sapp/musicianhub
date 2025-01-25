<?php
global $pdo;
session_start();
require 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Get current UTC timestamp and admin username
$currentUTC = '2025-01-17 20:19:40';  // Your specified time
$adminUsername = 'zip-sapp';  // Your specified user

try {
    // Get the action from POST request
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($action) {
        // USER MANAGEMENT
        case 'createUser':
            // Validate required fields
            if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                throw new Exception('Required fields missing');
            }

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$_POST['username'], $_POST['email']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Username or email already exists');
            }

            // Hash password
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, country, phone, created_by, updated_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['username'],
                $_POST['email'],
                $hashedPassword,
                $_POST['country'] ?? null,
                $_POST['phone'] ?? null,
                $adminUsername,
                $adminUsername,
                $currentUTC,
                $currentUTC
            ]);
            $response = ['success' => true, 'message' => 'User created successfully'];
            break;

        case 'updateUser':
            if (empty($_POST['id']) || empty($_POST['username']) || empty($_POST['email'])) {
                throw new Exception('Required fields missing');
            }

            // Check if username or email exists for other users
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE (username = ? OR email = ?) 
                AND id != ?
            ");
            $stmt->execute([$_POST['username'], $_POST['email'], $_POST['id']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Username or email already exists');
            }

            // Build update query
            $updateFields = [
                'username = ?',
                'email = ?',
                'country = ?',
                'phone = ?',
                'updated_by = ?',
                'updated_at = ?'
            ];
            $params = [
                $_POST['username'],
                $_POST['email'],
                $_POST['country'] ?? null,
                $_POST['phone'] ?? null,
                $adminUsername,
                $currentUTC
            ];

            // Add password update if provided
            if (!empty($_POST['password'])) {
                $updateFields[] = 'password = ?';
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            // Add ID to params
            $params[] = $_POST['id'];

            // Execute update
            $stmt = $pdo->prepare("
                UPDATE users 
                SET " . implode(', ', $updateFields) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            $response = ['success' => true, 'message' => 'User updated successfully'];
            break;

        case 'deleteUser':
            if (empty($_POST['id'])) {
                throw new Exception('User ID required');
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $response = ['success' => true, 'message' => 'User deleted successfully'];
            break;

        // MUSICIAN MANAGEMENT
        case 'createMusician':
            if (empty($_POST['name']) || empty($_POST['genre'])) {
                throw new Exception('Required fields missing');
            }

            $stmt = $pdo->prepare("
                INSERT INTO musicians (
                    name, 
                    genre, 
                    description, 
                    image_url, 
                    created_by, 
                    updated_by
                ) VALUES (
                    :name,
                    :genre,
                    :description,
                    :image_url,
                    :created_by,
                    :updated_by
                )
            ");

            $result = $stmt->execute([
                'name' => $_POST['name'],
                'genre' => $_POST['genre'],
                'description' => $_POST['description'] ?? null,
                'image_url' => $_POST['image_url'] ?? null,
                'created_by' => $adminUsername,
                'updated_by' => $adminUsername
            ]);

            if (!$result) {
                throw new Exception('Failed to create musician: ' . implode(', ', $stmt->errorInfo()));
            }

            $response = ['success' => true, 'message' => 'Musician created successfully'];
            break;

        case 'updateMusician':
            if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['genre'])) {
                throw new Exception('Required fields missing');
            }

            $stmt = $pdo->prepare("
                UPDATE musicians 
                SET name = :name,
                    genre = :genre,
                    description = :description,
                    image_url = :image_url,
                    updated_by = :updated_by
                WHERE id = :id
            ");

            $result = $stmt->execute([
                'name' => $_POST['name'],
                'genre' => $_POST['genre'],
                'description' => $_POST['description'] ?? null,
                'image_url' => $_POST['image_url'] ?? null,
                'updated_by' => $adminUsername,
                'id' => $_POST['id']
            ]);

            if (!$result) {
                throw new Exception('Failed to update musician: ' . implode(', ', $stmt->errorInfo()));
            }

            $response = ['success' => true, 'message' => 'Musician updated successfully'];
            break;

        case 'deleteMusician':
            if (empty($_POST['id'])) {
                throw new Exception('Musician ID required');
            }

            $stmt = $pdo->prepare("DELETE FROM musicians WHERE id = :id");
            $result = $stmt->execute(['id' => $_POST['id']]);

            if (!$result) {
                throw new Exception('Failed to delete musician: ' . implode(', ', $stmt->errorInfo()));
            }

            $response = ['success' => true, 'message' => 'Musician deleted successfully'];
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'A database error occurred. Please try again.',
        'debug' => $e->getMessage()  // Remove this in production
    ];
} catch (Exception $e) {
    error_log("Admin handler error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $e->getMessage()  // Remove this in production
    ];
}

// Send JSON response
echo json_encode($response);