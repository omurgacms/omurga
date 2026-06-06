<?php
if(!defined('OMURGA_INIT')) { http_response_code(403); exit('Forbidden'); }

/**
 * Omurga Developer API
 * Tema ve paket geliştiricileri için kararlı, belgelenebilir API katmanı.
 * Bu dosya çekirdek korumasını delmez; yalnızca güvenli kayıt ve hook işlemleri sağlar.
 */
final class Omurga_DeveloperApi {
    private static array $adminPages = [];
    private static array $routes = [];
    private static array $assets = ['style'=>[], 'script'=>[]];
    private static array $themeDemos = [];
    private static array $permissions = [];
    private static array $packageSettings = [];
    private static array $packageMigrations = [];

    public static function addAction(string $hook, $callback, int $priority=10): void {
        if(function_exists('omurga_add_action')) omurga_add_action($hook, $callback, $priority);
    }

    public static function doAction(string $hook, ...$args): void {
        if(function_exists('omurga_do_action')) omurga_do_action($hook, ...$args);
    }

    public static function addFilter(string $hook, $callback, int $priority=10): void {
        if(function_exists('omurga_add_filter')) omurga_add_filter($hook, $callback, $priority);
    }

    public static function applyFilters(string $hook, $value, ...$args) {
        return function_exists('omurga_apply_filters') ? omurga_apply_filters($hook, $value, ...$args) : $value;
    }

    public static function registerBlock($id, array $definition=[]): bool {
        if(!function_exists('omurga_register_block')) return false;
        omurga_register_block($id, $definition);
        return true;
    }


    public static function registerPackageSettings(string $package, array $settings): bool {
        $package = self::slug($package);
        if($package==='') return false;
        self::$packageSettings[$package] = array_replace(self::$packageSettings[$package] ?? [], $settings);
        return true;
    }

    public static function registerPackageSetting(string $package, string $key, array $field): bool {
        $package = self::slug($package);
        $key = self::slug($key);
        if($package==='' || $key==='') return false;
        self::$packageSettings[$package][$key] = $field;
        return true;
    }

    public static function packageSettings(string $package=''): array {
        $package = self::slug($package);
        if($package!=='') return self::$packageSettings[$package] ?? [];
        return self::$packageSettings;
    }

    public static function registerPackageSettingsPage(string $package, string $title='Ayarlar', string $capability='plugins.manage', int $position=60): bool {
        $package = self::slug($package);
        if($package==='') return false;
        return self::registerAdminPage([
            'id'=>$package.'-settings',
            'package'=>$package,
            'title'=>$title,
            'menu_title'=>$title,
            'capability'=>$capability,
            'position'=>$position,
            'icon'=>'⚙',
            'source'=>'package-api',
            'file'=>function(array $page=[]) use ($package) {
                if(function_exists('omurga_render_package_settings_page')) {
                    return omurga_render_package_settings_page($package);
                }
                return '<div class="alert error">Paket ayar API yüklenemedi.</div>';
            },
        ]);
    }

    public static function registerPackageMigration(string $package, string $version, $callback): bool {
        $package = self::slug($package);
        $version = trim($version);
        if($package==='' || $version==='' || !is_callable($callback)) return false;
        self::$packageMigrations[$package][$version] = $callback;
        return true;
    }

    public static function packageMigrations(string $package=''): array {
        $package = self::slug($package);
        if($package!=='') return self::$packageMigrations[$package] ?? [];
        return self::$packageMigrations;
    }

    public static function registerAdminPage(array $page): bool {
        $id = self::slug((string)($page['id'] ?? $page['slug'] ?? ''));
        $file = (string)($page['file'] ?? '');
        if($id==='' || $file==='') return false;
        self::$adminPages[$id] = [
            'id'=>$id,
            'plugin'=>(string)($page['package'] ?? $page['plugin'] ?? 'registered'),
            'package'=>(string)($page['package'] ?? $page['plugin'] ?? 'registered'),
            'title'=>(string)($page['title'] ?? ucwords(str_replace('-', ' ', $id))),
            'menu_title'=>(string)($page['menu_title'] ?? $page['title'] ?? ucwords(str_replace('-', ' ', $id))),
            'file'=>$file,
            'cap'=>(string)($page['capability'] ?? $page['cap'] ?? 'plugins.manage'),
            'icon'=>(string)($page['icon'] ?? '▣'),
            'position'=>(int)($page['position'] ?? 50),
            'registered'=>true,
            'source'=>(string)($page['source'] ?? 'api'),
        ];
        return true;
    }

