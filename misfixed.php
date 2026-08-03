<?php
require_once "../../config/auth.php";

$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$customer=(int)($_GET['customer_id']??0);
$tracking=trim($_GET['tracking']??'');
$destination=trim($_GET['destination']??'');

$where=["b.invoice_date BETWEEN ? AND ?"];
$params=[$from,$to];
if($customer){$where[]="b.customer_id=?";$params[]=$customer;}
if($tracking!==''){
    $where[]="(bi.gauraa_tracking LIKE ? OR bi.third_party_tracking LIKE ?)";
    $params[]="%$tracking%"; $params[]="%$tracking%";
}
if($destination!==''){
    $where[]="bi.destination LIKE ?";
    $params[]="%$destination%";
}

$sql="SELECT b.invoice_no,b.invoice_date,c.company_name,b.billing_method,
bi.gauraa_tracking,bi.third_party_tracking,bi.sender_name,bi.receiver_name,
bi.destination,bi.carrier,bi.actual_weight,bi.volumetric_weight,
bi.chargeable_weight,bi.boxes,bi.rate,bi.freight,bi.vehicle_no,
bi.opening_meter,bi.closing_meter,bi.total_km,bi.toll_amount,bi.parking_amount
FROM billing b
LEFT JOIN customers c ON c.id=b.customer_id
LEFT JOIN billing_items bi ON bi.billing_id=b.id
WHERE ".implode(' AND ',$where)."
ORDER BY b.invoice_date,b.id,bi.id";

$q=$pdo->prepare($sql);
$q->execute($params);
$rows=$q->fetchAll(PDO::FETCH_ASSOC);

/* Detect the report type from actual returned rows */
$hasVehicle=false; $hasBox=false; $hasWeight=false;
foreach($rows as $r){
    $m=strtoupper(trim((string)($r['billing_method']??'')));
    if(in_array($m,['VEHICLE','VEHICLE_KM','VEHICLE_FIXED','VEHICLE_SUMMARY'],true)){
        $hasVehicle=true;
    }elseif($m==='BOX'){
        $hasBox=true;
    }else{
        $hasWeight=true;
    }
}

$viewMode='MIXED';
if($hasVehicle && !$hasBox && !$hasWeight) $viewMode='VEHICLE';
elseif($hasBox && !$hasVehicle && !$hasWeight) $viewMode='BOX';
elseif($hasWeight && !$hasVehicle && !$hasBox) $viewMode='WEIGHT';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="billing_mis_'.$from.'_to_'.$to.'.csv"');
echo "\xEF\xBB\xBF";
$o=fopen('php://output','w');

if($viewMode==='VEHICLE'){
    fputcsv($o,['Invoice','Date','Customer','Vehicle','Opening','Closing','KM','Rate','Freight','Toll','Parking']);
    foreach($rows as $r){
        $m=strtoupper(trim((string)($r['billing_method']??'')));
        if($m==='VEHICLE_SUMMARY') continue;
        fputcsv($o,[
            $r['invoice_no'],$r['invoice_date'],$r['company_name'],$r['vehicle_no'],
            $r['opening_meter'],$r['closing_meter'],$r['total_km'],$r['rate'],
            $r['freight'],$r['toll_amount'],$r['parking_amount']
        ]);
    }
}elseif($viewMode==='BOX'){
    fputcsv($o,['Invoice','Date','Customer','Gauraa Tracking','Third Party AWB','Sender','Receiver','Destination','Carrier','Boxes','Rate','Freight']);
    foreach($rows as $r){
        fputcsv($o,[
            $r['invoice_no'],$r['invoice_date'],$r['company_name'],$r['gauraa_tracking'],
            $r['third_party_tracking'],$r['sender_name'],$r['receiver_name'],$r['destination'],
            $r['carrier'],$r['boxes'],$r['rate'],$r['freight']
        ]);
    }
}elseif($viewMode==='WEIGHT'){
    fputcsv($o,['Invoice','Date','Customer','Gauraa Tracking','Third Party AWB','Sender','Receiver','Destination','Carrier','Actual Weight','Volumetric Weight','Chargeable Weight','Rate','Freight']);
    foreach($rows as $r){
        fputcsv($o,[
            $r['invoice_no'],$r['invoice_date'],$r['company_name'],$r['gauraa_tracking'],
            $r['third_party_tracking'],$r['sender_name'],$r['receiver_name'],$r['destination'],
            $r['carrier'],$r['actual_weight'],$r['volumetric_weight'],$r['chargeable_weight'],
            $r['rate'],$r['freight']
        ]);
    }
}else{
    fputcsv($o,['Invoice','Date','Customer','Method','Gauraa Tracking','Third Party AWB','Sender','Receiver','Destination','Carrier','Actual Weight','Chargeable Weight','Boxes','Rate','Freight','Vehicle','KM']);
    foreach($rows as $r){
        $m=strtoupper(trim((string)($r['billing_method']??'')));
        if($m==='VEHICLE_SUMMARY') continue;
        fputcsv($o,[
            $r['invoice_no'],$r['invoice_date'],$r['company_name'],$r['billing_method'],
            $r['gauraa_tracking'],$r['third_party_tracking'],$r['sender_name'],$r['receiver_name'],
            $r['destination'],$r['carrier'],$r['actual_weight'],$r['chargeable_weight'],
            $r['boxes'],$r['rate'],$r['freight'],$r['vehicle_no'],$r['total_km']
        ]);
    }
}
fclose($o);
exit;
