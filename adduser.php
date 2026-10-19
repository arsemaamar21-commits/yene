<?php
include 'config.php';
$page_title = "LibraFlow - Add User";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// Handle user addition
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    
    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $success = "User added successfully!";
            $_POST = array();
        } else {
            $error = "Failed to add user: " . $stmt->error;
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
                        <h2>Add New User</h2>
                        <p>Create a new user account for the free e-library system.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <a href="admin-dashboard.php?tab=overview" class="tab-btn">Dashboard</a>
                        <a href="admin-dashboard.php?tab=users" class="tab-btn">User Management</a>
                        <a href="adduser.php" class="tab-btn active">Add User</a>
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

                <!-- Add User Form -->
                <div style="background-color: orange;" class="form-card">
                    <div class="form-header">
                        <h3>User Information</h3>
                        <p>Fill in the details below to create a new user account.</p>
                    </div>
                    
                    <form style="background-color: orange;" method="POST" class="user-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Enter full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Enter email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required placeholder="Enter password">
                                <div class="form-hint">Minimum 6 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">User Role *</label>
                                <select id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="member" <?php echo (($_POST['role'] ?? '') == 'member') ? 'selected' : ''; ?>>Member</option>
                                    <option value="admin" <?php echo (($_POST['role'] ?? '') == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                Create User
                            </button>
                            <a href="admin-dashboard.php?tab=users" class="btn btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                </svg>
                                Back to Users
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.user-form');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const role = document.getElementById('role').value;
                
                if (!name || !email || !password || !role) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password should be at least 6 characters long.');
                    return;
                }
                
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
                        <path d="M21 12a9 9 0 11-6.219-8.56"></path>
                    </svg>
                    Creating User...
                `;
                submitBtn.disabled = true;
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .spin {
                animation: spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>