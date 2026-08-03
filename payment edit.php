<?php
require_once "../../config/auth.php";
$pid=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($pid<=0){ header("Location:index.php"); exit; }

$q=$pdo->prepare("SELECT p.*, b.invoice_no, b.grand_total, b.id AS bid, c.company_name
FROM customer_payments p
JOIN billing b ON b.id=p.billing_id
LEFT JOIN customers c ON c.id=b.customer_id
WHERE p.id=? LIMIT 1");
$q->execute([$pid]);
$p=$q->fetch(PDO::FETCH_ASSOC);
if(!$p){ die("Payment not found"); }

$error="";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount=round((float)($_POST['amount'] ?? 0),2);
    $mode=trim($_POST['payment_mode'] ?? '');
    $date=trim($_POST['payment_date'] ?? '');
    $ref=trim($_POST['reference_no'] ?? '');

    $sum=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM customer_payments WHERE billing_id=? AND id<>?");
    $sum->execute([(int)$p['bid'],$pid]);
    $other=(float)$sum->fetchColumn();
    $grand=(float)$p['grand_total'];

    if($amount<=0) $error="Amount must be greater than 0.";
    elseif(($other+$amount)>$grand+0.009) $error="Total received cannot be more than invoice grand total.";
    else{
        try{
            $pdo->beginTransaction();
            $u=$pdo->prepare("UPDATE customer_payments SET amount=?, payment_mode=?, payment_date=?, reference_no=?, updated_at=NOW() WHERE id=?");
            $u->execute([$amount,$mode?:null,$date,$ref?:null,$pid]);

            $received=round($other+$amount,2);
            $balance=max(0,round($grand-$received,2));
            $ps=$balance<=0.009?'Paid':($received>0?'Partially Paid':'Pending');
            $legacy=$ps==='Paid'?'Paid':'Pending';

            $b=$pdo->prepare("UPDATE billing SET received_amount=?, balance_amount=?, payment_status=?, status=? WHERE id=?");
            $b->execute([$received,$balance,$ps,$legacy,(int)$p['bid']]);
            $pdo->commit();
            header("Location:payment.php?id=".(int)$p['bid']."&updated=1"); exit;
        }catch(Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            $error="Payment could not be updated: ".$e->getMessage();
        }
    }
}
include "../layouts/header.php";
include "../layouts/sidebar.php";
?>
<div class="content"><?php include "../layouts/topbar.php"; ?>
<div class="container-fluid mt-4">
<div class="card shadow border-0"><div class="card-header bg-white"><h3 class="mb-0">Edit Payment</h3></div>
<div class="card-body">
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="mb-3"><b>Invoice:</b> <?=htmlspecialchars($p['invoice_no'])?> &nbsp; <b>Customer:</b> <?=htmlspecialchars($p['company_name'] ?? '-')?></div>
<form method="post"><input type="hidden" name="id" value="<?=$pid?>">
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" value="<?=htmlspecialchars($p['amount'])?>" required></div>
<div class="col-md-3"><label class="form-label">Payment Mode</label><select class="form-select" name="payment_mode" required>
<?php foreach(['Cash','UPI','Bank Transfer','Cheque','Other'] as $m): ?><option <?=$p['payment_mode']===$m?'selected':''?>><?=htmlspecialchars($m)?></option><?php endforeach; ?>
</select></div>
<div class="col-md-3"><label class="form-label">Payment Date</label><input class="form-control" type="date" name="payment_date" value="<?=htmlspecialchars($p['payment_date'])?>" required></div>
<div class="col-md-3"><label class="form-label">Reference / UTR</label><input class="form-control" name="reference_no" value="<?=htmlspecialchars($p['reference_no'] ?? '')?>"></div>
</div>
<div class="mt-4"><button class="btn btn-success"><i class="fa fa-save"></i> Update Payment</button>
<a class="btn btn-secondary" href="payment.php?id=<?=(int)$p['bid']?>">Cancel</a></div>
</form></div></div></div></div>
<?php include "../layouts/footer.php"; ?>
