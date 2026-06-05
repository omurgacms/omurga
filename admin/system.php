<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
omurga_migrate();
$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $action=$_POST['action'] ?? '';
        if($action==='maintenance'){
            update_setting('maintenance_mode', !empty($_POST['maintenance_mode'])?'1':'0');
            update_setting('maintenance_message', trim($_POST['maintenance_message'] ?? 'Sitemiz kısa süreli bakımda.'));
            log_activity('system.maintenance','Bakım modu güncellendi');
            $msg='Bakım modu ayarları kaydedildi.';
        }
        if($action==='run_migration'){
            create_database_backup();
            omurga_migrate();
            db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,created_by) VALUES (?,?,?,?,?)')->execute([setting('db_version','0.7.0'),OMURGA_VERSION,'completed','Migration kontrolleri çalıştırıldı.',$_SESSION['omurga_user_id']??null]);
            log_activity('system.migrate','Migration kontrolleri çalıştırıldı');
            $msg='Migration kontrolleri çalıştırıldı. Güncelleme öncesi veritabanı yedeği alındı.';
        }
        if($action==='upload_update'){
            if(empty($_FILES['update_zip']['name']) || ($_FILES['update_zip']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Zip dosyası seçilmedi.');
            $name=basename($_FILES['update_zip']['name']);
            if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='zip') throw new RuntimeException('Sadece .zip güncelleme paketi yüklenebilir.');
            $safe=slugify(pathinfo($name,PATHINFO_FILENAME)).'-'.date('YmdHis').'.zip';
            $target=omurga_update_dir().'/'.$safe;
            if(!move_uploaded_file($_FILES['update_zip']['tmp_name'],$target)) throw new RuntimeException('Güncelleme paketi yüklenemedi.');
            create_database_backup();
            db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([OMURGA_VERSION,OMURGA_VERSION,'uploaded','Güncelleme paketi yüklendi, uygulamaya hazır.',$safe,$_SESSION['omurga_user_id']??null]);
            log_activity('system.update_upload','Güncelleme paketi yüklendi: '.$safe);
            $msg='Güncelleme paketi yüklendi. Paket aşağıdaki listeden kontrol edilip uygulanabilir.';
        }
        if($action==='apply_update'){
            $package=$_POST['package_name'] ?? '';
            $from=OMURGA_VERSION;
            $result=omurga_apply_update_package($package);
            db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([$from,$result['version'],'completed','Güncelleme uygulandı. Kopyalanan dosya: '.$result['copied'].', atlanan dosya: '.$result['skipped'],$package,$_SESSION['omurga_user_id']??null]);
            omurga_cleanup_old_update_logs();
            if(setting('delete_update_package_after_apply','1')==='1'){
                try{ omurga_delete_update_package($package); }catch(Throwable $e){ omurga_write_error($e); }
            }
            $msg='Güncelleme uygulandı. Hedef sürüm: '.$result['version'].'. Sistem Sağlığı ekranını tekrar kontrol et.';
        }
        if($action==='delete_update_package'){
            $package=$_POST['package_name'] ?? '';
            omurga_delete_update_package($package);
            db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([OMURGA_VERSION,OMURGA_VERSION,'deleted','Yüklü güncelleme paketi silindi.',$package,$_SESSION['omurga_user_id']??null]);
            omurga_cleanup_old_update_logs();
            $msg='Güncelleme paketi silindi.';
        }
        if($action==='delete_all_update_packages'){
            $count=0;
            foreach(omurga_uploaded_update_packages() as $pkg){ if(omurga_delete_update_package($pkg['name'])) $count++; }
            db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,created_by) VALUES (?,?,?,?,?)')->execute([OMURGA_VERSION,OMURGA_VERSION,'deleted','Tüm yüklü güncelleme paketleri silindi. Silinen paket: '.$count,$_SESSION['omurga_user_id']??null]);
            omurga_cleanup_old_update_logs();
            $msg=$count.' adet güncelleme paketi silindi.';
        }
        if($action==='update_limits'){
            $logMax=max(5,min(200,(int)($_POST['update_log_max_count']??30)));
            $pkgMax=max(1,min(100,(int)($_POST['update_package_max_count']??10)));
            update_setting('update_log_max_count',(string)$logMax);
            update_setting('update_package_max_count',(string)$pkgMax);
            update_setting('delete_update_package_after_apply', !empty($_POST['delete_update_package_after_apply'])?'1':'0');
            omurga_cleanup_old_update_logs($logMax);
            omurga_cleanup_uploaded_update_packages($pkgMax);
            log_activity('system.update_limits','Güncelleme paket/kayıt limitleri güncellendi.');
            $msg='Güncelleme paket ve kayıt limitleri kaydedildi.';
        }
    }catch(Throwable $e){ omurga_write_error($e); $err=$e->getMessage(); }
}
$updateLogMax=omurga_update_log_max_count();
$updates=[]; try{ omurga_cleanup_old_update_logs($updateLogMax); $stmt=db()->prepare('SELECT * FROM '.table_name('update_logs').' ORDER BY id DESC LIMIT ?'); $stmt->bindValue(1,$updateLogMax,PDO::PARAM_INT); $stmt->execute(); $updates=$stmt->fetchAll(); }catch(Throwable $e){}
$packages=omurga_uploaded_update_packages();
$health=omurga_system_health_full();
$errors=omurga_recent_error_lines(100);
?>
<div class="page-head"><div><h1>Sistem Sağlığı ve Güncellemeler</h1><p>PHP, veritabanı, disk, cron, cache, SSL, upload izinleri ve hata kayıtlarını tek ekrandan kontrol eder.</p></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<?php $healthOk=0; $healthWarn=0; $healthErr=0; foreach($health as $h){ if(($h['level']??'ok')==='ok') $healthOk++; elseif(($h['level']??'ok')==='error') $healthErr++; else $healthWarn++; } ?>
<div class="system-grid">
<section class="system-card"><h2>Sistem Sağlığı</h2><p><span class="pill-ok">Sorunsuz: <?=e((string)$healthOk)?></span> <span class="pill-info">Uyarı: <?=e((string)$healthWarn)?></span> <span class="pill-bad">Hata: <?=e((string)$healthErr)?></span></p><table class="status-table"><thead><tr><th>Kontrol</th><th>Değer</th><th>Durum</th><th>Not</th></tr></thead><tbody><?php foreach($health as $row): ?><tr><td><?=e($row['name'])?></td><td><?=e($row['value'])?></td><td><span class="<?=$row['level']==='error'?'pill-bad':($row['level']==='warning'?'pill-info':'pill-ok')?>"><?=$row['level']==='error'?'Hata':($row['level']==='warning'?'Uyarı':'Sorunsuz')?></span></td><td><?=e($row['note'] ?? '')?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="system-card"><h2>Bakım Modu</h2><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="maintenance"><label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?=setting('maintenance_mode','0')==='1'?'checked':''?>> Bakım modunu aç</label><label>Bakım Mesajı<textarea name="maintenance_message" rows="4"><?=e(setting('maintenance_message','Sitemiz kısa süreli bakımda.'))?></textarea></label><button class="btn primary">Kaydet</button></form></section>
</div>

