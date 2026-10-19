<?php
session_start();
require_once 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// Query all downloads with book and user info
$sql = "SELECT fd.id as download_id, b.title as book_title, b.id as book_id, u.name as user_name, u.id as user_id, b.file_type as file_type, fd.downloaded_at
        FROM file_downloads fd
        LEFT JOIN books b ON fd.book_id = b.id
        LEFT JOIN users u ON fd.user_id = u.id
        ORDER BY fd.downloaded_at DESC";

$result = $conn->query($sql);
if (!$result) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Database error';
    exit;
}

$filename = 'downloads_report_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
// Header row
fputcsv($out, ['download_id', 'book_id', 'book_title', 'user_id', 'user_name', 'file_type', 'downloaded_at']);

while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['download_id'],
        $row['book_id'],
        $row['book_title'],
        $row['user_id'],
        $row['user_name'],
        $row['file_type'],
        $row['downloaded_at']
    ]);
}

fclose($out);
exit;

?>
