<?php
session_start();
include 'connect.php';

// 1. Admin Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Handle Return Action (Kapag isinauli na ang libro)
if (isset($_GET['return_id'])) {
    $borrowID = $_GET['return_id'];
    
    // Kunin ang BookID bago i-update
    $info = $conn->query("SELECT BookID FROM borrow WHERE BorrowID = '$borrowID'")->fetch_assoc();
    $bookID = $info['BookID'];

    // Update Status at maglagay ng ReturnDate
    $updateBorrow = $conn->query("UPDATE borrow SET Status = 'Returned', Returndate = CURDATE() WHERE BorrowID = '$borrowID'");
    
    // Ibalik ang Quantity (+1) sa books table
    $updateQty = $conn->query("UPDATE books SET Quantity = Quantity + 1 WHERE BookID = '$bookID'");

    if ($updateBorrow && $updateQty) {
        echo "<script>alert('Book marked as Returned!'); window.location='borrow_records.php';</script>";
    }
}

// 3. Fetch All Borrowing Records
$sql = "SELECT b.BorrowID, u.Name as UserName, bk.BookTitle, b.BorrowDate, b.DueDate, b.Status 
        FROM borrow b
        JOIN user u ON b.UserID = u.RoleID
        JOIN books bk ON b.BookID = bk.BookID
        ORDER BY b.BorrowDate DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrowing Records - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #007bff; color: white; }
        .status-borrowed { color: #d9534f; font-weight: bold; }
        .status-returned { color: #5cb85c; font-weight: bold; }
        .btn-return { background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <h2>📝 Borrowing Records Management</h2>
    <a href="admindashboard.php">← Back to Dashboard</a>

    <table>
        <thead>
            <tr>
                <th>Borrow ID</th>
                <th>Borrower Name</th>
                <th>Book Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['BorrowID'] ?></td>
                    <td><strong><?= htmlspecialchars($row['UserName']) ?></strong></td>
                    <td><?= htmlspecialchars($row['BookTitle']) ?></td>
                    <td><?= $row['BorrowDate'] ?></td>
                    <td><?= $row['DueDate'] ?></td>
                    <td>
                        <span class="<?= ($row['Status'] == 'Borrowed') ? 'status-borrowed' : 'status-returned' ?>">
                            <?= $row['Status'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['Status'] == 'Borrowed'): ?>
                            <a href="borrow_records.php?return_id=<?= $row['BorrowID'] ?>" 
                               class="btn-return" onclick="return confirm('Mark this book as returned?')">
                               Return Book
                            </a>
                        <?php else: ?>
                            <small>Completed</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No borrowing records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>