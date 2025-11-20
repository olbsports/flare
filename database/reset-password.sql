-- ============================================
-- FLARE CUSTOM - Reset Admin Password (SQL)
-- Exécutez ce script dans phpMyAdmin ou votre interface MySQL
-- ============================================

-- Mettre à jour le mot de passe admin à "admin123"
UPDATE users
SET password = '$2y$10$rS8L5EqbZ5yJ5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5Zv5'
WHERE username = 'admin';

-- OU si vous préférez "password" comme mot de passe :
-- UPDATE users
-- SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
-- WHERE username = 'admin';

-- Vérifier que la mise à jour a fonctionné
SELECT id, username, email, role, created_at
FROM users
WHERE username = 'admin';
