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
    // Update shared contact status
    $stmt = $conn->prepare("
        UPDATE shared_contacts
        SET status = 'rejected'
        WHERE id = :shared_id AND receiver_id = :user_id AND status = 'pending'
    ");
    $stmt->bindParam(':shared_id', $shared_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header("Location: shared_contacts.php?rejected=true");
    } else {
        header("Location: shared_contacts.php?error=not_found");
    }
    exit();
} catch(PDOException $e) {
    header("Location: shared_contacts.php?error=database");
    exit();
}
?>