<?php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

require "../../config/auth.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location:index.php"); exit; }

function arr($name,$i,$default=''){ return $_POST[$name][$i] ?? $default; }
function num($v){ return max(0,(float)$v); }

try{
$pdo->beginTransaction();
$type=($_POST['billing_type']??'booking')==='direct'?'direct':'booking';
$customer=(int)($_POST['customer_id']??0);
if($customer<=0) throw new Exception("Please select customer.");
$date=$_POST['invoice_date']??date('Y-m-d');
$gstType=($_POST['gst_type']??'Extra');
$gstPercent=num($_POST['gst_percent']??18);
$remarks=trim($_POST['remarks']??'');
$method=$_POST['billing_method']??'WEIGHT';
$methodKey=strtoupper(trim((string)$method));
$methodKey=str_replace([' / ','/','-',' '],['_','_','_','_'],$methodKey);
if(in_array($methodKey,['MONTHLY_VEHICLE','VEHICLE','MONTHLYVEHICLE'],true)) $method='VEHICLE';
if($method==='VEHICLE') $type='direct';
elseif(in_array($methodKey,['KG_WEIGHT','KG__WEIGHT','WEIGHT','KG'],true)) $method='WEIGHT';
elseif(in_array($methodKey,['BOX_BILLING','BOX'],true)) $method='BOX';
if($method==='VEHICLE'){
    $type='direct';
}
$invoiceTo=$_POST['invoice_to']??'Customer';
$partyGstStatus=$_POST['party_gst_status']??'Registered';
$partyGstin=trim($_POST['party_gstin']??'');

$fuel=num($_POST['fuel_charge']??0); $docket=num($_POST['docket_charge']??0);
$fov=num($_POST['fov']??0); $packing=num($_POST['packing']??0);
$loading=num($_POST['loading_charge']??0); $unloading=num($_POST['unloading_charge']??0);
$other=num($_POST['other_charge']??0); $discount=num($_POST['discount']??0);

/* One common continuous sequence inside each financial year. Lock matching rows during generation. */
$ts=strtotime($date); if(!$ts) throw new Exception("Invalid invoice date.");
$y=(int)date('Y',$ts); $m=(int)date('n',$ts);
$a=$m>=4?$y:$y-1; $b=$a+1;
$prefix='GC/'.substr((string)$a,-2).'-'.substr((string)$b,-2).'/';
$q=$pdo->prepare("SELECT invoice_no FROM billing WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
$q->execute([$prefix.'%']); $last=$q->fetchColumn();
$next=$last?((int)substr($last,-6)+1):1;
$invoice=$prefix.str_pad((string)$next,6,'0',STR_PAD_LEFT);

/* Recalculate server-side. */
$freightTotal=0; $manualRows=[];
if($type==='booking'){
 $ids=array_values(array_filter(array_map('intval',$_POST['booking_ids']??[])));
 if(!$ids) throw new Exception("Please select at least one booking.");
 $get=$pdo->prepare("SELECT * FROM bookings WHERE id=? AND customer_id=? LIMIT 1");
 foreach($ids as $bid){ $get->execute([$bid,$customer]); if($r=$get->fetch(PDO::FETCH_ASSOC)){ $freightTotal+=num($r['total_amount']); $manualRows[]=$r; } }
 if(!$manualRows) throw new Exception("No valid booking selected.");
}else{
 /* Monthly Vehicle is date-wise meter billing, not courier shipment billing. */
 if($method==='VEHICLE'){
   $manualRows=[];
 }else{
 $tracks=$_POST['gauraa_tracking']??[];
 if(!is_array($tracks)||!count($tracks)) throw new Exception("Please add at least one manual shipment.");
 $div=max(1,num($_POST['volumetric_divisor']??5000));
 foreach($tracks as $i=>$track){
   $track=trim((string)$track); $receiver=trim((string)arr('receiver_name',$i));
   if($track==='' && $receiver==='') continue;
   $actual=num(arr('actual_weight',$i)); $l=num(arr('length',$i)); $w=num(arr('width',$i)); $h=num(arr('height',$i));
   $vol=($l*$w*$h)/$div; $charge=max($actual,$vol); $boxes=(int)max(0,num(arr('boxes',$i,1))); $rate=num(arr('rate',$i));
   $rowMethod=$method; $amount=0;
   if($rowMethod==='BOX') $amount=$boxes*$rate;
   elseif(in_array($rowMethod,['FIXED','MONTHLY','MANUAL'],true)) $amount=num(arr('amount',$i));
   elseif(!in_array($rowMethod,['VEHICLE_KM','VEHICLE_FIXED'],true)) $amount=$charge*$rate;
   $manualRows[]=['i'=>$i,'track'=>$track,'receiver'=>$receiver,'actual'=>$actual,'l'=>$l,'w'=>$w,'h'=>$h,'vol'=>$vol,'charge'=>$charge,'boxes'=>$boxes,'rate'=>$rate,'amount'=>$amount,'div'=>$div];
   $freightTotal+=$amount;
 }
 if(!$manualRows) throw new Exception("Please enter a valid manual shipment.");
 }
}

/* Monthly Vehicle billing - date wise meter readings */
$mvRows=[];
$mvVehicleNo=trim($_POST['mv_vehicle_no']??($_POST['vehicle_no']??''));
$mvFixed=num($_POST['mv_fixed_charge']??($_POST['monthly_fixed_charge']??0));
$mvTollParking=num($_POST['mv_toll_parking']??0);
if($mvTollParking<=0){
  $mvTollParking=num($_POST['mv_total_toll']??0)+num($_POST['mv_total_parking']??0);
}
$mvTotalKm=0; $mvTotalFree=0; $mvTotalExtra=0; $mvExtraAmount=0;

if($type==='direct' && $method==='VEHICLE'){
  if($mvVehicleNo==='') throw new Exception("Please enter Vehicle No.");

  $dates=$_POST['mv_date']??($_POST['vehicle_date']??[]);
  if(!is_array($dates) || !count($dates)) throw new Exception("Please add date-wise meter readings.");

  foreach($dates as $i=>$d){
    $d=trim((string)$d);
    if($d==='') continue;

    $op=num(arr('mv_opening_km',$i,arr('opening_km',$i,0)));
    $cl=num(arr('mv_closing_km',$i,arr('closing_km',$i,0)));
    if($cl < $op) throw new Exception("Closing KM cannot be less than Opening KM on ".$d);

    $total=max(0,$cl-$op);
    $free=num(arr('mv_free_km',$i,arr('free_km',$i,$_POST['mv_free_km_day']??($_POST['free_km_per_day']??100))));
    $extra=max(0,$total-$free);
    $rate=num(arr('mv_extra_rate',$i,arr('extra_km_rate',$i,$_POST['mv_default_extra_rate']??($_POST['extra_km_rate']??9))));
    $amount=$extra*$rate;
    $dayToll=num(arr('mv_toll',$i,arr('toll',$i,0)));
    $dayParking=num(arr('mv_parking',$i,arr('parking',$i,0)));

    $mvRows[]=[
      'date'=>$d,'opening'=>$op,'closing'=>$cl,'total'=>$total,
      'free'=>$free,'extra'=>$extra,'rate'=>$rate,'amount'=>$amount,
      'toll'=>$dayToll,'parking'=>$dayParking
    ];
    $mvTotalKm += $total;
    $mvTotalFree += $free;
    $mvTotalExtra += $extra;
    $mvExtraAmount += $amount;
  }

  if(!$mvRows) throw new Exception("Please add at least one valid date-wise meter reading.");

  if($mvTollParking<=0){
      foreach($mvRows as $vr){
          $mvTollParking += num($vr['toll']??0) + num($vr['parking']??0);
      }
  }

  $freightTotal=$mvFixed+$mvExtraAmount+$mvTollParking;

  /* Additional courier charges do not apply to Monthly Vehicle. */
  $fuel=$docket=$fov=$packing=$loading=$unloading=$other=$discount=0;
}

/* Legacy Vehicle billing */
$vehicleFixed=num($_POST['vehicle_fixed_charge']??0); $opening=num($_POST['opening_meter']??0); $closing=num($_POST['closing_meter']??0);
$totalKm=max(0,$closing-$opening); $included=num($_POST['included_km']??0); $extraKm=max(0,$totalKm-$included);
$kmRate=num($_POST['per_km_rate']??0); $toll=num($_POST['toll_amount']??0); $parking=num($_POST['parking_amount']??0); $bata=num($_POST['driver_bata']??0);
if($method==='VEHICLE_KM') $freightTotal=$vehicleFixed+($extraKm*$kmRate)+$toll+$parking+$bata;
if($method==='VEHICLE_FIXED') $freightTotal=$vehicleFixed+$toll+$parking+$bata;

$beforeTax=max(0,$freightTotal+$fuel+$docket+$fov+$packing+$loading+$unloading+$other-$discount);
if($gstType==='Included'){ $grand=$beforeTax; $gst=$gstPercent>0?$grand-($grand/(1+$gstPercent/100)):0; $subtotal=$grand-$gst; }
elseif($gstType==='No GST'){ $subtotal=$beforeTax; $gst=0; $grand=$beforeTax; }
else{ $subtotal=$beforeTax; $gst=$subtotal*$gstPercent/100; $grand=$subtotal+$gst; }

$q=$pdo->prepare("INSERT INTO billing
(invoice_no,invoice_date,customer_id,billing_type,billing_method,invoice_to,party_gst_status,party_gstin,gst_type,subtotal,gst_percent,gst_amount,fuel_charge,docket_charge,fov,packing,loading_charge,unloading_charge,other_charge,discount,grand_total,remarks,status,created_at)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
$q->execute([$invoice,$date,$customer,$type,$method,$invoiceTo,$partyGstStatus,$partyGstin,$gstType,$subtotal,$gstPercent,$gst,$fuel,$docket,$fov,$packing,$loading,$unloading,$other,$discount,$grand,$remarks,'Pending']);
$billingId=(int)$pdo->lastInsertId();

$item=$pdo->prepare("INSERT INTO billing_items
(billing_id,booking_id,item_date,gauraa_tracking,third_party_tracking,sender_name,receiver_name,destination,carrier,actual_weight,length_cm,width_cm,height_cm,volumetric_divisor,volumetric_weight,chargeable_weight,boxes,billing_method,qty,unit,rate,freight,total,vehicle_no,route_name,opening_meter,closing_meter,total_km,included_km,extra_km,per_km_rate,toll_amount,parking_amount,driver_bata,fixed_amount)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

if($type==='booking'){
 $mark=$pdo->prepare("UPDATE bookings SET billing_status='Billed',billing_id=? WHERE id=?");
 foreach($manualRows as $r){
   $item->execute([$billingId,$r['id'],$r['booking_date'],$r['booking_no'],$r['partner_awb']?:$r['awb_no'],$r['sender_name'],$r['receiver_name'],$r['destination'],null,$r['actual_weight'],0,0,0,5000,$r['volumetric_weight'],$r['chargeable_weight'],$r['total_boxes'],$r['rate_mode'],$r['chargeable_weight'],'KG',0,$r['total_amount'],$r['total_amount'],null,null,0,0,0,0,0,0,0,0,0,0]);
   $mark->execute([$billingId,$r['id']]);
 }
}else{
 if($method==='VEHICLE'){
   foreach($mvRows as $r){
     /* One billing_items row per date. Existing vehicle columns store the meter statement. */
     $item->execute([
       $billingId,null,$r['date'],'','','','','','',
       0,0,0,0,5000,0,0,1,'VEHICLE',1,'Day',
       $r['rate'],$r['amount'],$r['amount'],
       $mvVehicleNo,'',
       $r['opening'],$r['closing'],$r['total'],$r['free'],$r['extra'],$r['rate'],
       $r['toll']??0,$r['parking']??0,0,0
     ]);
   }

   /* Store monthly fixed/toll values in a separate summary row so print/MIS can read them. */
   $item->execute([
     $billingId,null,$date,'','','','','','',
     0,0,0,0,5000,0,0,1,'VEHICLE_SUMMARY',1,'Month',
     0,$mvFixed+$mvTollParking,$mvFixed+$mvTollParking,
     $mvVehicleNo,'',
     0,0,$mvTotalKm,$mvTotalFree,$mvTotalExtra,0,
     0,0,0,$mvFixed
   ]);
 }else{
 foreach($manualRows as $r){
   $i=$r['i'];
   $item->execute([$billingId,null,arr('manual_date',$i,$date),$r['track'],trim((string)arr('third_party_tracking',$i)),trim((string)arr('sender_name',$i)),trim((string)arr('receiver_name',$i)),trim((string)arr('destination',$i)),trim((string)arr('carrier',$i)),$r['actual'],$r['l'],$r['w'],$r['h'],$r['div'],$r['vol'],$r['charge'],$r['boxes'],$method,$r['charge'],'KG',$r['rate'],$r['amount'],$r['amount'],null,null,0,0,0,0,0,0,0,0,0,0]);
 }
 }
 if(in_array($method,['VEHICLE_KM','VEHICLE_FIXED'],true)){
   $item->execute([$billingId,null,$date,'','','','',trim($_POST['route_name']??''),'',0,0,0,0,5000,0,0,1,$method,1,'Trip',0,$freightTotal,$freightTotal,trim($_POST['vehicle_no']??''),trim($_POST['route_name']??''),$opening,$closing,$totalKm,$included,$extraKm,$kmRate,$toll,$parking,$bata,$vehicleFixed]);
 }
}

$pdo->commit();
$_SESSION['success']="Invoice ".$invoice." created successfully.";
header("Location:view.php?id=".$billingId); exit;
}catch(Throwable $e){
 if($pdo->inTransaction()) $pdo->rollBack();
 $_SESSION['error']=$e->getMessage();
 header("Location:add.php"); exit;
}
