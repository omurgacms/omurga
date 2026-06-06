<?php
if(!defined('OMURGA_INIT')) { http_response_code(403); exit('Forbidden'); }

/**
 * Omurga Builder API
 * Tema ve paketlerin Sayfa Tasarımcısı / Header / Footer / bölge düzenlerine
 * güvenli şekilde blok, bölge ve hazır şablon eklemesi için kararlı API.
 */
final class Omurga_BuilderApi {
    private static array $regions = [];
    private static array $templates = [];

    private static function slug(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        return trim($value, '-_');
    }

    public static function registerRegion(string $id, string $label, string $context='page', array $options=[]): bool {
        $id = self::slug($id);
        if($id==='') return false;
        $context = function_exists('omurga_block_context_from_region') ? (omurga_block_context_from_region($context) ?: $context) : $context;
        self::$regions[$id] = array_replace([
            'id'=>$id,
            'label'=>$label,
            'context'=>$context ?: 'page',
            'description'=>'',
            'position'=>50,
            'theme'=>'',
            'source'=>'api',
        ], $options);
        return true;
    }

    public static function regions(?string $context=null): array {
        self::seedDefaultRegions();
        $regions = self::$regions;
        if($context!==null && $context!==''){
            $normalized = function_exists('omurga_block_context_from_region') ? (omurga_block_context_from_region($context) ?: $context) : $context;
            $regions = array_filter($regions, fn($r)=>in_array($r['context'] ?? 'page', [$normalized, 'any'], true));
        }
        uasort($regions, fn($a,$b)=>(int)($a['position'] ?? 50) <=> (int)($b['position'] ?? 50));
        return $regions;
    }

    public static function registerBlock($id, array $definition=[]): bool {
        $definition['source'] = $definition['source'] ?? 'builder-api';
        if(!isset($definition['usage']) && isset($definition['regions'])) $definition['usage'] = $definition['regions'];
        if(!isset($definition['allowed_contexts']) && isset($definition['contexts'])) $definition['allowed_contexts'] = $definition['contexts'];
        if(function_exists('omurga_register_block')){
            omurga_register_block($id, $definition);
            return true;
        }
        return false;
    }

    public static function field(string $type, string $label, $default='', array $options=[]): array {
        $type = self::slug($type) ?: 'text';
        return array_replace([
            'type'=>$type,
            'label'=>$label,
            'default'=>$default,
            'placeholder'=>'',
            'help'=>'',
            'required'=>false,
            'options'=>[],
        ], $options);
    }

    public static function createBlock(string $slug, array $settings=[], array $options=[]): array {
        $slug = self::slug($slug);
        return array_replace([
            'id'=>'b'.date('ymdHis').substr(md5($slug.serialize($settings).microtime(true)),0,6),
            'slug'=>$slug,
            'source'=>$options['source'] ?? 'api',
            'enabled'=>1,
            'sort'=>0,
            'width'=>'100',
            'width_tablet'=>'100',
            'width_mobile'=>'100',
            'settings'=>$settings,
        ], $options, ['slug'=>$slug, 'settings'=>$settings]);
    }

    public static function layout(?string $theme=null): array {
        return function_exists('omurga_layout') ? omurga_layout($theme) : [];
    }

    public static function saveLayout(array $layout, ?string $theme=null): bool {
        if(!function_exists('omurga_update_layout')) return false;
        omurga_update_layout($layout, $theme);
        return true;
    }

    public static function addBlock(string $region, string $slug, array $settings=[], array $options=[]): array {
        $region = self::slug($region);
        $layout = self::layout($options['theme'] ?? null);
        $blocks = $layout[$region] ?? [];
        $maxSort = 0;
        foreach($blocks as $b){ $maxSort=max($maxSort, (int)($b['sort'] ?? 0)); }
        $block = self::createBlock($slug, $settings, array_replace(['sort'=>$maxSort+10], $options));
        $layout[$region][] = $block;
        self::saveLayout($layout, $options['theme'] ?? null);
        return $block;
    }

    public static function updateBlock(string $region, string $blockId, array $changes, ?string $theme=null): bool {
        $region = self::slug($region);
        $layout = self::layout($theme);
        if(empty($layout[$region]) || !is_array($layout[$region])) return false;
        foreach($layout[$region] as &$block){
            if((string)($block['id'] ?? '') === (string)$blockId){
                if(isset($changes['settings']) && is_array($changes['settings'])){
                    $changes['settings'] = array_replace($block['settings'] ?? [], $changes['settings']);
                }
                $block = array_replace($block, $changes);
                return self::saveLayout($layout, $theme);
            }
        }
        return false;
    }

