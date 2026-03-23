<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign-up</title>
    <style>
        /* Basic styling to keep things neat */
        body { font-family: sans-serif; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 100px; }
    </style>
</head>
<body>

<h1>User Sign-up</h1>
<a href="index.php"><button>Home</button></a>
<br><br>

<form method="POST" id="signupForm">
    <div class="form-group">
        <label>Name:</label>
        <input type="text" name="fname" required>
    </div>

    <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required>
    </div>

    <div class="form-group">
        <label>Role:</label>
        <select name="role" id="roleSelect" required>
            <option value="">--Select Role--</option>
            <option value="Admin">Admin</option>
            <option value="Staff">Staff</option>
            <option value="Faculty">Faculty</option>
            <option value="Student">Student</option>
            <option value="Visitor">Visitor</option>
        </select>
    </div>

    <div class="form-group" id="course_section">
        <label>Course ID:</label>
        <input type="text" name="cID" id="cID_field" required>
    </div>

    <div class="form-group">
        <label for="password">Password: </label>
        <input type="password" name="password" id="pword_field" required>
    </div>

    <input type="checkbox" onclick="togglePassword()"> Show Password 
    <br><br>

    <a href="index.php">Already have an account?</a>
    <br><br>
        
    <input type="submit" name="signup" value="Sign-up">
</form>

<script>
// 1. Toggle Password Visibility
function togglePassword() {
    var x = document.getElementById("pword_field");
    x.type = (x.type === "password") ? "text" : "password";
}

// 2. Handle Role-based Course ID Visibility
document.getElementById('roleSelect').addEventListener('change', function() {
    var role = this.value;
    var courseSection = document.getElementById('course_section');
    var courseInput = document.getElementById('cID_field');

    // If Admin, Staff, or Guest is picked, hide the Course ID field
    if (role === "Admin" || role === "Staff" || role === "Visitor") {
        courseSection.style.display = "none";
        courseInput.value = "";       // Clear value so it doesn't submit old data
        courseInput.required = false;  // Remove 'required' so form can submit
    } else {
        courseSection.style.display = "block";
        courseInput.required = true;   // Re-enable 'required' for Student/Faculty
    }
});
</script>

<?php
include 'connect.php'; 

if (isset($_POST['signup'])) {
    // Sanitize basic inputs
    $name = mysqli_real_escape_string($conn, $_POST['fname']);   
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    $pword = $_POST['password']; 
    $role = $_POST['role'];

    // If cID was hidden/empty, set it to 0 in the database
    $cID = !empty($_POST['cID']) ? mysqli_real_escape_string($conn, $_POST['cID']) : 0;

    // Check if email already exists to prevent recurring emails
    $checkEmail = $conn->query("SELECT * FROM user WHERE Email='$email'");
    if ($checkEmail->num_rows > 0) {
        echo "<script>alert('Email already registered!'); window.history.back();</script>";
        exit();
    }

    // 1. Insert into main 'user' table
    $sql = "INSERT INTO user (Name, Email, Password, CourseID) 
            VALUES ('$name', '$email', '$pword', '$cID')";

    if ($conn->query($sql) === TRUE) {
        $generatedID = $conn->insert_id; // Get the auto-incremented RoleID

        // 2. Map the selection to the correct column in 'user_roles'
        $student = ($role == 'Student') ? $generatedID : 0;
        $admin   = ($role == 'Admin')   ? $generatedID : 0;
        $visitor  = ($role == 'Visitor')   ? $generatedID : 0;
        $staff   = ($role == 'Staff')   ? $generatedID : 0;
        $faculty = ($role == 'Faculty') ? $generatedID : 0;

        $roleSql = "INSERT INTO user_roles (RoleID, StudentID, AdminID, VisitorID, StaffID, FacultyID) 
                    VALUES ('$generatedID', '$student', '$admin', '$visitor', '$staff', '$faculty')";
        
        if ($conn->query($roleSql) === TRUE) {
            echo "<script>alert('Registered successfully! Your Login ID is $generatedID'); window.location='index.php';</script>";
        } else {
            echo "Error updating roles: " . $conn->error;
        }
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

</body>
</html>