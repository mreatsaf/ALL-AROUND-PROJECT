<?php
session_start();
include 'connect.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uID = $_SESSION['user_id'];
$uName = $_SESSION['user_name'];

// 2. FETCH Notifications
$notifQuery = $conn->query("SELECT * FROM notifications WHERE UserID = '$uID' ORDER BY CreatedAt DESC LIMIT 5");

// 3. FETCH Borrowed Books (Active & History)
$borrowQuery = $conn->query("SELECT b.BorrowID, bk.BookTitle, b.BorrowDate, b.DueDate, b.Status 
                             FROM borrow b 
                             JOIN books bk ON b.BookID = bk.BookID 
                             WHERE b.UserID = '$uID' 
                             ORDER BY b.BorrowDate DESC");

// 4. FETCH Total Unpaid Fines
$fineQuery = $conn->query("SELECT SUM(Amount) as total FROM fine JOIN borrow ON fine.BorrowID = borrow.BorrowID WHERE borrow.UserID = '$uID' AND PaidStatus = 'Unpaid'");
$fine = $fineQuery->fetch_assoc();

// 5. FETCH Dynamic Role for Sidebar
$roleQuery = $conn->query("SELECT * FROM user_roles WHERE RoleID = '$uID'");
$role = $roleQuery->fetch_assoc();

$displayRole = "Student"; 
if ($role) {
    if ($role['FacultyID'] != 0) $displayRole = "Faculty";
    elseif ($role['StaffID'] != 0) $displayRole = "Library Staff";
    elseif ($role['VisitorID'] != 0) $displayRole = "Visitor";
    elseif ($role['AdminID'] != 0) $displayRole = "Administrator";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - BulSU Library</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .header { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .notif-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .status-read-text { color: #007bff; font-weight: bold; font-size: 0.9em; }
        .role-badge { background: #e7f3ff; color: #007bff; padding: 3px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; border: 1px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        .badge { padding: 4px 8px; border-radius: 5px; font-size: 0.8em; font-weight: bold; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-returned { background: #d4edda; color: #155724; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: bold; border: 1px solid #dc3545; padding: 8px 15px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin:0; color: #007bff;">BulSU Library System</h1>
        <p style="margin:5px 0 0;">Welcome, <strong><?= htmlspecialchars($uName) ?></strong></p>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="dashboard-grid">
    <div class="main-content">
        
        <div class="card" style="text-align: center; padding: 30px 20px; border-bottom: 5px solid #007bff;">
            <h2 style="margin-top: 0; color: #333;">Library Resources Catalog</h2>
            <p style="color: #666;">Access Research Papers, Magazines, and Journals.</p>
            <a href="view_books.php" class="catalog-link" style="background: #007bff; color: white; padding: 12px 35px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px;">
                Open Full Catalog →
            </a>
        </div>

        <div class="card">
            <h3>My Borrowed Books</h3>
            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($borrowQuery && $borrowQuery->num_rows > 0): ?>
                        <?php while($b = $borrowQuery->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['BookTitle']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($b['BorrowDate'])) ?></td>
                            <td><?= date('M d, Y', strtotime($b['DueDate'])) ?></td>
                            <td>
                                <span class="badge <?= ($b['Status'] == 'Returned') ? 'badge-returned' : 'badge-pending' ?>">
                                    <?= $b['Status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#888;">You haven't borrowed any books yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Recent Notifications</h3>
            <?php if ($notifQuery && $notifQuery->num_rows > 0): ?>
                <?php while($n = $notifQuery->fetch_assoc()): ?>
                    <div class="notif-item">
                        <div>
                            <p style="margin: 0; font-weight: 500;"><?= htmlspecialchars($n['Message']) ?></p>
                            <small style="color: gray;"><?= date('M d, Y', strtotime($n['CreatedAt'])) ?></small>
                        </div>
                        <div id="status-box-<?= $n['ID'] ?>">
                            <?php if ($n['Status'] == 'Read'): ?>
                                <span class="status-read-text">✔ Read</span>
                            <?php else: ?>
                                <label style="font-size: 0.85em; color: #666; cursor: pointer;">
                                    <input type="checkbox" class="mark-read-cb" data-id="<?= $n['ID'] ?>"> Mark read
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#888;">No notifications.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar">
        <div class="card" style="text-align: center; border-top: 5px solid #007bff;">
            <p style="color: #666; margin-bottom: 5px;">Outstanding Balance</p>
            <h1 style="margin: 0; font-size: 2.5em;">₱<?= number_format($fine['total'] ?? 0, 2) ?></h1>
            <small style="color: <?= ($fine['total'] > 0) ? '#dc3545' : '#28a745' ?>; font-weight: bold;">
                <?= ($fine['total'] > 0) ? 'Unpaid Fines Found' : 'Account is Clear' ?>
            </small>
        </div>

        <div class="card">
            <h3>Profile Summary</h3>
