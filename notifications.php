<?php
require_once '../includes/config.php';
requireLogin();
$db=getDB();$uid=(int)$_SESSION['user_id'];
$action=$_POST['action']??'list';
if($action==='list'){
  $stmt=$db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
  $stmt->execute([$uid]);
  jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
}
if($action==='mark_all_read'){
  $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
  jsonResponse(['success'=>true]);
}
if($action==='delete'){
  $id=(int)($_POST['id']??0);
  $db->prepare("DELETE FROM notifications WHERE id=? AND user_id=?")->execute([$id,$uid]);
  jsonResponse(['success'=>true]);
}
jsonResponse(['success'=>false,'message'=>'Unknown action'],400);
