<?php
session_start();
include 'connect.php';

// 1. Security check: Ensure the user is logged in AND is an Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uID = $_SESSION['user_id'];
// Check user_roles table to see if the current RoleID has an AdminID entry
$checkAdmin = $conn->query("SELECT AdminID FROM user_roles WHERE RoleID = '$uID'");
$roleData = $checkAdmin->fetch_assoc();

// If AdminID is 0 or the record doesn't exist, they aren't an admin
if (!$roleData || $roleData['AdminID'] == 0) {
    echo "<script>alert('Access Denied: Admins Only'); window.location='userdashboard.php';</script>";
    exit();
}

// 2. Fetch system statistics
// Total Books from the books table
$bookCount = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'];
// Total Registered Users from the user table
$userCount = $conn->query("SELECT COUNT(*) as total FROM user")->fetch_assoc()['total'];
// Total Borrowed Books with 'Borrowed' status
$borrowedCount = $conn->query("SELECT COUNT(*) as total FROM borrow WHERE Status = 'Borrowed'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; margin: 0; }
        .sidebar { width: 250px; background: #333; color: white; height: 100vh; padding: 20px; box-sizing: border-box; }
        .sidebar h2 { color: #fff; }
        .sidebar hr { border: 0.5px solid #555; }
        .sidebar a { display: block; margin: 15px 0; text-decoration: none; color: #bbb; transition: 0.3s; }
        .sidebar a:hover { color: white; }
        .sidebar a.active { color: white; font-weight: bold; }
        
        .main-content { flex-grow: 1; padding: 40px; background: #f4f4f4; }
        .stats-container { display: flex; gap: 20px; margin-top: 20px; }
        .card { padding: 20px; border: none; background: white; border-radius: 8px; width: 220px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card h3 { margin: 0; color: #666; font-size: 1rem; }
        .card h2 { margin: 10px 0 0; font-size: 2rem; color: #333; }
        .logout-btn { float: right; text-decoration: none; color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Library System</h2>
    <p>Welcome, Admin: <br><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
    <hr>
    <a href="admindashboard.php" class="active">Dashboard</a>
    <a href="book.php">Manage Books</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="borrow_records.php">Borrow Records</a>
    <a href="fines.php">Fines & Penalties</a>
</div>

<div class="main-content">
    <a href="logout.php" class="logout-btn">Logout</a>
    <h1>Admin Dashboard</h1>
    <p>System Overview</p>

    <div class="stats-container">
        <div class="card">
            <h3>Total Books</h3>
            <h2><?php echo $bookCount; ?></h2>
        </div>
        <div class="card">
            <h3>Registered Users</h3>
            <h2><?php echo $userCount; ?></h2>
        </div>
        <div class="card">
            <h3>Currently Borrowed</h3>
            <h2><?php echo $borrowedCount; ?></h2>
        </div>
    </div>
</div>

</body>
</html>