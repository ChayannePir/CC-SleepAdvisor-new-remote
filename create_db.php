<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "✓ Base de données 'hotel' créée avec succès!\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de la création de la base : " . $e->getMessage() . "\n";
    exit(1);
}
?>

