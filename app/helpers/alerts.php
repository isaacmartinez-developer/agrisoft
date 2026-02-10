<?php
require_once __DIR__ . '/../config/db.php';
function alert_create(string $type,string $title,string $body=''): void {
  $st=db()->prepare("INSERT INTO alerts(type,title,body) VALUES(?,?,?)");
  $st->execute([$type,$title,$body]);
  if (ALERT_EMAIL_ENABLED && ALERT_EMAIL_TO) { @mail(ALERT_EMAIL_TO, "[AGRISOFT] ".$title, $body); }
}
function run_alert_checks(): array {
  $low=db()->query("SELECT id,name,stock,low_stock_threshold FROM phyto_products WHERE stock <= low_stock_threshold")->fetchAll();
  foreach($low as $p){ alert_create('stock_baix',"Stock baix: {$p['name']}", "Stock actual: {$p['stock']}"); }
  $exp=db()->query("SELECT id,name,expiry_date FROM phyto_products WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchAll();
  foreach($exp as $p){ alert_create('caducitat',"Caduca aviat: {$p['name']}", "Data caducitat: {$p['expiry_date']}"); }
  $docs=db()->query("SELECT wd.id,w.full_name,wd.doc_type,wd.expires_on FROM worker_documents wd JOIN workers w ON w.id=wd.worker_id WHERE wd.expires_on IS NOT NULL AND wd.expires_on <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchAll();
  foreach($docs as $d){ alert_create('venciment',"Venciment document: {$d['full_name']}", "{$d['doc_type']} venç el {$d['expires_on']}"); }
  $tasks=db()->query("SELECT id,title,due_date FROM tasks WHERE status='pendent' AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchAll();
  foreach($tasks as $t){ alert_create('tasca',"Tasca vençuda: {$t['title']}", "Data límit: {$t['due_date']}"); }
  return ['stock_baix'=>count($low),'caducitat'=>count($exp),'venciments'=>count($docs),'tasques_vençudes'=>count($tasks)];
}
