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

// --- BAGONG LOGIC: HANDLE PERMANENT DELETE ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Burahin muna ang references sa book_authors bago ang main book record (Foreign Key constraint)
    $conn->query("DELETE FROM book_authors WHERE BookID = '$id'");
    if ($conn->query("DELETE FROM books WHERE BookID = '$id'")) {
        echo "<script>alert('Book permanently deleted!'); window.location='book.php';</script>";
    }
}

// --- BAGONG LOGIC: HANDLE EDIT/UPDATE ---
if (isset($_POST['update_book'])) {
    $id = $_POST['book_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $qty = (int)$_POST['quantity'];

    $updateQuery = "UPDATE books SET BookTitle = '$title', Quantity = '$qty' WHERE BookID = '$id'";
    if ($conn->query($updateQuery)) {
        echo "<script>alert('Book updated successfully!'); window.location='book.php';</script>";
    }
}

// 2. HANDLE ARCHIVING (SOFT DELETE)
if (isset($_GET['archive_id'])) {
    $id = $_GET['archive_id'];
    $getBook = $conn->query("SELECT * FROM books WHERE BookID = '$id'")->fetch_assoc();
    
    if ($getBook) {
        $title = mysqli_real_escape_string($conn, $getBook['BookTitle']);
        $cat = $getBook['CategoryID'];
        $pub = $getBook['PublisherId'];
        $qty = $getBook['Quantity'];

        $archiveQuery = "INSERT INTO book_archive (BookID, BookTitle, CategoryID, PublisherId, Quantity) 
                         VALUES ('$id', '$title', '$cat', '$pub', '$qty')";
        
        if ($conn->query($archiveQuery)) {
            $conn->query("DELETE FROM books WHERE BookID = '$id'");
            echo "<script>alert('Book moved to Archive!'); window.location='book.php';</script>";
        }
    }
}

// 3. HANDLE ADD BOOK SUBMISSION
if (isset($_POST['add_book'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $catName = mysqli_real_escape_string($conn, $_POST['category_name']);
    $pubName = mysqli_real_escape_string($conn, $_POST['publisher_name']);
    $qty = (int)$_POST['quantity']; 
    $authorName = mysqli_real_escape_string($conn, $_POST['author_name']);

    if ($qty < 0) {
        echo "<script>alert('Error: Quantity cannot be negative!'); window.history.back();</script>";
        exit();
    }

    $catCheck = $conn->query("SELECT CategoryID FROM categories WHERE CategoryName = '$catName'");
    $catID = ($catCheck->num_rows > 0) ? $catCheck->fetch_assoc()['CategoryID'] : ($conn->query("INSERT INTO categories (CategoryName) VALUES ('$catName')") ? $conn->insert_id : 0);

    $pubCheck = $conn->query("SELECT PublisherID FROM publisher WHERE PublisherName = '$pubName'");
    $pubID = ($pubCheck->num_rows > 0) ? $pubCheck->fetch_assoc()['PublisherID'] : ($conn->query("INSERT INTO publisher (PublisherName) VALUES ('$pubName')") ? $conn->insert_id : 0);

    $authCheck = $conn->query("SELECT AuthorID FROM authors WHERE AuthorName = '$authorName'");
    $authID = ($authCheck->num_rows > 0) ? $authID = $authCheck->fetch_assoc()['AuthorID'] : ($conn->query("INSERT INTO authors (AuthorName) VALUES ('$authorName')") ? $conn->insert_id : 0);

    $insertBook = "INSERT INTO books (BookTitle, CategoryID, PublisherId, Quantity) VALUES ('$title', '$catID', '$pubID', '$qty')";
    if ($conn->query($insertBook)) {
        $newBookID = $conn->insert_id;
        $conn->query("INSERT INTO book_authors (BookID, AuthorID) VALUES ('$newBookID', '$authID')");
        echo "<script>alert('Book added successfully!'); window.location='book.php';</script>";
    }
}

// 4. Fetch Active Books
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
        .header-nav { display: flex; justify-content: space-between; align-items: center; }
        .form-section { border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 20px; }
        .form-section input { padding: 8px; margin-right: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #333; color: white; }
        .btn { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        .btn-edit { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; }
        .btn-archive { background: #ffc107; color: #000; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; }
        .edit-row { background: #f9f9f9; display: none; } /* Hidden edit form */
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <a href="admindashboard.php" style="text-decoration: none; color: blue;">← Back to Dashboard</a>
        <a href="archive.php" class="btn-view-archive" style="background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">📂 View Archive</a>
    </div>

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

    <h3>Active Inventory</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author(s)</th>
                <th>Category</th>
                <th>Publisher</th>
                <th>Qty</th>
                <th>Action</th>
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
                    <td>
                        <button class="btn-edit" onclick="toggleEdit(<?= $row['BookID'] ?>)">Edit</button>
                        <a href="book.php?archive_id=<?= $row['BookID'] ?>" class="btn-archive" onclick="return confirm('Move to Archive?')">Archive</a>
                        <a href="book.php?delete_id=<?= $row['BookID'] ?>" class="btn-delete" onclick="return confirm('PERMANENTLY DELETE this book?')">Delete</a>
                    </td>
                </tr>
                <tr id="edit-form-<?= $row['BookID'] ?>" class="edit-row">
                    <form method="POST">
                        <input type="hidden" name="book_id" value="<?= $row['BookID'] ?>">
                        <td colspan="1">Editing...</td>
                        <td colspan="4"><input type="text" name="title" value="<?= htmlspecialchars($row['BookTitle']) ?>" required style="width:90%"></td>
                        <td colspan="1"><input type="number" name="quantity" value="<?= $row['Quantity'] ?>" required style="width:50px"></td>
                        <td>
                            <button type="submit" name="update_book" class="btn" style="padding: 5px 10px;">Save</button>
                            <button type="button" onclick="toggleEdit(<?= $row['BookID'] ?>)" style="padding: 5px 10px;">Cancel</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center;">No books found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-form-' + id);
    if (row.style.display === 'table-row') {
        row.style.display = 'none';
    } else {
        row.style.display = 'table-row';
    }
}
</script>

</body>
</html>
