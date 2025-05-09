<?php
session_start();
include 'connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE user_id = :user_id ORDER BY fullname ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(); 
}

try {
    $stmt_user = $conn->prepare("SELECT fullname FROM users WHERE id = :user_id");
    $stmt_user->bindParam(':user_id', $user_id);
    $stmt_user->execute();
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_fullname = $user['fullname'];
    } else {
        $user_fullname = 'User';
    }
} catch(PDOException $e) {
    echo "Error fetching user: " . $e->getMessage();
    exit();
}

// Count pending shared contacts
try {
    $stmt_pending = $conn->prepare("SELECT COUNT(*) FROM shared_contacts WHERE receiver_id = :user_id AND status = 'pending'");
    $stmt_pending->bindParam(':user_id', $user_id);
    $stmt_pending->execute();
    $pending_count = $stmt_pending->fetchColumn();
} catch(PDOException $e) {
    $pending_count = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Contact Management System</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $user_fullname; ?></span>
                <a href="?logout=true" class="logout-btn">Logout</a>
            </div>
        </header>
        
        <nav>
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="create.php">Add Contact</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li>
                    <a href="shared_contacts.php">Shared Contacts 
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>
        
        <main>
            <div class="dashboard-header">
                <h2>My Contacts</h2>
                <a href="create.php" class="btn">Add New Contact</a>
            </div>
            
            <?php if (count($contacts) > 0): ?>
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search contacts...">
                </div>
                
                <table id="contactsTable">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?php echo $contact['fullname']; ?></td>
                                <td><?php echo $contact['email']; ?></td>
                                <td><?php echo $contact['phonenumber']; ?></td>
                                <td class="actions">
                                    <a href="view_contact.php?id=<?php echo $contact['id']; ?>" class="btn-view" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit_contact.php?id=<?php echo $contact['id']; ?>" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="share_contact.php?contact_id=<?php echo $contact['id']; ?>" class="btn-share" title="Share"><i class="fas fa-share-alt"></i></a>
                                    <a href="delete_contact.php?id=<?php echo $contact['id']; ?>" class="btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this contact?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-contacts">
                    <p>You don't have any contacts yet.</p>
                    <a href="create.php" class="btn">Add Your First Contact</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('contactsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const fullname = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                const email = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const phone = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                
                if (fullname.includes(searchValue) || email.includes(searchValue) || phone.includes(searchValue)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>