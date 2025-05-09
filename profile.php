<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Fetch user details
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching user details: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Check if email is already in use by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = "Email is already in use by another user.";
        } else {
            // Begin transaction
            $conn->beginTransaction();
            
            // Update user details
            if (!empty($current_password)) {
                // Verify current password
                if (password_verify($current_password, $user['password'])) {
                    // Check if new passwords match
                    if ($new_password === $confirm_password) {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET fullname = :fullname, email = :email, password = :password WHERE id = :user_id");
                        $stmt->bindParam(':fullname', $fullname);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                    } else {
                        throw new Exception("New passwords do not match.");
                    }
                } else {
                    throw new Exception("Current password is incorrect.");
                }
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET fullname = :fullname, email = :email WHERE id = :user_id");
                $stmt->bindParam(':fullname', $fullname);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Update session data
            $_SESSION['fullname'] = $fullname;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success_message = "Profile updated successfully!";
        }
    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
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
                <li><a href="profile.php" class="active">My Profile</a></li>
                <li><a href="create.php">Add Contact</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li><a href="shared_contacts.php">Shared Contacts</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>My Profile</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($user): ?>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" value="<?php echo $user['username']; ?>" disabled>
                        <small>Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo $user['fullname']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <h3>Change Password</h3>
                    <div class="password-section">
                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" id="current_password" name="current_password">
                            <small>Leave blank to keep current password</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Update Profile</button>
                    </div>
                </form>
                
                <div class="account-info">
                    <h3>Account Information</h3>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>