<div class="system-grid" style="margin-top:18px">
<section class="system-card"><h2>Migration / Veritabanı Güncelleme</h2><p>Eksik tabloları ve ayarları kontrol eder. İşlemden önce otomatik SQL yedeği alır.</p><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="run_migration"><button class="btn primary">Migration Kontrolünü Çalıştır</button></form></section>
<section class="system-card"><h2>Güncelleme Paketi Yükle</h2><p>Zip paketi önce <code>storage/updates</code> içine alınır, SQL yedeği oluşturulur. Sonra aşağıdaki listeden uygulanır.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="upload_update"><input type="file" name="update_zip" accept=".zip"><button class="btn primary">Zip Yükle ve Yedek Al</button></form></section>
</div>

<section class="card" style="margin-top:18px"><h2>Güncelleme Temizliği ve Limitler</h2><form method="post" class="form-grid"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update_limits"><label>Son Güncelleme Kayıt Limiti <input type="number" min="5" max="200" name="update_log_max_count" value="<?=e((string)$updateLogMax)?>"></label><label>Yüklü Paket Limiti <input type="number" min="1" max="100" name="update_package_max_count" value="<?=e(setting('update_package_max_count','10'))?>"></label><label class="check"><input type="checkbox" name="delete_update_package_after_apply" value="1" <?=setting('delete_update_package_after_apply','1')==='1'?'checked':''?>> Güncelleme uygulanınca zip paketini otomatik sil</label><button class="btn primary">Limitleri Kaydet</button></form></section>

<section class="card" style="margin-top:18px"><div class="card-head"><h2>Yüklü Güncelleme Paketleri</h2><?php if($packages): ?><form method="post" onsubmit="return confirm('Tüm yüklü güncelleme paketleri silinsin mi?')"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_all_update_packages"><button class="btn danger">Tüm Paketleri Sil</button></form><?php endif; ?></div><table><thead><tr><th>Paket</th><th>Boyut</th><th>Tarih</th><th>İşlem</th></tr></thead><tbody><?php foreach($packages as $p): ?><tr><td><?=e($p['name'])?></td><td><?=number_format((int)$p['size']/1024,1)?> KB</td><td><?=e($p['date'])?></td><td class="actions"><form method="post" onsubmit="return confirm('Güncelleme uygulanacak. Önce otomatik yedek alınır. Devam edilsin mi?')"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="apply_update"><input type="hidden" name="package_name" value="<?=e($p['name'])?>"><button class="btn primary">Güncellemeyi Uygula</button></form><form method="post" onsubmit="return confirm('Bu güncelleme paketi silinsin mi?')"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_update_package"><input type="hidden" name="package_name" value="<?=e($p['name'])?>"><button class="btn danger">Sil</button></form></td></tr><?php endforeach; if(!$packages): ?><tr><td colspan="4">Henüz yüklenmiş güncelleme paketi yok.</td></tr><?php endif; ?></tbody></table><p class="muted">Güvenlik için <code>config.php</code>, <code>uploads</code> ve <code>storage</code> klasörleri korunur. Güncelleme öncesi otomatik yedek alınır. Uygulanan paketler istenirse otomatik silinir.</p></section>
<section class="card" style="margin-top:18px"><h2>Son Güncelleme Kayıtları <small class="muted">Maksimum <?=$updateLogMax?> kayıt</small></h2><table><thead><tr><th>Tarih</th><th>Sürüm</th><th>Durum</th><th>Paket</th><th>Mesaj</th></tr></thead><tbody><?php foreach($updates as $u): ?><tr><td><?=e($u['created_at'])?></td><td><?=e(($u['from_version']??'').' → '.($u['to_version']??''))?></td><td><span class="pill-info"><?=e($u['status'])?></span></td><td><?=e($u['package_name']??'-')?></td><td><?=e($u['message']??'')?></td></tr><?php endforeach; if(!$updates): ?><tr><td colspan="5">Henüz güncelleme kaydı yok.</td></tr><?php endif; ?></tbody></table></section>
<section class="card" style="margin-top:18px"><h2>Son 100 Hata Kaydı</h2><?php if($errors): ?><pre class="codebox"><?=e(implode("\n",$errors))?></pre><?php else: ?><p>Henüz hata kaydı yok.</p><?php endif; ?></section>
<?php require __DIR__.'/_footer.php'; ?>
