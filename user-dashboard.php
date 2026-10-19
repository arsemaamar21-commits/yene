<?php
include 'config.php';
$page_title = "LibraFlow - My Dashboard";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle file download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_book'])) {
    $book_id = intval($_POST['book_id']);
    
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

// Get user's download statistics
$download_stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_downloads,
        COUNT(DISTINCT book_id) as unique_books,
        MAX(downloaded_at) as last_download
    FROM file_downloads 
    WHERE user_id = ?
");
$download_stats_stmt->bind_param("i", $user_id);
$download_stats_stmt->execute();
$download_stats = $download_stats_stmt->get_result()->fetch_assoc();
$download_stats_stmt->close();

// Get recently downloaded books
$recent_downloads_stmt = $conn->prepare("
    SELECT d.*, b.title, b.author, b.cover_url, b.file_type 
    FROM file_downloads d
    JOIN books b ON d.book_id = b.id
    WHERE d.user_id = ?
    ORDER BY d.downloaded_at DESC
    LIMIT 5
");
$recent_downloads_stmt->bind_param("i", $user_id);
$recent_downloads_stmt->execute();
$recent_downloads_result = $recent_downloads_stmt->get_result();
$recent_downloads = $recent_downloads_result->fetch_all(MYSQLI_ASSOC);
$recent_downloads_stmt->close();

// Get recommended books (books not yet downloaded by user)
$recommended_books_stmt = $conn->prepare("
    SELECT b.* 
    FROM books b
    WHERE b.file_path IS NOT NULL 
    AND b.id NOT IN (
        SELECT book_id FROM file_downloads WHERE user_id = ?
    )
    ORDER BY RAND()
    LIMIT 4
");
$recommended_books_stmt->bind_param("i", $user_id);
$recommended_books_stmt->execute();
$recommended_books_result = $recommended_books_stmt->get_result();
$recommended_books = $recommended_books_result->fetch_all(MYSQLI_ASSOC);
$recommended_books_stmt->close();

// Get all books with files (for quick access)
$all_books_stmt = $conn->prepare("
    SELECT b.*, 
        (SELECT COUNT(*) FROM file_downloads WHERE book_id = b.id AND user_id = ?) as downloaded
    FROM books b
    WHERE b.file_path IS NOT NULL
    ORDER BY b.title
    LIMIT 10
");
$all_books_stmt->bind_param("i", $user_id);
$all_books_stmt->execute();
$all_books_result = $all_books_stmt->get_result();
$all_books = $all_books_result->fetch_all(MYSQLI_ASSOC);
$all_books_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .dashboard-header {
            padding: 2rem 0;
            margin-bottom: 1rem;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            color: var(--slate-600);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-content {
            position: relative;
            z-index: 2;
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--slate-500);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--slate-900);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .text-emerald { color: var(--emerald-600); }
        .text-blue { color: var(--blue-600); }
        .text-purple { color: var(--purple-600); }

        .stat-icon-bg {
            position: absolute;
            right: -20px;
            bottom: -20px;
            opacity: 0.1;
            color: var(--slate-400);
            z-index: 1;
        }

        .section-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-900);
        }

        .view-history-btn {
            color: var(--blue-600);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-history-btn:hover {
            color: var(--blue-700);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .book-cover {
            height: 150px;
            background: var(--slate-100);
            position: relative;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: white;
            color: var(--purple-600);
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .book-info {
            padding: 1rem;
        }

        .book-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 0.875rem;
            color: var(--slate-600);
            margin-bottom: 1rem;
        }

        .book-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            flex: 1;
        }

        .btn-primary {
            background: var(--blue-600);
            color: white;
        }

        .btn-primary:hover {
            background: var(--blue-700);
        }

        .btn-success {
            background: var(--emerald-600);
            color: white;
        }

        .btn-success:hover {
            background: var(--emerald-700);
        }

        .btn-outline {
            background: transparent;
            color: var(--blue-600);
            border: 1px solid var(--blue-600);
        }

        .btn-outline:hover {
            background: var(--blue-50);
        }

        .downloads-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .download-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--slate-50);
            border-radius: 0.75rem;
            border: 1px solid var(--slate-200);
        }

        .download-cover {
            width: 60px;
            height: 80px;
            background: var(--slate-200);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .download-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .download-info {
            flex: 1;
        }

        .download-title {
            font-weight: 600;
            color: var(--slate-900);
            margin-bottom: 0.25rem;
        }

        .download-meta {
            font-size: 0.875rem;
            color: var(--slate-600);
        }

        .download-time {
            font-size: 0.75rem;
            color: var(--slate-500);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: var(--emerald-50);
            color: var(--emerald-700);
            border: 1px solid var(--emerald-200);
        }

        .alert-error {
            background: var(--rose-50);
            color: var(--rose-700);
            border: 1px solid var(--rose-200);
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--blue-500), var(--purple-500));
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-banner h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-banner p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .quick-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 0.75rem;
            text-align: center;
        }

        .quick-stat h4 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .quick-stat p {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .download-item {
                flex-direction: column;
                text-align: center;
            }

            .download-cover {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="user-dashboard">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>! 📚</h2>
                    <p>Access thousands of free books. Download, read, and explore our growing collection.</p>
                    
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <h4><?php echo $download_stats['total_downloads']; ?></h4>
                            <p>Books Downloaded</p>
                        </div>
                        <div class="quick-stat">
                            <h4><?php echo count($recommended_books); ?>+</h4>
                            <p>New Recommendations</p>
                        </div>
                        <div class="quick-stat">
                            <h4>∞</h4>
                            <p>Free Downloads</p>
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

                <!-- Download Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Total Downloads</p>
                            <h3 class="stat-value"><?php echo $download_stats['total_downloads']; ?></h3>
                            <p class="stat-status text-emerald">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                                Active Reader
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Unique Books</p>
                            <h3 class="stat-value"><?php echo $download_stats['unique_books']; ?></h3>
                            <p class="stat-status text-blue">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Diverse collection
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <p class="stat-label">Last Download</p>
                            <h3 class="stat-value" style="font-size: 1.5rem;">
                                <?php 
                                if ($download_stats['last_download']) {
                                    echo date('M j, g:i A', strtotime($download_stats['last_download']));
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </h3>
                            <p class="stat-status text-purple">
                                <?php echo $download_stats['last_download'] ? 'Recent activity' : 'Start reading!'; ?>
                            </p>
                        </div>
                        <div class="stat-icon-bg">
                            <svg width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3>Quick Access</h3>
                        <a href="catalog.php" class="view-history-btn">
                            Browse All Books
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="books-grid">
                        <?php if (empty($all_books)): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--slate-500);">
                                <p>No books available yet. Check back soon!</p>
                                <a href="catalog.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Catalog</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($all_books as $book): 
                                $has_files = !empty($book['file_path']) && file_exists($book['file_path']);
                                $is_downloaded = $book['downloaded'] > 0;
                                $cover_url = $book['cover_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4=';
                            ?>
                            <div class="book-card">
                                <div class="book-cover">
                                    <img src="<?php echo htmlspecialchars($cover_url); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php if ($book['file_type']): ?>
                                        <span class="file-badge"><?php echo strtoupper($book['file_type']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-actions">
                                        <?php if ($has_files): ?>
                                            <form method="POST" style="margin: 0; flex: 1;">
                                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                <button type="submit" name="download_book" class="btn <?php echo $is_downloaded ? 'btn-outline' : 'btn-success'; ?>">
                                                    <?php echo $is_downloaded ? 'Download Again' : 'Download Free'; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-outline" style="background: var(--slate-200);" disabled>
                                                No File
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Downloads Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3>Recent Downloads</h3>
                        <a href="download-history.php" class="view-history-btn">
                            View All History
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="downloads-list">
                        <?php if (empty($recent_downloads)): ?>
                            <div style="text-align: center; padding: 2rem; color: var(--slate-500);">
                                <p>You haven't downloaded any books yet.</p>
                                <a href="catalog.php" class="btn btn-primary" style="margin-top: 1rem;">Start Downloading</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_downloads as $download): 
                                $cover_url = $download['cover_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4=';
                            ?>
                            <div class="download-item">
                                <div class="download-cover">
                                    <img src="<?php echo htmlspecialchars($cover_url); ?>" alt="<?php echo htmlspecialchars($download['title']); ?>">
                                </div>
                                <div class="download-info">
                                    <div class="download-title"><?php echo htmlspecialchars($download['title']); ?></div>
                                    <div class="download-meta"><?php echo htmlspecialchars($download['author']); ?> • <?php echo strtoupper($download['file_type'] ?? 'FILE'); ?></div>
                                    <div class="download-time">Downloaded on <?php echo date('M j, g:i A', strtotime($download['downloaded_at'])); ?></div>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $download['book_id']; ?>">
                                    <button type="submit" name="download_book" class="btn btn-outline">Download Again</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recommended Books Section -->
                <?php if (!empty($recommended_books)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <h3>Recommended For You</h3>
                        <a href="catalog.php" class="view-history-btn">
                            View All Recommendations
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="books-grid">
                        <?php foreach ($recommended_books as $book): 
                            $has_files = !empty($book['file_path']) && file_exists($book['file_path']);
                            $cover_url = $book['cover_url'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4=';
                        ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <img src="<?php echo htmlspecialchars($cover_url); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php if ($book['file_type']): ?>
                                    <span class="file-badge"><?php echo strtoupper($book['file_type']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="book-info">
                                <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                <?php if (!empty($book['description'])): ?>
                                    <p style="font-size: 0.875rem; color: var(--slate-500); margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars(substr($book['description'], 0, 100)); ?>...
                                    </p>
                                <?php endif; ?>
                                <div class="book-actions">
                                    <?php if ($has_files): ?>
                                        <form method="POST" style="margin: 0; flex: 1;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="download_book" class="btn btn-success">
                                                Download Now
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline" style="background: var(--slate-200);" disabled>
                                            No File
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>