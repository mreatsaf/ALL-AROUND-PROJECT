<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uID = $_SESSION['user_id'];
$uName = $_SESSION['user_name'];

// Fetch specific role flags
$roleQuery = "SELECT * FROM user_roles WHERE RoleID = '$uID'";
$role = $conn->query($roleQuery)->fetch_assoc();

// Base queries for books and fines
$borrowed = $conn->query("SELECT b.BookTitle, br.DueDate FROM borrow br JOIN books b ON br.BookID = b.BookID WHERE br.UserID = '$uID' AND br.Status = 'Borrowed'");
$fine = $conn->query("SELECT SUM(Amount) as total FROM fine JOIN borrow ON fine.BorrowID = borrow.BorrowID WHERE borrow.UserID = '$uID' AND PaidStatus = 'Unpaid'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 20px; }
        .card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .role-badge { background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>

<h1>Library Dashboard</h1>
<p>Welcome, <strong><?php echo htmlspecialchars($uName); ?></strong> 
   <span class="role-badge">
       <?php 
       if($role['FacultyID'] != 0) echo "Faculty";
       elseif($role['StaffID'] != 0) echo "Staff";
       elseif($role['VisitorID'] != 0) echo "Visitor";
       else echo "Student";
       ?>
   </span>
</p>
<a href="logout.php">Logout</a>

<div class="dashboard-grid">
    <div class="main-tools">
        
        <?php if($role['FacultyID'] != 0): ?>
            <div class="card">
                <h3>Faculty Action Center</h3>
                <ul>
                    <li><a href="request_book.php">Request New Book for Course</a></li>
                    <li><a href="classroom_reserves.php">Manage Classroom Reserves</a></li>
                </ul>
            </div>

        <?php elseif($role['StaffID'] != 0): ?>
            <div class="card">
                <h3>Staff Action Center</h3>
                <ul>
                    <li><a href="verify_returns.php">Verify Book Returns</a></li>
                    <li><a href="organize_shelves.php">Shelf Organization List</a></li>
                </ul>
            </div>

        <?php elseif($role['VisitorID'] != 0): ?>
            <div class="card">
                <h3>Visitor Action Center</h3>
                <p>Limited Access Mode: You can browse the catalog but cannot checkout items.</p>
                <button onclick="location.href='catalog.php'">Search Catalog</button>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>My Activity</h3>
            <table border="1" width="100%">
                <tr><th>Book</th><th>Due Date</th></tr>
                <?php while($b = $borrowed->fetch_assoc()): ?>
                    <tr><td><?= $b['BookTitle'] ?></td><td><?= $b['DueDate'] ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="sidebar">
        <div class="card" style="border-color: red;">
            <h3>Outstanding Fines</h3>
            <h2 style="color:red;">₱<?php echo number_format($fine['total'] ?? 0, 2); ?></h2>
        </div>
    </div>
</div>

</body>
</html>