<?php
include 'config.php';
$page_title = "eLibrary - Add Book";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// File upload directory
$upload_dir = "uploads/books/";

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle book addition
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if form was submitted
    if (isset($_POST['title'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $cover_url = trim($_POST['cover_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $published_year = trim($_POST['published_year'] ?? '');
        
        // Validate required fields
        if (empty($title) || empty($author) || empty($isbn) || empty($category)) {
            $error = "Please fill in all required fields (Title, Author, ISBN, Category).";
        } else {
            // Handle file upload
            $file_name = null;
            $file_path = null;
            $file_type = null;
            $file_size = null;
            
            if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
                $file_info = $_FILES['book_file'];
                $file_tmp_name = $file_info['tmp_name'];
                $file_size = $file_info['size'];
                
                // Generate safe filename
                $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $file_info['name']);
                $file_path = $upload_dir . $file_name;
                
                // Try to move uploaded file
                if (move_uploaded_file($file_tmp_name, $file_path)) {
                    $success = "Book and file uploaded successfully!";
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            }
            
            // Insert book into database if no errors
            if (!isset($error)) {
                try {
                    if ($file_name) {
                        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, cover_url, description, publisher, published_year, file_name, file_path, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ssssssssssi", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year, $file_name, $file_path, $file_size);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, cover_url, description, publisher, published_year, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ssssssss", $title, $author, $isbn, $category, $cover_url, $description, $publisher, $published_year);
                    }
                    
                    if ($stmt->execute()) {
                        $success = $success ?? "Book added successfully!";
                        // Clear form
                        $_POST = array();
                    } else {
                        $error = "Failed to add book to database: " . $stmt->error;
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
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
    <style>
        .form-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #6b7280;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-hint {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .file-preview {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
            margin-top: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-outline {
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-outline:hover {
            background: #f9fafb;
            color: #374151;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="app-container">
    <!-- Left Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div  class="main-content">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Content -->
        <div  class="content-area">
            <div class="admin-dashboard">
                <div class="admin-header">
                    <div class="header-info">
                        <h2>Add New Book</h2>
                        <p>Add a new book to your free e-library collection.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <a href="admin-dashboard.php?tab=overview" class="tab-btn">Dashboard</a>
                        <a href="admin-dashboard.php?tab=books" class="tab-btn">Book Management</a>
                        <a href="addbook.php" class="tab-btn active">Add Book</a>
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

                <!-- Add Book Form -->
                <div style="background-color: orange;" class="form-card">
                    <div class="form-header">
                        <h3>Book Information</h3>
                        <p>Fill in the details below to add a new book to your free e-library.</p>
                    </div>
                    
                    <form style="background-color: orange;" method="POST" enctype="multipart/form-data" id="bookForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Title *</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="Enter book title">
                            </div>
                            
                            <div class="form-group">
                                <label for="author">Author *</label>
                                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>" required placeholder="Enter author name">
                            </div>
                            
                            <div class="form-group">
                                <label for="isbn">ISBN *</label>
                                <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>" required placeholder="Enter ISBN number">
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" required placeholder="Enter category">
                            </div>
                            
                            <div class="form-group">
                                <label for="cover_url">Cover Image URL</label>
                                <input type="url" id="cover_url" name="cover_url" value="<?php echo htmlspecialchars($_POST['cover_url'] ?? ''); ?>" placeholder="https://example.com/cover.jpg">
                                <div class="form-hint">URL to book cover image (optional)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="publisher">Publisher</label>
                                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>" placeholder="Enter publisher name">
                            </div>
                            
                            <div class="form-group">
                                <label for="published_year">Published Year</label>
                                <input type="number" id="published_year" name="published_year" value="<?php echo htmlspecialchars($_POST['published_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="YYYY">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" placeholder="Enter book description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="book_file">Book File</label>
                                <input type="file" id="book_file" name="book_file" accept=".pdf,.ppt,.pptx,.doc,.docx,.txt" onchange="previewFile(this)">
                                <div class="form-hint">Allowed formats: PDF, PPT, PPTX, DOC, DOCX, TXT (optional)</div>
                                <div class="file-preview" id="file-preview" style="display: none;">
                                    <div id="file-info"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 5v14M5 12h14"></path>
                                </svg>
                                Add Book to Library
                            </button>
                            <a href="admin-dashboard.php?tab=books" class="btn btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                                </svg>
                                Back to Books
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File upload preview
        function previewFile(input) {
            const file = input.files[0];
            const preview = document.getElementById('file-preview');
            const fileInfo = document.getElementById('file-info');
            
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                const fileName = file.name;
                const fileType = file.type || fileName.split('.').pop();
                
                fileInfo.innerHTML = `
                    <strong>Selected File:</strong><br>
                    Name: ${fileName}<br>
                    Type: ${fileType}<br>
                    Size: ${fileSize} MB
                `;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bookForm');
            const submitBtn = document.getElementById('submitBtn');
            
            form.addEventListener('submit', function(e) {
                // Basic validation
                const title = document.getElementById('title').value.trim();
                const author = document.getElementById('author').value.trim();
                const isbn = document.getElementById('isbn').value.trim();
                const category = document.getElementById('category').value.trim();
                
                if (!title || !author || !isbn || !category) {
                    e.preventDefault();
                    alert('Please fill in all required fields (Title, Author, ISBN, Category).');
                    return;
                }
                
                // Show loading state
                submitBtn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="spin">
                        <path d="M21 12a9 9 0 11-6.219-8.56"></path>
                    </svg>
                    Adding Book...
                `;
                submitBtn.disabled = true;
                form.classList.add('loading');
                
                // Allow form to submit normally
                return true;
            });
        });

        // Add spin animation for loading
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