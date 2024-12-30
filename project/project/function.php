<?php

function fetchSingle($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function executeQuery($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}

function fetchAll($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchOne($conn, $query, $params = [])
{
    $stmt = $conn->prepare($query); // Prepare the SQL statement
    $stmt->execute($params);        // Execute with parameters
    return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch one row as an associative array
}
function redirectTo($location) {
    header("Location: $location");
    exit();
}
?>
