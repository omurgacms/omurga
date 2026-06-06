<?php
if(!defined('OMURGA_INIT')) exit;

omurga_add_filter('omurga_menu_locations', function(array $locations){
    $locations['quick'] = 'Hızlı Linkler';
    return $locations;
});
