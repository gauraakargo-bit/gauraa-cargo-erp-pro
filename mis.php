<?php
require_once "../../config/auth.php";
function h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
$from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??date('Y-m-d');
$customer=(int)($_GET['customer_id']??0); $tracking=trim($_GET['tracking']??''); $destination=trim($_GET['destination']??'');
$customers=$pdo->query("SELECT id,company_name FROM customers WHERE status='Active' ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);
$where=["b.invoice_date BETWEEN ? AND ?"]; $params=[$from,$to];
if($customer){$where[]="b.customer_id=?";$params[]=$customer;}
if($tracking!==''){$where[]="(bi.gauraa_tracking LIKE ? OR bi.third_party_tracking LIKE ?)";$params[]="%$tracking%";$params[]="%$tracking%";}
if($destination!==''){$where[]="bi.destination LIKE ?";$params[]="%$destination%";}
$sql="SELECT b.id billing_id,b.invoice_no,b.invoice_date,b.billing_type,b.billing_method,b.gst_amount,b.grand_total,b.status,c.company_name,bi.* FROM billing b LEFT JOIN customers c ON c.id=b.customer_id LEFT JOIN billing_items bi ON bi.billing_id=b.id WHERE ".implode(' AND ',$where)." ORDER BY COALESCE(bi.item_date,b.invoice_date),b.id,bi.id";
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
$tot=['actual'=>0,'vol'=>0,'charge'=>0,'boxes'=>0,'trips'=>0,'km'=>0,'freight'=>0,'gst'=>0,'grand'=>0];$seen=[];
$hasVehicle=false;$hasBox=false;$hasWeight=false;
foreach($rows as $r){
    $m=strtoupper(trim((string)($r['billing_method']??'')));
    $isVehicle=in_array($m,['VEHICLE','VEHICLE_KM','VEHICLE_FIXED'],true);
    $isSummary=($m==='VEHICLE_SUMMARY');
    if($isVehicle){
        $hasVehicle=true;
        $tot['trips']++;
        $tot['km']+=(float)$r['total_km'];
        $tot['freight']+=(float)$r['freight'];
    }elseif($isSummary){
        $hasVehicle=true;
    }elseif($m==='BOX'){
        $hasBox=true;
        $tot['boxes']+=(float)$r['boxes'];
        $tot['freight']+=(float)$r['freight'];
    }else{
        $hasWeight=true;
        $tot['actual']+=(float)$r['actual_weight'];
        $tot['vol']+=(float)$r['volumetric_weight'];
        $tot['charge']+=(float)$r['chargeable_weight'];
        $tot['boxes']+=(float)$r['boxes'];
        $tot['freight']+=(float)$r['freight'];
    }
    if(!isset($seen[$r['billing_id']])){
        $tot['gst']+=(float)$r['gst_amount'];
        $tot['grand']+=(float)$r['grand_total'];
        $seen[$r['billing_id']]=1;
    }
}

$viewMode='MIXED';
if($hasVehicle && !$hasBox && !$hasWeight) $viewMode='VEHICLE';
elseif($hasBox && !$hasVehicle && !$hasWeight) $viewMode='BOX';
elseif($hasWeight && !$hasVehicle && !$hasBox) $viewMode='WEIGHT';
include "../layouts/header.php"; include "../layouts/sidebar.php";
?>
<div class="content"><?php include "../layouts/topbar.php"; ?><div class="container-fluid mt-4 mb-5">
<div class="d-flex justify-content-between align-items-center mb-3"><h3><i class="fa fa-chart-line"></i> Billing MIS / Customer Summary</h3><div><a href="index.php" class="btn btn-secondary">Back</a> <a class="btn btn-success" href="export_mis.php?<?=h(http_build_query($_GET))?>">Excel/CSV</a> <button class="btn btn-dark" onclick="window.print()">Print / PDF</button></div></div>
<form class="card card-body mb-3"><div class="row g-2"><div class="col-md-2"><label>From</label><input type="date" name="from" value="<?=h($from)?>" class="form-control"></div><div class="col-md-2"><label>To</label><input type="date" name="to" value="<?=h($to)?>" class="form-control"></div><div class="col-md-3"><label>Customer</label><select name="customer_id" class="form-select"><option value="0">All Customers</option><?php foreach($customers as $c):?><option value="<?=$c['id']?>" <?=$customer==$c['id']?'selected':''?>><?=h($c['company_name'])?></option><?php endforeach?></select></div><div class="col-md-2"><label>Tracking/AWB</label><input name="tracking" value="<?=h($tracking)?>" class="form-control"></div><div class="col-md-2"><label>Destination</label><input name="destination" value="<?=h($destination)?>" class="form-control"></div><div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100">Go</button></div></div></form>
<div class="row g-2 mb-3">
<?php if($hasVehicle):?>
<div class="col"><div class="card card-body"><b>Trips</b><?=number_format($tot['trips'],0)?></div></div>
<div class="col"><div class="card card-body"><b>Total KM</b><?=number_format($tot['km'],2)?> KM</div></div>
<?php endif;?>
<?php if($hasWeight):?>
<div class="col"><div class="card card-body"><b>Weight</b><?=number_format($tot['actual'],2)?> KG</div></div>
<div class="col"><div class="card card-body"><b>Chargeable</b><?=number_format($tot['charge'],2)?> KG</div></div>
<?php endif;?>
<?php if($hasBox):?>
<div class="col"><div class="card card-body"><b>Boxes</b><?=number_format($tot['boxes'],0)?></div></div>
<?php endif;?>
<div class="col"><div class="card card-body"><b>Freight</b>₹<?=number_format($tot['freight'],2)?></div></div>
<div class="col"><div class="card card-body"><b>Grand Total</b>₹<?=number_format($tot['grand'],2)?></div></div>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-bordered table-sm mb-0" style="min-width:<?= $viewMode==='VEHICLE'?'1150':'1500' ?>px">
<thead class="table-dark">
<?php if($viewMode==='VEHICLE'):?>
<tr><th>S.No</th><th>Invoice</th><th>Date</th><th>Customer</th><th>Vehicle</th><th>Opening</th><th>Closing</th><th>KM</th><th>Rate</th><th>Freight</th><th>Toll</th><th>Parking</th></tr>
<?php elseif($viewMode==='BOX'):?>
<tr><th>S.No</th><th>Invoice</th><th>Date</th><th>Customer</th><th>Gauraa Tracking</th><th>Third Party AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Boxes</th><th>Rate</th><th>Freight</th></tr>
<?php elseif($viewMode==='WEIGHT'):?>
<tr><th>S.No</th><th>Invoice</th><th>Date</th><th>Customer</th><th>Gauraa Tracking</th><th>Third Party AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Actual</th><th>Vol.</th><th>Chargeable</th><th>Rate</th><th>Freight</th></tr>
<?php else:?>
<tr><th>S.No</th><th>Invoice</th><th>Date</th><th>Customer</th><th>Method</th><th>Gauraa Tracking</th><th>Third Party AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Actual</th><th>Chargeable</th><th>Boxes</th><th>Rate</th><th>Freight</th><th>Vehicle</th><th>KM</th></tr>
<?php endif;?>
</thead><tbody>
<?php $sn=0; foreach($rows as $r):
$m=strtoupper(trim((string)($r['billing_method']??'')));
if($viewMode==='VEHICLE' && $m==='VEHICLE_SUMMARY') continue;
$sn++;
?>
<?php if($viewMode==='VEHICLE'):?>
<tr><td><?=$sn?></td><td><a href="view.php?id=<?=$r['billing_id']?>"><?=h($r['invoice_no'])?></a></td><td><?=h($r['item_date']?:$r['invoice_date'])?></td><td><?=h($r['company_name'])?></td><td><?=h($r['vehicle_no'])?></td><td><?=number_format((float)$r['opening_meter'],2)?></td><td><?=number_format((float)$r['closing_meter'],2)?></td><td><?=number_format((float)$r['total_km'],2)?></td><td><?=number_format((float)$r['rate'],2)?></td><td><?=number_format((float)$r['freight'],2)?></td><td><?=number_format((float)$r['toll_amount'],2)?></td><td><?=number_format((float)$r['parking_amount'],2)?></td></tr>
<?php elseif($viewMode==='BOX'):?>
<tr><td><?=$sn?></td><td><a href="view.php?id=<?=$r['billing_id']?>"><?=h($r['invoice_no'])?></a></td><td><?=h($r['item_date']?:$r['invoice_date'])?></td><td><?=h($r['company_name'])?></td><td><?=h($r['gauraa_tracking'])?></td><td><?=h($r['third_party_tracking'])?></td><td><?=h($r['sender_name'])?></td><td><?=h($r['receiver_name'])?></td><td><?=h($r['destination'])?></td><td><?=h($r['carrier'])?></td><td><?=number_format((float)$r['boxes'],0)?></td><td><?=number_format((float)$r['rate'],2)?></td><td><?=number_format((float)$r['freight'],2)?></td></tr>
<?php elseif($viewMode==='WEIGHT'):?>
<tr><td><?=$sn?></td><td><a href="view.php?id=<?=$r['billing_id']?>"><?=h($r['invoice_no'])?></a></td><td><?=h($r['item_date']?:$r['invoice_date'])?></td><td><?=h($r['company_name'])?></td><td><?=h($r['gauraa_tracking'])?></td><td><?=h($r['third_party_tracking'])?></td><td><?=h($r['sender_name'])?></td><td><?=h($r['receiver_name'])?></td><td><?=h($r['destination'])?></td><td><?=h($r['carrier'])?></td><td><?=number_format((float)$r['actual_weight'],2)?></td><td><?=number_format((float)$r['volumetric_weight'],2)?></td><td><?=number_format((float)$r['chargeable_weight'],2)?></td><td><?=number_format((float)$r['rate'],2)?></td><td><?=number_format((float)$r['freight'],2)?></td></tr>
<?php else:?>
<tr><td><?=$sn?></td><td><a href="view.php?id=<?=$r['billing_id']?>"><?=h($r['invoice_no'])?></a></td><td><?=h($r['item_date']?:$r['invoice_date'])?></td><td><?=h($r['company_name'])?></td><td><?=h($r['billing_method'])?></td><td><?=h($r['gauraa_tracking'])?></td><td><?=h($r['third_party_tracking'])?></td><td><?=h($r['sender_name'])?></td><td><?=h($r['receiver_name'])?></td><td><?=h($r['destination'])?></td><td><?=h($r['carrier'])?></td><td><?=number_format((float)$r['actual_weight'],2)?></td><td><?=number_format((float)$r['chargeable_weight'],2)?></td><td><?=number_format((float)$r['boxes'],0)?></td><td><?=number_format((float)$r['rate'],2)?></td><td><?=number_format((float)$r['freight'],2)?></td><td><?=h($r['vehicle_no'])?></td><td><?=number_format((float)$r['total_km'],2)?></td></tr>
<?php endif;?>
<?php endforeach;?>
<?php if(!$sn):?><tr><td colspan="18" class="text-center p-4">No data found</td></tr><?php endif;?>
</tbody></table></div></div></div></div>
</div></div><style>@media print{.sidebar,.topbar,nav,.btn,form{display:none!important}.content{margin:0!important}}</style><?php include "../layouts/footer.php";?>
