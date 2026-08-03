<?php
require_once "../../config/auth.php";
$id=(int)($_GET['id']??0);if(!$id){header('Location:index.php');exit;}function h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}function n($v){return number_format((float)($v??0),2);}
function amountWords($amount){
 $num=(int)round((float)$amount);
 if(class_exists('NumberFormatter')){
  $f=new NumberFormatter('en_IN',NumberFormatter::SPELLOUT);
  return 'Rupees '.ucwords(str_replace('-', ' ', $f->format($num))).' Only';
 }
 return 'Rupees '.number_format($num,0).' Only';
}
$q=$pdo->prepare("SELECT b.*,c.company_name,c.contact_person,c.mobile customer_mobile,c.email customer_email,c.gst_no customer_gst,c.address customer_address,c.city customer_city,c.state customer_state,c.pincode customer_pincode FROM billing b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.id=?");$q->execute([$id]);$b=$q->fetch(PDO::FETCH_ASSOC);if(!$b){$_SESSION['error']='Invoice not found';header('Location:index.php');exit;}
$q=$pdo->prepare("SELECT * FROM billing_items WHERE billing_id=? ORDER BY item_date ASC, id ASC");$q->execute([$id]);$items=$q->fetchAll(PDO::FETCH_ASSOC);

$isMonthlyVehicle=(($b['billing_method']??'')==='VEHICLE');
$vehicleRows=[];$vehicleSummary=null;
if($isMonthlyVehicle){
 foreach($items as $r){
   if(($r['billing_method']??'')==='VEHICLE') $vehicleRows[]=$r;
   elseif(($r['billing_method']??'')==='VEHICLE_SUMMARY') $vehicleSummary=$r;
 }
}
$vehicleNo=$vehicleSummary['vehicle_no']??($vehicleRows[0]['vehicle_no']??'');
$fixedCharge=(float)($vehicleSummary['fixed_amount']??0);
$tollParking=(float)($vehicleSummary['toll_amount']??0);
$totalVehicleKm=0;$totalFreeKm=0;$totalExtraKm=0;$totalExtraAmount=0;
foreach($vehicleRows as $r){
 $totalVehicleKm+=(float)$r['total_km'];
 $totalFreeKm+=(float)$r['included_km'];
 $totalExtraKm+=(float)$r['extra_km'];
 $totalExtraAmount+=(float)$r['freight'];
}
$company=$pdo->query("SELECT * FROM company_profile ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC)?:[];

$coName=trim((string)($company['company_name']??'')) ?: 'GAURAA CARGO';
$coAddress=trim((string)($company['address']??''));
$coCity=trim((string)($company['city']??''));
$coState=trim((string)($company['state']??'')) ?: 'Uttarakhand';
$coPincode=trim((string)($company['pincode']??''));
$coGstin=trim((string)($company['gst_no']??''));
$coPan=trim((string)($company['pan_no']??''));
$coStateProfile=trim((string)($company['state_code']??''));
$coMobile=trim((string)($company['mobile']??''));
$coEmail=trim((string)($company['email']??''));
$coWebsite=trim((string)($company['website']??''));
$bankName=trim((string)($company['bank_name']??''));
$accountName=trim((string)($company['account_name']??''));
$accountNo=trim((string)($company['account_no']??''));
$ifsc=trim((string)($company['ifsc_code']??''));
$bankBranch=trim((string)($company['bank_branch']??''));
$upiId=trim((string)($company['upi_id']??''));
$logoUrl=BASE_URL.'assets/invoice/gauraa-logo.jpg';
$qrUrl=BASE_URL.'assets/invoice/payment-qr.jpg';
$totalDays=count($vehicleRows);
$billingDates=[]; foreach($vehicleRows as $vr){if(!empty($vr['item_date']))$billingDates[]=strtotime($vr['item_date']);}
$periodFrom=$billingDates?date('d-m-Y',min($billingDates)):'';
$periodTo=$billingDates?date('d-m-Y',max($billingDates)):'';
$monthlyFixed=(float)$fixedCharge;
$perDayFixed=$monthlyFixed>0?$monthlyFixed/30:0;
$actualFixed=$perDayFixed*$totalDays;
$vehicleTaxable=$actualFixed+$totalExtraAmount+$tollParking;
$gstPct=(float)($b['gst_percent']??18); if($gstPct<=0)$gstPct=18;
$vehicleGst=$vehicleTaxable*$gstPct/100; $vehicleGrand=$vehicleTaxable+$vehicleGst;
$partyGstin=trim((string)($b['party_gstin']?:($b['customer_gst']??'')));
$coStateCode=strlen($coGstin)>=2?substr($coGstin,0,2):'05';
$partyStateCode=strlen($partyGstin)>=2?substr($partyGstin,0,2):'';
$isIntraState=($partyStateCode!=='' && $coStateCode===$partyStateCode);

include "../layouts/header.php";include "../layouts/sidebar.php";?>
<div class="content"><?php include "../layouts/topbar.php"?>
<div class="container-fluid invoice-wrap">
<div class="no-print toolbar"><h3>Tax Invoice</h3><div><a href="index.php" class="btn btn-secondary">Back</a> <a href="mis.php?customer_id=<?=$b['customer_id']?>" class="btn btn-success">Customer MIS</a> <a href="print.php?id=<?=$id?>" target="_blank" class="btn btn-dark">Print / PDF</a></div></div>

<div class="invoice-paper <?=(!$isMonthlyVehicle && count($items)<=3)?'short-invoice':((!$isMonthlyVehicle && count($items)<=40)?'medium-invoice':'long-invoice')?>">
<?php if($isMonthlyVehicle):?>
<div class="header">
 <div class="logo-cell"><img src="<?=h($logoUrl)?>" class="logo" alt="GAURAA CARGO"></div>
 <div class="company-cell">
  <div class="company-name"><?=h($coName)?></div>
  <div class="company-address"><div><?=h($coAddress)?></div><div><?=h(trim($coCity.($coPincode?' - '.$coPincode:'').($coState?', '.$coState:'').', India'))?></div></div>
  <div><?php if($coMobile):?>Mob: <?=h($coMobile)?><?php endif?><?php if($coEmail):?> &nbsp; | &nbsp; <?=h($coEmail)?><?php endif?></div>
  <?php if($coWebsite):?><div><?=h($coWebsite)?></div><?php endif?>
 </div>
 <div class="tax-cell">
  <div><b>GSTIN</b><span><?=h($coGstin)?></span></div>
  <div><b>PAN</b><span><?=h($coPan)?></span></div>
  <div><b>State Code</b><span><?=h($coStateProfile?:$coStateCode)?></span></div>
 </div>
</div>

<div class="info-grid">
 <div class="billto">
  <div class="blue-title">BILL TO</div>
  <div class="customer"><?=h($b['company_name'])?></div>
  <div><?=h($b['customer_address'])?></div>
  <div><?=h(trim($b['customer_city'].', '.$b['customer_state'].' - '.$b['customer_pincode']))?></div>
  <div><b>GSTIN:</b> <?=h($partyGstin)?></div>
  <div><b>Place of Supply:</b> <?=h($b['customer_state'])?></div>
 </div>
 <div class="tax-title"><strong>TAX INVOICE</strong><span>Original for Recipient</span></div>
 <div class="invoice-details">
  <div><b>Invoice No.</b><span><?=h($b['invoice_no'])?></span></div>
  <div><b>Invoice Date</b><span><?=h(date('d-m-Y',strtotime($b['invoice_date'])))?></span></div>
  <div><b>Place of Supply</b><span><?=h($b['customer_state'])?></span></div>
  <div><b>Vehicle No.</b><span><?=h($vehicleNo)?></span></div>
  <div><b>Billing Period</b><span><?=h($periodFrom)?> to <?=h($periodTo)?></span></div>
  <div><b>SAC Code</b><span>996812</span></div>
 </div>
</div>

<div class="statement-title">VEHICLE BILLING STATEMENT (<?=h($periodFrom)?> TO <?=h($periodTo)?>)</div>
<table class="vehicle-table">
<thead><tr><th>Date</th><th>Opening KM</th><th>Closing KM</th><th>Total KM<br>(Daily)</th><th>Per Day<br>100 KM (Free)</th><th>Extra KM</th><th>Rate<br>(₹ / Extra KM)</th><th>Extra Amount<br>(₹)</th></tr></thead>
<tbody>
<?php foreach($vehicleRows as $r):?>
<tr><td><?=h(date('d-m-Y',strtotime($r['item_date'])))?></td><td><?=n($r['opening_meter'])?></td><td><?=n($r['closing_meter'])?></td><td><?=n($r['total_km'])?></td><td><?=n($r['included_km'])?></td><td><?=n($r['extra_km'])?></td><td>₹<?=n($r['per_km_rate'])?></td><td>₹<?=n($r['freight'])?></td></tr>
<?php endforeach?>
<tr class="total-row"><td><b>TOTAL</b></td><td></td><td></td><td><b><?=n($totalVehicleKm)?></b></td><td><b><?=n($totalFreeKm)?></b></td><td><b><?=n($totalExtraKm)?></b></td><td></td><td><b>₹<?=n($totalExtraAmount)?></b></td></tr>
</tbody></table>

<div class="blue-title summary-title">SUMMARY</div>
<table class="summary-table"><tr><th>Total Days</th><th>Total KM</th><th>Free KM<br>(100 per day)</th><th>Extra KM</th><th>Rate per Extra KM</th><th>Total Extra Amount</th></tr>
<tr><td><?=$totalDays?></td><td><?=n($totalVehicleKm)?></td><td><?=n($totalFreeKm)?></td><td><?=n($totalExtraKm)?></td><td>₹<?=n($vehicleRows[0]['per_km_rate']??9)?></td><td>₹<?=n($totalExtraAmount)?></td></tr></table>

<div class="calc-grid">
 <div class="terms">
  <div class="terms-title">TERMS & CONDITIONS</div>
  <ol>
   <li>100 KM per day is included/free.</li>
   <li>Extra KM will be charged at the agreed rate per KM.</li>
   <li>Toll / Parking charges, if applicable, will be charged extra.</li>
   <li>Payment is subject to the agreed credit terms.</li>
   <li>Any dispute will be subject to Haridwar jurisdiction.</li>
   <li>This is a computer-generated tax invoice.</li>
  </ol>
  <div class="words"><b>Amount Chargeable (in words):</b><br><?=h(amountWords($vehicleGrand))?></div>
 </div>
 <table class="totals">
  <tr><th>Fixed Vehicle Charges (<?=$totalDays?> Days)<small>₹<?=n($monthlyFixed)?> ÷ 30 × <?=$totalDays?> Days</small></th><td>₹<?=n($actualFixed)?></td></tr>
  <tr><th>Extra KM Charges (<?=n($totalExtraKm)?> KM × ₹<?=n($vehicleRows[0]['per_km_rate']??9)?>)</th><td>₹<?=n($totalExtraAmount)?></td></tr>
  <tr><th>Toll / Parking Charges</th><td>₹<?=n($tollParking)?></td></tr>
  <tr class="before-tax"><th>Total Before Tax</th><td>₹<?=n($vehicleTaxable)?></td></tr>
  <?php if($isIntraState):?>
  <tr><th>CGST @ <?=n($gstPct/2)?>%</th><td>₹<?=n($vehicleGst/2)?></td></tr>
  <tr><th>SGST @ <?=n($gstPct/2)?>%</th><td>₹<?=n($vehicleGst/2)?></td></tr>
  <?php else:?><tr><th>IGST @ <?=n($gstPct)?>%</th><td>₹<?=n($vehicleGst)?></td></tr><?php endif?>
  <tr class="grand"><th>GRAND TOTAL</th><td>₹<?=n($vehicleGrand)?></td></tr>
 </table>
</div>

<div class="footer-grid">
 <div class="bank">
  <div class="blue-title">BANK DETAILS</div>
  <div><b>Bank Name:</b> <?=h($bankName)?></div>
  <div><b>A/C Name:</b> <?=h($accountName)?></div>
  <div><b>A/C No.:</b> <?=h($accountNo)?></div>
  <div><b>IFSC Code:</b> <?=h($ifsc)?></div>
  <div><b>Branch:</b> <?=h($bankBranch)?></div>
 </div>
 <div class="pay">
  <b>ONLINE PAYMENT ACCEPTED</b>
  <img src="<?=h($qrUrl)?>" class="qr" alt="Payment QR">
  <?php if($upiId):?><div><?=h($upiId)?></div><?php endif?>
 </div>
 <div class="signature">
  <b>For <?=h($coName)?></b>
  <div class="sign-name">Anil Kumar</div>
  <b>Authorised Signatory</b>
 </div>
</div>
<div class="thanks">Thank you for your business!</div>

<?php else:
$normalTaxable=(float)($b['taxable_amount']??0);
if($normalTaxable<=0){
 $normalTaxable=0;
 foreach($items as $r){$normalTaxable+=(float)($r['freight']??0);}
 $normalTaxable+=(float)($b['pod_charge']??0)+(float)($b['oda_charge']??0)+(float)($b['other_charge']??0);
}
$normalGst=(float)($b['gst_amount']??0);
if($normalGst<=0 && strtoupper((string)($b['gst_type']??''))!=='NO GST')$normalGst=$normalTaxable*$gstPct/100;
$normalGrand=(float)($b['grand_total']??0);
if($normalGrand<=0)$normalGrand=$normalTaxable+$normalGst;
?>
<div class="header">
 <div class="logo-cell"><img src="<?=h($logoUrl)?>" class="logo" alt="GAURAA CARGO"></div>
 <div class="company-cell">
  <div class="company-name"><?=h($coName)?></div>
  <div class="company-address"><div><?=h($coAddress)?></div><div><?=h(trim($coCity.($coPincode?' - '.$coPincode:'').($coState?', '.$coState:'').', India'))?></div></div>
  <div><?php if($coMobile):?>Mob: <?=h($coMobile)?><?php endif?><?php if($coEmail):?> &nbsp; | &nbsp; <?=h($coEmail)?><?php endif?></div>
  <?php if($coWebsite):?><div><?=h($coWebsite)?></div><?php endif?>
 </div>
 <div class="tax-cell">
  <div><b>GSTIN</b><span><?=h($coGstin)?></span></div>
  <div><b>PAN</b><span><?=h($coPan)?></span></div>
  <div><b>State Code</b><span><?=h($coStateProfile?:$coStateCode)?></span></div>
 </div>
</div>

<div class="info-grid">
 <div class="billto">
  <div class="blue-title">BILL TO</div>
  <div class="customer"><?=h($b['company_name'])?></div>
  <div><?=h($b['customer_address'])?></div>
  <div><?=h(trim($b['customer_city'].', '.$b['customer_state'].' - '.$b['customer_pincode']))?></div>
  <div><b>GSTIN:</b> <?=h($partyGstin)?></div>
  <div><b>Place of Supply:</b> <?=h($b['customer_state'])?></div>
 </div>
 <div class="tax-title"><strong>TAX INVOICE</strong><span>Original for Recipient</span></div>
 <div class="invoice-details">
  <div><b>Invoice No.</b><span><?=h($b['invoice_no'])?></span></div>
  <div><b>Invoice Date</b><span><?=h(date('d-m-Y',strtotime($b['invoice_date'])))?></span></div>
  <div><b>Place of Supply</b><span><?=h($b['customer_state'])?></span></div>
  <div><b>Billing Type</b><span><?=h($b['billing_method']??$b['billing_type']??'')?></span></div>
  <div><b>SAC Code</b><span>996812</span></div>
 </div>
</div>

<div class="statement-title">SHIPMENT BILLING STATEMENT</div>
<table class="normal-table">
<thead><tr><th>#</th><th>Date</th><th>Tracking / AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th><th>Actual</th><th>Vol.</th><th>Chargeable</th><th>Boxes</th><th>Rate</th><th>Amount</th></tr></thead>
<tbody>
<?php foreach($items as $i=>$r):
$trk=trim((string)($r['gauraa_tracking']??'')); if(!$trk)$trk=trim((string)($r['third_party_tracking']??''));
?>
<tr><td><?=$i+1?></td><td><?=h(!empty($r['item_date'])?date('d-m-Y',strtotime($r['item_date'])):'')?></td><td><?=h($trk)?></td><td><?=h($r['sender_name']??'')?></td><td><?=h($r['receiver_name']??'')?></td><td><?=h($r['destination']??'')?></td><td><?=h($r['carrier']??'')?></td><td><?=n($r['actual_weight']??0)?></td><td><?=n($r['volumetric_weight']??0)?></td><td><?=n($r['chargeable_weight']??0)?></td><td><?=h($r['boxes']??'')?></td><td>₹<?=n($r['rate']??0)?></td><td>₹<?=n($r['freight']??0)?></td></tr>
<?php endforeach?>
</tbody></table>

<div class="normal-bottom">
 <div class="terms"><div class="terms-title">TERMS & CONDITIONS</div>
 <ol><li>Goods are carried at owner's risk unless otherwise agreed.</li><li>GST will be charged as applicable.</li><li>Payment is subject to the agreed credit terms.</li><li>Any dispute will be subject to Haridwar jurisdiction.</li><li>This is a computer-generated tax invoice.</li></ol>
 <div class="words"><b>Amount Chargeable (in words):</b><br><?=h(amountWords($normalGrand))?></div></div>
 <table class="totals">
  <tr class="before-tax"><th>Taxable Amount</th><td>₹<?=n($normalTaxable)?></td></tr>
  <?php if($isIntraState):?>
  <tr><th>CGST @ <?=n($gstPct/2)?>%</th><td>₹<?=n($normalGst/2)?></td></tr>
  <tr><th>SGST @ <?=n($gstPct/2)?>%</th><td>₹<?=n($normalGst/2)?></td></tr>
  <?php else:?><tr><th>IGST @ <?=n($gstPct)?>%</th><td>₹<?=n($normalGst)?></td></tr><?php endif?>
  <tr class="grand"><th>GRAND TOTAL</th><td>₹<?=n($normalGrand)?></td></tr>
 </table>
</div>

<div class="footer-grid">
 <div class="bank"><div class="blue-title">BANK DETAILS</div><div><b>Bank Name:</b> <?=h($bankName)?></div><div><b>A/C Name:</b> <?=h($accountName)?></div><div><b>A/C No.:</b> <?=h($accountNo)?></div><div><b>IFSC Code:</b> <?=h($ifsc)?></div><div><b>Branch:</b> <?=h($bankBranch)?></div></div>
 <div class="pay"><b>ONLINE PAYMENT ACCEPTED</b><img src="<?=h($qrUrl)?>" class="qr" alt="Payment QR"><?php if($upiId):?><div><?=h($upiId)?></div><?php endif?></div>
 <div class="signature"><b>For <?=h($coName)?></b><div class="sign-name">Anil Kumar</div><b>Authorised Signatory</b></div>
</div>
<div class="thanks">Thank you for your business!</div>
<?php endif?>
</div></div></div>

<style>
:root{--blue:#09276a;--red:#d71920}
.invoice-wrap{padding:18px}.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.invoice-paper{max-width:950px;margin:auto;background:#fff;border:2px solid var(--blue);padding:12px;font-family:Arial,sans-serif;color:#111;font-size:11px}
.header{display:grid;grid-template-columns:150px 1fr 260px;align-items:center;border-bottom:2px solid var(--blue);padding-bottom:7px}.logo{max-width:150px;max-height:82px;object-fit:contain}.company-name{font-size:27px;font-weight:900;color:var(--blue)}.company-address{font-size:12px;font-weight:700;line-height:1.35}.tax-cell{border-left:1px solid #777;padding-left:12px}.tax-cell div,.invoice-details div{display:grid;grid-template-columns:90px 1fr;padding:2px 0}
.info-grid{display:grid;grid-template-columns:1.25fr .8fr 1.25fr;gap:8px;padding:8px 0}.billto{position:relative;padding:27px 8px 8px;border:1px solid #777;min-height:105px;box-sizing:border-box}.blue-title{background:var(--blue);color:#fff;font-weight:800;padding:4px 10px}.billto .blue-title{position:absolute;left:0;right:0;top:0}.customer{font-size:14px;font-weight:900;margin-bottom:2px}.tax-title{text-align:center;align-self:center;color:var(--blue)}.tax-title strong{display:block;font-size:24px}.tax-title span{display:inline-block;background:var(--blue);color:#fff;padding:4px 8px;border-radius:3px}.invoice-details{border:1px solid #777;padding:5px}
.statement-title{text-align:center;color:var(--blue);font-size:15px;font-weight:900;padding:5px 0}.vehicle-table,.normal-table,.summary-table,.totals{width:100%;border-collapse:collapse}.vehicle-table{font-size:8.5px}.vehicle-table th,.normal-table th{background:var(--blue);color:#fff}.normal-table{font-size:8.5px}.normal-table th,.normal-table td{border:1px solid #777;text-align:center;padding:2px}.vehicle-table th,.vehicle-table td,.summary-table th,.summary-table td{border:1px solid #777;text-align:center;padding:2px}.total-row{font-weight:800;background:#eef2fa}.summary-title{display:inline-block;margin-top:5px;min-width:140px}.summary-table{font-size:9px}.calc-grid{display:grid;grid-template-columns:45% 55%;gap:7px;margin-top:6px}.terms{border:1px solid #aaa;padding:6px}.terms-title{font-weight:900;color:var(--blue);font-size:12px}.terms ol{margin:4px 0 5px;padding-left:18px}.words{color:var(--red);font-weight:800}.totals th,.totals td{border:1px solid #999;padding:4px}.totals th{text-align:left}.totals th small{display:block;font-weight:normal}.totals td{text-align:right;font-weight:800}.before-tax{background:var(--blue);color:#fff}.grand{background:var(--red);color:#fff;font-size:14px}.normal-bottom{display:grid;grid-template-columns:45% 55%;gap:7px;margin-top:6px}.footer-grid{display:grid;grid-template-columns:1.1fr .8fr 1fr;gap:8px;margin-top:7px}.bank,.pay,.signature{border:1px solid var(--blue);min-height:125px;padding:6px}.bank .blue-title{margin:-6px -6px 6px}.bank div{margin:3px 0}.pay,.signature{text-align:center}.qr{width:140px;height:140px;object-fit:contain;display:block;margin:3px auto}.signature{display:flex;flex-direction:column;justify-content:space-between}.sign-name{font-family:cursive;font-size:25px;font-style:italic;margin-top:30px}.thanks{text-align:center;color:var(--blue);font-weight:800;font-style:italic;padding-top:4px}
@media print{
 @page{size:A4 portrait;margin:3mm}
 html,body{background:#fff!important;margin:0!important;padding:0!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
 .sidebar,.topbar,nav,.no-print{display:none!important}
 .content,.container-fluid,.invoice-wrap{margin:0!important;padding:0!important;width:100%!important;max-width:none!important}
 .invoice-paper{width:204mm!important;max-width:204mm!important;height:auto!important;min-height:0!important;box-sizing:border-box;margin:0 auto!important;padding:3mm!important;font-size:7.8px!important;overflow:visible!important;page-break-after:auto!important;break-after:auto!important}
 .header{grid-template-columns:38mm 1fr 51mm;padding-bottom:2.2mm}.logo{max-width:36mm!important;max-height:21mm!important}
 .company-name{font-size:17.5px!important;line-height:1.05}.company-address{font-size:7.8px!important;line-height:1.3;margin-top:.5mm}.company-address div{margin:.25mm 0}.tax-cell{padding-left:2mm;font-size:7.2px}.tax-cell div,.invoice-details div{grid-template-columns:20mm 1fr;padding:.38mm 0}
 .info-grid{gap:2mm;padding:1.8mm 0}.billto{padding:6mm 1.5mm 1.5mm!important;line-height:1.3;border:1px solid #777!important;min-height:24mm!important;box-sizing:border-box}.blue-title{padding:.9mm 2mm}.customer{font-size:8.6px!important}.tax-title strong{font-size:15px!important}.tax-title span{font-size:6.3px;padding:.7mm 1.4mm}.invoice-details{padding:.9mm;font-size:6.9px}
 .statement-title{font-size:9px!important;padding:.8mm 0}
 .vehicle-table{font-size:6.15px!important}.vehicle-table th,.vehicle-table td{padding:.48mm .35mm!important;line-height:1.02}
 .normal-table{font-size:6.2px!important}.normal-table th,.normal-table td{padding:.55mm .35mm!important;line-height:1.04}
 .summary-title{margin-top:.8mm}.summary-table{font-size:6.3px!important}.summary-table th,.summary-table td{padding:.65mm!important}
 .calc-grid,.normal-bottom{gap:1.3mm!important;margin-top:1.2mm!important}.terms{padding:1.2mm;font-size:6.6px;line-height:1.18}.terms-title{font-size:7.2px!important}.terms ol{margin:.7mm 0;padding-left:3.8mm}.words{font-size:6.6px;line-height:1.2}
 .totals th,.totals td{padding:.62mm!important;font-size:6.35px;line-height:1.1}.grand{font-size:8px!important}
 .footer-grid{gap:1.5mm;margin-top:1.2mm;page-break-inside:avoid!important;break-inside:avoid!important}
 .bank,.pay,.signature{min-height:28mm!important;height:28mm!important;padding:1.3mm;box-sizing:border-box}
 .bank .blue-title{margin:-1.3mm -1.3mm 1.2mm}.bank div{margin:.42mm 0;font-size:6.6px}
 .qr{width:25mm!important;height:25mm!important;object-fit:contain;margin:.3mm auto!important}
 .signature{font-size:6.8px}.sign-name{font-size:16px!important;margin-top:6mm}
 .thanks{padding-top:.5mm!important;font-size:6.5px!important;line-height:1!important;margin:0!important;page-break-before:avoid!important;break-before:avoid-page!important}
 .short-invoice{font-size:9.6px!important;padding:5mm!important}
 .short-invoice .header{grid-template-columns:40mm 1fr 53mm!important;padding-bottom:3mm!important}
 .short-invoice .logo{max-width:38mm!important;max-height:23mm!important}
 .short-invoice .company-name{font-size:20px!important}
 .short-invoice .company-address{font-size:8.8px!important}
 .short-invoice .tax-cell{font-size:8.2px!important}
 .short-invoice .info-grid{padding:3mm 0!important;gap:2.5mm!important}
 .short-invoice .billto{min-height:30mm!important;font-size:8.5px!important;line-height:1.45!important}
 .short-invoice .customer{font-size:10px!important}
 .short-invoice .invoice-details{font-size:8px!important}
 .short-invoice .tax-title strong{font-size:18px!important}
 .short-invoice .statement-title{font-size:11px!important;padding:1.5mm 0!important}
 .short-invoice .normal-table{font-size:7.8px!important}
 .short-invoice .normal-table th,.short-invoice .normal-table td{padding:1.2mm .65mm!important}
 .short-invoice .normal-bottom{margin-top:3mm!important;gap:2mm!important}
 .short-invoice .terms{font-size:8px!important;padding:2mm!important;min-height:36mm!important}
 .short-invoice .terms-title{font-size:9px!important}
 .short-invoice .words{font-size:8px!important}
 .short-invoice .totals th,.short-invoice .totals td{font-size:8px!important;padding:1.4mm!important}
 .short-invoice .grand{font-size:10px!important}
 .short-invoice .footer-grid{margin-top:3mm!important;gap:2mm!important}
 .short-invoice .bank,.short-invoice .pay,.short-invoice .signature{height:42mm!important;min-height:42mm!important;padding:2mm!important}
 .short-invoice .bank .blue-title{margin:-2mm -2mm 2mm!important}
 .short-invoice .bank div{font-size:8px!important;margin:1mm 0!important}
 .short-invoice .qr{width:32mm!important;height:32mm!important}
 .short-invoice .signature{font-size:8px!important}
 .short-invoice .sign-name{font-size:20px!important;margin-top:10mm!important}
 .short-invoice .thanks{font-size:8px!important;padding-top:1.5mm!important}

 .normal-table thead{display:table-header-group}
 .normal-table tr{page-break-inside:avoid!important;break-inside:avoid!important}
 .long-invoice .normal-bottom,.long-invoice .footer-grid{page-break-inside:avoid!important;break-inside:avoid!important}

 .medium-invoice{padding:4mm!important}
 .medium-invoice .header{grid-template-columns:40mm 1fr 53mm!important;padding-bottom:2.8mm!important}
 .medium-invoice .logo{max-width:38mm!important;max-height:23mm!important}
 .medium-invoice .company-name{font-size:19px!important}
 .medium-invoice .company-address{font-size:8.3px!important;line-height:1.35!important}
 .medium-invoice .tax-cell{font-size:7.7px!important}
 .medium-invoice .info-grid{padding:2.4mm 0!important;gap:2.2mm!important}
 .medium-invoice .billto{min-height:27mm!important;font-size:7.7px!important;line-height:1.35!important}
 .medium-invoice .customer{font-size:9.3px!important}
 .medium-invoice .invoice-details{font-size:7.5px!important}
 .medium-invoice .tax-title strong{font-size:17px!important}
 .medium-invoice .statement-title{font-size:10px!important;padding:1.2mm 0!important}
 .medium-invoice .normal-table{font-size:6.75px!important}
 .medium-invoice .normal-table th,.medium-invoice .normal-table td{padding:.72mm .38mm!important;line-height:1.08!important}
 .medium-invoice .normal-bottom{margin-top:1.8mm!important;gap:1.6mm!important}
 .medium-invoice .terms{font-size:7px!important;padding:1.5mm!important}
 .medium-invoice .terms-title{font-size:7.8px!important}
 .medium-invoice .words{font-size:7px!important}
 .medium-invoice .totals th,.medium-invoice .totals td{font-size:6.9px!important;padding:.85mm!important}
 .medium-invoice .grand{font-size:8.8px!important}
 .medium-invoice .footer-grid{margin-top:1.8mm!important;gap:1.7mm!important}
 .medium-invoice .bank,.medium-invoice .pay,.medium-invoice .signature{height:34mm!important;min-height:34mm!important;padding:1.6mm!important}
 .medium-invoice .bank .blue-title{margin:-1.6mm -1.6mm 1.4mm!important}
 .medium-invoice .bank div{font-size:7px!important;margin:.6mm 0!important}
 .medium-invoice .qr{width:28mm!important;height:28mm!important}
 .medium-invoice .signature{font-size:7.2px!important}
 .medium-invoice .sign-name{font-size:18px!important;margin-top:7mm!important}
 .medium-invoice .thanks{font-size:7px!important;padding-top:.8mm!important}

 /* FINAL A4 SHORT-INVOICE OVERRIDE */
 .short-invoice{width:204mm!important;min-height:270mm!important;padding:6mm!important;font-size:11px!important}
 .short-invoice .header{grid-template-columns:43mm 1fr 55mm!important;padding-bottom:4mm!important}
 .short-invoice .logo{max-width:41mm!important;max-height:26mm!important}
 .short-invoice .company-name{font-size:23px!important}.short-invoice .company-address{font-size:9.5px!important;line-height:1.45!important}.short-invoice .tax-cell{font-size:9px!important}
 .short-invoice .tax-cell div,.short-invoice .invoice-details div{grid-template-columns:23mm 1fr!important;padding:.7mm 0!important}
 .short-invoice .info-grid{grid-template-columns:1.3fr .8fr 1.2fr!important;gap:3mm!important;padding:4mm 0!important}
 .short-invoice .billto{min-height:38mm!important;padding:8mm 2.5mm 2.5mm!important;font-size:9.5px!important;line-height:1.55!important}
 .short-invoice .customer{font-size:11px!important}.short-invoice .tax-title strong{font-size:21px!important}.short-invoice .tax-title span{font-size:8px!important;padding:1mm 2mm!important}
 .short-invoice .invoice-details{font-size:9px!important;padding:2mm!important}.short-invoice .statement-title{font-size:12px!important;padding:2.5mm 0 1.5mm!important}
 .short-invoice .normal-table{font-size:9px!important}.short-invoice .normal-table th,.short-invoice .normal-table td{padding:2mm .7mm!important;line-height:1.2!important}
 .short-invoice .normal-bottom{margin-top:5mm!important;gap:3mm!important;grid-template-columns:46% 54%!important}
 .short-invoice .terms{min-height:50mm!important;padding:3mm!important;font-size:9px!important;line-height:1.45!important}.short-invoice .terms-title{font-size:10px!important}.short-invoice .terms ol{margin:2mm 0!important;padding-left:5mm!important}.short-invoice .words{font-size:9px!important;line-height:1.4!important}
 .short-invoice .totals th,.short-invoice .totals td{font-size:9px!important;padding:2.2mm!important}.short-invoice .grand{font-size:12px!important}
 .short-invoice .footer-grid{margin-top:5mm!important;gap:3mm!important;grid-template-columns:1.15fr .85fr 1.05fr!important}
 .short-invoice .bank,.short-invoice .pay,.short-invoice .signature{height:55mm!important;min-height:55mm!important;padding:3mm!important}
 .short-invoice .bank .blue-title{margin:-3mm -3mm 3mm!important}.short-invoice .bank div{font-size:9px!important;margin:1.5mm 0!important}.short-invoice .pay{font-size:9px!important}
 .short-invoice .qr{width:38mm!important;height:38mm!important;margin:1mm auto!important}.short-invoice .signature{font-size:9px!important}.short-invoice .sign-name{font-size:24px!important;margin-top:14mm!important}.short-invoice .thanks{font-size:9px!important;padding-top:2mm!important}

}
</style>

<style>
@media print {
 @page{size:A4 portrait;margin:8mm}
 html,body,.content,.container-fluid,.invoice-wrap{height:auto!important;min-height:0!important;overflow:visible!important}
 .invoice-paper.long-invoice{width:194mm!important;max-width:194mm!important;min-height:0!important;height:auto!important;border:0!important;padding:0!important;margin:0 auto!important;overflow:visible!important;font-size:9px!important}
 .long-invoice .header{grid-template-columns:38mm 1fr 50mm!important;padding-bottom:2.5mm!important}
 .long-invoice .logo{max-width:36mm!important;max-height:22mm!important}
 .long-invoice .company-name{font-size:19px!important}
 .long-invoice .company-address{font-size:8.3px!important}
 .long-invoice .tax-cell{font-size:7.8px!important}
 .long-invoice .info-grid{padding:2.5mm 0!important;gap:2mm!important}
 .long-invoice .billto{min-height:28mm!important;font-size:8px!important;line-height:1.35!important}
 .long-invoice .customer{font-size:9.5px!important}
 .long-invoice .invoice-details{font-size:7.8px!important}
 .long-invoice .tax-title strong{font-size:17px!important}
 .long-invoice .statement-title{font-size:10.5px!important;padding:1.5mm 0!important}
 .long-invoice .normal-table{width:100%!important;font-size:7.5px!important;table-layout:auto!important;page-break-inside:auto!important;break-inside:auto!important}
 .long-invoice .normal-table thead{display:table-header-group!important}
 .long-invoice .normal-table tbody{display:table-row-group!important}
 .long-invoice .normal-table tr{page-break-inside:avoid!important;break-inside:avoid!important}
 .long-invoice .normal-table th,.long-invoice .normal-table td{padding:1.15mm .55mm!important;line-height:1.18!important;overflow-wrap:anywhere!important}
 .long-invoice .normal-bottom{margin-top:3mm!important;gap:2mm!important;page-break-inside:avoid!important;break-inside:avoid!important}
 .long-invoice .terms{font-size:7.8px!important;line-height:1.3!important;padding:2mm!important}
 .long-invoice .terms-title,.long-invoice .words{font-size:7.8px!important}
 .long-invoice .totals th,.long-invoice .totals td{font-size:7.8px!important;padding:1.25mm!important}
 .long-invoice .grand{font-size:10px!important}
 .long-invoice .footer-grid{margin-top:3mm!important;gap:2mm!important;page-break-inside:avoid!important;break-inside:avoid!important}
 .long-invoice .bank,.long-invoice .pay,.long-invoice .signature{height:38mm!important;min-height:38mm!important;padding:2mm!important}
 .long-invoice .bank .blue-title{margin:-2mm -2mm 2mm!important}
 .long-invoice .bank div{font-size:7.8px!important;margin:.8mm 0!important}
 .long-invoice .qr{width:30mm!important;height:30mm!important}
 .long-invoice .signature{font-size:8px!important}
 .long-invoice .sign-name{font-size:19px!important;margin-top:8mm!important}
 .long-invoice .thanks{font-size:8px!important;padding-top:1mm!important}
}
</style>


<style>
.no-print.toolbar{margin:20px 0 15px 0!important;}
.invoice-wrap{padding:20px!important;}
</style>

<?php include "../layouts/footer.php"?>
