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

// Fetch pending shared contacts
try {
    $stmt_pending = $conn->prepare("
        SELECT sc.id AS shared_id, c.id AS contact_id, c.fullname, c.email, c.phonenumber, 
               u.fullname AS sender_name
        FROM shared_contacts sc
        JOIN contacts c ON sc.contact_id = c.id
        JOIN users u ON sc.sender_id = u.id
        WHERE sc.receiver_id = :user_id AND sc.status = 'pending'
        ORDER BY sc.created_at DESC
    ");
    $stmt_pending->bindParam(':user_id', $user_id);
    $stmt_pending->execute();
    $pending_contacts = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching pending contacts: " . $e->getMessage();
}

// Fetch accepted shared contacts
try {
    $stmt_accepted = $conn->prepare("
        SELECT sc.id AS shared_id, c.id AS contact_id, c.fullname, c.email, c.phonenumber, 
               u.fullname AS sender_name, sc.created_at
        FROM shared_contacts sc
        JOIN contacts c ON sc.contact_id = c.id
        JOIN users u ON sc.sender_id = u.id
        WHERE sc.receiver_id = :user_id AND sc.status = 'accepted'
        ORDER BY sc.created_at DESC
    ");
    $stmt_accepted->bindParam(':user_id', $user_id);
    $stmt_accepted->execute();
    $accepted_contacts = $stmt_accepted->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching accepted contacts: " . $e->getMessage();
}

// Fetch contacts you've shared with others
try {
    $stmt_shared_by_me = $conn->prepare("
        SELECT sc.id AS shared_id, c.fullname, u.fullname AS receiver_name, 
               sc.status, sc.created_at
        FROM shared_contacts sc
        JOIN contacts c ON sc.contact_id = c.id
        JOIN users u ON sc.receiver_id = u.id
        WHERE sc.sender_id = :user_id
        ORDER BY sc.created_at DESC
    ");
    $stmt_shared_by_me->bindParam(':user_id', $user_id);
    $stmt_shared_by_me->execute();
    $shared_by_me = $stmt_shared_by_me->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching shared contacts: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shared Contacts</title>
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
                <li><a href="shared_contacts.php" class="active">Shared Contacts</a></li>
            </ul>
        </nav>
        
        <main>
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'pending')">Pending (<?php echo count($pending_contacts); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'accepted')">Accepted (<?php echo count($accepted_contacts); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'shared-by-me')">Shared by Me (<?php echo count($shared_by_me); ?>)</button>
            </div>
            
            <div id="pending" class="tab-content" style="display: block;">
                <h2>Pending Shared Contacts</h2>
                
                <?php if (count($pending_contacts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Contact Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Shared By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_contacts as $contact): ?>
                                <tr>
                                    <td><?php echo $contact['fullname']; ?></td>
                                    <td><?php echo $contact['email']; ?></td>
                                    <td><?php echo $contact['phonenumber']; ?></td>
                                    <td><?php echo $contact['sender_name']; ?></td>
                                    <td class="actions">
                                        <a href="accept_contact.php?shared_id=<?php echo $contact['shared_id']; ?>" class="btn-accept">Accept</a>
                                        <a href="reject_contact.php?shared_id=<?php echo $contact['shared_id']; ?>" class="btn-reject">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No pending shared contacts.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="accepted" class="tab-content">
                <h2>Accepted Shared Contacts</h2>
                
                <?php if (count($accepted_contacts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Contact Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Shared By</th>
                                <th>Date Accepted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accepted_contacts as $contact): ?>
                                <tr>
                                    <td><?php echo $contact['fullname']; ?></td>
                                    <td><?php echo $contact['email']; ?></td>
                                    <td><?php echo $contact['phonenumber']; ?></td>
                                    <td><?php echo $contact['sender_name']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($contact['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No accepted shared contacts.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="shared-by-me" class="tab-content">
                <h2>Contacts Shared by Me</h2>
                
                <?php if (count($shared_by_me) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Contact Name</th>
                                <th>Shared With</th>
                                <th>Status</th>
                                <th>Date Shared</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shared_by_me as $contact): ?>
                                <tr>
                                    <td><?php echo $contact['fullname']; ?></td>
                                    <td><?php echo $contact['receiver_name']; ?></td>
                                    <td>
                                        <?php if ($contact['status'] == 'pending'): ?>
                                            <span class="status-pending">Pending</span>
                                        <?php elseif ($contact['status'] == 'accepted'): ?>
                                            <span class="status-accepted">Accepted</span>
                                        <?php else: ?>
                                            <span class="status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($contact['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>You haven't shared any contacts yet.</p>
                        <a href="share_contact.php" class="btn">Share a Contact</a>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>