    public static function adminPages(): array {
        return array_values(self::$adminPages);
    }

    public static function registerRoute(string $method, string $path, $callback, array $options=[]): bool {
        $method = strtoupper(trim($method));
        $path = '/'.trim($path, '/');
        if($method==='' || $path==='/' || !is_callable($callback)) return false;
        self::$routes[$method.' '.$path] = [
            'method'=>$method,
            'path'=>$path,
            'callback'=>$callback,
            'capability'=>(string)($options['capability'] ?? ''),
            'public'=>!empty($options['public']),
        ];
        return true;
    }

    public static function routes(): array {
        return array_values(self::$routes);
    }

    public static function enqueueStyle(string $handle, string $url, array $deps=[], string $version=''): bool {
        return self::enqueueAsset('style', $handle, $url, $deps, $version);
    }

    public static function enqueueScript(string $handle, string $url, array $deps=[], string $version='', bool $footer=true): bool {
        return self::enqueueAsset('script', $handle, $url, $deps, $version, ['footer'=>$footer]);
    }

    public static function assets(string $type=''): array {
        if($type!=='' && isset(self::$assets[$type])) return self::$assets[$type];
        return self::$assets;
    }

    public static function registerThemeDemo(string $theme, array $demo): bool {
        $theme = self::slug($theme);
        $slug = self::slug((string)($demo['slug'] ?? $demo['id'] ?? ''));
        if($theme==='' || $slug==='') return false;
        $demo['slug']=$slug;
        $demo['id']=$slug;
        $demo['theme']=$theme;
        self::$themeDemos[$theme][$slug] = $demo;
        return true;
    }

    public static function themeDemos(string $theme=''): array {
        $theme = self::slug($theme);
        if($theme!=='') return self::$themeDemos[$theme] ?? [];
        return self::$themeDemos;
    }

    public static function registerPermission(string $key, string $label, string $description=''): bool {
        $key = self::capability($key);
        if($key==='') return false;
        self::$permissions[$key] = ['key'=>$key,'label'=>$label,'description'=>$description];
        return true;
    }

    public static function permissions(): array {
        return self::$permissions;
    }

    private static function enqueueAsset(string $type, string $handle, string $url, array $deps=[], string $version='', array $extra=[]): bool {
        $handle = self::slug($handle);
        $url = trim($url);
        if($handle==='' || $url==='') return false;
        self::$assets[$type][$handle] = array_replace([
            'handle'=>$handle,
            'url'=>$url,
            'deps'=>$deps,
            'version'=>$version,
        ], $extra);
        return true;
    }

    private static function slug(string $value): string {
        $value=strtolower(trim($value));
        $value=preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim($value, '-_');
    }

    private static function capability(string $value): string {
        $value=strtolower(trim($value));
        $value=preg_replace('/[^a-z0-9_.:\-]+/', '', $value) ?? '';
        return trim($value, '.:-_');
    }
}

