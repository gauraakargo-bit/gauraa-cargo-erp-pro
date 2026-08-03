<?php
require_once "../../config/auth.php";
$id=(int)($_GET['id']??0);
if($id<=0){header('Location:index.php');exit;}
try{
 $pdo->beginTransaction();
 $q=$pdo->prepare("SELECT id FROM bookings WHERE billing_id=?");$q->execute([$id]);$bookingIds=$q->fetchAll(PDO::FETCH_COLUMN);
 if($bookingIds){$u=$pdo->prepare("UPDATE bookings SET billing_status='Unbilled', billing_id=NULL WHERE billing_id=?");$u->execute([$id]);}
 $q=$pdo->prepare("DELETE FROM billing_items WHERE billing_id=?");$q->execute([$id]);
 $q=$pdo->prepare("DELETE FROM billing WHERE id=?");$q->execute([$id]);
 $pdo->commit();$_SESSION['success']='Invoice deleted successfully.';
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['error']='Delete failed: '.$e->getMessage();}
header('Location:index.php');exit;
