<?php
require_once __DIR__.'/config/config.php';require_once __DIR__.'/includes/functions.php';require_login();$id=(int)($_GET['id']??0);
if(!$id||!user_can_access_issue($pdo,$id)){http_response_code(404);exit('Complaint not found or you do not have access.');}
$s=$pdo->prepare('SELECT i.*,
c.name AS category_name,
u.name AS reporter_name,
d.name AS district_name,
m.name AS municipality_name
FROM issues i
JOIN categories c ON c.id=i.category_id
JOIN users u ON u.id=i.user_id
LEFT JOIN districts d ON d.id=i.district_id
LEFT JOIN municipalities m ON m.id=i.municipality_id
WHERE i.id=?');$s->execute([$id]);$issue=$s->fetch();if(!$issue)exit('Complaint not found.');
$h=$pdo->prepare('SELECT iu.*,u.name actor_name FROM issue_updates iu LEFT JOIN users u ON u.id=iu.user_id WHERE iu.issue_id=? ORDER BY iu.created_at DESC');$h->execute([$id]);$updates=$h->fetchAll();
$im=$pdo->prepare('SELECT * FROM issue_images WHERE issue_id=?');$im->execute([$id]);$images=$im->fetchAll();
$pageTitle='Track complaint';require __DIR__.'/includes/header.php';?>
<div class="page-heading"><div><span class="eyebrow">COMPLAINT TRACKER</span><h1><?=e($issue['complaint_code'])?></h1><p class="muted">Submitted <?=e(date('d M Y, h:i A',strtotime($issue['created_at'])))?></p></div><span class="status status-<?=e($issue['status'])?>"><?=e(status_label($issue['status']))?></span></div>
<div class="detail-grid"><section class="panel"><span class="eyebrow"><?=e($issue['category_name'])?></span><h2><?=e($issue['title'])?></h2><p><?=nl2br(e($issue['description']))?></p><div class="detail-list"><div><small>Location</small><strong><?=e($issue['location_text'])?></strong></div><div><small>District / Ward</small><strong><?=e($issue['district_name']??'Not specified')?> / <?=e($issue['ward_name']?:'Not specified')?></strong></div><div><small>Reported by</small><strong><?=e($issue['reporter_name'])?></strong></div></div><?php foreach($images as $img):?><img class="issue-photo" src="uploads/<?=e($img['file_name'])?>" alt="Uploaded issue evidence"><?php endforeach;?></section>
<section class="panel"><h2>Status timeline</h2><div class="timeline"><?php foreach($updates as $u):?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?=e(status_label($u['new_status']))?></strong><p><?=e($u['note']?:'Status updated.')?></p><small><?=e($u['actor_name']??'System')?> • <?=e(date('d M Y, h:i A',strtotime($u['created_at'])))?></small></div></div><?php endforeach;?></div></section></div>
<?php require __DIR__.'/includes/footer.php';?>
