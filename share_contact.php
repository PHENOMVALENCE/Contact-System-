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
$contact_id = isset($_GET['contact_id']) ? $_GET['contact_id'] : null;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact_id = $_POST['contact_id'];
    $friend_id = $_POST['friend_id'];
    
    try {
        // Check if they are friends
        $stmt_friend = $conn->prepare("SELECT 1 FROM friend_requests 
                                      WHERE (sender_id = :user_id AND receiver_id = :friend_id AND status = 'accepted')
                                      OR (sender_id = :friend_id AND receiver_id = :user_id AND status = 'accepted')");
        $stmt_friend->bindParam(':user_id', $user_id);
        $stmt_friend->bindParam(':friend_id', $friend_id);
        $stmt_friend->execute();
        $is_friend = $stmt_friend->fetchColumn();
        
        if ($is_friend) {
            // Check if contact belongs to user
            $stmt_contact = $conn->prepare("SELECT 1 FROM contacts WHERE id = :contact_id AND user_id = :user_id");
            $stmt_contact->bindParam(':contact_id', $contact_id);
            $stmt_contact->bindParam(':user_id', $user_id);
            $stmt_contact->execute();
            $is_owner = $stmt_contact->fetchColumn();
            
            if ($is_owner) {
                // Check if already shared
                $stmt_check = $conn->prepare("SELECT 1 FROM shared_contacts 
                                            WHERE contact_id = :contact_id AND sender_id = :user_id AND receiver_id = :friend_id");
                $stmt_check->bindParam(':contact_id', $contact_id);
                $stmt_check->bindParam(':user_id', $user_id);
                $stmt_check->bindParam(':friend_id', $friend_id);
                $stmt_check->execute();
                $already_shared = $stmt_check->fetchColumn();
                
                if (!$already_shared) {
                    // Share the contact
                    $stmt_share = $conn->prepare("INSERT INTO shared_contacts (contact_id, sender_id, receiver_id, status) 
                                                VALUES (:contact_id, :user_id, :friend_id, 'pending')");
                    $stmt_share->bindParam(':contact_id', $contact_id);
                    $stmt_share->bindParam(':user_id', $user_id);
                    $stmt_share->bindParam(':friend_id', $friend_id);
                    $stmt_share->execute();
                    
                    $success_message = "Contact shared successfully!";
                } else {
                    $error_message = "You have already shared this contact with this friend.";
                }
            } else {
                $error_message = "You don't own this contact.";
            }
        } else {
            $error_message = "You can only share contacts with your friends.";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch user's contacts
try {
    $stmt_contacts = $conn->prepare("SELECT id, fullname FROM contacts WHERE user_id = :user_id ORDER BY fullname ASC");
    $stmt_contacts->bindParam(':user_id', $user_id);
    $stmt_contacts->execute();
    $contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching contacts: " . $e->getMessage();
}

// Fetch user's friends
try {
    $stmt_friends = $conn->prepare("
        SELECT u.id, u.fullname FROM users u
        JOIN friend_requests fr ON (fr.sender_id = u.id OR fr.receiver_id = u.id)
        WHERE ((fr.sender_id = :user_id AND fr.receiver_id = u.id) 
            OR (fr.receiver_id = :user_id AND fr.sender_id = u.id))
            AND fr.status = 'accepted'
    ");
    $stmt_friends->bindParam(':user_id', $user_id);
    $stmt_friends->execute();
    $friends = $stmt_friends->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching friends: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Share Contact</title>
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
            <h2>Share Contact</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success">
                    <?php echo $success_message; ?>
                    <p><a href="dashboard.php">Return to Dashboard</a></p>
                </div>
            <?php else: ?>
                <?php if (count($contacts) > 0 && count($friends) > 0): ?>
                    <form method="POST" action="share_contact.php">
                        <div class="form-group">
                            <label for="contact_id">Select Contact:</label>
                            <select id="contact_id" name="contact_id" required>
                                <?php foreach ($contacts as $contact): ?>
                                    <option value="<?php echo $contact['id']; ?>" <?php echo ($contact_id == $contact['id']) ? 'selected' : ''; ?>>
                                        <?php echo $contact['fullname']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="friend_id">Share with Friend:</label>
                            <select id="friend_id" name="friend_id" required>
                                <?php foreach ($friends as $friend): ?>
                                    <option value="<?php echo $friend['id']; ?>">
                                        <?php echo $friend['fullname']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Share Contact</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                <?php elseif (count($contacts) == 0): ?>
                    <div class="no-data">
                        <p>You don't have any contacts to share.</p>
                        <a href="create.php" class="btn">Add a Contact</a>
                    </div>
                <?php elseif (count($friends) == 0): ?>
                    <div class="no-data">
                        <p>You don't have any friends to share contacts with.</p>
                        <a href="friends.php" class="btn">Find Friends</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>