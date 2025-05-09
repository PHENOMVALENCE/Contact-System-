<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phonenumber = $_POST['phonenumber'];
    $address = $_POST['address'];
    $notes = $_POST['notes'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO contacts (user_id, fullname, email, phonenumber, address, notes) VALUES (:user_id, :fullname, :email, :phonenumber, :address, :notes)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phonenumber', $phonenumber);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
        
        $success_message = "Contact added successfully!";
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Contact</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Contact Management System</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['fullname']; ?></span>
                <a href="dashboard.php?logout=true" class="logout-btn">Logout</a>
            </div>
        </header>
        
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="create.php" class="active">Add Contact</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li><a href="shared_contacts.php">Shared Contacts</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Add New Contact</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success">
                    <?php echo $success_message; ?>
                    <p><a href="dashboard.php">Return to Dashboard</a></p>
                </div>
            <?php else: ?>
                <form method="POST" action="create.php">
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phonenumber">Phone Number:</label>
                        <input type="text" id="phonenumber" name="phonenumber">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Save Contact</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>