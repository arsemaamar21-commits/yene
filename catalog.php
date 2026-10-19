<?php
include 'config.php';
$page_title = "LibraFlow - Book Catalog";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Handle file download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_file'])) {
    $book_id = intval($_POST['book_id']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT file_name, file_path, title FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        if ($book['file_path'] && file_exists($book['file_path'])) {
            
            // Log download
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
            $error = "File not found or not available for download.";
        }
    } else {
        $error = "Book not found.";
    }
    $stmt->close();
}

// Search functionality
$search = $_GET['search'] ?? '';

// Get books from database
try {
    if (!empty($search)) {
        $sql = "SELECT *, 
                (SELECT COUNT(*) FROM file_downloads WHERE book_id = books.id) as download_count
                FROM books 
                WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ?
                ORDER BY title ASC";
        $stmt = $conn->prepare($sql);
        $search_term = "%$search%";
        $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    } else {
        $sql = "SELECT *, 
                (SELECT COUNT(*) FROM file_downloads WHERE book_id = books.id) as download_count
                FROM books 
                ORDER BY title ASC";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $books_result = $stmt->get_result();
    $books = $books_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
} catch (Exception $e) {
    // Fallback if columns don't exist
    if (!empty($search)) {
        $sql = "SELECT * FROM books 
                WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ?
                ORDER BY title ASC";
        $stmt = $conn->prepare($sql);
        $search_term = "%$search%";
        $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    } else {
        $sql = "SELECT * FROM books ORDER BY title ASC";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $books_result = $stmt->get_result();
    $books = $books_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Add default values for missing fields
    foreach ($books as &$book) {
        $book['download_count'] = 0;
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
        .catalog-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .catalog-header {
            background: white;
            padding: 2rem 0 1rem 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--slate-200);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--slate-800);
            margin: 0;
        }

        .book-count {
            font-size: 1rem;
            color: var(--slate-600);
            font-weight: 500;
        }

        .free-banner {
            background: linear-gradient(135deg, var(--emerald-500), var(--emerald-600));
            color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--emerald-200);
        }

        .free-banner-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .free-banner-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .free-banner-content p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .search-section {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--slate-200);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .search-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .search-label {
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            background: white;
            width: 100%;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--blue-500);
        }

        .btn-search {
            background: var(--blue-600);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
            transition: background 0.2s;
            height: fit-content;
        }

        .btn-search:hover {
            background: var(--blue-700);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .book-item {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--slate-200);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .book-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .book-media {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--slate-100);
        }

        .book-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-badges {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            right: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .file-badge {
            background: white;
            color: var(--purple-600);
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .book-content {
            padding: 1.5rem;
        }

        .book-category {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--blue-600);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }

        .book-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--slate-900);
            line-height: 1.3;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 1rem;
            color: var(--slate-600);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .book-description {
            font-size: 0.875rem;
            color: var(--slate-500);
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--slate-100);
        }

        .download-count {
            font-size: 0.875rem;
            color: var(--slate-500);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .book-isbn {
            font-size: 0.75rem;
            color: var(--slate-400);
            font-family: 'Courier New', monospace;
        }

        .book-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-download {
            flex: 1;
            background: var(--emerald-600);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            transition: background 0.2s;
            text-decoration: none;
        }

        .btn-download:hover {
            background: var(--emerald-700);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--slate-500);
            grid-column: 1 / -1;
            background: white;
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            font-size: 1rem;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .search-results-info {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--slate-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-count {
            font-size: 0.875rem;
            color: var(--slate-600);
            font-weight: 500;
        }

        .clear-search {
            color: var(--blue-600);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .clear-search:hover {
            color: var(--blue-700);
        }

        @media (max-width: 768px) {
            .catalog-header {
                padding: 1.5rem 0 1rem 0;
            }

            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .search-form {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-search {
                width: 100%;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }

            .free-banner {
                flex-direction: column;
                text-align: center;
            }

            .search-results-info {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
                text-align: center;
            }
        }

        @media (max-width: 640px) {
            .books-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="catalog-container">
                <!-- Header -->
                <div class="catalog-header">
                    <div class="header-content">
                        <div>
                            <h1 class="page-title">Free E-Library</h1>
                            <p class="book-count"><?php echo count($books); ?> books available for free download</p>
                        </div>
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

                <!-- Free Books Banner -->
                <div class="free-banner">
                    <div class="free-banner-icon">🎁</div>
                    <div class="free-banner-content">
                        <h3>Welcome to Our Free E-Library</h3>
                        <p>All books are completely free to download. No registration fees, no hidden costs!</p>
                    </div>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                    <form method="GET" class="search-form">
                        <div class="search-group">
                            <label class="search-label">Search Books</label>
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search by title, author, ISBN, or category..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="btn-search">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Search
                        </button>
                    </form>
                </div>

                <!-- Search Results Info -->
                <?php if (!empty($search)): ?>
                <div class="search-results-info">
                    <div class="results-count">
                        Found <?php echo count($books); ?> books for "<?php echo htmlspecialchars($search); ?>"
                    </div>
                    <a href="?" class="clear-search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Clear search
                    </a>
                </div>
                <?php endif; ?>

                <!-- Books Grid -->
                <div class="books-grid">
                    <?php if (empty($books)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📚</div>
                            <h3 class="empty-title">
                                <?php if (!empty($search)): ?>
                                    No Books Found
                                <?php else: ?>
                                    Library is Empty
                                <?php endif; ?>
                            </h3>
                            <p class="empty-description">
                                <?php if (!empty($search)): ?>
                                    No books found for "<?php echo htmlspecialchars($search); ?>". 
                                    Try searching with different keywords.
                                <?php else: ?>
                                    There are no books available in the library yet. 
                                    Check back later for new additions!
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search)): ?>
                                <a href="?" class="btn-download" style="text-decoration: none; background: var(--blue-600);">
                                    View All Books
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($books as $book): 
                            $has_files = !empty($book['file_path']) && file_exists($book['file_path']);
                            $cover_url = $book['cover_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4=';
                        ?>
                        <div class="book-item" data-book-id="<?php echo $book['id']; ?>">
                            <div class="book-media">
                                <img src="<?php echo htmlspecialchars($cover_url); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     class="book-cover"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4='">
                                
                                <div class="book-badges">
                                    <?php if ($has_files): ?>
                                        <span class="file-badge">
                                            <?php echo strtoupper($book['file_type'] ?? 'FILE'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="book-content">
                                <span class="book-category"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span>
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                
                                <?php if (!empty($book['description'])): ?>
                                    <p class="book-description"><?php echo htmlspecialchars($book['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="book-meta">
                                    <div class="download-count">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7 10 12 15 17 10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        <?php echo $book['download_count']; ?> downloads
                                    </div>
                                    <?php if (!empty($book['isbn'])): ?>
                                        <div class="book-isbn">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="book-actions">
                                    <?php if ($has_files): ?>
                                        <form method="POST" class="download-form" style="margin: 0; flex: 1;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="download_file" class="btn-download">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                                Download Free
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-download" style="background: var(--slate-400);" disabled>
                                            No File Available
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>