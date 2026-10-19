<?php
include 'config.php';
$page_title = "elibrary - Admin Dashboard";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth.php');
    exit;
}

// Get admin stats
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total_books FROM books");
$stats['total_books'] = $result->fetch_assoc()['total_books'];

$result = $conn->query("SELECT COUNT(*) as books_with_files FROM books WHERE file_name IS NOT NULL");
$stats['books_with_files'] = $result->fetch_assoc()['books_with_files'];

$result = $conn->query("SELECT COUNT(*) as active_members FROM users WHERE role = 'member'");
$stats['active_members'] = $result->fetch_assoc()['active_members'];

$result = $conn->query("SELECT COUNT(*) as total_downloads FROM file_downloads");
$stats['total_downloads'] = $result->fetch_assoc()['total_downloads'];

// Get recent downloads
$downloads_result = $conn->query("
    SELECT fd.*, b.title as book_title, u.name as user_name, b.file_type
    FROM file_downloads fd
    JOIN books b ON fd.book_id = b.id
    JOIN users u ON fd.user_id = u.id
    ORDER BY fd.downloaded_at DESC
    LIMIT 10
");
$recent_downloads = $downloads_result->fetch_all(MYSQLI_ASSOC);

// Get all books for display
$books_result = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 6");
$recent_books = $books_result->fetch_all(MYSQLI_ASSOC);
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
                        <h2>Administrator Console</h2>
                        <p>Manage your free e-library system.</p>
                    </div>
                    
                    <div class="admin-tabs">
                        <button class="tab-btn active" data-tab="overview">Overview</button>
                        <button class="tab-btn" data-tab="books">Books Management</button>
                        <button class="tab-btn" data-tab="users">Users Management</button>
                        <button class="tab-btn" data-tab="downloads">Download History</button>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div id="overview-tab" class="tab-pane active">
                        <!-- Stats Cards -->
                        <div class="stats-grid admin-stats">
                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-blue">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Books</p>
                                    <h3 class="stat-value"><?php echo $stats['total_books']; ?></h3>
                                    <span class="proof-badge success">Free E-Library</span>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-purple">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Digital Books</p>
                                    <h3 class="stat-value"><?php echo $stats['books_with_files']; ?></h3>
                                    <span class="proof-badge info">Ready to Download</span>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-emerald">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Total Downloads</p>
                                    <h3 class="stat-value"><?php echo $stats['total_downloads']; ?></h3>
                                    <span class="proof-badge success">Active</span>
                                </div>
                            </div>

                            <div class="stat-card admin-stat-card">
                                <div class="stat-icon admin-stat-icon bg-orange">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <div class="stat-info">
                                    <p class="stat-label">Active Members</p>
                                    <h3 class="stat-value"><?php echo $stats['active_members']; ?></h3>
                                    <span class="proof-badge info">Registered Users</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Books -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3>Recently Added Books</h3>
                                <a href="admin-dashboard.php?tab=books" class="btn btn-outline">View All Books</a>
                            </div>
                            <div class="books-grid">
                                <?php foreach ($recent_books as $book): ?>
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4='">
                                        <?php if ($book['file_name']): ?>
                                        <div class="book-status available">Free Download</div>
                                        <?php else: ?>
                                        <div class="book-status unavailable">No File</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-info">
                                        <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                        <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                        <div class="book-meta">
                                            <span class="book-category"><?php echo htmlspecialchars($book['category']); ?></span>
                                            <?php if ($book['file_name']): ?>
                                            <span class="proof-badge success"><?php echo strtoupper(pathinfo($book['file_name'], PATHINFO_EXTENSION)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="proof-container">
                            <div class="section-card">
                                <div class="section-header">
                                    <h3>Recent Downloads</h3>
                                    <span class="proof-badge">Live Updates</span>
                                </div>
                                <div class="proofs-list">
                                    <?php if (count($recent_downloads) > 0): ?>
                                        <?php foreach (array_slice($recent_downloads, 0, 5) as $download): ?>
                                        <div class="action-proof">
                                            <div class="action-proof-header">
                                                <div class="action-proof-title">Book Downloaded</div>
                                                <div class="action-proof-time"><?php echo date('M j, g:i A', strtotime($download['downloaded_at'])); ?></div>
                                            </div>
                                            <div class="action-proof-content">
                                                <strong><?php echo htmlspecialchars($download['book_title']); ?></strong><br>
                                                User: <?php echo htmlspecialchars($download['user_name']); ?><br>
                                                Format: <?php echo strtoupper($download['file_type']); ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="action-proof">
                                            <div class="action-proof-content">
                                                No downloads yet. Books will appear here when users start downloading.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Books Management Tab -->
                    <div id="books-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Books Management</h3>
                                <a href="addbook.php" class="btn btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"></path>
                                    </svg>
                                    Add New Book
                                </a>
                            </div>
                            
                            <?php
                            // Get all books
                            $books_result = $conn->query("SELECT * FROM books ORDER BY title");
                            $books = $books_result->fetch_all(MYSQLI_ASSOC);
                            ?>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Book Details</th>
                                            <th>ISBN</th>
                                            <th>Category</th>
                                            <th>File</th>
                                            <th>Downloads</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $book): 
                                            $download_count = $conn->query("SELECT COUNT(*) as count FROM file_downloads WHERE book_id = " . $book['id'])->fetch_assoc()['count'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="book-cell">
                                                    <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIj5ObyBDb3ZlcjwvdGV4dD48L3N2Zz4='">
                                                    <div>
                                                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                                        <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="isbn-cell"><?php echo htmlspecialchars($book['isbn']); ?></td>
                                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                                            <td>
                                                <?php if ($book['file_name']): ?>
                                                <span class="proof-badge success"><?php echo strtoupper(pathinfo($book['file_name'], PATHINFO_EXTENSION)); ?></span>
                                                <?php else: ?>
                                                <span class="proof-badge warning">No File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="download-count"><?php echo $download_count; ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($book['file_name']): ?>
                                                    <a href="download.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-success">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                            <polyline points="7 10 12 15 17 10"></polyline>
                                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                                        </svg>
                                                        Download
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="editbook.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="deletebook.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this book? This will also delete all download records.')">
                                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline" style="color: var(--rose-600);">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Users Management Tab -->
                    <div id="users-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Users Management</h3>
                                <a href="adduser.php" class="btn btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    Add New User
                                </a>
                            </div>
                            
                            <?php
                            // Get all users
                            $users_result = $conn->query("SELECT * FROM users ORDER BY name");
                            $users = $users_result->fetch_all(MYSQLI_ASSOC);
                            ?>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Downloads</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): 
                                            $initials = getInitials($user['name']);
                                            $download_count = $conn->query("SELECT COUNT(*) as count FROM file_downloads WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar-small"><?php echo $initials; ?></div>
                                                    <div>
                                                        <div class="book-title"><?php echo htmlspecialchars($user['name']); ?></div>
                                                        <div class="book-author">ID: <?php echo $user['id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-borrowed' : 'status-available'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="download-count"><?php echo $download_count; ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edituser.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" action="deleteuser.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their download records.')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline" style="color: var(--rose-600);">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Downloads Tab -->
                    <div id="downloads-tab" class="tab-pane">
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Download History</h3>
                                <a href="export_downloads.php" class="btn btn-outline" target="_blank" rel="noopener">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Export Report
                                </a>
                            </div>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Book</th>
                                            <th>User</th>
                                            <th>File Type</th>
                                            <th>Downloaded At</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_downloads as $download): ?>
                                        <tr>
                                            <td>
                                                <div class="book-title"><?php echo htmlspecialchars($download['book_title']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($download['user_name']); ?></td>
                                            <td>
                                                <span class="proof-badge info"><?php echo strtoupper($download['file_type']); ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($download['downloaded_at'])); ?></td>
                                           
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            // Check if there's a tab parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'overview';
            
            // Activate the correct tab
            tabBtns.forEach(btn => {
                if (btn.getAttribute('data-tab') === activeTab) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            tabPanes.forEach(pane => {
                if (pane.id === activeTab + '-tab') {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        });

        // Handle tab clicks
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `admin-dashboard.php?tab=${tabId}`;
            });
        });

        // Export now handled server-side via export_downloads.php
    </script>
</body>
</html>

<?php
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return $initials;
}
?>