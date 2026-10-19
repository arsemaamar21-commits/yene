<?php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Handle file download
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT file_name, file_path, title FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        if ($book['file_path'] && file_exists($book['file_path'])) {
            
            // Log download (FREE - no payment required)
            $log_stmt = $conn->prepare("INSERT INTO file_downloads (book_id, user_id, ip_address, downloaded_at) VALUES (?, ?, ?, NOW())");
            $log_stmt->bind_param("iis", $book_id, $user_id, $_SERVER['REMOTE_ADDR']);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $book['file_name'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($book['file_path']));
            readfile($book['file_path']);
            exit;
            
        } else {
            $_SESSION['error'] = "File not found or not available for download.";
        }
    } else {
        $_SESSION['error'] = "Book not found.";
    }
    $stmt->close();
}

// Redirect back with error message
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $redirect_url);
exit;
?>