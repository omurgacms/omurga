<?php
if (!defined('OMURGA_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class OmurgaUpdater
{
    private const CACHE_TTL = 21600;
    private const DEFAULT_OWNER = 'omurgacms';
    private const DEFAULT_REPO = 'omurga';

    public static function currentVersion(): string
    {
        return defined('OMURGA_VERSION') ? (string)OMURGA_VERSION : '0.0.0';
    }

    public static function displayVersion(?string $version = null): string
    {
        $version = $version ?? self::currentVersion();
        $normalized = self::normalizeVersion($version);
        return preg_replace('/-beta$/i', ' Beta', $normalized) ?: $version;
    }

    public static function normalizeVersion(string $version): string
    {
        $v = strtolower(trim($version));
        $v = str_replace(['omurga cms', 'omurga-cms', 'clean-release', 'release'], '', $v);
        $v = preg_replace('/^v/', '', trim($v));
        if (preg_match('/(\d+(?:\.\d+){1,3}(?:[\s._-]*(?:alpha|beta|rc)\d*)?)/i', $v, $m)) {
            $v = strtolower($m[1]);
        }
        $v = str_replace(['_', ' '], '-', $v);
        $v = preg_replace('/\.+(alpha|beta|rc)/', '-$1', $v);
        return trim($v, '-');
    }

    public static function compareVersions(string $a, string $b): int
    {
        return version_compare(self::normalizeVersion($a), self::normalizeVersion($b));
    }

    public static function cachePath(): string
    {
        return OMURGA_ROOT . '/storage/cache/update-check.json';
    }

    public static function logPath(): string
    {
        return OMURGA_ROOT . '/storage/logs/update.log';
    }

    public static function maintenancePath(): string
    {
        return OMURGA_ROOT . '/storage/maintenance.lock';
    }

    public static function tmpDir(): string
    {
        $dir = OMURGA_ROOT . '/storage/tmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function backupsDir(): string
    {
        $dir = OMURGA_ROOT . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function updatesDir(): string
    {
        $dir = OMURGA_ROOT . '/storage/updates';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function writeLog(string $message): void
    {
        try {
            $dir = dirname(self::logPath());
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents(self::logPath(), '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND);
        } catch (Throwable $e) {
        }
    }

    public static function endpoint(): string
    {
        $custom = function_exists('setting') ? trim((string)setting('update_endpoint', '')) : '';
        if ($custom !== '') {
            return $custom;
        }
        $owner = function_exists('setting') ? trim((string)setting('update_github_owner', self::DEFAULT_OWNER)) : self::DEFAULT_OWNER;
        $repo = function_exists('setting') ? trim((string)setting('update_github_repo', self::DEFAULT_REPO)) : self::DEFAULT_REPO;
        $owner = preg_replace('/[^a-zA-Z0-9_.-]/', '', $owner) ?: self::DEFAULT_OWNER;
        $repo = preg_replace('/[^a-zA-Z0-9_.-]/', '', $repo) ?: self::DEFAULT_REPO;
        return 'https://api.github.com/repos/' . $owner . '/' . $repo . '/releases';
    }

    public static function cachedCheck(): ?array
    {
        $file = self::cachePath();
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    public static function check(bool $force = false): array
    {
        $cache = self::cachedCheck();
        if (!$force && $cache && !empty($cache['checked_at']) && time() - strtotime((string)$cache['checked_at']) < self::CACHE_TTL) {
            return $cache;
        }

        self::writeLog('Guncelleme kontrolu basladi.');
        $current = self::currentVersion();
        $result = [
            'checked_at' => date('c'),
            'current_version' => $current,
            'latest_version' => $current,
            'has_update' => false,
            'release_name' => '',
            'release_date' => '',
            'changelog' => '',
            'download_url' => '',
            'status' => 'ok',
            'message' => 'Yeni surum bulunamadi.',
        ];

        try {
            $payload = self::fetchJson(self::endpoint());
            $release = self::selectLatestRelease($payload);
            $tag = (string)($release['tag_name'] ?? $release['name'] ?? '');
            $latest = self::normalizeVersion($tag);
            if ($latest === '') {
                throw new RuntimeException('GitHub release surumu okunamadi.');
            }
            $asset = self::findCoreAsset($release['assets'] ?? [], $latest);
            $result['latest_version'] = $latest;
            $result['release_name'] = (string)($release['name'] ?? $tag);
            $result['release_date'] = (string)($release['published_at'] ?? '');
            $result['changelog'] = (string)($release['body'] ?? '');
            $result['download_url'] = $asset['browser_download_url'] ?? '';
            $result['has_update'] = self::compareVersions($latest, $current) > 0;
            $result['message'] = $result['has_update'] ? 'Yeni guncelleme bulundu.' : 'Sistem guncel.';
            if ($result['has_update'] && $result['download_url'] === '') {
                $result['status'] = 'warning';
                $result['message'] = 'Yeni surum var fakat Omurga cekirdek zip dosyasi bulunamadi.';
            }
            self::writeLog($result['message'] . ' Son surum: ' . $latest);
        } catch (Throwable $e) {
            $result['status'] = 'error';
            $result['message'] = 'Guncelleme kontrolu yapilamadi.';
            $result['error'] = $e->getMessage();
            self::writeLog('Guncelleme kontrolu hatasi: ' . $e->getMessage());
        }

        $dir = dirname(self::cachePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(self::cachePath(), json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $result;
    }

    private static function fetchJson(string $url): array
    {
        $body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'User-Agent: Omurga-CMS-Updater'],
            ]);
            $body = (string)curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($body === '' || $code >= 400) {
                throw new RuntimeException($err ?: 'GitHub API HTTP ' . $code);
            }
        } else {
            $ctx = stream_context_create(['http' => ['header' => "User-Agent: Omurga-CMS-Updater\r\nAccept: application/vnd.github+json\r\n", 'timeout' => 30]]);
            $body = (string)@file_get_contents($url, false, $ctx);
            if ($body === '') {
                throw new RuntimeException('GitHub API yanit vermedi.');
            }
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new RuntimeException('GitHub API JSON yaniti okunamadi.');
        }
        return $json;
    }

    private static function selectLatestRelease(array $payload): array
    {
        $releases = [];
        if (isset($payload['tag_name'])) {
            $releases = [$payload];
        } else {
            $releases = $payload;
        }
        $best = null;
        $bestVersion = '';
        foreach ($releases as $release) {
            if (!is_array($release) || !empty($release['draft'])) {
                continue;
            }
            $tag = (string)($release['tag_name'] ?? $release['name'] ?? '');
            $version = self::normalizeVersion($tag);
            if ($version === '') {
                continue;
            }
            if ($best === null || self::compareVersions($version, $bestVersion) > 0) {
                $best = $release;
                $bestVersion = $version;
            }
        }
        if ($best === null) {
            throw new RuntimeException('GitHub release listesinde uygun surum bulunamadi.');
        }
        return $best;
    }

    private static function findCoreAsset(array $assets, string $latestVersion): array
    {
        foreach ($assets as $asset) {
            $name = (string)($asset['name'] ?? '');
            if (preg_match('/^omurga-cms-v[0-9][0-9A-Za-z._-]*(?:-[0-9A-Za-z._-]+)*\.zip$/i', $name)) {
                $assetVersion = self::normalizeVersion($name);
                if ($assetVersion === '' || $assetVersion === self::normalizeVersion($latestVersion)) {
                    return $asset;
                }
            }
        }
        return [];
    }

    public static function downloadLatest(): string
    {
        $check = self::check(false);
        $url = (string)($check['download_url'] ?? '');
        if ($url === '') {
            throw new RuntimeException('Guncelleme paketinin indirme baglantisi yok. Once guncelleme kontrolu yapin.');
        }
        $name = basename(parse_url($url, PHP_URL_PATH) ?: 'omurga-update.zip');
        if (!preg_match('/^omurga-cms-v[0-9][0-9A-Za-z._-]*(?:-[0-9A-Za-z._-]+)*\.zip$/i', $name)) {
            throw new RuntimeException('Indirme dosyasi Omurga cekirdek zip standardina uymuyor.');
        }
        $target = self::updatesDir() . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) . '-' . date('YmdHis') . '.zip';
        self::writeLog('Guncelleme paketi indirme basladi: ' . $name);
        self::downloadFile($url, $target);
        self::writeLog('Guncelleme paketi indirildi ve beklemeye alindi: ' . basename($target));
        return $target;
    }

    private static function downloadFile(string $url, string $target): void
    {
        if (function_exists('curl_init')) {
            $fp = fopen($target, 'wb');
            if (!$fp) {
                throw new RuntimeException('Gecici dosya olusturulamadi.');
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => ['User-Agent: Omurga-CMS-Updater'],
            ]);
            $ok = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fp);
            if (!$ok || $code >= 400) {
                @unlink($target);
                throw new RuntimeException($err ?: 'Zip indirilemedi. HTTP ' . $code);
            }
        } else {
            $ctx = stream_context_create(['http' => ['header' => "User-Agent: Omurga-CMS-Updater\r\n", 'timeout' => 120]]);
            if (!@copy($url, $target, $ctx)) {
                throw new RuntimeException('Zip indirilemedi.');
            }
        }
        if (!is_file($target) || filesize($target) < 100) {
            @unlink($target);
            throw new RuntimeException('Indirilen zip bos veya bozuk.');
        }
    }

    public static function stageUploadedPackage(array $file): array
    {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Guncelleme zip dosyasi secilmedi.');
        }
        $name = basename((string)$file['name']);
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
            throw new RuntimeException('Sadece .zip guncelleme paketi yuklenebilir.');
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '-', pathinfo($name, PATHINFO_FILENAME)) . '-' . date('YmdHis') . '.zip';
        $target = self::updatesDir() . '/' . $safe;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Guncelleme paketi yuklenemedi.');
        }
        self::writeLog('Manuel paket yuklendi ve beklemeye alindi: ' . basename($target));
        return self::inspectPackage($target);
    }

    public static function inspectPackage(string $zipPath): array
    {
        $info = [
            'file' => basename($zipPath),
            'path' => $zipPath,
            'size' => is_file($zipPath) ? (int)filesize($zipPath) : 0,
            'date' => is_file($zipPath) ? date('Y-m-d H:i:s', filemtime($zipPath) ?: time()) : '',
            'version' => 'bilinmiyor',
            'valid' => false,
            'error' => '',
        ];
        try {
            if (!is_file($zipPath)) {
                throw new RuntimeException('Paket dosyasi bulunamadi.');
            }
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Zip bozuk veya acilamiyor.');
            }
            self::validateZipEntries($zip);
            $tmp = self::tmpDir() . '/inspect-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            @mkdir($tmp, 0775, true);
            if (!$zip->extractTo($tmp)) {
                $zip->close();
                throw new RuntimeException('Zip gecici klasore acilamadi.');
            }
            $zip->close();
            $root = self::detectRoot($tmp);
            self::validatePackageRoot($root);
            $version = self::packageVersion($root);
            self::validateExtractedPaths($root);
            $info['version'] = $version !== '' ? $version : 'bilinmiyor';
            $info['valid'] = $version !== '' && $version !== 'bilinmiyor';
            if (function_exists('omurga_rrmdir')) {
                omurga_rrmdir($tmp);
            }
        } catch (Throwable $e) {
            $info['error'] = $e->getMessage();
            if (isset($tmp) && is_dir($tmp) && function_exists('omurga_rrmdir')) {
                omurga_rrmdir($tmp);
            }
        }
        return $info;
    }

    public static function stagedPackages(int $limit = 20): array
    {
        $files = glob(self::updatesDir() . '/*.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $rows = [];
        foreach (array_slice($files, 0, max(1, $limit)) as $file) {
            $rows[] = self::inspectPackage($file);
        }
        return $rows;
    }

    public static function stagedPackagePath(string $file): string
    {
        $safe = basename($file);
        $path = self::updatesDir() . '/' . $safe;
        $real = realpath($path);
        $updates = realpath(self::updatesDir());
        if (!$real || !$updates || !str_starts_with(str_replace('\\', '/', $real), rtrim(str_replace('\\', '/', $updates), '/') . '/')) {
            throw new RuntimeException('Guncelleme paketi bulunamadi veya guvenli degil.');
        }
        return $real;
    }

    public static function applyStagedPackage(string $file): array
    {
        $path = self::stagedPackagePath($file);
        $result = self::applyPackage($path, 'staged');
        return $result;
    }

    public static function deleteStagedPackage(string $file): void
    {
        $path = self::stagedPackagePath($file);
        @unlink($path);
        self::writeLog('Bekleyen guncelleme paketi silindi: ' . basename($path));
    }

    public static function applyDownloadedLatest(): array
    {
        $zip = self::downloadLatest();
        return self::applyPackage($zip, 'automatic');
    }

    public static function applyPackage(string $zipPath, string $source = 'manual'): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive sunucuda aktif degil.');
        }
        if (!is_file($zipPath)) {
            throw new RuntimeException('Guncelleme paketi bulunamadi.');
        }

        self::enableMaintenance('Omurga CMS guncelleniyor.');
        $tmp = '';
        try {
            $validation = self::preparePackage($zipPath);
            $tmp = $validation['tmp'];
            self::createBackups($validation['version']);
            $copy = self::copyPackageFiles($validation['root']);
            $migrations = self::runMigrations($validation['root']);
            self::clearCache();
            if (function_exists('update_setting')) {
                update_setting('omurga_version', $validation['version']);
                update_setting('db_version', $validation['version']);
            }
            self::writeLog('Guncelleme basarili. Hedef surum: ' . $validation['version']);
            if (function_exists('log_activity')) {
                log_activity('system.update_apply', 'Guncelleme uygulandi: ' . basename($zipPath) . ' -> ' . $validation['version']);
            }
            return [
                'version' => $validation['version'],
                'version_warning' => $validation['version_warning'] ?? '',
                'copied' => $copy['copied'],
                'skipped' => $copy['skipped'],
                'migrations' => $migrations,
                'source' => $source,
            ];
        } catch (Throwable $e) {
            self::writeLog('Guncelleme hatasi: ' . $e->getMessage());
            if (function_exists('omurga_write_error')) {
                omurga_write_error($e);
            }
            throw $e;
        } finally {
            self::disableMaintenance();
            if ($tmp !== '' && is_dir($tmp) && function_exists('omurga_rrmdir')) {
                omurga_rrmdir($tmp);
            }
        }
    }

    private static function preparePackage(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Zip bozuk veya acilamiyor.');
        }
        self::validateZipEntries($zip);
        $tmp = self::tmpDir() . '/update-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        @mkdir($tmp, 0775, true);
        if (!$zip->extractTo($tmp)) {
            $zip->close();
            throw new RuntimeException('Zip gecici klasore acilamadi.');
        }
        $zip->close();

        $root = self::detectRoot($tmp);
        self::validatePackageRoot($root);
        $version = self::packageVersion($root);
        if ($version === '' || $version === 'bilinmiyor') {
            throw new RuntimeException('Paket surumu okunamadi.');
        }
        $versionCompare = self::compareVersions($version, self::currentVersion());
        $versionWarning = '';
        if ($versionCompare < 0) {
            throw new RuntimeException('Surum dusurme engellendi. Paket surumu: ' . $version . ', mevcut surum: ' . self::currentVersion() . '.');
        } elseif ($versionCompare === 0) {
            $devMode = function_exists('omurga_developer_mode_enabled') && omurga_developer_mode_enabled();
            if (!$devMode) {
                throw new RuntimeException('Ayni surum kurulumu production modda engellendi. Paket surumu: ' . $version . '.');
            }
            $versionWarning = 'Paket mevcut surumle ayni. Yalniz gelistirici modunda uyarili yeniden kurulum yapildi. Paket surumu: ' . $version;
            self::writeLog('UYARI: ' . $versionWarning);
        }
        self::validateExtractedPaths($root);
        self::writeLog('Zip dogrulandi. Paket surumu: ' . $version);
        return ['tmp' => $tmp, 'root' => $root, 'version' => $version, 'version_warning' => $versionWarning];
    }

    private static function validateZipEntries(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            $norm = str_replace('\\', '/', trim($name));
            if ($norm === '' || str_starts_with($norm, '/') || preg_match('#^[a-zA-Z]:/#', $norm)) {
                throw new RuntimeException('Zip icinde guvensiz dosya yolu var: ' . $name);
            }
            foreach (explode('/', $norm) as $part) {
                if ($part === '..') {
                    throw new RuntimeException('Zip icinde path traversal var: ' . $name);
                }
            }
            if (method_exists($zip, 'getExternalAttributesIndex')) {
                $opsys = 0;
                $attr = 0;
                if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                    $mode = ($attr >> 16) & 0170000;
                    if ($mode === 0120000) {
                        throw new RuntimeException('Zip icinde symlink var: ' . $name);
                    }
                }
            }
            $ext = strtolower(pathinfo($norm, PATHINFO_EXTENSION));
            if (in_array($ext, ['exe', 'bat', 'cmd', 'sh', 'ps1', 'phar'], true)) {
                throw new RuntimeException('Zip icinde calistirilabilir riskli dosya var: ' . $name);
            }
        }
    }

    private static function detectRoot(string $tmp): string
    {
        if (is_file($tmp . '/bootstrap.php')) {
            return $tmp;
        }
        foreach ((glob($tmp . '/*') ?: []) as $dir) {
            if (is_dir($dir) && is_file($dir . '/bootstrap.php')) {
                return $dir;
            }
        }
        throw new RuntimeException('Omurga paketi dogrulanamadi: bootstrap.php yok.');
    }

    private static function validatePackageRoot(string $root): void
    {
        $required = ['bootstrap.php', 'admin', 'core'];
        foreach ($required as $rel) {
            if (!file_exists($root . '/' . $rel)) {
                throw new RuntimeException('Omurga paketi eksik: ' . $rel);
            }
        }
    }

    private static function packageVersion(string $root): string
    {
        $file = $root . '/bootstrap.php';
        $txt = is_file($file) ? (string)file_get_contents($file) : '';
        if (preg_match("/define\\(\\s*'OMURGA_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $txt, $m)) {
            return self::normalizeVersion($m[1]);
        }
        return '';
    }

    private static function validateExtractedPaths(string $root): void
    {
        $rootReal = realpath($root);
        if (!$rootReal) {
            throw new RuntimeException('Paket gecici klasoru okunamadi.');
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $path = $file->getPathname();
            $real = realpath($path);
            if (!$real || !str_starts_with(str_replace('\\', '/', $real), rtrim(str_replace('\\', '/', $rootReal), '/') . '/')) {
                throw new RuntimeException('Paket klasoru disina cikan dosya algilandi.');
            }
            if (!$file->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
            if ($rel === 'config.php' || $rel === '.env') {
                throw new RuntimeException('Guncelleme paketi config.php veya .env iceremez.');
            }
            if (preg_match('#^uploads/.+\.php$#i', $rel)) {
                throw new RuntimeException('Uploads icinde PHP dosyasi engellendi.');
            }
        }
    }

    private static function createBackups(string $targetVersion): void
    {
        $db = self::createDatabaseBackup($targetVersion);
        $files = self::createFileBackup($targetVersion);
        if (!is_file($db) || !is_file($files)) {
            throw new RuntimeException('Yedek alinamadi. Guncelleme baslatilmadi.');
        }
        self::writeLog('Yedek alindi: ' . basename($db) . ' / ' . basename($files));
    }

    private static function createDatabaseBackup(string $targetVersion): string
    {
        if (!function_exists('db') || !function_exists('table_name')) {
            throw new RuntimeException('Veritabani baglantisi hazir degil.');
        }
        $safeVersion = preg_replace('/[^0-9A-Za-z._-]/', '-', self::normalizeVersion($targetVersion));
        $file = self::backupsDir() . '/database-before-' . $safeVersion . '-' . date('YmdHis') . '.sql';
        $cfg = function_exists('omurga_config') ? omurga_config() : ['db' => ['prefix' => '']];
        $prefix = (string)($cfg['db']['prefix'] ?? '');
        $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $out = "-- Omurga CMS update database backup\n-- Target: {$targetVersion}\n-- Date: " . date('c') . "\n\n";
        foreach ($tables as $table) {
            if ($prefix !== '' && strpos((string)$table, $prefix) !== 0) {
                continue;
            }
            $create = db()->query('SHOW CREATE TABLE `' . str_replace('`', '``', (string)$table) . '`')->fetch();
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n" . ($create['Create Table'] ?? '') . ";\n\n";
            $rows = db()->query('SELECT * FROM `' . str_replace('`', '``', (string)$table) . '`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(fn($c) => '`' . str_replace('`', '``', (string)$c) . '`', array_keys($row));
                $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), array_values($row));
                $out .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ');' . "\n";
            }
            $out .= "\n";
        }
        if (file_put_contents($file, $out) === false) {
            throw new RuntimeException('Veritabani yedegi yazilamadi.');
        }
        return $file;
    }

    private static function createFileBackup(string $targetVersion): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Dosya yedegi icin ZipArchive gerekli.');
        }
        $safeVersion = preg_replace('/[^0-9A-Za-z._-]/', '-', self::normalizeVersion($targetVersion));
        $file = self::backupsDir() . '/omurga-backup-before-' . $safeVersion . '-' . date('YmdHis') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Dosya yedegi olusturulamadi.');
        }
        $files = [];
        $root = rtrim(OMURGA_ROOT, '/\\');
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
            if (self::skipBackupPath($rel)) {
                continue;
            }
            $zip->addFile($item->getPathname(), $rel);
            $files[] = $rel;
        }
        $zip->addFromString('update-metadata.json', json_encode([
            'created_at' => date('c'),
            'from_version' => self::currentVersion(),
            'target_version' => $targetVersion,
            'file_count' => count($files),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $zip->addFromString('core-files.json', json_encode($files, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $zip->close();
        if (!is_file($file) || filesize($file) <= 0) {
            throw new RuntimeException('Dosya yedegi yazilamadi.');
        }
        return $file;
    }

    private static function skipBackupPath(string $rel): bool
    {
        if (preg_match('#^(storage/cache|storage/logs|storage/tmp|storage/update-temp|storage/updates)/#', $rel)) {
            return true;
        }
        if (preg_match('#^(uploads|packages)/#', $rel)) {
            return true;
        }
        if (preg_match('#^storage/backups/#', $rel)) {
            return true;
        }
        return false;
    }

    private static function copyPackageFiles(string $srcRoot): array
    {
        $copied = 0;
        $skipped = 0;
        $srcRoot = rtrim($srcRoot, '/\\');
        $dstRoot = rtrim(OMURGA_ROOT, '/\\');
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($srcRoot) + 1));
            if (self::skipUpdateCopyPath($rel)) {
                $skipped++;
                continue;
            }
            $target = $dstRoot . '/' . $rel;
            $dir = dirname($target);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!copy($file->getPathname(), $target)) {
                throw new RuntimeException('Dosya kopyalanamadi: ' . $rel);
            }
            $copied++;
        }
        self::writeLog('Dosyalar kopyalandi. Kopyalanan: ' . $copied . ', atlanan: ' . $skipped);
        return ['copied' => $copied, 'skipped' => $skipped];
    }

    private static function skipUpdateCopyPath(string $rel): bool
    {
        $rel = str_replace('\\', '/', ltrim($rel, '/'));
        return !self::isCoreUpdatePath($rel);
    }

    private static function isCoreUpdatePath(string $rel): bool
    {
        $rel = str_replace('\\', '/', ltrim($rel, '/'));
        if ($rel === '' || str_contains($rel, '../') || str_starts_with($rel, '/')) {
            return false;
        }
        if (in_array($rel, ['bootstrap.php', 'index.php', '.htaccess', 'config.sample.php', 'README.md', 'CHANGELOG.md', 'RELEASE_NOTES.md', 'SURUM_NOTLARI.md', 'KURULUM.md', 'LICENSE'], true)) {
            return true;
        }
        foreach (['admin/', 'api/', 'assets/', 'core/', 'docs/'] as $prefix) {
            if (str_starts_with($rel, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private static function runMigrations(string $packageRoot): int
    {
        if (!function_exists('db') || !function_exists('table_name')) {
            return 0;
        }
        $table = table_name('migrations');
        db()->exec("CREATE TABLE IF NOT EXISTS {$table} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(190) NOT NULL UNIQUE, batch INT UNSIGNED NOT NULL DEFAULT 1, executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = db()->query("SELECT migration FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
        $done = array_flip(array_map('strval', $done));
        $files = glob($packageRoot . '/core/migrations/*.php') ?: [];
        sort($files);
        $batch = (int)(db()->query("SELECT COALESCE(MAX(batch),0)+1 FROM {$table}")->fetchColumn() ?: 1);
        $count = 0;
        foreach ($files as $file) {
            $name = basename($file);
            if (isset($done[$name])) {
                continue;
            }
            try {
                self::assertMigrationSafe($file);
                $result = require $file;
                if (is_callable($result)) {
                    $result(db());
                } elseif (is_string($result) && trim($result) !== '') {
                    db()->exec($result);
                } elseif (is_array($result)) {
                    foreach ($result as $sql) {
                        if (is_string($sql) && trim($sql) !== '') {
                            db()->exec($sql);
                        }
                    }
                }
                db()->prepare("INSERT INTO {$table} (migration,batch) VALUES (?,?)")->execute([$name, $batch]);
                $count++;
                self::writeLog('Migration calisti: ' . $name);
            } catch (Throwable $e) {
                self::writeLog('Migration hatasi: ' . $name . ' - ' . $e->getMessage());
                throw new RuntimeException('Migration basarisiz: ' . $name);
            }
        }
        return $count;
    }

    private static function assertMigrationSafe(string $file): void
    {
        $source = is_file($file) ? strtolower((string)file_get_contents($file)) : '';
        $contentTables = '(posts|post_meta|post_categories|post_tags|pages|categories|tags|media|comments|users)';
        $dangerous = [
            '#\btruncate\s+table\s+[`"\']?[^`"\';\s]*'.$contentTables.'\b#i',
            '#\bdrop\s+table\s+(?:if\s+exists\s+)?[`"\']?[^`"\';\s]*'.$contentTables.'\b#i',
            '#\bdelete\s+from\s+[`"\']?[^`"\';\s]*'.$contentTables.'\b#i',
            '#\binsert\s+into\s+[`"\']?[^`"\';\s]*(posts|pages)\b#i',
            '#\breplace\s+into\s+[`"\']?[^`"\';\s]*(posts|pages)\b#i',
        ];
        foreach ($dangerous as $pattern) {
            if (preg_match($pattern, $source)) {
                throw new RuntimeException('Migration icerik verisine mudahale ediyor gibi gorunuyor ve engellendi: ' . basename($file));
            }
        }
    }

    public static function clearCache(): void
    {
        $dir = OMURGA_ROOT . '/storage/cache';
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            if ($rel === '.gitkeep') {
                continue;
            }
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        self::writeLog('Cache temizlendi.');
    }

    public static function enableMaintenance(string $message): void
    {
        $dir = dirname(self::maintenancePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(self::maintenancePath(), json_encode(['enabled_at' => date('c'), 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        self::writeLog('Bakim modu acildi.');
    }

    public static function disableMaintenance(): void
    {
        if (is_file(self::maintenancePath())) {
            @unlink(self::maintenancePath());
        }
        self::writeLog('Bakim modu kapatildi.');
    }

    public static function isMaintenanceActive(): bool
    {
        return is_file(self::maintenancePath());
    }

    public static function maintenanceMessage(): string
    {
        $data = is_file(self::maintenancePath()) ? json_decode((string)file_get_contents(self::maintenancePath()), true) : [];
        return (string)($data['message'] ?? 'Omurga CMS guncelleniyor. Lutfen birkac dakika sonra tekrar deneyin.');
    }

    public static function recentBackups(int $limit = 8): array
    {
        $files = array_merge(glob(self::backupsDir() . '/omurga-backup-before-*.zip') ?: [], glob(self::backupsDir() . '/database-before-*.sql') ?: []);
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $rows = [];
        foreach (array_slice($files, 0, max(1, $limit)) as $file) {
            $rows[] = [
                'name' => basename($file),
                'size' => filesize($file) ?: 0,
                'date' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
            ];
        }
        return $rows;
    }
}
