<?php
if (!defined('OMURGA_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Omurga_BlockRegistry
{
    private static array $blocks = [];
    private static array $warnings = [];

    private const CATEGORIES = [
        'content',
        'media',
        'layout',
        'navigation',
        'news',
        'service',
        'user',
        'custom',
    ];

    private const CONTEXTS = ['page', 'header', 'footer', 'sidebar', 'any'];

    public static function register($id, array $definition = []): bool
    {
        if (is_array($id)) {
            $definition = $id;
            $id = $definition['id'] ?? $definition['slug'] ?? '';
        }

        $block = self::normalize($id, $definition);
        if (!$block) {
            self::warn('Geçersiz blok tanımı atlandı.');
            return false;
        }

        if (isset(self::$blocks[$block['id']])) {
            self::warn('Aynı blok id tekrar kaydedildi: '.$block['id']);
        }

        self::$blocks[$block['id']] = $block;
        return true;
    }

    public static function normalize($id, array $definition = []): ?array
    {
        $id = self::normalizeId((string)($definition['id'] ?? $definition['slug'] ?? $id));
        if ($id === '') {
            return null;
        }

        $source = self::normalizeSource((string)($definition['source'] ?? 'registered'));
        $settingsSchema = $definition['settings_schema'] ?? $definition['settings'] ?? [];
        $settingsSchema = is_array($settingsSchema) ? $settingsSchema : [];
        $defaultSettings = self::defaultsFromSchema($settingsSchema);
        if (is_array($definition['default_settings'] ?? null)) {
            $defaultSettings = array_replace($defaultSettings, $definition['default_settings']);
        }

        $hasContextDefinition = array_key_exists('allowed_contexts', $definition);
        $usage = self::normalizeList($definition['usage'] ?? []);
        if (!$usage && !$hasContextDefinition) {
            $usage = ['any'];
        }

        $allowedContexts = self::normalizeContexts($definition['allowed_contexts'] ?? null, $usage);
        $renderCallback = $definition['render_callback'] ?? null;
        $previewCallback = $definition['preview_callback'] ?? null;
        $view = $definition['view'] ?? null;
        $hasRenderer = is_callable($renderCallback) || (is_string($view) && $view !== '') || $source === 'core';

        return array_replace($definition, [
            'id' => $id,
            'slug' => $id,
            'name' => (string)($definition['name'] ?? ucwords(str_replace('-', ' ', $id))),
            'description' => (string)($definition['description'] ?? ''),
            'category' => self::normalizeCategory((string)($definition['category'] ?? ''), $source),
            'category_label' => (string)($definition['category_label'] ?? $definition['category'] ?? ''),
            'icon' => (string)($definition['icon'] ?? 'square'),
            'source' => $source,
            'settings_schema' => $settingsSchema,
            'settings' => $settingsSchema,
            'default_settings' => $defaultSettings,
            'render_callback' => is_callable($renderCallback) ? $renderCallback : null,
            'preview_callback' => is_callable($previewCallback) ? $previewCallback : null,
            'allowed_contexts' => $allowedContexts,
            'has_allowed_contexts' => $hasContextDefinition ? 1 : 0,
            'usage' => $usage,
            'active' => $hasRenderer ? 1 : 0,
        ]);
    }

    public static function all(?string $context = null, bool $includeInactive = false): array
    {
        $blocks = [];
        foreach (self::$blocks as $id => $block) {
            if (!$includeInactive && empty($block['active'])) {
                continue;
            }
            if ($context !== null && !self::matchesContext($block, $context)) {
                continue;
            }
            $blocks[$id] = $block;
        }
        return $blocks;
    }

    public static function get(string $id, bool $includeInactive = false): ?array
    {
        $id = self::normalizeId($id);
        if ($id === '' || !isset(self::$blocks[$id])) {
            return null;
        }
        if (!$includeInactive && empty(self::$blocks[$id]['active'])) {
            return null;
        }
        return self::$blocks[$id];
    }

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public static function warnings(): array
    {
        return self::$warnings;
    }

    public static function contextFromRegion(?string $region): ?string
    {
        if ($region === null || $region === '') {
            return null;
        }
        $region = self::normalizeId($region);
        if (in_array($region, self::CONTEXTS, true)) {
            return $region;
        }
        if (in_array($region, ['mobile_bottom', 'footer_main'], true)) {
            return 'footer';
        }
        if (in_array($region, ['header_main', 'topbar'], true)) {
            return 'header';
        }
        if (strpos($region, 'sidebar') !== false || strpos($region, 'side') !== false) {
            return 'sidebar';
        }
        return 'page';
    }

    private static function matchesContext(array $block, string $context): bool
    {
        $normalized = self::contextFromRegion($context);
        $allowed = self::normalizeList($block['allowed_contexts'] ?? []);
        if(!empty($block['has_allowed_contexts'])){
            return $normalized !== null && (in_array('any', $allowed, true) || in_array($normalized, $allowed, true));
        }

        $usage = self::normalizeList($block['usage'] ?? []);
        if (in_array('any', $usage, true) || in_array($context, $usage, true)) {
            return true;
        }

        return $normalized !== null && (in_array('any', $allowed, true) || in_array($normalized, $allowed, true));
    }

    private static function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        $id = preg_replace('/[^a-z0-9_\-]+/', '-', $id) ?? '';
        return trim($id, '-_');
    }

    private static function normalizeSource(string $source): string
    {
        return in_array($source, ['core', 'theme', 'package', 'plugin', 'custom', 'registered'], true) ? $source : 'custom';
    }

    private static function normalizeCategory(string $category, string $source): string
    {
        $normalized = self::normalizeId($category);
        if (in_array($normalized, self::CATEGORIES, true)) {
            return $normalized;
        }

        if (strpos($normalized, 'news') !== false || strpos($normalized, 'haber') !== false) {
            return 'news';
        }
        if (strpos($normalized, 'media') !== false || strpos($normalized, 'image') !== false || strpos($normalized, 'video') !== false || strpos($normalized, 'galeri') !== false) {
            return 'media';
        }
        if (strpos($normalized, 'menu') !== false || strpos($normalized, 'nav') !== false || strpos($normalized, 'logo') !== false) {
            return 'navigation';
        }
        if (strpos($normalized, 'user') !== false || strpos($normalized, 'auth') !== false || strpos($normalized, 'giris') !== false) {
            return 'user';
        }
        if ($source === 'core') {
            return 'content';
        }
        return 'custom';
    }

    private static function normalizeContexts($contexts, array $usage): array
    {
        $contexts = self::normalizeList($contexts);
        $valid = array_values(array_intersect($contexts, self::CONTEXTS));
        if ($valid) {
            return $valid;
        }

        $derived = [];
        foreach ($usage as $region) {
            $context = self::contextFromRegion($region);
            if ($context !== null) {
                $derived[] = $context;
            }
        }
        $derived = array_values(array_unique($derived));
        return $derived ?: ['any'];
    }

    private static function normalizeList($value): array
    {
        if (is_string($value) && $value !== '') {
            $value = [$value];
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $item = self::normalizeId((string)$item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return array_values(array_unique($out));
    }

    private static function defaultsFromSchema(array $schema): array
    {
        $out = [];
        foreach ($schema as $key => $field) {
            if (!is_array($field)) {
                continue;
            }
            $out[$key] = $field['default'] ?? '';
        }
        return $out;
    }

    private static function warn(string $message): void
    {
        self::$warnings[] = $message;
        if (function_exists('omurga_write_error')) {
            try {
                omurga_write_error(new RuntimeException($message));
            } catch (Throwable $e) {
                // Registry warnings must never break the builder.
            }
        }
    }
}
