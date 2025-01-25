<?php
global $pdo;
ob_start();
session_start();
require 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: home.php');
    exit();
}

// Initialize variables with dynamic data
$adminUsername = htmlspecialchars($_SESSION['user']);
$currentUTC = gmdate('Y-m-d H:i:s');

// Fetch all data
try {
    // Fetch users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch musicians
    $stmt = $pdo->query("SELECT * FROM musicians ORDER BY created_at DESC");
    $musicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $totalUsers = count($users);
    $verifiedUsers = array_filter($users, fn($user) => $user['email_verified'] == 1);
    $onlineUsers = array_filter($users, fn($user) => $user['is_online'] == 1);
    $totalVerified = count($verifiedUsers);
    $totalOnline = count($onlineUsers);

} catch (PDOException $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $users = [];
    $musicians = [];
    $totalUsers = $totalVerified = $totalOnline = 0;
}

function handleDatabaseError($e, $context = '') {
    error_log("Database error in $context: " . $e->getMessage());
    return [
        'success' => false,
        'message' => "A database error has occurred. Please try again.",
        'debug' => $e->getMessage()
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Database Management</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00ffff;
            --primary-hover: rgba(0, 255, 255, 0.2);
            --background-dark: rgba(0, 0, 0, 0.8);
            --border-color: rgba(0, 255, 255, 0.1);
            --text-color: #ffffff;
            --error-color: #ff4444;
            --success-color: #00c851;
        }

        /* Core styles */
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--text-color);
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            letter-spacing: -0.011em;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(rgba(26, 26, 46, 0.8), rgba(22, 33, 62, 0.8)),
            repeating-linear-gradient(0deg,
                    transparent,
                    transparent 2px,
                    rgba(26, 26, 46, 0.1) 2px,
                    rgba(26, 26, 46, 0.1) 4px);
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--background-dark);
            backdrop-filter: blur(10px);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            font-size: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Main Container */
        .admin-container {
            margin-top: 80px;
            padding: 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }

        /* Tabs */
        .tab-container {
            margin: 20px 0;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: var(--primary-hover);
            border-color: var(--primary-color);
        }

        /* Table Styles */
        .table-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: rgba(0, 0, 0, 0.4);
            color: var(--primary-color);
        }

        tr:hover {
            background: var(--primary-hover);
        }

        /* Action Buttons */
        .action-btn {
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 5px;
        }

        .action-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 255, 255, 0.2);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
            display: block;
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            position: relative;
            transform: translateY(-20px);
            transition: all 0.3s ease-in-out;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .close-btn:hover {
            transform: rotate(90deg);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-color);
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-online {
            background-color: var(--success-color);
        }

        .status-offline {
            background-color: var(--error-color);
        }

        .status-unverified {
            background-color: #ffeb3b;
        }

        /* Popup Notifications */
        .popup {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 9999;
            color: var(--text-color);
            animation: slideIn 0.5s ease-out;
        }

        .popup.success {
            background-color: rgba(40, 167, 69, 0.9);
            border-left: 4px solid #218838;
        }

        .popup.error {
            background-color: rgba(220, 53, 69, 0.9);
            border-left: 4px solid #c82333;
        }

        @keyframes slideIn {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<div class="background"></div>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-shield-alt"></i> Admin Dashboard
    </div>
    <div class="nav-controls">
        <span class="admin-badge">
            <i class="fas fa-user-shield"></i> <?php echo $adminUsername; ?>
        </span>
        <a href="logout.php" class="action-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<!-- Main Container -->
<div class="admin-container">
    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Total Users</h3>
            <div class="stat-value"><?php echo $totalUsers; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-check-circle"></i> Verified Users</h3>
            <div class="stat-value"><?php echo $totalVerified; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-signal"></i> Online Users</h3>
            <div class="stat-value"><?php echo $totalOnline; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-music"></i> Total Musicians</h3>
            <div class="stat-value"><?php echo count($musicians); ?></div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="tab-container">
        <div class="tab-buttons">
            <button type="button" class="tab-btn active" data-tab="users">
                <i class="fas fa-users"></i> Users Management
            </button>
            <button type="button" class="tab-btn" data-tab="musicians">
                <i class="fas fa-music"></i> Musicians Management
            </button>
        </div>

        <!-- Users Tab -->
        <div id="usersTab" class="tab-content active">
            <div class="admin-header">
                <h2><i class="fas fa-users"></i> Users Management</h2>
                <button type="button" class="action-btn" data-modal="createUser">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Country</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_online']): ?>
                                    <span class="status-indicator status-online"></span>Online
                                <?php elseif (!$user['email_verified']): ?>
                                    <span class="status-indicator status-unverified"></span>Unverified
                                <?php else: ?>
                                    <span class="status-indicator status-offline"></span>Offline
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['country'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td>
                                <button type="button" class="action-btn" data-modal="editUser" data-modal-data='<?php echo htmlspecialchars(json_encode($user)); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="action-btn" data-modal="deleteUser" data-modal-data='<?php echo json_encode($user['id']); ?>'>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Musicians Tab -->
        <div id="musiciansTab" class="tab-content">
            <div class="admin-header">
                <h2><i class="fas fa-music"></i> Musicians Management</h2>
                <button type="button" class="action-btn" data-modal="createMusician">
                    <i class="fas fa-plus"></i> Add New Musician
                </button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Genre</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($musicians as $musician): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($musician['id']); ?></td>
                            <td><?php echo htmlspecialchars($musician['name']); ?></td>
                            <td><?php echo htmlspecialchars($musician['genre']); ?></td>
                            <td><?php echo htmlspecialchars($musician['description']); ?></td>
                            <td><?php echo htmlspecialchars($musician['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($musician['created_by']); ?></td>
                            <td>
                                <button type="button" class="action-btn" data-modal="editMusician" data-modal-data='<?php echo htmlspecialchars(json_encode($musician)); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="action-btn" data-modal="deleteMusician" data-modal-data='<?php echo json_encode($musician['id']); ?>'>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Add New User</h2>
            <button type="button" class="close-btn" onclick="closeModal('userModal')">&times;</button>
        </div>
        <form id="userForm" onsubmit="handleUserSubmit(event)">
            <input type="hidden" id="userId" name="id">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
                <small style="color: #00ffff;">Leave blank to keep existing password when editing</small>
            </div>
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <button type="submit" class="action-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- Musician Modal -->
<div id="musicianModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="musicianModalTitle">Add New Musician</h2>
            <button type="button" class="close-btn" onclick="closeModal('musicianModal')">&times;</button>
        </div>
        <form id="musicianForm" onsubmit="handleMusicianSubmit(event)">
            <input type="hidden" id="musicianId" name="id">
            <div class="form-group">
                <label for="musicianName">Name</label>
                <input type="text" id="musicianName" name="name" required>
            </div>
            <div class="form-group">
                <label for="genre">Genre</label>
                <input type="text" id="genre" name="genre" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="imageUrl">Image URL</label>
                <input type="url" id="imageUrl" name="image_url">
            </div>
            <button type="submit" class="action-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modals -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Delete User</h2>
            <button type="button" class="close-btn" onclick="closeModal('deleteUserModal')">&times;</button>
        </div>
        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
        <input type="hidden" id="deleteUserId">
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button type="button" class="action-btn" onclick="deleteUser()">
                <i class="fas fa-trash"></i> Confirm Delete
            </button>
            <button type="button" class="action-btn" onclick="closeModal('deleteUserModal')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<div id="deleteMusicianModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Delete Musician</h2>
            <button type="button" class="close-btn" onclick="closeModal('deleteMusicianModal')">&times;</button>
        </div>
        <p>Are you sure you want to delete this musician? This action cannot be undone.</p>
        <input type="hidden" id="deleteMusicianId">
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <button type="button" class="action-btn" onclick="deleteMusician()">
                <i class="fas fa-trash"></i> Confirm Delete
            </button>
            <button type="button" class="action-btn" onclick="closeModal('deleteMusicianModal')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        const selectedTab = document.getElementById(tabName + 'Tab');
        if (selectedTab) {
            selectedTab.classList.add('active');
        }

        // Activate the clicked button
        const clickedButton = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
        if (clickedButton) {
            clickedButton.classList.add('active');
        }
    }

    function handleMusicianSubmit(e) {
        e.preventDefault();
        e.stopPropagation();

        const form = e.target;
        const formData = new FormData(form);
        const musicianId = document.getElementById('musicianId').value;
        formData.append('action', musicianId ? 'updateMusician' : 'createMusician');

        $.ajax({
            url: 'admin_handlers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message);
                    closeModal('musicianModal');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showPopup('error', response.message || 'Operation failed');
                    console.error('Error details:', response.debug);
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Server error: ' + error);
                console.error('Ajax error:', status, error);
            }
        });
    }

    function populateUserForm(data) {
        document.getElementById('userId').value = data.id;
        document.getElementById('username').value = data.username;
        document.getElementById('email').value = data.email;
        document.getElementById('country').value = data.country || '';
        document.getElementById('phone').value = data.phone || '';
    }

    function populateMusicianForm(data) {
        document.getElementById('musicianId').value = data.id;
        document.getElementById('musicianName').value = data.name;
        document.getElementById('genre').value = data.genre;
        document.getElementById('description').value = data.description || '';
        if (document.getElementById('imageUrl')) {
            document.getElementById('imageUrl').value = data.image_url || '';
        }
    }

    function showPopup(type, message) {
        const popup = document.createElement('div');
        popup.className = `popup ${type}`;
        popup.textContent = message;
        document.body.appendChild(popup);

        setTimeout(() => {
            popup.remove();
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Prevent default form submissions and setup proper handling
        setupFormHandlers();
        setupModalHandlers();
        setupTabHandlers();

        // Show users tab by default
        document.getElementById('usersTab').classList.add('active');
    });

    function setupFormHandlers() {
        // User form handling
        const userForm = document.getElementById('userForm');
        if (userForm) {
            userForm.onsubmit = function(e) {
                e.preventDefault();
                e.stopPropagation();

                const formData = new FormData(this);
                const userId = document.getElementById('userId').value;
                formData.append('action', userId ? 'updateUser' : 'createUser');

                submitFormData(formData, 'userModal');
                return false;
            };
        }

        // Musician form handling
        const musicianForm = document.getElementById('musicianForm');
        if (musicianForm) {
            musicianForm.onsubmit = function(e) {
                e.preventDefault();
                e.stopPropagation();

                const formData = new FormData(this);
                const musicianId = document.getElementById('musicianId').value;
                formData.append('action', musicianId ? 'updateMusician' : 'createMusician');

                submitFormData(formData, 'musicianModal');
                return false;
            };
        }
    }

    function setupModalHandlers() {
        // Setup open modal buttons
        document.querySelectorAll('[data-modal]').forEach(button => {
            button.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalType = this.getAttribute('data-modal');
                const modalData = this.getAttribute('data-modal-data');
                openModal(modalType, modalData ? JSON.parse(modalData) : null);
                return false;
            };
        });

        // Setup close modal buttons
        document.querySelectorAll('.close-btn').forEach(button => {
            button.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalId = this.closest('.modal').id;
                closeModal(modalId);
                return false;
            };
        });
    }

    function setupTabHandlers() {
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const tabName = this.getAttribute('data-tab');
                switchTab(tabName);
                return false;
            };
        });
    }

    function openModal(type, data = null) {
        const modalMap = {
            userModal: document.getElementById('userModal'),
            musicianModal: document.getElementById('musicianModal'),
            deleteUserModal: document.getElementById('deleteUserModal'),
            deleteMusicianModal: document.getElementById('deleteMusicianModal')
        };

        // Hide all modals first
        Object.values(modalMap).forEach(modal => {
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        });

        try {
            let activeModal;
            switch(type) {
                case 'createUser':
                case 'editUser':
                    activeModal = modalMap.userModal;
                    document.getElementById('userModalTitle').textContent =
                        type === 'createUser' ? 'Add New User' : 'Edit User';
                    if (type === 'createUser') {
                        document.getElementById('userForm').reset();
                        document.getElementById('userId').value = '';
                    } else if (data) {
                        populateUserForm(data);
                    }
                    break;

                case 'deleteUser':
                    activeModal = modalMap.deleteUserModal;
                    document.getElementById('deleteUserId').value = data;
                    break;

                case 'createMusician':
                case 'editMusician':
                    activeModal = modalMap.musicianModal;
                    document.getElementById('musicianModalTitle').textContent =
                        type === 'createMusician' ? 'Add New Musician' : 'Edit Musician';
                    if (type === 'createMusician') {
                        document.getElementById('musicianForm').reset();
                        document.getElementById('musicianId').value = '';
                    } else if (data) {
                        populateMusicianForm(data);
                    }
                    break;

                case 'deleteMusician':
                    activeModal = modalMap.deleteMusicianModal;
                    document.getElementById('deleteMusicianId').value = data;
                    break;
            }

            if (activeModal) {
                activeModal.style.display = 'block';
                // Use a timeout to ensure the display: block is processed before adding the show class
                setTimeout(() => activeModal.classList.add('show'), 10);
            }
        } catch (error) {
            console.error('Modal operation error:', error);
            showPopup('error', 'An error occurred while opening the modal');
        }
    }

    function deleteUser() {
        const userId = document.getElementById('deleteUserId').value;
        if (!userId) {
            showPopup('error', 'Invalid user ID');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'deleteUser');
        formData.append('id', userId);

        $.ajax({
            url: 'admin_handlers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', 'User deleted successfully');
                    closeModal('deleteUserModal');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showPopup('error', response.message || 'Failed to delete user');
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Server error: ' + error);
            }
        });
    }

    function deleteMusician() {
        const musicianId = document.getElementById('deleteMusicianId').value;
        if (!musicianId) {
            showPopup('error', 'Invalid musician ID');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'deleteMusician');
        formData.append('id', musicianId);

        $.ajax({
            url: 'admin_handlers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', 'Musician deleted successfully');
                    closeModal('deleteMusicianModal');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showPopup('error', response.message || 'Failed to delete musician');
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Server error: ' + error);
            }
        });
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }
    }

    function submitFormData(formData, modalId) {
        $.ajax({
            url: 'admin_handlers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message);
                    closeModal(modalId);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showPopup('error', response.message || 'Operation failed');
                }
            },
            error: function(xhr, status, error) {
                showPopup('error', 'Server error: ' + error);
            }
        });
    }

</script>
</body>
</html>
<?php ob_end_flush(); ?>