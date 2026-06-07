<?php if (!defined('OMURGA_INIT')) { http_response_code(403); exit; } ?>
<section class="om-user-center om-user-center-mode-<?=e($mode)?>">
  <style>
    .om-user-center{--om-accent:#f97316;--om-line:#e5e7eb;--om-muted:#64748b;display:block;width:100%;box-sizing:border-box}
    .om-user-center *{box-sizing:border-box}.om-user-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px}
    .om-user-head h2{margin:0;font-size:clamp(22px,3vw,34px)}.om-user-head p{margin:4px 0 0;color:var(--om-muted)}
    .om-user-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px}.om-user-tabs a{border:1px solid var(--om-line);border-radius:999px;padding:9px 13px;text-decoration:none;color:#334155;background:#fff;font-weight:700;font-size:14px}
    .om-user-tabs a.active{background:var(--om-accent);border-color:var(--om-accent);color:#fff}.om-user-card{border:1px solid var(--om-line);border-radius:14px;background:#fff;padding:18px;margin-bottom:14px}
    .om-user-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.om-user-center label{display:grid;gap:6px;font-weight:700;color:#334155}.om-user-center input,.om-user-center textarea,.om-user-center select{width:100%;border:1px solid var(--om-line);border-radius:10px;padding:10px 12px;font:inherit}
    .om-user-center textarea{min-height:190px;resize:vertical}.om-user-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.om-user-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--om-line);border-radius:10px;background:#fff;color:#1f2937;text-decoration:none;padding:10px 13px;font-weight:800;cursor:pointer}
    .om-user-btn.primary{background:var(--om-accent);border-color:var(--om-accent);color:#fff}.om-user-alert{border-radius:12px;padding:11px 13px;margin-bottom:14px;background:#f8fafc;border:1px solid var(--om-line)}.om-user-alert.success{background:#ecfdf5;border-color:#bbf7d0}.om-user-alert.error{background:#fef2f2;border-color:#fecaca}
    .om-user-table{width:100%;border-collapse:collapse}.om-user-table th,.om-user-table td{border-bottom:1px solid var(--om-line);padding:10px;text-align:left;vertical-align:top}.om-user-table small,.om-user-muted{color:var(--om-muted)}
    @media(max-width:720px){.om-user-head{display:block}.om-user-grid{grid-template-columns:1fr}.om-user-table,.om-user-table tbody,.om-user-table tr,.om-user-table td{display:block;width:100%}.om-user-table thead{display:none}.om-user-table td{padding:8px 0}}
  </style>
  <div class="om-user-head">
    <div>
      <h2><?=e(om_t('user_center.title','Hesabım'))?></h2>
      <p><?=e($user['name'] ?? $user['username'] ?? $user['email'] ?? '')?> · <?=e(omurga_role_labels()[$user['role'] ?? 'member'] ?? ($user['role'] ?? 'member'))?></p>
    </div>
    <a class="om-user-btn" href="<?=e(omurga_url('admin/logout.php'))?>"><?=e(om_t('blocks.logout','Çıkış'))?></a>
  </div>
  <?php if($mode === 'center'): ?>
    <nav class="om-user-tabs">
      <?php foreach($tabs as $key=>$label): ?><a class="<?=$tab===$key?'active':''?>" href="<?=e(omurga_user_center_url($key))?>"><?=e($label)?></a><?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <?php if(!empty($notice['message'])): ?><div class="om-user-alert <?=e($notice['type'] ?? '')?>"><?=e($notice['message'])?></div><?php endif; ?>

  <?php if($tab === 'profile'): ?>
    <form class="om-user-card" method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="profile">
      <div class="om-user-grid">
        <label><?=e(om_t('user_center.name','Ad Soyad'))?><input name="name" value="<?=e($user['name'] ?? '')?>" required></label>
        <label><?=e(om_t('user_center.email','E-posta'))?><input type="email" name="email" value="<?=e($user['email'] ?? '')?>" required></label>
      </div>
      <div class="om-user-actions"><button class="om-user-btn primary"><?=e(om_t('user_center.save','Kaydet'))?></button></div>
    </form>
  <?php elseif($tab === 'posts'): $rows=omurga_user_center_posts((int)$user['id']); ?>
    <div class="om-user-card">
      <table class="om-user-table"><thead><tr><th><?=e(om_t('user_center.post_title','Başlık'))?></th><th><?=e(om_t('user_center.status','Durum'))?></th><th></th></tr></thead><tbody>
      <?php if(!$rows): ?><tr><td colspan="3"><?=e(om_t('user_center.no_posts','Henüz yazınız yok.'))?></td></tr><?php endif; ?>
      <?php foreach($rows as $row): ?><tr><td><strong><?=e($row['title'])?></strong><br><small><?=e($row['created_at'] ?? '')?></small></td><td><?=e(omurga_public_status_label($row))?></td><td><a class="om-user-btn" href="<?=e(omurga_user_center_url('submit',['edit'=>(int)$row['id']]))?>"><?=e(om_t('user_center.edit','Düzenle'))?></a></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
  <?php elseif($tab === 'submit' && omurga_user_center_can_write()): ?>
    <form class="om-user-card" method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="post_save"><input type="hidden" name="post_id" value="<?=e((string)($editPost['id'] ?? 0))?>">
      <label><?=e(om_t('user_center.post_title','Başlık'))?><input name="title" value="<?=e($editPost['title'] ?? '')?>" required></label>
      <label>Slug<input name="slug" value="<?=e($editPost['slug'] ?? '')?>" placeholder="<?=e(om_t('user_center.slug_auto','Boşsa başlıktan oluşur'))?>"></label>
      <label><?=e(om_t('user_center.excerpt','Kısa açıklama'))?><input name="spot" value="<?=e($editPost['spot'] ?? '')?>"></label>
      <label><?=e(om_t('user_center.content','İçerik'))?><textarea name="content" required><?=e($editPost['content'] ?? '')?></textarea></label>
      <?php if(can('posts.publish')): ?><label><?=e(om_t('user_center.status','Durum'))?><select name="status"><?php foreach(omurga_status_options_for_current_user($editPost['status'] ?? 'draft') as $k=>$v): ?><option value="<?=e($k)?>" <?=($editPost['status'] ?? 'draft')===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select></label><?php endif; ?>
      <div class="om-user-actions"><button class="om-user-btn primary"><?=e(can('posts.publish') ? om_t('user_center.save','Kaydet') : om_t('user_center.submit_review','İncelemeye Gönder'))?></button></div>
    </form>
  <?php elseif($tab === 'notifications'): $nt=table_name('notifications'); $st=db()->prepare("SELECT * FROM $nt WHERE user_id IS NULL OR user_id=? ORDER BY is_read ASC,id DESC LIMIT 40"); $st->execute([(int)$user['id']]); $notes=$st->fetchAll(); ?>
    <form class="om-user-card" method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="notifications_read">
      <table class="om-user-table"><thead><tr><th></th><th><?=e(om_t('user_center.notifications','Bildirimler'))?></th><th><?=e(om_t('user_center.date','Tarih'))?></th></tr></thead><tbody>
      <?php if(!$notes): ?><tr><td colspan="3"><?=e(om_t('user_center.no_notifications','Bildirim yok.'))?></td></tr><?php endif; ?>
      <?php foreach($notes as $n): ?><tr><td><input type="checkbox" name="ids[]" value="<?=e((string)$n['id'])?>" <?=empty($n['is_read'])?'':'disabled'?>></td><td><strong><?=e($n['title'])?></strong><br><span class="om-user-muted"><?=e($n['message'] ?? '')?></span></td><td><?=e($n['created_at'] ?? '')?></td></tr><?php endforeach; ?>
      </tbody></table>
      <div class="om-user-actions"><button class="om-user-btn"><?=e(om_t('user_center.mark_read','Okundu yap'))?></button></div>
    </form>
  <?php elseif($tab === 'settings'): ?>
    <form class="om-user-card" method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="password">
      <div class="om-user-grid"><label><?=e(om_t('user_center.password','Yeni şifre'))?><input type="password" name="password" required></label><label><?=e(om_t('user_center.password_repeat','Şifre tekrar'))?><input type="password" name="password2" required></label></div>
      <div class="om-user-actions"><button class="om-user-btn primary"><?=e(om_t('user_center.password_save','Şifreyi Güncelle'))?></button></div>
    </form>
  <?php endif; ?>
</section>
