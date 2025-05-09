<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['shared_id'])) {
    header("Location: shared_contacts.php");
    exit();
}

$shared_id = $_GET['shared_id'];
$user_id = $_SESSION['user_id'];

try {
    // Get shared contact details
    $stmt = $conn->prepare("
        SELECT sc.contact_id, c.fullname, c.email, c.phonenumber, c.address, c.notes
        FROM shared_contacts sc
        JOIN contacts c ON sc.contact_id = c.id
        WHERE sc.id = :shared_id AND sc.receiver_id = :user_id AND sc.status = 'pending'
    ");
    $stmt->bindParam(':shared_id', $shared_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        // Begin transaction
        $conn->beginTransaction();
        
        // Add contact to user's contacts
        $stmt = $conn->prepare("
            INSERT INTO contacts (user_id, fullname, email, phonenumber, address, notes)
            VALUES (:user_id, :fullname, :email, :phonenumber, :address, :notes)
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':fullname', $contact['fullname']);
        $stmt->bindParam(':email', $contact['email']);
        $stmt->bindParam(':phonenumber', $contact['phonenumber']);
        $stmt->bindParam(':address', $contact['address']);
        $stmt->bindParam(':notes', $contact['notes']);
        $stmt->execute();
        
        // Update shared contact status
        $stmt = $conn->prepare("
            UPDATE shared_contacts
            SET status = 'accepted'
            WHERE id = :shared_id AND receiver_id = :user_id
        ");
        $stmt->bindParam(':shared_id', $shared_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header("Location: shared_contacts.php?accepted=true");
        exit();
    } else {
        header("Location: shared_contacts.php?error=not_found");
        exit();
    }
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    header("Location: shared_contacts.php?error=database");
    exit();
}
?>