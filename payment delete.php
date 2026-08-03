<?php
require_once "../../config/auth.php";
$pid=(int)($_GET['id'] ?? 0);
if($pid<=0){ header("Location:index.php"); exit; }

$q=$pdo->prepare("SELECT p.*, b.grand_total FROM customer_payments p JOIN billing b ON b.id=p.billing_id WHERE p.id=? LIMIT 1");
$q->execute([$pid]);
$p=$q->fetch(PDO::FETCH_ASSOC);
if(!$p){ die("Payment not found"); }

$bid=(int)$p['billing_id'];
try{
    $pdo->beginTransaction();
    $d=$pdo->prepare("DELETE FROM customer_payments WHERE id=?");
    $d->execute([$pid]);

    $s=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE billing_id=?");
    $s->execute([$bid]);
    $received=round((float)$s->fetchColumn(),2);
    $grand=(float)$p['grand_total'];
    $balance=max(0,round($grand-$received,2));
    $ps=$balance<=0.009?'Paid':($received>0?'Partially Paid':'Pending');
    $legacy=$ps==='Paid'?'Paid':'Pending';

    $u=$pdo->prepare("UPDATE billing SET received_amount=?, balance_amount=?, payment_status=?, status=? WHERE id=?");
    $u->execute([$received,$balance,$ps,$legacy,$bid]);
    $pdo->commit();
    header("Location:payment.php?id=".$bid."&reversed=1"); exit;
}catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    die("Payment reverse failed: ".htmlspecialchars($e->getMessage()));
}