final class Omurga {
    public static function addAction(string $hook, $callback, int $priority=10): void { Omurga_DeveloperApi::addAction($hook,$callback,$priority); }
    public static function doAction(string $hook, ...$args): void { Omurga_DeveloperApi::doAction($hook,...$args); }
    public static function addFilter(string $hook, $callback, int $priority=10): void { Omurga_DeveloperApi::addFilter($hook,$callback,$priority); }
    public static function applyFilters(string $hook, $value, ...$args) { return Omurga_DeveloperApi::applyFilters($hook,$value,...$args); }
    public static function addBlock($id, array $definition=[]): bool { return Omurga_DeveloperApi::registerBlock($id,$definition); }
    public static function addBuilderBlock($id, array $definition=[]): bool { return class_exists('Omurga_BuilderApi') ? Omurga_BuilderApi::registerBlock($id,$definition) : false; }
    public static function addBuilderRegion(string $id, string $label, string $context='page', array $options=[]): bool { return class_exists('Omurga_BuilderApi') ? Omurga_BuilderApi::registerRegion($id,$label,$context,$options) : false; }
    public static function addBuilderTemplate(string $id, string $label, array $layout, array $options=[]): bool { return class_exists('Omurga_BuilderApi') ? Omurga_BuilderApi::registerTemplate($id,$label,$layout,$options) : false; }
    public static function builderField(string $type, string $label, $default='', array $options=[]): array { return class_exists('Omurga_BuilderApi') ? Omurga_BuilderApi::field($type,$label,$default,$options) : ['type'=>$type,'label'=>$label,'default'=>$default]+$options; }
    public static function addAdminPage(array $page): bool { return Omurga_DeveloperApi::registerAdminPage($page); }
    public static function addRoute(string $method, string $path, $callback, array $options=[]): bool { return Omurga_DeveloperApi::registerRoute($method,$path,$callback,$options); }
    public static function addStyle(string $handle, string $url, array $deps=[], string $version=''): bool { return Omurga_DeveloperApi::enqueueStyle($handle,$url,$deps,$version); }
    public static function addScript(string $handle, string $url, array $deps=[], string $version='', bool $footer=true): bool { return Omurga_DeveloperApi::enqueueScript($handle,$url,$deps,$version,$footer); }
    public static function addThemeDemo(string $theme, array $demo): bool { return Omurga_DeveloperApi::registerThemeDemo($theme,$demo); }
    public static function addPermission(string $key, string $label, string $description=''): bool { return Omurga_DeveloperApi::registerPermission($key,$label,$description); }
    public static function addMenu(array $page): bool { return Omurga_DeveloperApi::registerAdminPage($page); }
    public static function addPackagePage(string $package, string $id, string $title, $fileOrCallback, string $capability='plugins.manage', int $position=60): bool { return Omurga_DeveloperApi::registerAdminPage(['package'=>$package,'id'=>$id,'title'=>$title,'menu_title'=>$title,'file'=>$fileOrCallback,'capability'=>$capability,'position'=>$position,'source'=>'package-api']); }
    public static function addPackageSettings(string $package, array $settings): bool { return Omurga_DeveloperApi::registerPackageSettings($package,$settings); }
    public static function addPackageSetting(string $package, string $key, array $field): bool { return Omurga_DeveloperApi::registerPackageSetting($package,$key,$field); }
    public static function addPackageSettingsPage(string $package, string $title='Ayarlar', string $capability='plugins.manage', int $position=60): bool { return Omurga_DeveloperApi::registerPackageSettingsPage($package,$title,$capability,$position); }
    public static function getPackageSetting(string $package, string $key, $default=null) { return function_exists('omurga_package_setting') ? omurga_package_setting($package,$key,$default) : $default; }
    public static function updatePackageSettings(string $package, array $settings): void { if(function_exists('omurga_update_package_settings')) omurga_update_package_settings($package,$settings); }
    public static function addPackageMigration(string $package, string $version, $callback): bool { return Omurga_DeveloperApi::registerPackageMigration($package,$version,$callback); }
    public static function packageAsset(string $package, string $path=''): string { return function_exists('omurga_package_asset_url') ? omurga_package_asset_url($package,$path) : ''; }

