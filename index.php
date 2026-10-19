<?php
session_start();
include 'config.php';
$page_title = "LibraFlow - Discover Your Next Great Read";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="shortcut icon" href="./img/download.jfif" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Enhanced Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.1"><polygon fill="white" points="1000,1000 0,1000 0,0 500,500"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .text-accent {
            color: #fbbf24;
            background: linear-gradient(45deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-input-wrapper input:focus {
            outline: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 4rem 2rem;
            background: #f8fafc;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .stat-icon.bg-blue { background: #dbeafe; color: #1d4ed8; }
        .stat-icon.bg-emerald { background: #d1fae5; color: #047857; }
        .stat-icon.bg-purple { background: #e9d5ff; color: #7c3aed; }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Featured Sections */
        .featured-section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .view-all-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .view-all-link:hover {
            color: #1d4ed8;
        }

        /* Book Cards */
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .book-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        .book-cover {
            height: 200px;
            position: relative;
            overflow: hidden;
            background: #f1f5f9;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }

        .book-status {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: #10b981;
            color: white;
        }

        .status-borrowed {
            background: #ef4444;
            color: white;
        }

        .status-new {
            background: #f59e0b;
            color: white;
        }

        .book-info {
            padding: 1.5rem;
        }

        .book-category {
            font-size: 0.75rem;
            font-weight: 700;
            color: #3b82f6;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
            display: block;
            letter-spacing: 0.5px;
        }

        .book-info h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-info p {
            color: #6b7280;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .book-description {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-actions {
            display: flex;
            gap: 0.75rem;
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
            flex: 1;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }

        .btn-outline:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-1px);
        }

        .btn-login {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }

        /* Login Prompt */
        .login-prompt {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #fef3c7, #fef7cd);
            border-radius: 1rem;
            margin: 2rem auto;
            max-width: 600px;
            border: 2px solid #fbbf24;
        }

        .login-prompt h3 {
            font-size: 1.5rem;
            color: #92400e;
            margin-bottom: 1rem;
        }

        .login-prompt p {
            color: #92400e;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .search-container {
                flex-direction: column;
            }

            .search-input-wrapper {
                min-width: auto;
            }

            .stats-section {
                grid-template-columns: 1fr;
                padding: 3rem 1rem;
            }

            .featured-section {
                padding: 3rem 1rem;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .book-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 4rem 0;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .stat-info h3 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-area">
            <div class="landing-page">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Discover Your Next <span class="text-accent">Great Read</span></h1>
                        <p>Access thousands of free books and digital resources. Join our community of readers and explore our growing collection.</p>
                        
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                <input type="text" placeholder="Search by title, author, or ISBN..." id="searchInput">
                            </div>
                            <a href="catalog.php" class="btn btn-primary">Browse Catalog</a>
                        </div>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div style="margin-top: 2rem;">
                                <a href="auth.php" class="btn btn-login">Login to Download Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats-section">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3> 
                                <?php
                                $book_count = "SELECT COUNT(*) AS total_books FROM books";
                                $book_result = mysqli_query($conn, $book_count);
                                if ($book_result) {
                                    $row = mysqli_fetch_assoc($book_result);
                                    echo $row['total_books'];
                                } else {
                                    echo "0";
                                }
                                ?>+
                            </h3>
                            <p>Free Books Available</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-emerald">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3> 
                                <?php
                                $user_count = "SELECT COUNT(*) AS total_users FROM users";
                                $user_result = mysqli_query($conn, $user_count);
                                if ($user_result) {
                                    $row = mysqli_fetch_assoc($user_result);
                                    echo $row['total_users'];
                                } else {
                                    echo "0";
                                }
                                ?>+
                            </h3>
                            <p>Active Readers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-purple">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>24/7</h3>
                            <p>Digital Access</p>
                        </div>
                    </div>
                </div>

                <!-- Login Prompt for Non-Logged Users -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="login-prompt">
                    <h3>📚 Ready to Start Reading?</h3>
                    <p>Login to access our complete collection and download books for free!</p>
                    <a href="auth.php" class="btn btn-primary">Login Now</a>
                </div>
                <?php endif; ?>

                <!-- New Arrivals Section -->
                <div class="featured-section">
                    <div class="section-header">
                        <h2>New Arrivals</h2>
                        <a href="catalog.php" class="view-all-link">View All Books
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="featured-grid">
                        <?php
                        // Get the 3 newest books from the database
                        $new_books_query = "SELECT * FROM books ORDER BY id DESC LIMIT 3";
                        $new_books_result = mysqli_query($conn, $new_books_query);
                        
                        if ($new_books_result && mysqli_num_rows($new_books_result) > 0) {
                            while ($book = mysqli_fetch_assoc($new_books_result)) {
                                $cover_url = $book['cover_url'] ?? 'https://picsum.photos/seed/book' . $book['id'] . '/400/200';
                                $author = $book['author'] ?? 'Unknown Author';
                                $category = $book['category'] ?? 'General';
                                
                                echo '
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="' . $cover_url . '" alt="' . htmlspecialchars($book['title']) . '">
                                        <div class="book-status status-new">New</div>
                                    </div>
                                    <div class="book-info">
                                        <span class="book-category">' . htmlspecialchars($category) . '</span>
                                        <h3>' . htmlspecialchars($book['title']) . '</h3>
                                        <p>by ' . htmlspecialchars($author) . '</p>
                                        ' . (!empty($book['description']) ? '<div class="book-description">' . substr(htmlspecialchars($book['description']), 0, 100) . '...</div>' : '') . '
                                        <div class="book-actions">
                                            <a href="catalog.php" class="btn btn-outline">Browse</a>
                                            ' . (isset($_SESSION['user_id']) ? '<a href="catalog.php" class="btn btn-primary">Read Now</a>' : '<a href="auth.php" class="btn btn-primary">Login to Read</a>') . '
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            // Fallback if no books in database
                            $fallback_books = [
                                ['title' => 'The Future of AI', 'author' => 'Tech Visionary', 'cover' => 'https://picsum.photos/seed/book1/400/200', 'category' => 'Technology', 'desc' => 'Explore the exciting world of artificial intelligence and its impact on our future.'],
                                ['title' => 'Modern Web Development', 'author' => 'Web Expert', 'cover' => 'https://picsum.photos/seed/book2/400/200', 'category' => 'Programming', 'desc' => 'Learn the latest techniques and tools for modern web development.'],
                                ['title' => 'Data Science Essentials', 'author' => 'Data Scientist', 'cover' => 'https://picsum.photos/seed/book3/400/200', 'category' => 'Data Science', 'desc' => 'Master the fundamentals of data science and analytics.']
                            ];
                            
                            foreach ($fallback_books as $book) {
                                echo '
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="' . $book['cover'] . '" alt="' . $book['title'] . '">
                                        <div class="book-status status-new">New</div>
                                    </div>
                                    <div class="book-info">
                                        <span class="book-category">' . $book['category'] . '</span>
                                        <h3>' . $book['title'] . '</h3>
                                        <p>by ' . $book['author'] . '</p>
                                        <div class="book-description">' . $book['desc'] . '</div>
                                        <div class="book-actions">
                                            <a href="catalog.php" class="btn btn-outline">Browse</a>
                                            ' . (isset($_SESSION['user_id']) ? '<a href="catalog.php" class="btn btn-primary">Read Now</a>' : '<a href="auth.php" class="btn btn-primary">Login to Read</a>') . '
                                        </div>
                                    </div>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const browseButton = document.querySelector('.btn-primary[href="catalog.php"]');
            
            if (searchInput && browseButton) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const searchTerm = this.value.trim();
                        if (searchTerm) {
                            window.location.href = 'catalog.php?search=' + encodeURIComponent(searchTerm);
                        } else {
                            window.location.href = 'catalog.php';
                        }
                    }
                });
                
                // Update browse button to include search term
                browseButton.addEventListener('click', function(e) {
                    const searchTerm = searchInput.value.trim();
                    if (searchTerm) {
                        e.preventDefault();
                        window.location.href = 'catalog.php?search=' + encodeURIComponent(searchTerm);
                    }
                });
            }
        });
    </script>
</body>
</html>