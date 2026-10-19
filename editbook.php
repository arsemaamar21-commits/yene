<?php
include 'config.php';
$page_title = "LibraFlow - Edit Book";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// Get book ID from URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch book data
$book = null;
if ($book_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
}

// If book not found, redirect
if (!$book) {
    header('Location: admin-dashboard.php?tab=books');
    exit;
}

// File upload directory
$upload_dir = "uploads/books/";

// Handle book update
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category = trim($_POST['category']);
    $cover_url = trim($_POST['cover_url']);
    $description = trim($_POST['description']);
    $publisher = trim($_POST['publisher']);
    $published_year = trim($_POST['published_year']);
    
    // Handle file upload if new file is provided
    $file_name = $book['file_name'];
    $file_path = $book['file_path'];
    $file_type = $book['file_type'];
    $file_size = $book['file_size'];
    
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'application/pdf' => 'pdf',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'application/vnd.ms-works' => 'wps',
            'application/octet-stream' => 'wps'
        ];
        
        $file_info = $_FILES['book_file'];
        $file_type = $file_info['type'];
        $file_size = $file_info['size'];
        
        // Check if file type is allowed
        if (array_key_exists($file_type, $allowed_types)) {
            // Delete old file if exists
            if ($book['file_path'] && file_exists($book['file_path'])) {
                unlink($book['file_path']);
            }
            
            $extension = $allowed_types[$file_type];
            $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $file_info['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
                $success = "Book updated successfully with new file!";
            } else {
                $error = "Failed to upload new file.";
            }
        } else {
            $error = "Invalid file type. Allowed: PDF, PPT, PPTX, XML, WPS";
        }
    }
    
    // Update book in database
    if (!isset($error)) {
        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, cover_url=?, description=?, publisher=?, published_year=?, file_name=?, file_path=?, file_type=?, file_size=? WHERE id=?");
        $stmt->bind_param("sssssssssssii", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year, $file_name, $file_path, $file_type, $file_size, $book_id);
        
        if ($stmt->execute()) {
            $success = $success ?? "Book updated successfully!";
            // Refresh book data
            $book = $conn->query("SELECT * FROM books WHERE id = $book_id")->fetch_assoc();
        } else {
            $error = "Failed to update book: " . $stmt->error;
        }
        $stmt->close();
    }
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
                        <h2>Edit Book</h2>
                        <p>Update book information and files.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <a href="admin-dashboard.php?tab=overview" class="tab-btn">Dashboard</a>
                        <a href="admin-dashboard.php?tab=books" class="tab-btn">Book Management</a>
                        <a href="editbook.php?id=<?php echo $book_id; ?>" class="tab-btn active">Edit Book</a>
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

                <!-- Edit Book Form -->
                <div style="background-color: orange;" class="form-card">
                    <div class="form-header">
                        <h3>Edit Book Information</h3>
                        <p>Update the details below to modify the book information.</p>
                    </div>
                    
                    <form style="background-color: orange;" method="POST" enctype="multipart/form-data" class="book-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Title *</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="author">Author *</label>
                                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="isbn">ISBN *</label>
                                <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($book['category']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="cover_url">Cover Image URL</label>
                                <input type="url" id="cover_url" name="cover_url" value="<?php echo htmlspecialchars($book['cover_url']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="publisher">Publisher</label>
                                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="published_year">Published Year</label>
                                <input type="number" id="published_year" name="published_year" value="<?php echo htmlspecialchars($book['published_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="book_file">Update Book File (Optional)</label>
                                <input type="file" id="book_file" name="book_file" accept=".pdf,.ppt,.pptx,.xml,.wps" onchange="previewFile(this)">
                                <?php if ($book['file_name']): ?>
                                <div class="current-file">
                                    <div class="proof-badge info">
                                        Current File: <?php echo htmlspecialchars($book['file_name']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="file-preview" id="file-preview" style="display: none;">
                                    <div class="proof-badge success" id="file-info"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                            <a href="admin-dashboard.php?tab=books" class="btn btn-outline">Back to Books</a>
                            <?php if ($book['file_name']): ?>
                            <a href="download.php?book_id=<?php echo $book_id; ?>" class="btn btn-success">Download File</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewFile(input) {
            const file = input.files[0];
            const preview = document.getElementById('file-preview');
            const fileInfo = document.getElementById('file-info');
            
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileName = file.name;
                const fileType = file.type;
                
                fileInfo.innerHTML = `<strong>${fileName}</strong><br>Type: ${fileType} | Size: ${fileSize} MB`;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>