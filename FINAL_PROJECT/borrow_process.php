<?php
session_start();
include 'connect.php';

// 1. Security Check: Dapat naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $bookID = $_GET['id'];
    $userID = $_SESSION['user_id'];
    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+7 days')); // 1 week deadline
    $status = 'Borrowed';

    // 2. I-check uli kung may stock pa (Safety double-check)
    $checkStock = $conn->query("SELECT Quantity FROM books WHERE BookID = '$bookID'");
    $book = $checkStock->fetch_assoc();

    if ($book && $book['Quantity'] > 0) {
        
        // 3. I-insert sa borrow table
        $sqlBorrow = "INSERT INTO borrow (UserID, BookID, BorrowDate, DueDate, Status) 
                      VALUES ('$userID', '$bookID', '$borrowDate', '$dueDate', '$status')";

        if ($conn->query($sqlBorrow)) {
            // 4. Bawasan ang quantity sa books table
            $conn->query("UPDATE books SET Quantity = Quantity - 1 WHERE BookID = '$bookID'");

            // 5. Opsyonal: Mag-send ng notification sa user (gamit ang notifications table mo)
            $msg = "You successfully borrowed a book. Please return it by " . $dueDate;
            $conn->query("INSERT INTO notifications (UserID, Message, Status) VALUES ('$userID', '$msg', 'Unread')");

            echo "<script>alert('Success! Please proceed to the librarian for pickup.'); window.location='userdashboard.php';</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Sorry, this book is no longer available.'); window.location='view_books.php';</script>";
    }
} else {
    header("Location: view_books.php");
}
?>