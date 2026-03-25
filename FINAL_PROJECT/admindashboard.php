<?php
session_start();
include 'connect.php';

// 1. Admin Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uID = $_SESSION['user_id'];
$uName = $_SESSION['user_name'];

// Check if the user is actually an admin
$adminCheck = $conn->query("SELECT AdminID FROM user_roles WHERE RoleID = '$uID'");
$isAdmin = $adminCheck->fetch_assoc();

if (!$isAdmin || $isAdmin['AdminID'] == 0) {
    header("Location: userdashboard.php");
    exit();
}

// 2. Fetch Stats for the Dashboard
$totalBooks = $conn->query("SELECT SUM(Quantity) as total FROM books")->fetch_assoc();
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM user")->fetch_assoc();
$pendingReturns = $conn->query("SELECT COUNT(*) as total FROM borrow WHERE Status = 'Borrowed'")->fetch_assoc();
// Count unread notifications globally so admin knows if people are ignoring them
$unreadNotifs = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE Status = 'Unread'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BulSU Library</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .header { background: #343a40; color: white; padding: 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h2 { margin: 5px 0; color: #007bff; }
        
        .menu-grid {  display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .menu-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: 0.3s; text-decoration: none; color: #333; border-left: 5px solid #007bff; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .menu-card h3 { margin-top: 0; color: #333; }
        .menu-card p { color: #666; font-size: 0.9em; }
        
        .logout-btn { background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; }
        .badge { background: #ffc107; color: black; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin:0;">Library Admin Panel</h1>
        <p style="margin:5px 0 0;">Welcome, Administrator <strong><?php echo htmlspecialchars($uName); ?></strong></p>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <p>Total Books</p>
        <h2><?php echo $totalBooks['total'] ?? 0; ?></h2>
    </div>
    <div class="stat-card">
        <p>Registered Users</p>
        <h2><?php echo $totalUsers['total']; ?></h2>
    </div>
    <div class="stat-card">
        <p>Active Borrows</p>
        <h2><?php echo $pendingReturns['total']; ?></h2>
    </div>
    <div class="stat-card">
        <p>Unread Notices</p>
        <h2><?php echo $unreadNotifs['total']; ?></h2>
    </div>
</div>

<div class="menu-grid">
    
    <a href="book.php" class="menu-card">
        <h3>📚 Book Management</h3>
        <p>Add new titles, update quantities, and manage authors or publishers.</p>
    </a>

    <a href="materials_catalog.php" class="menu-card" style="border-left-color: #6610f2;">
    <h3>🧰 Materials Management</h3>
    <p>Manage library materials like equipment, references, and other resources.</p>
    </a>

    <a href="admin_notifications.php" class="menu-card" style="border-left-color: #ffc107;">
        <h3>📣 Notification Center</h3>
        <p>Send messages to users and check if they have read your notices.</p>
    </a>

    <a href="archive.php" class="menu-card" style="border-left-color: #6c757d;">
        <h3>📂 Archive & Recovery</h3>
        <p>View recently deleted books and restore them to the main inventory.</p>
    </a>

    <a href="borrow_records.php" class="menu-card" style="border-left-color: #28a745;">
        <h3>📝 Borrowing Records</h3>
        <p>Process new borrows, track due dates, and manage book returns.</p>
    </a>

    <a href="manage_users.php" class="menu-card" style="border-left-color: #17a2b8;">
        <h3>👥 User Management</h3>
        <p>Review user roles (Faculty, Staff, Student) and account statuses.</p>
    </a>

    <a href="manage_fines.php" class="menu-card" style="border-left-color: #dc3545;">
        <h3>💰 Fine Management</h3>
        <p>Track unpaid fines and record payments from returned books.</p>
    </a>

</div>

</body>
</html>
