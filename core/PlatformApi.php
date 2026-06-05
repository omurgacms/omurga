<?php
if(!defined('OMURGA_INIT')) { http_response_code(403); exit('Forbidden'); }

/**
 * Omurga Platform API
 * Developer API üzerine eklenen kararlı platform katmanı.
 */
final class Omurga_PlatformApi {
    private static array $events = [
        'omurga.init','omurga.admin.init','omurga.front.init','omurga.theme.loaded',
        'omurga.package.loaded','omurga.package.activated','omurga.package.deactivated',
        'omurga.post.save','omurga.post.publish','omurga.user.login','omurga.user.logout',
        'omurga.media.uploaded','omurga.cron.run','omurga.center.check_updates'
    ];
    private static array $schedules = [];
    private static array $dependencies = [];

    public static function events(): array { return self::$events; }

    public static function fire(string $event, ...$args): void {
        if(!in_array($event, self::$events, true)) self::$events[] = $event;
        if(function_exists('omurga_do_action')) omurga_do_action($event, ...$args);
    }

    public static function validateManifest(array $manifest, string $type='package'): array {
        $errors = [];
        if(empty($manifest['slug']) && !empty($manifest['id'])) $manifest['slug']=(string)$manifest['id'];
        $required = ['name','slug','version'];
        foreach($required as $key){ if(empty($manifest[$key])) $errors[] = $key.' zorunlu.'; }
        if(!empty($manifest['slug']) && !preg_match('/^[a-z0-9][a-z0-9\-_]*$/', (string)$manifest['slug'])) $errors[] = 'slug sadece küçük harf, rakam, tire ve alt çizgi içerebilir.';
        if(!empty($manifest['permissions']) && !is_array($manifest['permissions'])) $errors[] = 'permissions dizi olmalı.';
        if(!empty($manifest['requires']) && !is_array($manifest['requires'])) $errors[] = 'requires dizi olmalı.';
        if($type === 'theme' && !empty($manifest['permissions'])) {
            $blocked = array_intersect($manifest['permissions'], ['users','roles','core','system','sql']);
            if($blocked) $errors[] = 'Temalar kullanıcı, rol, sistem, SQL veya çekirdek izni isteyemez: '.implode(', ', $blocked);
        }
        return ['valid'=>empty($errors), 'errors'=>$errors, 'manifest'=>$manifest];
    }

    public static function readManifest(string $path, string $type='package'): array {
        if(is_dir($path)){
            $base=rtrim($path,'/');
            $preferred=$type==='theme' ? 'theme.json' : 'package.json';
            $file=$base.'/'.$preferred;
        } else {
            $file=$path;
        }
        if(!is_file($file)) return ['valid'=>false, 'errors'=>[($type==='theme'?'theme.json':'package.json').' bulunamadı.'], 'manifest'=>[]];
        $json = json_decode((string)file_get_contents($file), true);
        if(!is_array($json)) return ['valid'=>false, 'errors'=>[basename($file).' okunamadı veya JSON bozuk.'], 'manifest'=>[]];
        return self::validateManifest($json, $type);
    }

    public static function compareVersion(string $installed, string $incoming): string {
        $i = self::normalizeVersion($installed); $n = self::normalizeVersion($incoming);
        $cmp = version_compare($n, $i);
        if($cmp > 0) return 'upgrade';
        if($cmp === 0) return 'reinstall';
        return 'downgrade';
    }

    public static function registerDependency(string $slug, array $requires): void { self::$dependencies[$slug] = $requires; }
    public static function checkDependencies(array $manifest, callable $isInstalled): array {
        $missing = [];
        foreach(($manifest['requires'] ?? []) as $req){
            $slug = is_array($req) ? (string)($req['slug'] ?? '') : (string)$req;
            if($slug !== '' && !$isInstalled($slug)) $missing[] = $slug;
        }
        return ['ok'=>empty($missing), 'missing'=>$missing];
    }

    public static function schedule(string $hook, string $frequency, array $args=[]): bool {
        $hook = trim($hook); $frequency = strtolower(trim($frequency));
        if($hook==='' || !in_array($frequency, ['hourly','daily','weekly','monthly'], true)) return false;
        self::$schedules[$hook] = ['hook'=>$hook,'frequency'=>$frequency,'args'=>$args,'next_run'=>self::nextRun($frequency)];
        return self::saveSchedules();
    }

    public static function unschedule(string $hook): bool { unset(self::$schedules[$hook]); return self::saveSchedules(); }

    public static function dueSchedules(): array {
        self::loadSchedules(); $now = time();
        return array_values(array_filter(self::$schedules, fn($s)=>($s['next_run'] ?? PHP_INT_MAX) <= $now));
    }

    public static function runCron(): int {
        self::loadSchedules(); $count = 0;
        foreach(self::dueSchedules() as $schedule){
            self::fire($schedule['hook'], $schedule['args'] ?? []);
            self::$schedules[$schedule['hook']]['next_run'] = self::nextRun($schedule['frequency'] ?? 'daily');
            $count++;
        }
        self::saveSchedules();
        self::fire('omurga.cron.run', $count);
        return $count;
    }

