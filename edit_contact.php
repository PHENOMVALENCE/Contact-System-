<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = "";
$success_message = "";
$contact = null;

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$contact_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch contact details
try {
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = :contact_id AND user_id = :user_id");
    $stmt->bindParam(':contact_id', $contact_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        header("Location: dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phonenumber = $_POST['phonenumber'];
    $address = $_POST['address'];
    $notes = $_POST['notes'];
    
    try {
        $stmt = $conn->prepare("UPDATE contacts SET fullname = :fullname, email = :email, phonenumber = :phonenumber, address = :address, notes = :notes WHERE id = :contact_id AND user_id = :user_id");
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phonenumber', $phonenumber);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':contact_id', $contact_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $success_message = "Contact updated successfully!";
        
        // Refresh contact data
        $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = :contact_id AND user_id = :user_id");
        $stmt->bindParam(':contact_id', $contact_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Contact</title>
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
                <li><a href="create.php">Add Contact</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li><a href="shared_contacts.php">Shared Contacts</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Edit Contact</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($contact): ?>
                <form method="POST" action="edit_contact.php?id=<?php echo $contact_id; ?>">
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo $contact['fullname']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $contact['email']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phonenumber">Phone Number:</label>
                        <input type="text" id="phonenumber" name="phonenumber" value="<?php echo $contact['phonenumber']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="3"><?php echo $contact['address']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo $contact['notes']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Update Contact</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>