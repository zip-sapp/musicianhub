<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musician Database</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Calibri', serif;
            color: #fff;
            background: linear-gradient(135deg, #2e0267, #0b1a59);
            overflow: hidden;
            height: 100vh;
        }

        /* Moving Blob Background */
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 20% 20%, #ff0099, transparent 40%),
            radial-gradient(circle at 80% 80%, #00d4ff, transparent 40%);
            animation: moveBlobs 10s infinite alternate;
        }

        @keyframes moveBlobs {
            0% {
                background-position: 20% 20%, 80% 80%;
            }
            100% {
                background-position: 40% 40%, 70% 70%;
            }
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
            padding: 10px 0px;
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
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #fff;
        }

        .form-group input {
            width: calc(100% - 20px);
            padding: 12px;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            outline: none;
            box-sizing: border-box;
            transition: background 0.3s ease-in-out;
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
    </style>
</head>
<body>
<div class="background"></div>
<div class="navbar">
    <div class="logo">🎵 Musician Database</div>
    <div class="nav-links">
        <a href="#" title="Home"><i class="fas fa-home"></i></a>
        <a href="#" title="Login" onclick="openModal('login')"><i class="fas fa-sign-in-alt"></i></a>
        <a href="#" title="Sign Up" onclick="openModal('signup')"><i class="fas fa-user-plus"></i></a>
    </div>
</div>
<div class="container">
    <div class="content">
        <h1>Welcome to Musician Database</h1>
        <p>Sign up or log in to unlock the curated table of electronic artists.</p>
        <div class="table-preview">
            <p><strong>Table Locked</strong></p>
            <p>Login to access exclusive content!</p>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="modal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <div id="login-form" style="display: none;">
            <div class="modal-header">Login</div>
            <div class="form-group">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" placeholder="Enter your password">
            </div>
            <button class="btn-modal" onclick="submitLogin()">Login</button>
        </div>

        <div id="signup-form" style="display: none;">
            <div class="modal-header">Sign Up</div>
            <div class="form-group">
                <label for="signup-username">Username</label>
                <input type="text" id="signup-username" placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="signup-email">Email</label>
                <input type="email" id="signup-email" placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" placeholder="Enter your password">
            </div>
            <div class="form-group">
                <label for="signup-country">Country</label>
                <input type="text" id="signup-country" placeholder="Enter your country">
            </div>
            <div class="form-group">
                <label for="signup-phone">Phone Number</label>
                <input type="text" id="signup-phone" placeholder="Enter your phone number">
            </div>
            <button class="btn-modal" onclick="submitSignup()">Sign Up</button>
        </div>
    </div>
</div>

<script>
    function openModal(type) {
        const modal = document.getElementById('modal');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');

        modal.classList.add('active');
        if (type === 'login') {
            loginForm["style"].display = 'block';
            signupForm["style"].display = 'none';
        } else {
            loginForm["style"].display = 'none';
            signupForm["style"].display = 'block';
        }
    }

    function closeModal() {
        const modal = document.getElementById('modal');
        modal.classList.remove('active');
    }

    function submitLogin() {
        alert('Login submitted');
    }

    function submitSignup() {
        alert('Signup submitted');
    }
</script>
</body>
</html>
