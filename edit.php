<?php
require_once "../../config/auth.php";
$id=(int)($_GET['id']??0);
if(!$id){header("Location:index.php");exit;}
function h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
$q=$pdo->prepare("SELECT b.*,c.company_name FROM billing b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.id=?");
$q->execute([$id]); $b=$q->fetch(PDO::FETCH_ASSOC);
if(!$b){header("Location:index.php");exit;}
$q=$pdo->prepare("SELECT * FROM billing_items WHERE billing_id=? ORDER BY item_date DESC,id DESC");
$q->execute([$id]); $items=$q->fetchAll(PDO::FETCH_ASSOC);
$method=strtoupper((string)($b['billing_method']??'WEIGHT'));
$isBox=($method==='BOX');
include "../layouts/header.php";
?>
<?php
$senderSuggestions=[]; $receiverSuggestions=[];
foreach($items as $x){
  $sv=trim((string)($x['sender_name']??'')); $rv=trim((string)($x['receiver_name']??''));
  if($sv!=='') $senderSuggestions[$sv]=true;
  if($rv!=='') $receiverSuggestions[$rv]=true;
}
?>
<datalist id="senderSuggestions">
<?php foreach(array_keys($senderSuggestions) as $v):?><option value="<?=h($v)?>"><?php endforeach;?>
</datalist>
<datalist id="receiverSuggestions">
<?php foreach(array_keys($receiverSuggestions) as $v):?><option value="<?=h($v)?>"><?php endforeach;?>
</datalist>

<form method="post" action="update.php" id="invoiceEditForm">
<input type="hidden" name="id" value="<?=$id?>">
<div class="card shadow border-0 mb-4">
 <div class="card-header"><h4 class="mb-0">Edit Invoice <?=h($b['invoice_no'])?></h4></div>
 <div class="card-body">
  <div class="row g-4">
   <div class="col-md-3"><label>Invoice Date</label><input type="date" name="invoice_date" class="form-control" value="<?=h($b['invoice_date'])?>" required></div>
   <div class="col-md-4"><label>Customer</label><input class="form-control" value="<?=h($b['company_name'])?>" readonly></div>
   <div class="col-md-2"><label>Billing Method</label><input class="form-control" value="<?=h($method)?>" readonly></div>
   <div class="col-md-3"><label>Remarks</label><input name="remarks" class="form-control" value="<?=h($b['remarks'])?>"></div>
  </div>
 </div>
</div>

<div class="card shadow border-0">
 <div class="card-header d-flex justify-content-between align-items-center">
  <h5 class="mb-0">Shipment Entries</h5>
  <button type="button" class="btn btn-success btn-sm" id="addShipment">+ Add Shipment</button>
 </div>
 <div class="card-body p-2">
  <div class="table-responsive">
   <table class="table table-bordered table-sm align-middle" id="shipTable">
    <thead class="table-dark"><tr>
     <th>#</th><th>Date</th><th>Gauraa Tracking</th><th>Third Party AWB</th><th>Sender</th><th>Receiver</th><th>Destination</th><th>Carrier</th>
     <?php if($isBox):?><th>Boxes</th><?php else:?><th>Actual Wt.</th><th>L</th><th>W</th><th>H</th><th>Chargeable</th><?php endif;?>
     <th>Rate</th><th>Amount</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php foreach($items as $i=>$r):
      if(in_array(($r['billing_method']??''),['VEHICLE','VEHICLE_SUMMARY'],true)) continue;
      $amount=(float)($r['freight']??$r['total']??0);
    ?>
     <tr>
      <td class="sr"><?=($i+1)?></td>
      <td><input type="date" name="item_date[]" class="form-control" value="<?=h($r['item_date'])?>" required></td>
      <td><div class="input-group"><input name="gauraa_tracking[]" class="form-control gauraa-track" value="<?=h($r['gauraa_tracking'])?>"><button type="button" class="btn btn-outline-primary gen-track">Generate ID</button></div></td>
      <td><input name="third_party_tracking[]" class="form-control" value="<?=h($r['third_party_tracking'])?>"></td>
      <td><input name="sender_name[]" class="form-control" list="senderSuggestions" autocomplete="off" value="<?=h($r['sender_name'])?>"></td>
      <td><input name="receiver_name[]" class="form-control" list="receiverSuggestions" autocomplete="off" value="<?=h($r['receiver_name'])?>"></td>
      <td><input name="destination[]" class="form-control" value="<?=h($r['destination'])?>"></td>
      <td><input name="carrier[]" class="form-control" value="<?=h($r['carrier'])?>"></td>
      <?php if($isBox):?>
       <td><input type="number" name="boxes[]" class="form-control boxes" min="0" step="1" value="<?=h($r['boxes'])?>"></td>
       <input type="hidden" name="actual_weight[]" value="<?=h($r['actual_weight'])?>"><input type="hidden" name="length[]" value="<?=h($r['length_cm'])?>"><input type="hidden" name="width[]" value="<?=h($r['width_cm'])?>"><input type="hidden" name="height[]" value="<?=h($r['height_cm'])?>">
      <?php else:?>
       <td><input type="number" name="actual_weight[]" class="form-control actual" min="0" step=".01" value="<?=h($r['actual_weight'])?>"></td>
       <td><input type="number" name="length[]" class="form-control dim" min="0" step=".01" value="<?=h($r['length_cm'])?>"></td>
       <td><input type="number" name="width[]" class="form-control dim" min="0" step=".01" value="<?=h($r['width_cm'])?>"></td>
       <td><input type="number" name="height[]" class="form-control dim" min="0" step=".01" value="<?=h($r['height_cm'])?>"></td>
       <td><input class="form-control charge" value="<?=h($r['chargeable_weight'])?>" readonly></td>
       <input type="hidden" name="boxes[]" value="<?=h($r['boxes'])?>">
      <?php endif;?>
      <td><input type="number" name="rate[]" class="form-control rate" min="0" step=".01" value="<?=h($r['rate'])?>"></td>
      <td><input type="number" name="amount[]" class="form-control amount" step=".01" value="<?=number_format($amount,2,'.','')?>" readonly></td>
      <td><button type="button" class="btn btn-danger btn-sm remove">Delete</button></td>
     </tr>
    <?php endforeach;?>
    </tbody>
   </table>
  </div>
  <div class="d-flex justify-content-end mt-2"><h5>Freight Total: ₹ <span id="freightTotal">0.00</span></h5></div>
 </div>
</div>
<div class="mt-3 mb-4">
 <button class="btn btn-primary">Update Invoice</button>
 <a href="view.php?id=<?=$id?>" class="btn btn-secondary">Cancel</a>
</div>
</form>

<?php include "../layouts/footer.php";?>