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

// Process friend request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username = $_POST['username'];
    
    try {
        // Find user by username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $friend = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($friend) {
            $friend_id = $friend['id'];
            
            // Check if it's not the user themselves
            if ($friend_id != $user_id) {
                // Check if request already exists
                $stmt = $conn->prepare("
                    SELECT * FROM friend_requests 
                    WHERE (sender_id = :user_id AND receiver_id = :friend_id)
                    OR (sender_id = :friend_id AND receiver_id = :user_id)
                ");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':friend_id', $friend_id);
                $stmt->execute();
                $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existing_request) {
                    // Send friend request
                    $stmt = $conn->prepare("
                        INSERT INTO friend_requests (sender_id, receiver_id, status)
                        VALUES (:user_id, :friend_id, 'pending')
                    ");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':friend_id', $friend_id);
                    $stmt->execute();
                    
                    $success_message = "Friend request sent successfully!";
                } else {
                    if ($existing_request['status'] == 'pending') {
                        if ($existing_request['sender_id'] == $user_id) {
                            $error_message = "You already sent a friend request to this user.";
                        } else {
                            $error_message = "This user already sent you a friend request. Check your pending requests.";
                        }
                    } else if ($existing_request['status'] == 'accepted') {
                        $error_message = "You are already friends with this user.";
                    } else {
                        $error_message = "A previous request was rejected. Please try again later.";
                    }
                }
            } else {
                $error_message = "You cannot send a friend request to yourself.";
            }
        } else {
            $error_message = "User not found.";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle request actions (accept/reject)
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $request_id = $_GET['request_id'];
    
    try {
        // Verify the request belongs to the user
        $stmt = $conn->prepare("
            SELECT * FROM friend_requests 
            WHERE id = :request_id AND receiver_id = :user_id AND status = 'pending'
        ");
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            if ($action == 'accept') {
                // Accept friend request
                $stmt = $conn->prepare("
                    UPDATE friend_requests
                    SET status = 'accepted'
                    WHERE id = :request_id
                ");
                $stmt->bindParam(':request_id', $request_id);
                $stmt->execute();
                
                $success_message = "Friend request accepted!";
            } else if ($action == 'reject') {
                // Reject friend request
                $stmt = $conn->prepare("
                    UPDATE friend_requests
                    SET status = 'rejected'
                    WHERE id = :request_id
                ");
                $stmt->bindParam(':request_id', $request_id);
                $stmt->execute();
                
                $success_message = "Friend request rejected.";
            }
        } else {
            $error_message = "Invalid request.";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch pending friend requests
try {
    $stmt = $conn->prepare("
        SELECT fr.id, u.username, u.fullname
        FROM friend_requests fr
        JOIN users u ON fr.sender_id = u.id
        WHERE fr.receiver_id = :user_id AND fr.status = 'pending'
        ORDER BY fr.created_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching pending requests: " . $e->getMessage();
}

// Fetch friends list
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.fullname, fr.created_at as friendship_date
        FROM friend_requests fr
        JOIN users u ON (fr.sender_id = u.id OR fr.receiver_id = u.id)
        WHERE ((fr.sender_id = :user_id AND fr.receiver_id = u.id) 
            OR (fr.receiver_id = :user_id AND fr.sender_id = u.id))
            AND fr.status = 'accepted'
        ORDER BY u.fullname ASC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching friends: " . $e->getMessage();
}

// Fetch sent requests
try {
    $stmt = $conn->prepare("
        SELECT fr.id, u.username, u.fullname, fr.created_at
        FROM friend_requests fr
        JOIN users u ON fr.receiver_id = u.id
        WHERE fr.sender_id = :user_id AND fr.status = 'pending'
        ORDER BY fr.created_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $sent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching sent requests: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Friends</title>
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
                <li><a href="friends.php" class="active">Friends</a></li>
                <li><a href="shared_contacts.php">Shared Contacts</a></li>
            </ul>
        </nav>
        
        <main>
            <h2>Friends Management</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'friends-list')">My Friends (<?php echo count($friends); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'pending-requests')">Pending Requests (<?php echo count($pending_requests); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'sent-requests')">Sent Requests (<?php echo count($sent_requests); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'add-friend')">Add Friend</button>
            </div>
            
            <div id="friends-list" class="tab-content" style="display: block;">
                <h3>My Friends</h3>
                
                <?php if (count($friends) > 0): ?>
                    <div class="search-container">
                        <input type="text" id="friendSearchInput" placeholder="Search friends...">
                    </div>
                    
                    <table id="friendsTable">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Friends Since</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($friends as $friend): ?>
                                <tr>
                                    <td><?php echo $friend['fullname']; ?></td>
                                    <td><?php echo $friend['username']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($friend['friendship_date'])); ?></td>
                                    <td class="actions">
                                        <a href="share_contact.php?friend_id=<?php echo $friend['id']; ?>" class="btn-share">Share Contact</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>You don't have any friends yet.</p>
                        <p>Send a friend request to start connecting!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="pending-requests" class="tab-content">
                <h3>Pending Friend Requests</h3>
                
                <?php if (count($pending_requests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['fullname']; ?></td>
                                    <td><?php echo $request['username']; ?></td>
                                    <td class="actions">
                                        <a href="friends.php?action=accept&request_id=<?php echo $request['id']; ?>" class="btn-accept">Accept</a>
                                        <a href="friends.php?action=reject&request_id=<?php echo $request['id']; ?>" class="btn-reject">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No pending friend requests.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="sent-requests" class="tab-content">
                <h3>Sent Friend Requests</h3>
                
                <?php if (count($sent_requests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Date Sent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sent_requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['fullname']; ?></td>
                                    <td><?php echo $request['username']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                    <td><span class="status-pending">Pending</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No sent friend requests.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="add-friend" class="tab-content">
                <h3>Add a Friend</h3>
                
                <form method="POST" action="friends.php">
                    <div class="form-group">
                        <label for="username">Enter Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Send Friend Request</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabContent, tabButtons;
            
            // Hide all tab content
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
            }
            
            // Remove "active" class from all tab buttons
            tabButtons = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabButtons.length; i++) {
                tabButtons[i].className = tabButtons[i].className.replace(" active", "");
            }
            
            // Show the current tab and add "active" class to the button
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        
        // Search functionality for friends list
        document.getElementById('friendSearchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('friendsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const fullname = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                const username = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                
                if (fullname.includes(searchValue) || username.includes(searchValue)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>