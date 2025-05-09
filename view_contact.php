<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = "";
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Contact</title>
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
            <div class="view-header">
                <h2>Contact Details</h2>
                <div class="view-actions">
                    <a href="edit_contact.php?id=<?php echo $contact_id; ?>" class="btn">Edit</a>
                    <a href="share_contact.php?contact_id=<?php echo $contact_id; ?>" class="btn">Share</a>
                    <a href="delete_contact.php?id=<?php echo $contact_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this contact?');">Delete</a>
                </div>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($contact): ?>
                <div class="contact-details">
                    <div class="detail-row">
                        <div class="detail-label">Full Name:</div>
                        <div class="detail-value"><?php echo $contact['fullname']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value"><?php echo $contact['email'] ? $contact['email'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Phone Number:</div>
                        <div class="detail-value"><?php echo $contact['phonenumber'] ? $contact['phonenumber'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value"><?php echo $contact['address'] ? $contact['address'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Notes:</div>
                        <div class="detail-value"><?php echo $contact['notes'] ? $contact['notes'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Created:</div>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($contact['created_at'])); ?></div>
                    </div>
                </div>
                
                <div class="back-link">
                    <a href="dashboard.php">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>