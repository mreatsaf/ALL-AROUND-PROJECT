<?php
session_start();
include 'connect.php';

// ✅ ADMIN SECURITY (same as dashboard)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ADD MATERIAL
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];

    $conn->query("INSERT INTO materials (MaterialName, Category, Quantity) 
                  VALUES ('$name', '$category', '$quantity')");
}

// DELETE MATERIAL
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM materials WHERE MaterialID = $id");
}

// FETCH MATERIALS
$result = $conn->query("SELECT * FROM materials ORDER BY MaterialID DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Materials Catalog</title>
    <style>
     body {
    font-family: Arial;
    background: #f0f2f5;
    padding: 20px;
}

.container {
    background: white;
    padding: 20px;
    border-radius: 10px;
}

/* FORM */
form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

input {
    padding: 8px;
    width: 200px;
}

button {
    padding: 8px 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #f8f9fa;
    font-weight: bold;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

/* LEFT ALIGN TEXT COLUMNS */
th:nth-child(2), td:nth-child(2),
th:nth-child(3), td:nth-child(3) {
    text-align: left;
}

/* CENTER OTHER COLUMNS */
th:nth-child(1), td:nth-child(1),
th:nth-child(4), td:nth-child(4),
th:nth-child(5), td:nth-child(5),
th:nth-child(6), td:nth-child(6) {
    text-align: center;
}

/* ROW HOVER */
tr:hover {
    background: #f1f1f1;
}

/* DELETE BUTTON */
.delete {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
}
    </style>
</head>
<body>


<div class="container">
    <a href="admindashboard.php" style="text-decoration: none; color: blue;">← Back to Dashboard</a>
    <h1>🧰 Materials Management</h1>
    <h3>Add New Material</h3>

    <!-- ADD FORM -->
    <form method="POST">
        <input type="text" name="name" placeholder="Material Name" required>
        <input type="text" name="category" placeholder="Category">
        <input type="number" name="quantity" placeholder="Quantity" required>
        <button type="submit" name="add">Add Material</button>
    </form>

    <!-- TABLE -->
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['MaterialID']; ?></td>
            <td><?= $row['MaterialName']; ?></td>
            <td><?= $row['Category']; ?></td>
            <td><?= $row['Quantity']; ?></td>
            <td><?= $row['Quantity'] > 0 ? 'Available' : 'Unavailable'; ?></td>
            <td>
                <a href="?delete=<?= $row['MaterialID']; ?>" 
                   class="delete"
                   onclick="return confirm('Delete this material?')">
                   Delete
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
