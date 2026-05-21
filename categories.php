<?php
require_once '../includes/config.php';
requireLogin();
$action=$_POST['action']??'list';
$db=getDB();$uid=(int)$_SESSION['user_id'];$isAdmin=isAdmin();
if($action==='list'){
  $type=$_POST['type']??'both';
  if($type==='income')$cond="AND (c.type='income' OR c.type='both')";
  elseif($type==='expense')$cond="AND (c.type='expense' OR c.type='both')";
  else $cond='';
  $stmt=$db->prepare("SELECT * FROM categories WHERE (user_id IS NULL OR user_id=?) $cond ORDER BY name");
  $stmt->execute([$uid]);
  jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
}
if($action==='create'){
  if(!$isAdmin&&!isset($_POST['user_budget'])){$userId=$uid;}else{$userId=null;}
  $name=sanitize($_POST['name']??'');$icon=sanitize($_POST['icon']??'📦');
  $color=sanitize($_POST['color']??'#6366f1');$type=$_POST['type']??'both';
  if(!$name)jsonResponse(['success'=>false,'message'=>'Name required'],400);
  $db->prepare("INSERT INTO categories (name,icon,color,type,user_id) VALUES (?,?,?,?,?)")->execute([$name,$icon,$color,$type,$isAdmin?null:$uid]);
  jsonResponse(['success'=>true,'message'=>'Category created!']);
}
if($action==='update'){
  $id=(int)($_POST['id']??0);$name=sanitize($_POST['name']??'');
  $icon=sanitize($_POST['icon']??'📦');$color=sanitize($_POST['color']??'#6366f1');$type=$_POST['type']??'both';
  if(!$isAdmin){$cond="id=? AND user_id=?";$p=[$name,$icon,$color,$type,$id,$uid];}
  else{$cond="id=?";$p=[$name,$icon,$color,$type,$id];}
  $db->prepare("UPDATE categories SET name=?,icon=?,color=?,type=? WHERE $cond")->execute($p);
  jsonResponse(['success'=>true,'message'=>'Category updated!']);
}
if($action==='delete'){
  $id=(int)($_POST['id']??0);
  if(!$isAdmin){$cond="id=? AND user_id=?";$p=[$id,$uid];}else{$cond="id=?";$p=[$id];}
  $db->prepare("DELETE FROM categories WHERE $cond")->execute($p);
  jsonResponse(['success'=>true,'message'=>'Category deleted!']);
}
jsonResponse(['success'=>false,'message'=>'Unknown action'],400);