    public static function events(): array { return Omurga_PlatformApi::events(); }
    public static function fire(string $event, ...$args): void { Omurga_PlatformApi::fire($event, ...$args); }
    public static function validateManifest(array $manifest, string $type='package'): array { return Omurga_PlatformApi::validateManifest($manifest,$type); }
    public static function readManifest(string $path, string $type='package'): array { return Omurga_PlatformApi::readManifest($path,$type); }
    public static function compareVersion(string $installed, string $incoming): string { return Omurga_PlatformApi::compareVersion($installed,$incoming); }
    public static function checkDependencies(array $manifest, callable $isInstalled): array { return Omurga_PlatformApi::checkDependencies($manifest,$isInstalled); }
    public static function schedule(string $hook, string $frequency, array $args=[]): bool { return Omurga_PlatformApi::schedule($hook,$frequency,$args); }
    public static function runCron(): int { return Omurga_PlatformApi::runCron(); }
    public static function upload(array $file, string $subdir=''): array { return Omurga_PlatformApi::uploadFile($file,$subdir); }
    public static function webp(string $source, int $quality=82): array { return Omurga_PlatformApi::makeWebp($source,$quality); }
    public static function center(string $path=''): string { return Omurga_PlatformApi::centerEndpoint($path); }
}


if (!function_exists('omurga_register_admin_page')) {
    function omurga_register_admin_page(array $page): bool { return Omurga_DeveloperApi::registerAdminPage($page); }
}
if (!function_exists('omurga_registered_admin_pages')) {
    function omurga_registered_admin_pages(): array { return Omurga_DeveloperApi::adminPages(); }
}
if (!function_exists('omurga_register_route')) {
    function omurga_register_route(string $method, string $path, $callback, array $options=[]): bool { return Omurga_DeveloperApi::registerRoute($method,$path,$callback,$options); }
}
if (!function_exists('omurga_registered_routes')) {
    function omurga_registered_routes(): array { return Omurga_DeveloperApi::routes(); }
}
if (!function_exists('omurga_enqueue_style')) {
    function omurga_enqueue_style(string $handle, string $url, array $deps=[], string $version=''): bool { return Omurga_DeveloperApi::enqueueStyle($handle,$url,$deps,$version); }
}
if (!function_exists('omurga_enqueue_script')) {
    function omurga_enqueue_script(string $handle, string $url, array $deps=[], string $version='', bool $footer=true): bool { return Omurga_DeveloperApi::enqueueScript($handle,$url,$deps,$version,$footer); }
}
if (!function_exists('omurga_registered_assets')) {
    function omurga_registered_assets(string $type=''): array { return Omurga_DeveloperApi::assets($type); }
}
if (!function_exists('omurga_register_theme_demo')) {
    function omurga_register_theme_demo(string $theme, array $demo): bool { return Omurga_DeveloperApi::registerThemeDemo($theme,$demo); }
}
if (!function_exists('omurga_registered_theme_demos')) {
    function omurga_registered_theme_demos(string $theme=''): array { return Omurga_DeveloperApi::themeDemos($theme); }
}
if (!function_exists('omurga_register_permission')) {
    function omurga_register_permission(string $key, string $label, string $description=''): bool { return Omurga_DeveloperApi::registerPermission($key,$label,$description); }
}
if (!function_exists('omurga_registered_permissions')) {
    function omurga_registered_permissions(): array { return Omurga_DeveloperApi::permissions(); }
}


if (!function_exists('omurga_register_package_settings')) {
    function omurga_register_package_settings(string $package, array $settings): bool { return Omurga_DeveloperApi::registerPackageSettings($package,$settings); }
}
if (!function_exists('omurga_register_package_setting')) {
    function omurga_register_package_setting(string $package, string $key, array $field): bool { return Omurga_DeveloperApi::registerPackageSetting($package,$key,$field); }
}
if (!function_exists('omurga_registered_package_settings')) {
    function omurga_registered_package_settings(string $package=''): array { return Omurga_DeveloperApi::packageSettings($package); }
}
if (!function_exists('omurga_register_package_settings_page')) {
    function omurga_register_package_settings_page(string $package, string $title='Ayarlar', string $capability='plugins.manage', int $position=60): bool { return Omurga_DeveloperApi::registerPackageSettingsPage($package,$title,$capability,$position); }
}
if (!function_exists('omurga_register_package_migration')) {
    function omurga_register_package_migration(string $package, string $version, callable $callback): bool { return Omurga_DeveloperApi::registerPackageMigration($package,$version,$callback); }
}
if (!function_exists('omurga_registered_package_migrations')) {
    function omurga_registered_package_migrations(string $package=''): array { return Omurga_DeveloperApi::packageMigrations($package); }
}
