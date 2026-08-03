<?php
require_once "../../config/auth.php";
$id=(int)($_GET['id']??0);
if($id>0){
  $_GET['id']=$id;
  include __DIR__.'/view.php';
  echo '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},300);});</script>';
  exit;
}
header('Location:index.php');exit;
