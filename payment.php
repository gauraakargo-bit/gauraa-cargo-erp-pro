<?php
require_once "../../config/auth.php";

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id <= 0){ header("Location:index.php"); exit; }

$stmt=$pdo->prepare("SELECT b.*, c.company_name FROM billing b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.id=? LIMIT 1");
$stmt->execute([$id]);
$bill=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$bill){ die("Invoice not found"); }

$error="";
if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount=(float)($_POST['amount'] ?? 0);
    $mode=trim($_POST['payment_mode'] ?? '');
    $date=trim($_POST['payment_date'] ?? date('Y-m-d'));
    $reference=trim($_POST['payment_reference'] ?? '');

    $grand=(float)$bill['grand_total'];
    $already=(float)$bill['received_amount'];
    $currentBalance=max(0,$grand-$already);

    if($amount<=0) $error="Received amount must be greater than 0.";
    elseif($amount>$currentBalance+0.001) $error="Received amount cannot be more than outstanding balance.";
    else{
        $newReceived=round($already+$amount,2);
        $newBalance=max(0,round($grand-$newReceived,2));
        $pstatus = $newBalance<=0.009 ? 'Paid' : ($newReceived>0 ? 'Partially Paid' : 'Pending');
        $legacyStatus = $pstatus==='Paid' ? 'Paid' : 'Pending';

        try{
            $pdo->beginTransaction();

            $p=$pdo->prepare("INSERT INTO customer_payments
                (customer_id, billing_id, payment_date, payment_mode, reference_no, amount, remarks)
                VALUES (?,?,?,?,?,?,?)");
            $p->execute([
                (int)$bill['customer_id'], $id, $date, $mode?:null,
                $reference?:null, $amount, 'Invoice payment: '.$bill['invoice_no']
            ]);

            $u=$pdo->prepare("UPDATE billing SET received_amount=?, balance_amount=?, payment_status=?, payment_mode=?, payment_date=?, payment_reference=?, status=? WHERE id=?");
            $u->execute([$newReceived,$newBalance,$pstatus,$mode?:null,$date?:null,$reference?:null,$legacyStatus,$id]);

            $pdo->commit();
            header("Location:index.php?payment=success");
            exit;
        }catch(Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            $error="Payment could not be saved: ".$e->getMessage();
        }
    }
}


$hist=$pdo->prepare("SELECT * FROM customer_payments WHERE billing_id=? ORDER BY payment_date DESC, id DESC");
$hist->execute([$id]);
$payments=$hist->fetchAll(PDO::FETCH_ASSOC);

include "../layouts/header.php";
include "../layouts/sidebar.php";
?>
<div class="content">
<?php include "../layouts/topbar.php"; ?>
<div class="container-fluid mt-4">
<div class="card shadow border-0">
<div class="card-header bg-white"><h3 class="mb-0">Receive Payment</h3></div>
<div class="card-body">
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="row mb-4">
<div class="col-md-3"><b>Invoice No</b><br><?=htmlspecialchars($bill['invoice_no'])?></div>
<div class="col-md-3"><b>Customer</b><br><?=htmlspecialchars($bill['company_name'] ?? '-')?></div>
<div class="col-md-2"><b>Grand Total</b><br>₹ <?=number_format((float)$bill['grand_total'],2)?></div>
<div class="col-md-2"><b>Received</b><br><span class="text-success">₹ <?=number_format((float)$bill['received_amount'],2)?></span></div>
<div class="col-md-2"><b>Outstanding</b><br><span class="text-danger">₹ <?=number_format((float)$bill['balance_amount'],2)?></span></div>
</div>

<?php if(($bill['payment_status'] ?? '')==='Paid'): ?>
<div class="alert alert-success">This invoice is fully paid.</div>
<a href="index.php" class="btn btn-secondary">Back</a>
<?php else: ?>
<form method="post">
<input type="hidden" name="id" value="<?=$id?>">
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Received Amount</label><input type="number" step="0.01" min="0.01" max="<?=htmlspecialchars($bill['balance_amount'])?>" name="amount" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Payment Mode</label><select name="payment_mode" class="form-select" required><option value="">Select</option><option>Cash</option><option>UPI</option><option>Bank Transfer</option><option>Cheque</option><option>Other</option></select></div>
<div class="col-md-3"><label class="form-label">Payment Date</label><input type="date" name="payment_date" value="<?=date('Y-m-d')?>" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Reference / UTR</label><input type="text" name="payment_reference" class="form-control" placeholder="Optional"></div>
</div>
<div class="mt-4"><button class="btn btn-success"><i class="fa fa-save"></i> Save Payment</button> <a href="index.php" class="btn btn-secondary">Cancel</a></div>
</form>
<?php endif; ?>
</div></div></div>

<div class="container-fluid mt-3">
<div class="card shadow border-0">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
<h4 class="mb-0">Payment History</h4>
<span class="text-muted"><?=count($payments)?> payment(s)</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-bordered table-hover mb-0 align-middle">
<thead class="table-dark">
<tr><th>#</th><th>Date</th><th>Amount</th><th>Mode</th><th>Reference / UTR</th><th>Remarks</th><th style="width:150px">Action</th></tr>
</thead>
<tbody>
<?php if(!$payments): ?>
<tr><td colspan="7" class="text-center py-4 text-muted">No payment history found for this invoice.</td></tr>
<?php else: foreach($payments as $i=>$pay): ?>
<tr>
<td><?=($i+1)?></td>
<td><?=htmlspecialchars($pay['payment_date'])?></td>
<td class="text-success fw-bold">₹ <?=number_format((float)$pay['amount'],2)?></td>
<td><?=htmlspecialchars($pay['payment_mode'] ?? '-')?></td>
<td><?=htmlspecialchars($pay['reference_no'] ?? '-')?></td>
<td><?=htmlspecialchars($pay['remarks'] ?? '-')?></td>
<td>
<a class="btn btn-sm btn-warning" href="payment_edit.php?id=<?=(int)$pay['id']?>"><i class="fa fa-edit"></i> Edit</a>
<a class="btn btn-sm btn-danger" href="payment_delete.php?id=<?=(int)$pay['id']?>" onclick="return confirm('Reverse/delete this payment? Invoice received amount and balance will be recalculated automatically.');"><i class="fa fa-trash"></i> Reverse</a>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<?php include "../layouts/footer.php"; ?>
