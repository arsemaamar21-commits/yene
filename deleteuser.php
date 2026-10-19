<?php
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account!";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Delete related download records
            $stmt1 = $conn->prepare("DELETE FROM file_downloads WHERE user_id = ?");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            $stmt1->close();
            
            // Delete user
            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt2->bind_param("i", $user_id);
            
            if ($stmt2->execute()) {
                $conn->commit();
                $_SESSION['success'] = "User deleted successfully!";
            } else {
                throw new Exception("Failed to delete user");
            }
            $stmt2->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
        }
    }
}

// Redirect back to users page
header('Location: admin-dashboard.php?tab=users');
exit;
?>