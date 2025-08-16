<?php
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['term']) || strlen($_GET['term']) < 2) {
        echo json_encode([]);
        exit;
    }

    $term = $_GET['term'] . '%'; // Search for airports starting with the term
    
    $stmt = $conn->prepare("
        SELECT 
            code as value,
            CONCAT(city, ' (', code, ') - ', name) as label
        FROM 
            airports 
        WHERE 
            city ILIKE :term OR 
            code ILIKE :term OR 
            name ILIKE :term
        ORDER BY 
            city ASC
        LIMIT 10
    ");
    
    $stmt->bindParam(':term', $term);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]);
}
?>