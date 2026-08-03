<?php
require_once "../../config/auth.php";
function e($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}

$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$customer=(int)($_GET['customer_id']??0);
$tracking=trim($_GET['tracking']??'');
$destination=trim($_GET['destination']??'');

$where=["b.invoice_date BETWEEN ? AND ?"];$params=[$from,$to];
if($customer){$where[]="b.customer_id=?";$params[]=$customer;}
if($tracking!==''){$where[]="(bi.gauraa_tracking LIKE ? OR bi.third_party_tracking LIKE ?)";$params[]="%$tracking%";$params[]="%$tracking%";}
if($destination!==''){$where[]="bi.destination LIKE ?";$params[]="%$destination%";}

$sql="SELECT b.id billing_id,b.invoice_no,b.invoice_date,b.billing_method invoice_method,b.grand_total,b.gst_amount,
c.company_name,bi.* FROM billing b
LEFT JOIN customers c ON c.id=b.customer_id
INNER JOIN billing_items bi ON bi.billing_id=b.id
WHERE ".implode(" AND ",$where)."
ORDER BY COALESCE(bi.item_date,b.invoice_date),b.id,bi.id";
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC);

$company=$pdo->query("SELECT * FROM company_profile ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC)?:[];
$coName=trim((string)($company['company_name']??'')) ?: 'GAURAA CARGO';
$coAddress=trim((string)($company['address']??''));
$coCity=trim((string)($company['city']??''));
$coState=trim((string)($company['state']??''));
$coPincode=trim((string)($company['pincode']??''));
$coGstin=trim((string)($company['gst_no']??''));
$coMobile=trim((string)($company['mobile']??''));
$coEmail=trim((string)($company['email']??''));
$address=trim(implode(', ',array_filter([$coAddress,$coCity,$coState,$coPincode])));

$hasVehicle=false;$hasBox=false;$hasWeight=false;
foreach($rows as $r){
  $im=strtoupper(trim((string)($r['invoice_method']??'')));
  $bm=strtoupper(trim((string)($r['billing_method']??'')));
  if($bm==='VEHICLE_SUMMARY')continue;
  if(strpos($im,'VEHICLE')===0 || strpos($bm,'VEHICLE')===0)$hasVehicle=true;
  elseif($im==='BOX'||$bm==='BOX')$hasBox=true;
  else $hasWeight=true;
}
$mode='MIXED';
if($hasVehicle&&!$hasBox&&!$hasWeight)$mode='VEHICLE';
elseif($hasBox&&!$hasVehicle&&!$hasWeight)$mode='BOX';
elseif($hasWeight&&!$hasVehicle&&!$hasBox)$mode='WEIGHT';

$clean=[];
foreach($rows as $r){
  if(strtoupper(trim((string)($r['billing_method']??'')))==='VEHICLE_SUMMARY')continue;
  $clean[]=$r;
}
$invoiceNos=array_values(array_unique(array_filter(array_column($clean,'invoice_no'))));
$customers=array_values(array_unique(array_filter(array_column($clean,'company_name'))));
$vehicles=array_values(array_unique(array_filter(array_column($clean,'vehicle_no'))));
$dates=[];
foreach($clean as $r){$d=$r['item_date']?:$r['invoice_date'];if($d)$dates[]=$d;}
sort($dates);
$period=$dates ? date('d-m-Y',strtotime($dates[0])).' to '.date('d-m-Y',strtotime(end($dates))) : date('d-m-Y',strtotime($from)).' to '.date('d-m-Y',strtotime($to));

$trips=count($clean);$km=$freight=$toll=$parking=$boxes=$actual=$charge=0;
foreach($clean as $r){
 $km+=(float)$r['total_km'];$freight+=(float)$r['freight'];$toll+=(float)$r['toll_amount'];$parking+=(float)$r['parking_amount'];
 $boxes+=(float)$r['boxes'];$actual+=(float)$r['actual_weight'];$charge+=(float)$r['chargeable_weight'];
}
$grand=0;$seen=[];
foreach($clean as $r){if(!isset($seen[$r['billing_id']])){$grand+=(float)$r['grand_total'];$seen[$r['billing_id']]=1;}}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Professional_Billing_MIS_'.$from.'_to_'.$to.'.xls"');
header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF";
?>
<html><head><meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;font-size:10pt;color:#111}
.title{font-size:20pt;font-weight:bold;text-align:center}
.subtitle{font-size:13pt;font-weight:bold;text-align:center}
.info{font-size:10pt}
.label{font-weight:bold}
table{border-collapse:collapse;width:100%}
.data th{background:#1f4e78;color:#fff;font-weight:bold;border:1px solid #777;padding:6px;text-align:center}
.data td{border:1px solid #aaa;padding:5px}
.summary td{border:1px solid #999;padding:7px;text-align:center}
.summary .v{font-size:12pt;font-weight:bold}
.total td{font-weight:bold;background:#e7e6e6}
.money{text-align:right}.num{text-align:right}.center{text-align:center}
</style></head><body>
<table>
<tr><td colspan="12" class="title"><?=e($coName)?></td></tr>
<?php if($address):?><tr><td colspan="12" class="center"><?=e($address)?></td></tr><?php endif;?>
<tr><td colspan="12" class="center"><?php if($coGstin):?>GSTIN: <?=e($coGstin)?> &nbsp;<?php endif;?><?php if($coMobile):?>Mobile: <?=e($coMobile)?> &nbsp;<?php endif;?><?php if($coEmail):?>Email: <?=e($coEmail)?><?php endif;?></td></tr>
<tr><td colspan="12" class="subtitle"><?= $mode==='VEHICLE'?'VEHICLE TRIP MIS REPORT':'BILLING MIS REPORT' ?></td></tr>
<tr><td colspan="12">&nbsp;</td></tr>
<tr class="info"><td colspan="2" class="label">Customer</td><td colspan="4"><?=e(implode(', ',$customers))?></td><td colspan="2" class="label">Invoice No.</td><td colspan="4"><?=e(implode(', ',$invoiceNos))?></td></tr>
<tr class="info"><td colspan="2" class="label">Report Period</td><td colspan="4"><?=e($period)?></td><td colspan="2" class="label">Billing Method</td><td colspan="4"><?=e($mode)?></td></tr>
<?php if($mode==='VEHICLE'):?><tr class="info"><td colspan="2" class="label">Vehicle</td><td colspan="10"><?=e(implode(', ',$vehicles))?></td></tr><?php endif;?>
<tr><td colspan="12">&nbsp;</td></tr>
</table>

<table class="summary">
<tr>
<?php if($mode==='VEHICLE'):?>
<td><span class="label">Trips</span><br><span class="v"><?=$trips?></span></td>
<td><span class="label">Total KM</span><br><span class="v"><?=number_format($km,2)?></span></td>
<td><span class="label">Freight</span><br><span class="v">₹<?=number_format($freight,2)?></span></td>
<td><span class="label">Toll</span><br><span class="v">₹<?=number_format($toll,2)?></span></td>
<td><span class="label">Parking</span><br><span class="v">₹<?=number_format($parking,2)?></span></td>
<td><span class="label">Grand Total</span><br><span class="v">₹<?=number_format($grand,2)?></span></td>
<?php elseif($mode==='BOX'):?>
<td><span class="label">Shipments</span><br><span class="v"><?=$trips?></span></td>
<td><span class="label">Boxes</span><br><span class="v"><?=number_format($boxes,0)?></span></td>
<td><span class="label">Freight</span><br><span class="v">₹<?=number_format($freight,2)?></span></td>
<td><span class="label">Grand Total</span><br><span class="v">₹<?=number_format($grand,2)?></span></td>
<?php else:?>
<td><span class="label">Shipments</span><br><span class="v"><?=$trips?></span></td>
<td><span class="label">Actual Weight</span><br><span class="v"><?=number_format($actual,2)?> KG</span></td>
<td><span class="label">Chargeable</span><br><span class="v"><?=number_format($charge,2)?> KG</span></td>
<td><span class="label">Freight</span><br><span class="v">₹<?=number_format($freight,2)?></span></td>
<td><span class="label">Grand Total</span><br><span class="v">₹<?=number_format($grand,2)?></span></td>
<?php endif;?>
</tr></table><br>

<table class="data">
<?php if($mode==='VEHICLE'):?>
<tr><th>S.No</th><th>Trip Date</th><th>Vehicle</th><th>Opening</th><th>Closing</th><th>KM</th><th>Rate</th><th>Freight</th><th>Toll</th><th>Parking</th></tr>
<?php foreach($clean as $i=>$r):?><tr>
<td class="center"><?=$i+1?></td><td class="center"><?=e(date('d-m-Y',strtotime($r['item_date']?:$r['invoice_date'])))?></td><td><?=e($r['vehicle_no'])?></td>
<td class="num"><?=number_format((float)$r['opening_meter'],2,'.','')?></td><td class="num"><?=number_format((float)$r['closing_meter'],2,'.','')?></td><td class="num"><?=number_format((float)$r['total_km'],2,'.','')?></td>
<td class="money"><?=number_format((float)$r['rate'],2,'.','')?></td><td class="money"><?=number_format((float)$r['freight'],2,'.','')?></td><td class="money"><?=number_format((float)$r['toll_amount'],2,'.','')?></td><td class="money"><?=number_format((float)$r['parking_amount'],2,'.','')?></td>
</tr><?php endforeach;?>
<tr class="total"><td colspan="5">TOTAL</td><td class="num"><?=number_format($km,2,'.','')?></td><td></td><td class="money"><?=number_format($freight,2,'.','')?></td><td class="money"><?=number_format($toll,2,'.','')?></td><td class="money"><?=number_format($parking,2,'.','')?></td></tr>
<?php elseif($mode==='BOX'):?>
<tr><th>S.No</th><th>Date</th><th>Gauraa Tracking</th><th>AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Boxes</th><th>Rate</th><th>Freight</th></tr>
<?php foreach($clean as $i=>$r):?><tr><td><?=$i+1?></td><td><?=e(date('d-m-Y',strtotime($r['item_date']?:$r['invoice_date'])))?></td><td><?=e($r['gauraa_tracking'])?></td><td><?=e($r['third_party_tracking'])?></td><td><?=e($r['sender_name'])?></td><td><?=e($r['receiver_name'])?></td><td><?=e($r['destination'])?></td><td><?=e($r['carrier'])?></td><td><?=$r['boxes']?></td><td><?=$r['rate']?></td><td><?=$r['freight']?></td></tr><?php endforeach;?>
<?php else:?>
<tr><th>S.No</th><th>Date</th><th>Gauraa Tracking</th><th>AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Actual</th><th>Chargeable</th><th>Rate</th><th>Freight</th></tr>
<?php foreach($clean as $i=>$r):?><tr><td><?=$i+1?></td><td><?=e(date('d-m-Y',strtotime($r['item_date']?:$r['invoice_date'])))?></td><td><?=e($r['gauraa_tracking'])?></td><td><?=e($r['third_party_tracking'])?></td><td><?=e($r['sender_name'])?></td><td><?=e($r['receiver_name'])?></td><td><?=e($r['destination'])?></td><td><?=e($r['carrier'])?></td><td><?=$r['actual_weight']?></td><td><?=$r['chargeable_weight']?></td><td><?=$r['rate']?></td><td><?=$r['freight']?></td></tr><?php endforeach;?>
<?php endif;?>
</table>
</body></html>
