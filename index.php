<?php
session_start();

// --- CONFIGURATION ---
$admin_user = 'PortalUsername';
$admin_pass = 'PortalPassword';
$vpn_group = 'vpnusers';

// --- LOGIN LOGIC ---
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['loggedin'] = true;
    } else {
        $login_error = 'Invalid credentials!';
    }
}

// --- USER CREATION LOGIC ---
$message = '';
if ($_SESSION['loggedin'] ?? false) {
    if (isset($_POST['create_user'])) {
        $user = trim($_POST['new_user']);
        $pass = trim($_POST['new_pass']);
        $expiry_days = $_POST['expiry'];

        if (!empty($user) && !empty($pass) && preg_match('/^[a-zA-Z0-9]+$/', $user)) {
            // Sanitize arguments for shell
            $user_s = escapeshellarg($user);
            $pass_s = escapeshellarg($pass);
            $group_s = escapeshellarg($vpn_group);

            // Create user, add to vpnusers, and deny shell access
            $output1 = shell_exec("sudo /usr/sbin/useradd -m -G $group_s -s /usr/sbin/nologin $user_s 2>&1");

            // Set password
            $output2 = shell_exec("echo " . escapeshellarg("$user:$pass") . " | sudo /usr/sbin/chpasswd 2>&1");
            
            // Set expiration if not lifetime
            if ($expiry_days !== 'lifetime') {
                $expiry_date = date('Y-m-d', strtotime("+$expiry_days"));
                $expiry_date_s = escapeshellarg($expiry_date);
                $output3 = shell_exec("sudo /usr/bin/chage -E $expiry_date_s $user_s 2>&1");
            } else {
                // Remove expiration for lifetime accounts
                 $output3 = shell_exec("sudo /usr/bin/chage -E -1 $user_s 2>&1");
            }
            
            $message = "User '$user' created successfully!";
        } else {
            $message = "Error: Invalid username or password. Use only letters and numbers for the username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPN User Management</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1, h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        input, select { padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        button { background: #007bff; color: white; border: none; padding: 0.8rem; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; font-weight: bold; }
        .logout-form { margin-top: 1rem; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Soul Calibre VPN</h1>
        <?php if (!($_SESSION['loggedin'] ?? false)): ?>
            <h2>Admin Login</h2>
            <form method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
            </form>
        <?php else: ?>
            <h2>Create New User</h2>
            <form method="post">
                <input type="text" name="new_user" placeholder="New Username" required>
                <input type="text" name="new_pass" placeholder="New Password" required>
                <select name="expiry" required>
                    <option value="1 day">24 hours</option>
                    <option value="3 days">3 Days</option>
                    <option value="5 days">5 Days</option>
                    <option value="10 days">10 Days</option>
                    <option value="15 days">15 Days</option>
                    <option value="30 days">30 Days</option>
                    <option value="lifetime">Lifetime</option>
                </select>
                <button type="submit" name="create_user">Create User</button>
                <?php if ($message) echo "<p class='success'>$message</p>"; ?>
            </form>
            <form class="logout-form" method="post">
                <button type="submit" name="logout">Logout</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
