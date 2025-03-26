<?php
require_once 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (loginUser($username, $password)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AquaFarm Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c7873;
            --accent-color: #f8b400;
        }

        body {
            background: url('images/farm-login-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .login-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 20px;
            padding: 10px 15px;
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 20px;
            padding: 10px;
            width: 100%;
            font-weight: 600;
        }

        .btn-login:hover {
            background-color: #235c58;
        }

        .input-group-text {
            background-color: white;
            border-right: none;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container">
                    <div class="login-header">
                        <h3><i class="fas fa-fish me-2"></i>AquaFarm Login</h3>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-login text-white">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                            <div class="text-center">
                                <p class="mb-1">Don't have an account? <a href="register.php">Register here</a></p>
                                <p><a href="#">Forgot password?</a></p>
                            </div>
                            <p class="text-center mt-3">
                                <a href="welcome.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>