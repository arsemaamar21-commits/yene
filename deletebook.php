<?php
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get file path before deleting
        $stmt = $conn->prepare("SELECT file_path FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();
        
        // Delete related download records
        $stmt1 = $conn->prepare("DELETE FROM file_downloads WHERE book_id = ?");
        $stmt1->bind_param("i", $book_id);
        $stmt1->execute();
        $stmt1->close();
        
        // Delete book from database
        $stmt2 = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt2->bind_param("i", $book_id);
        
        if ($stmt2->execute()) {
            // Delete associated file if exists
            if ($book && $book['file_path'] && file_exists($book['file_path'])) {
                unlink($book['file_path']);
            }
            $conn->commit();
            $_SESSION['success'] = "Book deleted successfully!";
        } else {
            throw new Exception("Failed to delete book from database");
        }
        $stmt2->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete book: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Redirect back to books page
header('Location: admin-dashboard.php?tab=books');
exit;
?>