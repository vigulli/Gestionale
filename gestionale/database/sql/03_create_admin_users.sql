-- ============================================================
--  CREA UTENTI ADMIN
--  Esegui questo in CIASCUNO dei 3 database tenant
--  (gestionale_ilab, gestionale_nipotetech, gestionale_dtflab)
--
--  IMPORTANTE: sostituisci la password con un hash bcrypt reale.
--  Per generare un hash bcrypt della tua password vai su:
--  https://bcrypt-generator.com  (12 rounds)
--  oppure usa PHP: php -r "echo password_hash('TuaPassword123', PASSWORD_BCRYPT);"
-- ============================================================

-- Sostituisci il valore di `password` con il tuo hash bcrypt
INSERT INTO `users` (`name`, `email`, `password`, `created_at`, `updated_at`) VALUES
('Admin', 'admin@miodominio.ch', '$2y$12$SOSTITUISCI_CON_HASH_BCRYPT', NOW(), NOW());

-- Esempio hash per la password "Gestionale2024!"
-- $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- (questo è l'hash di 'password' — NON usare in produzione!)
