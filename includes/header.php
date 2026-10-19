<?php
include 'config.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info if logged in
$user_name = "Guest";
$user_role = "guest";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user_data = $result->fetch_assoc()) {
        $user_name = $user_data['name'];
        $user_role = $user_data['role'];
    }
    $stmt->close();
}
?>
<header class="header">
    <div class="breadcrumb">
        <span class="app-name">eLibrary</span>
        <span>/</span>
        <span class="current">
            <?php 
            $page_names = [
                'index.php' => 'Home',
                'roadmap.php' => 'Project',
                'catalog.php' => 'Book Catalog',
                'auth.php' => 'Authentication',
                'user-dashboard.php' => 'My Dashboard',
                'admin-dashboard.php' => 'Admin Dashboard'
            ];
            echo $page_names[$current_page] ?? ucfirst(str_replace('.php', '', $current_page));
            ?>
        </span>
    </div>
    
    <div class="header-actions" style="display:flex; align-items:center;">
        <!-- Mobile: menu toggle to show/hide sidebar -->
        <!-- Always render at the start of header actions -->
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <button id="open-sidebar-btn" class="settings-btn" aria-label="Open sidebar" style="display:inline-flex;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <span class="text-small" style="margin-left:0.5rem;">Open Sidebar</span>
            </button>
            <button id="close-sidebar-btn" class="settings-btn" aria-label="Close sidebar" style="display:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span class="text-small" style="margin-left:0.5rem;">Close Sidebar</span>
            </button>
        </div>

        <button class="settings-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </button>
        
        <!-- User quick actions: Edit profile only -->
        <a href="edituser.php?id=<?php echo isset($user_id) ? (int)$user_id : ''; ?>" class="btn btn-secondary" title="Edit profile" style="margin:0 0.5rem; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 20h9" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <span class="text-small">Edit</span>
        </a>
        
        <div class="user-profile">
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="user-role"><?php echo ucfirst($user_role); ?></p>
            </div>
            <img src="https://picsum.photos/seed/admin/100/100" alt="User" class="user-avatar">
        </div>
    </div>
</header>

<!-- Mobile sidebar overlay (controlled via body.sidebar-open) -->
<div class="modal-overlay-mobile" onclick="document.body.classList.remove('sidebar-open')"></div>

<script>
// Toggle mobile sidebar open/close buttons and overlay
(function(){
    var openBtn = document.getElementById('open-sidebar-btn');
    var closeBtn = document.getElementById('close-sidebar-btn');
    var mobileOverlay = document.querySelector('.modal-overlay-mobile');
    function updateVisibility() {
        var isSmall = window.matchMedia('(max-width: 640px)').matches;
        var sidebarOpen = document.body.classList.contains('sidebar-open');
        if (openBtn) openBtn.style.display = isSmall && !sidebarOpen ? 'inline-flex' : 'none';
        if (closeBtn) closeBtn.style.display = isSmall && sidebarOpen ? 'inline-flex' : 'none';
        if (mobileOverlay) mobileOverlay.style.display = sidebarOpen && isSmall ? 'block' : 'none';
    }
    if (openBtn) openBtn.addEventListener('click', function(){
        document.body.classList.add('sidebar-open');
        updateVisibility();
    });
    if (closeBtn) closeBtn.addEventListener('click', function(){
        document.body.classList.remove('sidebar-open');
        updateVisibility();
    });
    window.addEventListener('resize', updateVisibility);
    document.addEventListener('DOMContentLoaded', updateVisibility);
})();
</script>