<?php
require_once "../../config/auth.php";
if($_SERVER['REQUEST_METHOD']!=='POST'){header("Location:index.php");exit;}
$id=(int)($_POST['id']??0); if(!$id){header("Location:index.php");exit;}
function num($v){return is_numeric($v)?(float)$v:0;}
function arr($k,$i,$d=''){return isset($_POST[$k])&&is_array($_POST[$k])?($_POST[$k][$i]??$d):$d;}
try{
 $pdo->beginTransaction();
 $q=$pdo->prepare("SELECT * FROM billing WHERE id=? FOR UPDATE"); $q->execute([$id]); $b=$q->fetch(PDO::FETCH_ASSOC);
 if(!$b) throw new Exception("Invoice not found.");
 $method=strtoupper((string)($b['billing_method']??'WEIGHT'));
 if(in_array($method,['VEHICLE','VEHICLE_KM','VEHICLE_FIXED'],true)) throw new Exception("Vehicle invoice editing is not supported on this screen.");

 $tracks=$_POST['gauraa_tracking']??[]; if(!is_array($tracks)) $tracks=[];
 $rows=[]; $freight=0; $div=5000;
 foreach($tracks as $i=>$track){
   $track=trim((string)$track);
   $receiver=trim((string)arr('receiver_name',$i));
   if($track==='' && $receiver==='') continue;
   $actual=num(arr('actual_weight',$i)); $l=num(arr('length',$i)); $w=num(arr('width',$i)); $h=num(arr('height',$i));
   $boxes=(int)max(0,num(arr('boxes',$i,1))); $rate=num(arr('rate',$i));
   $vol=($l*$w*$h)/$div; $charge=max($actual,$vol);
   if($method==='BOX'){$amount=$boxes*$rate;$qty=$boxes;$unit='BOX';}
   elseif(in_array($method,['FIXED','MONTHLY','MANUAL'],true)){$amount=num(arr('amount',$i));$qty=1;$unit='FIXED';}
   else{$amount=$charge*$rate;$qty=$charge;$unit='KG';}
   $freight+=$amount;
   $rows[]=[
    arr('item_date',$i,$b['invoice_date']),$track,trim((string)arr('third_party_tracking',$i)),
    trim((string)arr('sender_name',$i)), $receiver,trim((string)arr('destination',$i)),trim((string)arr('carrier',$i)),
    $actual,$l,$w,$h,$vol,$charge,$boxes,$qty,$unit,$rate,$amount
   ];
 }
 if(!$rows) throw new Exception("At least one shipment is required.");

 $fuel=num($b['fuel_charge']);$docket=num($b['docket_charge']);$fov=num($b['fov']);$packing=num($b['packing']);
 $loading=num($b['loading_charge']);$unloading=num($b['unloading_charge']);$other=num($b['other_charge']);$discount=num($b['discount']);
 $before=max(0,$freight+$fuel+$docket+$fov+$packing+$loading+$unloading+$other-$discount);
 $gstType=$b['gst_type']??'Extra'; $gstPct=num($b['gst_percent']);
 if($gstType==='Included'){ $grand=$before; $gst=$gstPct>0?$grand-($grand/(1+$gstPct/100)):0; $subtotal=$grand-$gst; }
 elseif($gstType==='No GST'){ $subtotal=$before;$gst=0;$grand=$before; }
 else{$subtotal=$before;$gst=$subtotal*$gstPct/100;$grand=$subtotal+$gst;}

 $received=num($b['received_amount']??0);
 $balance=max(0,$grand-$received);
 $status=($balance<=0 && $grand>0)?'Paid':(($received>0)?'Partially Paid':'Pending');
 $paymentStatus=$status;

 $u=$pdo->prepare("UPDATE billing SET invoice_date=?,subtotal=?,gst_amount=?,grand_total=?,balance_amount=?,status=?,payment_status=?,remarks=? WHERE id=?");
 $u->execute([$_POST['invoice_date']??$b['invoice_date'],$subtotal,$gst,$grand,$balance,$status,$paymentStatus,trim((string)($_POST['remarks']??'')),$id]);

 $pdo->prepare("DELETE FROM billing_items WHERE billing_id=?")->execute([$id]);
 $ins=$pdo->prepare("INSERT INTO billing_items
 (billing_id,booking_id,item_date,gauraa_tracking,third_party_tracking,sender_name,receiver_name,destination,carrier,actual_weight,length_cm,width_cm,height_cm,volumetric_divisor,volumetric_weight,chargeable_weight,boxes,billing_method,qty,unit,rate,freight,total,vehicle_no,route_name,opening_meter,closing_meter,total_km,included_km,extra_km,per_km_rate,toll_amount,parking_amount,driver_bata,fixed_amount)
 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
 foreach($rows as $r){
   [$date,$track,$third,$sender,$receiver,$dest,$carrier,$actual,$l,$w,$h,$vol,$charge,$boxes,$qty,$unit,$rate,$amount]=$r;
   $ins->execute([$id,null,$date,$track,$third,$sender,$receiver,$dest,$carrier,$actual,$l,$w,$h,$div,$vol,$charge,$boxes,$method,$qty,$unit,$rate,$amount,$amount,null,null,0,0,0,0,0,0,0,0,0,0]);
 }
 $pdo->commit();
 $_SESSION['success']="Invoice updated successfully.";
 header("Location:view.php?id=".$id);exit;
}catch(Throwable $e){
 if($pdo->inTransaction())$pdo->rollBack();
 $_SESSION['error']=$e->getMessage();
 header("Location:edit.php?id=".$id);exit;
}
