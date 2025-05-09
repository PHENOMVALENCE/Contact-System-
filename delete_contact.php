<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$contact_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // First check if the contact belongs to the user
    $stmt = $conn->prepare("SELECT id FROM contacts WHERE id = :contact_id AND user_id = :user_id");
    $stmt->bindParam(':contact_id', $contact_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Delete the contact
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = :contact_id AND user_id = :user_id");
        $stmt->bindParam(':contact_id', $contact_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Redirect with success message
        header("Location: dashboard.php?deleted=true");
        exit();
    } else {
        // Contact not found or doesn't belong to user
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
} catch(PDOException $e) {
    // Redirect with error message
    header("Location: dashboard.php?error=database");
    exit();
}
?>