    public static function uploadFile(array $file, string $subdir=''): array {
        if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return ['ok'=>false,'error'=>'Geçerli yükleme bulunamadı.'];
        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowed=['image/jpeg','image/png','image/webp','image/gif','application/pdf','video/mp4'];
        if(!in_array($mime,$allowed,true)) return ['ok'=>false,'error'=>'Bu dosya türüne izin verilmiyor.'];
        $relDir=function_exists('omurga_media_rel_dir') ? omurga_media_rel_dir() : 'uploads/'.date('Y/m');
        if($subdir!=='') $relDir.='/'.trim(preg_replace('/[^a-zA-Z0-9_\-\/]/','',(string)$subdir),'/');
        $dir = OMURGA_ROOT.'/'.$relDir;
        if(!is_dir($dir)) mkdir($dir, 0755, true);
        $safe = function_exists('omurga_prepare_upload_name') ? omurga_prepare_upload_name((string)($file['name'] ?? 'file')) : (uniqid('media_', true).'-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', basename((string)($file['name'] ?? 'file'))));
        $target = $dir.'/'.$safe;
        if(!move_uploaded_file($file['tmp_name'], $target)) return ['ok'=>false,'error'=>'Dosya taşınamadı.'];
        $rel=$relDir.'/'.basename($target);
        if(function_exists('insert_media_record')) insert_media_record($rel, '', $_SESSION['omurga_user_id'] ?? null);
        self::fire('omurga.media.uploaded', $rel);
        return ['ok'=>true,'path'=>$rel,'url'=>function_exists('image_url') ? image_url($rel) : self::pathToUrl($target)];
    }

    public static function makeWebp(string $source, int $quality=82): array {
        if(!is_file($source)) return ['ok'=>false,'error'=>'Kaynak dosya yok.'];
        if(!function_exists('imagewebp')) return ['ok'=>false,'error'=>'Sunucuda WebP desteği yok.'];
        $info = getimagesize($source); if(!$info) return ['ok'=>false,'error'=>'Görsel okunamadı.'];
        $img = match($info[2]) { IMAGETYPE_JPEG => imagecreatefromjpeg($source), IMAGETYPE_PNG => imagecreatefrompng($source), default => false };
        if(!$img) return ['ok'=>false,'error'=>'Sadece JPG/PNG destekleniyor.'];
        $target = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source) ?: ($source.'.webp');
        $ok = imagewebp($img, $target, max(1,min(100,$quality))); imagedestroy($img);
        return ['ok'=>$ok,'path'=>$target,'url'=>self::pathToUrl($target)];
    }

    public static function centerEndpoint(string $path=''): string {
        $base = defined('OMURGA_CENTER_URL') ? OMURGA_CENTER_URL : 'https://merkez.omurga.dev';
        return rtrim($base,'/').'/'.ltrim($path,'/');
    }

    public static function childThemeParent(string $themeDir): string {
        $manifest = self::readManifest($themeDir, 'theme');
        return (string)($manifest['manifest']['parent'] ?? '');
    }

    private static function normalizeVersion(string $version): string {
        $version = strtolower(trim($version));
        $version = str_replace([' beta','-beta',' beta '], '.0', $version);
        return preg_replace('/[^0-9.]+/', '', $version) ?: '0.0.0';
    }
    private static function scheduleFile(): string { return OMURGA_ROOT.'/storage/cache/cron-schedules.php'; }
    private static function loadSchedules(): void { $f=self::scheduleFile(); if(is_file($f)){ $data=include $f; if(is_array($data)) self::$schedules=$data; } }
    private static function saveSchedules(): bool { $f=self::scheduleFile(); if(!is_dir(dirname($f))) mkdir(dirname($f),0755,true); return (bool)file_put_contents($f, '<?php return '.var_export(self::$schedules,true).';'); }
    private static function nextRun(string $frequency): int { return time() + ['hourly'=>3600,'daily'=>86400,'weekly'=>604800,'monthly'=>2592000][$frequency]; }
    private static function pathToUrl(string $path): string { return str_replace(OMURGA_ROOT, '', $path); }
}

if(class_exists('Omurga')) {
    class_alias('Omurga_PlatformApi', 'OmurgaPlatform');
}

function omurga_events(): array { return Omurga_PlatformApi::events(); }
function omurga_event(string $event, ...$args): void { Omurga_PlatformApi::fire($event, ...$args); }
function omurga_validate_manifest(array $manifest, string $type='package'): array { return Omurga_PlatformApi::validateManifest($manifest,$type); }
function omurga_read_manifest(string $path, string $type='package'): array { return Omurga_PlatformApi::readManifest($path,$type); }
function omurga_compare_version(string $installed, string $incoming): string { return Omurga_PlatformApi::compareVersion($installed,$incoming); }
function omurga_check_dependencies(array $manifest, callable $isInstalled): array { return Omurga_PlatformApi::checkDependencies($manifest,$isInstalled); }
function omurga_schedule(string $hook, string $frequency, array $args=[]): bool { return Omurga_PlatformApi::schedule($hook,$frequency,$args); }
function omurga_run_cron(): int { return Omurga_PlatformApi::runCron(); }
function omurga_media_upload(array $file, string $subdir=''): array { return Omurga_PlatformApi::uploadFile($file,$subdir); }
function omurga_media_webp(string $source, int $quality=82): array { return Omurga_PlatformApi::makeWebp($source,$quality); }
function omurga_center_endpoint(string $path=''): string { return Omurga_PlatformApi::centerEndpoint($path); }
