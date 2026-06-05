<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');

    // Drop DB
    $pdo->exec('DROP DATABASE IF EXISTS hotel');

    // Create DB
    $pdo->exec('CREATE DATABASE IF NOT EXISTS hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    echo "✓ Base de données réinitialisée avec succès!\n";
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>

