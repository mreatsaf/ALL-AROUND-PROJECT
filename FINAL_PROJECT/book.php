<?php
session_start();
include 'connect.php';

// 1. Admin Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$uID = $_SESSION['user_id'];
$checkAdmin = $conn->query("SELECT AdminID FROM user_roles WHERE RoleID = '$uID'");
$roleData = $checkAdmin->fetch_assoc();

if (!$roleData || $roleData['AdminID'] == 0) {
    echo "<script>alert('Access Denied'); window.location='userdashboard.php';</script>";
    exit();
}

// 2. Handle Add Book Submission
if (isset($_POST['add_book'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $catName = mysqli_real_escape_string($conn, $_POST['category_name']);
    $pubName = mysqli_real_escape_string($conn, $_POST['publisher_name']);
    $qty = (int)$_POST['quantity']; 
    $authorName = mysqli_real_escape_string($conn, $_POST['author_name']);

    // Check if quantity is negative
    if ($qty < 0) {
        echo "<script>alert('Error: Quantity cannot be negative!'); window.history.back();</script>";
        exit();
    }

    // --- RESTORED LOGIC START ---
    
    // Handle Category: Check if exists, else insert
    $catCheck = $conn->query("SELECT CategoryID FROM categories WHERE CategoryName = '$catName'");
    if ($catCheck->num_rows > 0) {
        $catID = $catCheck->fetch_assoc()['CategoryID'];
    } else {
        $conn->query("INSERT INTO categories (CategoryName) VALUES ('$catName')");
        $catID = $conn->insert_id;
    }

    // Handle Publisher: Check if exists, else insert
    $pubCheck = $conn->query("SELECT PublisherID FROM publisher WHERE PublisherName = '$pubName'");
    if ($pubCheck->num_rows > 0) {
        $pubID = $pubCheck->fetch_assoc()['PublisherID'];
    } else {
        $conn->query("INSERT INTO publisher (PublisherName) VALUES ('$pubName')");
        $pubID = $conn->insert_id;
    }

    // Handle Author: Check if exists, else insert
    $authCheck = $conn->query("SELECT AuthorID FROM authors WHERE AuthorName = '$authorName'");
    if ($authCheck->num_rows > 0) {
        $authID = $authCheck->fetch_assoc()['AuthorID'];
    } else {
        $conn->query("INSERT INTO authors (AuthorName) VALUES ('$authorName')");
        $authID = $conn->insert_id;
    }

    // --- RESTORED LOGIC END ---

    // Insert Book
    $insertBook = "INSERT INTO books (BookTitle, CategoryID, PublisherId, Quantity) 
                   VALUES ('$title', '$catID', '$pubID', '$qty')";
    
    if ($conn->query($insertBook)) {
        $newBookID = $conn->insert_id;
        // Link to Author in junction table
        $conn->query("INSERT INTO book_authors (BookID, AuthorID) VALUES ('$newBookID', '$authID')");
        echo "<script>alert('Book added successfully!'); window.location='book.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// 3. Fetch Data for Table
$sql = "SELECT b.BookID, b.BookTitle, c.CategoryName, p.PublisherName, b.Quantity 
        FROM books b
        JOIN categories c ON b.CategoryID = c.CategoryID
        JOIN publisher p ON b.PublisherId = p.PublisherID";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 20px; }
        .form-section input { padding: 8px; margin-right: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #333; color: white; }
        .btn { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <a href="admindashboard.php" style="text-decoration: none; color: blue;">← Back to Dashboard</a>
    <h1>Manage Library Books</h1>

    <div class="form-section">
        <h3>Add New Book</h3>
        <form method="POST">
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author_name" placeholder="Author Name" required>
            <input type="text" name="category_name" placeholder="Category" required>
            <input type="text" name="publisher_name" placeholder="Publisher" required>
            <input type="number" name="quantity" placeholder="Qty" style="width: 70px;" min="0" required>
            <button type="submit" name="add_book" class="btn">Add Book</button>
        </form>
    </div>

    <h3>Current Inventory</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author(s)</th>
                <th>Category</th>
                <th>Publisher</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['BookID'] ?></td>
                    <td><?= htmlspecialchars($row['BookTitle']) ?></td>
                    <td>
                        <?php
                        $bid = $row['BookID'];
                        $authList = $conn->query("SELECT a.AuthorName FROM authors a JOIN book_authors ba ON a.AuthorID = ba.AuthorID WHERE ba.BookID = '$bid'");
                        $names = [];
                        while($aname = $authList->fetch_assoc()) $names[] = $aname['AuthorName'];
                        echo htmlspecialchars(implode(", ", $names));
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['CategoryName']) ?></td>
                    <td><?= htmlspecialchars($row['PublisherName']) ?></td>
                    <td><?= $row['Quantity'] ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">No books in inventory.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>