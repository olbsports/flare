<?php
/**
 * Script de migration - Ajoute les colonnes manquantes à la table products
 * Accéder à: /admin/fix-columns.php
 */

require_once __DIR__ . '/../config/database.php';

echo "<html><head><title>Fix Columns</title><style>
body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
.success { color: #22c55e; }
.error { color: #ef4444; }
.info { color: #3b82f6; }
.warning { color: #f59e0b; }
pre { background: #0f172a; padding: 15px; border-radius: 8px; overflow-x: auto; }
</style></head><body>";
echo "<h1>🔧 Fix Database Columns</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Connexion BDD OK</p>";

    // Colonnes à ajouter avec leur définition SQL
    $columns = [
        'stock_status' => "VARCHAR(50) DEFAULT 'in_stock'",
        'slug' => 'VARCHAR(255) DEFAULT NULL',
        'meta_title' => 'VARCHAR(255) DEFAULT NULL',
        'meta_description' => 'TEXT DEFAULT NULL',
        'tab_description' => 'LONGTEXT DEFAULT NULL',
        'tab_specifications' => 'LONGTEXT DEFAULT NULL',
        'tab_sizes' => 'LONGTEXT DEFAULT NULL',
        'tab_templates' => 'LONGTEXT DEFAULT NULL',
        'tab_faq' => 'LONGTEXT DEFAULT NULL',
        'configurator_config' => 'LONGTEXT DEFAULT NULL',
        'size_chart_id' => 'INT DEFAULT NULL',
        'featured' => 'BOOLEAN DEFAULT FALSE',
        'is_new' => 'BOOLEAN DEFAULT FALSE',
        'on_sale' => 'BOOLEAN DEFAULT FALSE',
        'sort_order' => 'INT DEFAULT 0',
        'related_products' => 'JSON',
        'etiquettes' => 'TEXT DEFAULT NULL',
        'prix_enfant_1' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_5' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_10' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_20' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_50' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_100' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_250' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_500' => 'DECIMAL(10,2) DEFAULT NULL',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ];

    echo "<h2>📋 Vérification des colonnes</h2>";
    echo "<pre>";

    // Récupérer les colonnes existantes
    $stmt = $pdo->query("DESCRIBE products");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colonnes existantes: " . count($existingColumns) . "\n";
    echo implode(", ", $existingColumns) . "\n\n";

    $added = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($columns as $col => $definition) {
        if (in_array($col, $existingColumns)) {
            echo "<span class='info'>⏭️ $col - existe déjà</span>\n";
            $skipped++;
        } else {
            try {
                $sql = "ALTER TABLE products ADD COLUMN $col $definition";
                $pdo->exec($sql);
                echo "<span class='success'>✅ $col - AJOUTÉE</span>\n";
                $added++;
            } catch (PDOException $e) {
                echo "<span class='error'>❌ $col - ERREUR: " . $e->getMessage() . "</span>\n";
                $errors++;
            }
        }
    }

    echo "</pre>";

    echo "<h2>📊 Résumé</h2>";
    echo "<p class='success'>✅ Colonnes ajoutées: $added</p>";
    echo "<p class='info'>⏭️ Colonnes existantes: $skipped</p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Erreurs: $errors</p>";
    }

    // Créer la table product_photos si elle n'existe pas
    echo "<h2>📸 Table product_photos</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            url VARCHAR(500) NOT NULL,
            filename VARCHAR(255),
            alt_text VARCHAR(255),
            title VARCHAR(255),
            type ENUM('main', 'gallery', 'thumbnail', 'hover', 'zoom') DEFAULT 'gallery',
            ordre INT DEFAULT 0,
            width INT,
            height INT,
            size_bytes INT,
            mime_type VARCHAR(100),
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            INDEX idx_type (type),
            INDEX idx_ordre (ordre),
            INDEX idx_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='success'>✅ Table product_photos OK</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    }

    // Créer la table template_products si elle n'existe pas
    echo "<h2>🎨 Table template_products</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS template_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_template_product (template_id, product_id),
            INDEX idx_template (template_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='success'>✅ Table template_products OK</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    }

    echo "<hr>";
    echo "<h2 class='success'>🎉 Migration terminée!</h2>";
    echo "<p><a href='/admin/admin.php' style='color: #3b82f6;'>← Retour à l'admin</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
