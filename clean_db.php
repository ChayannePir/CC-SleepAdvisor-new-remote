<?php
try {
    // Connexion à MySQL
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');

    // Sélectionner la BD
    $pdo->exec('USE hotel');

    // Lister les tables
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "✓ Aucune table trouvée, la BD est vide.\n";
    } else {
        echo "Tables existantes:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }

        // Désactiver les foreign keys et supprimer les tables
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE `$table`");
            echo "✓ Table '$table' supprimée.\n";
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>

