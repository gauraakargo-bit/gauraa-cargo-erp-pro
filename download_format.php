<?php
require_once "../../config/auth.php";
header('Content-Type:text/csv; charset=UTF-8');
header('Content-Disposition:attachment; filename="gauraa_manual_billing_import_format.csv"');
echo "\xEF\xBB\xBF";
$o=fopen('php://output','w');
fputcsv($o,['customer_id','invoice_date','gst_type','gst_percent','invoice_to','billing_method','party_gst_status','party_gstin','shipment_date','gauraa_tracking','third_party_tracking','sender','receiver','destination','carrier','actual_weight','length_cm','width_cm','height_cm','boxes','rate','amount','remarks']);
fputcsv($o,['1',date('Y-m-d'),'Extra','18','Sender','WEIGHT','Registered','',date('Y-m-d'),'GC-TRACK-001','','Sender Name','Receiver Name','Haridwar','',10,30,20,20,1,40,'','Sample row - delete before import']);
fclose($o);exit;
