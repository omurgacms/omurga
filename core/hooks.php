<?php
if(!defined('OMURGA_INIT')) { http_response_code(403); exit('Forbidden'); }

final class Omurga_Hooks {
    private static array $actions = [];
    private static array $filters = [];

    public static function addAction(string $hook, $callback, int $priority=10): void {
        if($hook==='' || !is_callable($callback)) return;
        self::$actions[$hook][$priority][]=$callback;
    }

    public static function doAction(string $hook, array $args=[]): void {
        if(empty(self::$actions[$hook])) return;
        ksort(self::$actions[$hook]);
        foreach(self::$actions[$hook] as $callbacks){
            foreach($callbacks as $callback){
                if(!is_callable($callback)) continue;
                try { $callback(...$args); }
                catch(Throwable $e) { self::log($e); }
            }
        }
    }

    public static function addFilter(string $hook, $callback, int $priority=10): void {
        if($hook==='' || !is_callable($callback)) return;
        self::$filters[$hook][$priority][]=$callback;
    }

    public static function applyFilters(string $hook, $value, array $args=[]) {
        if(empty(self::$filters[$hook])) return $value;
        ksort(self::$filters[$hook]);
        foreach(self::$filters[$hook] as $callbacks){
            foreach($callbacks as $callback){
                if(!is_callable($callback)) continue;
                try { $value=$callback($value, ...$args); }
                catch(Throwable $e) { self::log($e); }
            }
        }
        return $value;
    }

    private static function log(Throwable $e): void {
        if(function_exists('omurga_write_error')) omurga_write_error($e);
        else error_log($e->getMessage());
    }
}

function omurga_add_action(string $hook, $callback, int $priority=10): void { Omurga_Hooks::addAction($hook, $callback, $priority); }
function omurga_do_action(string $hook, ...$args): void { Omurga_Hooks::doAction($hook, $args); }
function omurga_add_filter(string $hook, $callback, int $priority=10): void { Omurga_Hooks::addFilter($hook, $callback, $priority); }
function omurga_apply_filters(string $hook, $value, ...$args){ return Omurga_Hooks::applyFilters($hook, $value, $args); }
function omurga_action(string $hook, ...$args): void { omurga_do_action($hook, ...$args); }
function omurga_filter(string $hook, $value, ...$args){ return omurga_apply_filters($hook, $value, ...$args); }
