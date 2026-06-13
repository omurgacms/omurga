<?php

if (!function_exists('omurga_migration_registry')) {
    function omurga_migration_registry(): array {
        return $GLOBALS['omurga_migration_registry'] ?? [];
    }
}

if (!function_exists('omurga_migration_register')) {
    function omurga_migration_register(string $key, string $version, string $description = ''): void {
        $key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', trim($key));
        if ($key === '') return;
        $GLOBALS['omurga_migration_registry'][$key] = [
            'migration_key' => $key,
            'version' => $version,
            'description' => $description,
        ];
    }
}

if (!function_exists('omurga_migration_ensure_table')) {
    function omurga_migration_ensure_table(): bool {
        if (!function_exists('omurga_is_installed') || !omurga_is_installed()) return false;
        static $done = false;
        if ($done) return true;
        try {
            $t = table_name('migrations');
            db()->exec("CREATE TABLE IF NOT EXISTS $t (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration_key VARCHAR(190) NOT NULL,
                version VARCHAR(40) NOT NULL,
                description VARCHAR(255) NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                error_message TEXT NULL,
                executed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY migration_key (migration_key),
                INDEX(status),
                INDEX(version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $done = true;
            return true;
        } catch (Throwable $e) {
            if (function_exists('omurga_write_error')) omurga_write_error($e);
            return false;
        }
    }
}

if (!function_exists('omurga_migration_is_applied')) {
    function omurga_migration_is_applied(string $key): bool {
        if (!omurga_migration_ensure_table()) return false;
        try {
            $t = table_name('migrations');
            $st = db()->prepare("SELECT status FROM $t WHERE migration_key=? LIMIT 1");
            $st->execute([$key]);
            $row = $st->fetch();
            return $row && ($row['status'] ?? '') === 'applied';
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('omurga_migration_mark')) {
    function omurga_migration_mark(string $key, string $version, string $description, string $status, ?string $error = null): void {
        if (!omurga_migration_ensure_table()) return;
        try {
            $t = table_name('migrations');
            $sql = "INSERT INTO $t (migration_key, version, description, status, error_message, executed_at, updated_at)
                    VALUES (?,?,?,?,?,NOW(),NOW())
                    ON DUPLICATE KEY UPDATE version=VALUES(version), description=VALUES(description), status=VALUES(status), error_message=VALUES(error_message), executed_at=VALUES(executed_at), updated_at=NOW()";
            db()->prepare($sql)->execute([$key, $version, mb_substr($description, 0, 255), $status, $error]);
        } catch (Throwable $e) {
            if (function_exists('omurga_write_error')) omurga_write_error($e);
        }
    }
}

if (!function_exists('omurga_migration_run')) {
    function omurga_migration_run(string $key, string $version, string $description, callable $callback): bool {
        $key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', trim($key));
        if ($key === '') return false;
        omurga_migration_register($key, $version, $description);
        if (omurga_migration_is_applied($key)) return true;
        omurga_migration_mark($key, $version, $description, 'running');
        try {
            $callback();
            omurga_migration_mark($key, $version, $description, 'applied');
            return true;
        } catch (Throwable $e) {
            omurga_migration_mark($key, $version, $description, 'failed', $e->getMessage());
            if (function_exists('omurga_write_error')) omurga_write_error($e);
            return false;
        }
    }
}

if (!function_exists('omurga_migrations_rows')) {
    function omurga_migrations_rows(): array {
        if (!omurga_migration_ensure_table()) return [];
        try {
            $t = table_name('migrations');
            return db()->query("SELECT * FROM $t ORDER BY id ASC")->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('omurga_migrations_all_applied')) {
    function omurga_migrations_all_applied(): bool {
        if (!omurga_migration_ensure_table()) return false;
        try {
            $t = table_name('migrations');
            $st = db()->query("SELECT COUNT(*) c FROM $t WHERE status <> 'applied'");
            return ((int)($st->fetch()['c'] ?? 0)) === 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('omurga_migrations_status')) {
    function omurga_migrations_status(): array {
        $known = omurga_migration_registry();
        $rows = [];
        foreach (omurga_migrations_rows() as $row) {
            $rows[$row['migration_key']] = $row;
        }
        foreach ($known as $key => $meta) {
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'migration_key' => $key,
                    'version' => $meta['version'] ?? '',
                    'description' => $meta['description'] ?? '',
                    'status' => 'pending',
                    'error_message' => null,
                    'executed_at' => null,
                ];
            }
        }
        uasort($rows, fn($a,$b) => strcmp((string)$a['migration_key'], (string)$b['migration_key']));
        return array_values($rows);
    }
}