    public static function removeBlock(string $region, string $blockId, ?string $theme=null): bool {
        $region = self::slug($region);
        $layout = self::layout($theme);
        if(empty($layout[$region]) || !is_array($layout[$region])) return false;
        $before = count($layout[$region]);
        $layout[$region] = array_values(array_filter($layout[$region], fn($b)=>(string)($b['id'] ?? '') !== (string)$blockId));
        if(count($layout[$region]) === $before) return false;
        return self::saveLayout($layout, $theme);
    }

    public static function registerTemplate(string $id, string $label, array $layout, array $options=[]): bool {
        $id = self::slug($id);
        if($id==='' || !$layout) return false;
        self::$templates[$id] = array_replace([
            'id'=>$id,
            'label'=>$label,
            'description'=>'',
            'layout'=>$layout,
            'source'=>'api',
        ], $options, ['id'=>$id,'label'=>$label,'layout'=>$layout]);
        return true;
    }

    public static function templates(): array { return self::$templates; }

    public static function applyTemplate(string $id, ?string $theme=null): bool {
        $id = self::slug($id);
        if(empty(self::$templates[$id]['layout']) || !is_array(self::$templates[$id]['layout'])) return false;
        return self::saveLayout(self::$templates[$id]['layout'], $theme);
    }

    public static function renderRegion(string $region, array $context=[]): string {
        return function_exists('omurga_render_region') ? omurga_render_region($region, $context) : '';
    }

    public static function blockDefaults(string $slug): array {
        return function_exists('omurga_block_defaults') ? omurga_block_defaults($slug) : [];
    }

    private static function seedDefaultRegions(): void {
        static $seeded=false; if($seeded) return; $seeded=true;
        $defaults = [
            ['header','Header','header',10,'Site üst alanı'],
            ['home_top','Ana sayfa üstü','home',20,'Ana sayfanın üst bölümü'],
            ['home_main','Ana içerik','home',30,'Ana sayfa içerik bölümü'],
            ['sidebar','Yan alan','sidebar',40,'Yan sütun bölümü'],
            ['post_inside','Yazı içi','post',50,'Yazı detay içi'],
            ['footer','Footer','footer',90,'Site alt alanı'],
        ];
        foreach($defaults as $r){ self::registerRegion($r[0], $r[1], $r[2], ['position'=>$r[3], 'description'=>$r[4], 'source'=>'core']); }
    }
}

function omurga_register_builder_region(string $id, string $label, string $context='page', array $options=[]): bool { return Omurga_BuilderApi::registerRegion($id,$label,$context,$options); }
function omurga_builder_regions(?string $context=null): array { return Omurga_BuilderApi::regions($context); }
function omurga_register_builder_block($id, array $definition=[]): bool { return Omurga_BuilderApi::registerBlock($id,$definition); }
function omurga_builder_field(string $type, string $label, $default='', array $options=[]): array { return Omurga_BuilderApi::field($type,$label,$default,$options); }
function omurga_builder_layout(?string $theme=null): array { return Omurga_BuilderApi::layout($theme); }
function omurga_builder_save_layout(array $layout, ?string $theme=null): bool { return Omurga_BuilderApi::saveLayout($layout,$theme); }
function omurga_builder_create_block(string $slug, array $settings=[], array $options=[]): array { return Omurga_BuilderApi::createBlock($slug,$settings,$options); }
function omurga_builder_add_block(string $region, string $slug, array $settings=[], array $options=[]): array { return Omurga_BuilderApi::addBlock($region,$slug,$settings,$options); }
function omurga_builder_update_block(string $region, string $blockId, array $changes, ?string $theme=null): bool { return Omurga_BuilderApi::updateBlock($region,$blockId,$changes,$theme); }
function omurga_builder_remove_block(string $region, string $blockId, ?string $theme=null): bool { return Omurga_BuilderApi::removeBlock($region,$blockId,$theme); }
function omurga_register_builder_template(string $id, string $label, array $layout, array $options=[]): bool { return Omurga_BuilderApi::registerTemplate($id,$label,$layout,$options); }
function omurga_builder_templates(): array { return Omurga_BuilderApi::templates(); }
function omurga_builder_apply_template(string $id, ?string $theme=null): bool { return Omurga_BuilderApi::applyTemplate($id,$theme); }
function omurga_builder_render_region(string $region, array $context=[]): string { return Omurga_BuilderApi::renderRegion($region,$context); }
