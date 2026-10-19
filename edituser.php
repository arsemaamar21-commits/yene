<?php
include 'config.php';
$page_title = "LibraFlow - Edit User";

// Allow admin or the user themself to access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If non-admin, ensure user can only edit their own profile
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
        header('Location: user-dashboard.php');
        exit;
    }
}

// Fetch user data
$user = null;
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// If user not found, redirect
if (!$user) {
    header('Location: admin-dashboard.php?tab=users');
    exit;
}

// Handle user update
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    // Only allow role change when the current session user is admin
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && isset($_POST['role'])) {
        $role = trim($_POST['role']);
    } else {
        // preserve existing role from database for non-admins
        $role = $user['role'];
    }
    
    // Check if email already exists (excluding current user)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // If password is provided, update it
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role, $user_id);
        }
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
                $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
        } else {
            $error = "Failed to update user: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-container">
    <!-- Left Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Content -->
        <div class="content-area">
            <div class="admin-dashboard">
                <div class="admin-header">
                    <div class="header-info">
                        <h2>Edit User</h2>
                        <p>Update user information and permissions.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <a href="admin-dashboard.php?tab=overview" class="tab-btn">Dashboard</a>
                        <a href="admin-dashboard.php?tab=users" class="tab-btn">User Management</a>
                        <a href="edituser.php?id=<?php echo $user_id; ?>" class="tab-btn active">Edit User</a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Edit User Form -->
                <div class="form-card">
                    <div class="form-header">
                        <h3>Edit User Information</h3>
                        <p>Update the details below to modify user account.</p>
                    </div>
                    
                    <form method="POST" class="user-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">User Role *</label>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <select id="role" name="role" required>
                                        <option value="member" <?php echo ($user['role'] == 'member') ? 'selected' : ''; ?>>Member</option>
                                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                <?php else: ?>
                                    <div class="muted text-small" style="padding:0.5rem 0;"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></div>
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            <a href="admin-dashboard.php?tab=users" class="btn btn-outline">Back to Users</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>