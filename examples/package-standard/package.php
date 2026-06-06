<?php
if(!defined('OMURGA_INIT')) exit;

Omurga::addPermission('package_standard.manage', 'Standart paket yönetimi');

Omurga::addPackageSettings('package-standard', [
    'enabled' => ['type'=>'checkbox', 'label'=>'Paket aktif', 'default'=>'1'],
    'title' => ['type'=>'text', 'label'=>'Başlık', 'default'=>'Standart Paket']
]);

Omurga::addPackageSettingsPage('package-standard', 'Standart Paket Ayarları');

Omurga::addPackageMigration('package-standard', '1.0.0', function(){
    // İlk migration örneği.
});
