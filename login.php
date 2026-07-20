<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$messg = "";

// If already authenticated, skip to dashboard
if (isset($_SESSION['userusername']) && $_SESSION['userusername'] != '') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    include('db.php');

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $md5_password = md5($password);

    $query = "SELECT * FROM `0_users` WHERE `username`='$username' AND `password`='$md5_password' AND `active`='Active'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['userusername']  = $user['username'];
        $_SESSION['userfullnames'] = $user['fullnames'];
        $_SESSION['userrole']      = $user['role'];
        $_SESSION['userprofpic']   = $user['profpic'];

        // Populate access permissions for this role so caniacess() works on every page
        if (!function_exists('get_access')) {
            require_once(__DIR__ . '/server.php');
        }
        get_access($conn, $user['role']);

        session_write_close();
        header("Location: index.php");
        exit();
    } else {
        $messg = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CHG Patient Groups Monitoring System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1ea7e1 0%, #0d6ba8 100%);
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 380px;
            padding: 36px 32px 28px;
            text-align: center;
        }

        .login-logo {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 3px solid #1ea7e1;
            object-fit: contain;
            background: #ffffff;
            padding: 4px;
            margin-bottom: 14px;
        }

        .login-title {
            font-size: 18px;
            font-weight: 800;
            color: #1a3550;
            margin: 0 0 2px 0;
        }

        .login-title .brand-chg {
            color: #e02020;
        }

        .login-tagline {
            font-size: 12.5px;
            color: #8a94a6;
            margin: 0 0 26px 0;
            font-style: italic;
        }

        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #57606f;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d5dbe3;
            border-radius: 7px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1ea7e1;
            box-shadow: 0 0 0 3px rgba(30, 167, 225, 0.15);
        }

        .btn-login {
            width: 100%;
            background: #1ea7e1;
            color: #ffffff;
            border: none;
            padding: 12px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 7px;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s;
        }

        .btn-login:hover {
            background: #0d6ba8;
        }

        .login-error {
            background: #fdeaea;
            color: #e02020;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #eef1f5;
            font-size: 11.5px;
            color: #a4b0be;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <img src="images/chg-logo.jpg" alt="CHG Logo" class="login-logo"
             onerror="this.style.display='none';">

        <h1 class="login-title"><span class="brand-chg">CHG</span> Patient Groups Monitoring System</h1>
        <p class="login-tagline">"Bettering Health Care Together"</p>

        <?php if (!empty($messg)) { ?>
            <div class="login-error"><?php echo htmlspecialchars($messg); ?></div>
        <?php } ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn-login">Log In</button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> City Health Group Limited. All Rights Reserved.
        </div>
    </div>

</body>
</html>