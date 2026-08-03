<?php

require_once "../../config/auth.php";

/* ==========================================
   CREATE BILLING
   GAURAA OS ERP
========================================== */

/* Customer List */

try{

    $customers = $pdo->query("
        SELECT id,company_name,state,gst_no,gst_type
        FROM customers
        WHERE status='Active'
        ORDER BY company_name
    ")->fetchAll(PDO::FETCH_ASSOC);

}catch(Exception $e){

    $customers=[];

}

include "../layouts/header.php";
?>

<div class="card shadow border-0">

<div class="card-header bg-white">

<div class="row g-3 align-items-center">

<div class="col-md-6">

<h3 class="mb-0">

<i class="fa fa-file-invoice-dollar text-primary"></i>

Create Invoice

</h3>

</div>

<div class="col-lg-6 col-md-12 d-flex justify-content-lg-end gap-2 flex-wrap">

<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>

</div>

</div>

</div>

<div class="card-body">

<form action="save.php" method="POST" id="billingForm" novalidate>

<div class="row g-4">
<div class="col-lg-3 col-md-6">
<label class="form-label">Billing Type</label>
<select name="billing_type" id="billing_type" class="form-select">
<option value="booking">Booking Wise Billing</option>
<option value="direct">Direct Manual Billing</option>
</select>
</div>

<div class="col-lg-3 col-md-6">
<label class="form-label">Customer</label>
<select name="customer_id" id="customer_id" class="form-select" required>
<option value="">Select Customer</option>
<?php foreach($customers as $c){ ?>
<option value="<?= (int)$c['id']; ?>"
data-state="<?= htmlspecialchars($c['state'] ?? '', ENT_QUOTES); ?>"
data-gstin="<?= htmlspecialchars($c['gst_no'] ?? '', ENT_QUOTES); ?>"
data-gst-type="<?= htmlspecialchars($c['gst_type'] ?? '', ENT_QUOTES); ?>"
><?= htmlspecialchars($c['company_name']); ?></option>
<?php } ?>
</select>
</div>

<div class="col-lg-2 col-md-6">
<label class="form-label">Invoice Date</label>
<input type="date" name="invoice_date" id="invoice_date" class="form-control"
value="<?= date('Y-m-d'); ?>" required>
</div>

<div class="col-lg-2 col-md-6">
<label class="form-label">GST Type</label>
<select name="gst_type" id="gst_type" class="form-select">
<option value="Extra">GST Extra</option>
<option value="Included">GST Included</option>
</select>
</div>

<div class="col-lg-2 col-md-6">
<label class="form-label">GST %</label>
<input type="number" name="gst_percent" id="gst_percent" class="form-control"
value="18" min="0" max="100" step="0.01">
</div>
<div class="col-lg-3 col-md-6">
<label class="form-label">Customer State / GST Tax</label>
<input type="text" id="customer_tax_state" class="form-control" value="" readonly>
<input type="hidden" name="tax_mode" id="tax_mode" value="CGST_SGST">
</div>
</div>

<hr>

<!-- ==========================
BOOKING SECTION
========================== -->

<div id="bookingSection">

<div class="card border-primary mb-3">

<div class="card-header bg-light">

<h5 class="mb-0">
<i class="fa fa-filter"></i>
Booking Filter
</h5>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">

<label>Booking From</label>

<input
type="date"
class="form-control"
id="from_date"
name="from_date"
value="<?=date('Y-m-01')?>">

</div>

<div class="col-md-3">

<label>Booking To</label>

<input
type="date"
class="form-control"
id="to_date"
name="to_date"
value="<?=date('Y-m-d')?>">

</div>

<div class="col-md-3 d-flex align-items-end">

<button
type="button"
class="btn btn-primary w-100"
id="loadBookings">

<i class="fa fa-search"></i>

Load Bookings

</button>

</div>

<div class="col-md-3 d-flex align-items-end">

<button
type="button"
class="btn btn-secondary w-100"
id="clearBookings">

<i class="fa fa-refresh"></i>

Clear

</button>

</div>

</div>

</div>

</div>

<div class="card">

<div class="card-header bg-success text-white d-flex justify-content-between">

<span>

Selected Bookings

</span>

<div>

<input
type="checkbox"
id="checkAll">

<label for="checkAll">

Select All

</label>

</div>

</div>

<div class="card-body p-0">

<div class="table-responsive">

<table class="table table-bordered table-hover mb-0">

<thead>

<tr>

<th width="40"></th>

<th>Booking No</th>

<th>Date</th>

<th>Receiver</th>

<th>Destination</th>

<th>Carrier</th>

<th>Tracking</th>

<th class="text-end">Weight</th>

<th class="text-end">Amount</th>

</tr>

</thead>

<tbody id="bookingBody">

<tr>

<td colspan="9" class="text-center p-4 text-muted">

Click <strong>Load Bookings</strong> to load customer bookings.

</td>

</tr>

</tbody>

</table>

</div>

</div>

</div>

</div>

<!-- ==========================
DIRECT BILLING
========================== -->

<div id="directSection" style="display:none;">

<div class="card border-warning mt-3">
<div class="card-header bg-warning">
<h5 class="mb-0">Manual Shipment Billing</h5>
</div>
<div class="card-body">

<div class="row g-3 mb-3">
<div class="col-md-3">
<label class="form-label">Invoice To</label>
<select name="invoice_to" class="form-select">
<option value="Customer" selected>Customer</option>
<option value="Sender">Sender</option>
<option value="Receiver">Receiver</option>
<option value="Third Party">Third Party / Other</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Billing Method</label>
<select name="billing_method" id="billing_method" class="form-select">
<option value="WEIGHT">KG / Weight</option>
<option value="BOX">Box Billing</option>
<option value="VEHICLE">Monthly Vehicle</option>
</select>
</div>

<div class="col-md-3">
<label class="form-label">GST Registration</label>
<select name="party_gst_status" class="form-select">
<option value="Registered">GST Registered</option>
<option value="Unregistered">Without GST / Unregistered</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Party GSTIN</label>
<input type="text" name="party_gstin" class="form-control" placeholder="Optional GSTIN">
</div>

<div class="col-md-3">
<label class="form-label">SAC Code</label>
<input type="text" name="sac_code" id="sac_code" class="form-control"
       value="996812" maxlength="6" inputmode="numeric"
       placeholder="SAC Code" required>
</div>
<div class="col-md-3" id="volumetricWrap">
<label class="form-label">Volumetric Divisor</label>
<input type="number" name="volumetric_divisor" id="volumetric_divisor" class="form-control" value="5000" min="1">
</div>
</div>


<div class="table-responsive" id="manualShipmentWrap">
<table class="table table-bordered table-sm align-middle" id="manualTable" style="min-width:1900px">
<thead class="table-light">
<tr>
<th>S.No</th>
<th>Date</th>
<th>Gauraa Tracking</th>
<th>Third Party AWB</th>
<th>Sender</th>
<th>Receiver</th>
<th>Destination</th>
<th>Carrier</th>
<th>Actual Wt.</th>
<th>L (cm)</th>
<th>W (cm)</th>
<th>H (cm)</th>
<th>Vol. Wt.</th>
<th>Chargeable Wt.</th>
<th>Boxes</th>
<th>Rate</th>
<th>Amount</th>
<th>POD Charge</th>
<th>ODA Charge</th>
<th>Row Total</th>
<th>Action</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>


<button type="button" class="btn btn-success" id="addRow">
<i class="fa fa-plus"></i> Add Shipment
</button>
<span id="oldShipmentImportWrap">
<button type="button" class="btn btn-warning btn-sm ms-2" id="importOldShipmentBtn">Import Old Data</button>
<button type="button" class="btn btn-primary btn-sm ms-2" id="exportShipmentBtn">
<i class="fa fa-download"></i> Export Excel
</button>
<input type="file" id="oldShipmentCsv" accept=".csv,text/csv" style="display:none;">
<span class="ms-2 small text-muted">CSV: Date, Tracking, AWB, Sender, Receiver, Destination, Carrier, Weight, L, W, H, Boxes, Rate, POD Charge, ODA Charge</span>
</span>

<div id="monthlyVehiclePanel" class="mt-3" style="display:none;">
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Monthly Vehicle Billing</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Vehicle No.</label>
                    <input type="text" name="mv_vehicle_no" id="mv_vehicle_no" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monthly Fixed Charge</label>
                    <input type="number" step="0.01" min="0" name="mv_fixed_charge" id="mv_fixed_charge" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Free KM Per Day</label>
                    <input type="number" step="0.01" min="0" name="mv_free_km_day" id="mv_free_km_day" class="form-control" value="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Extra KM Rate</label>
                    <input type="number" step="0.01" min="0" name="mv_default_extra_rate" id="mv_default_extra_rate" class="form-control" value="9">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" id="monthlyVehicleTable">
                    <thead class="table-light">
                        <tr>
                            <th>S.No</th>
                            <th>Date</th>
                            <th>Opening KM</th>
                            <th>Closing KM</th>
                            <th>Total KM</th>
                            <th>Free KM / Day</th>
                            <th>Extra KM</th>
                            <th>Rate / Extra KM</th>
                            <th>Extra Amount</th>
                            <th>Toll</th>
                            <th>Parking</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="mvRows"></tbody>
                </table>
            </div>

            <button type="button" class="btn btn-success btn-sm" id="mvAddRow">
                <i class="fa fa-plus"></i> Add Date
            </button>
            <button type="button" class="btn btn-warning btn-sm ms-2" id="mvImportBtn">
                <i class="fa fa-upload"></i> Import Meter Reading
            </button>
            <input type="file" id="mvImportFile" accept=".csv,text/csv" style="display:none;">
            <small class="text-muted ms-2">CSV: Date, Opening KM, Closing KM</small>

            <div class="row g-3 mt-2">
                <div class="col-md-2">
                    <label>Total Days</label>
                    <input type="number" name="mv_total_days" id="mv_total_days" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total KM</label>
                    <input type="number" name="mv_total_km" id="mv_total_km" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total Free KM</label>
                    <input type="number" name="mv_total_free_km" id="mv_total_free_km" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total Extra KM</label>
                    <input type="number" name="mv_total_extra_km" id="mv_total_extra_km" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total Extra Amount</label>
                    <input type="number" step="0.01" name="mv_total_extra_amount" id="mv_total_extra_amount" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total Toll</label>
                    <input type="number" step="0.01" name="mv_total_toll" id="mv_total_toll" class="form-control" value="0" readonly>
                </div>
                <div class="col-md-2">
                    <label>Total Parking</label>
                    <input type="number" step="0.01" name="mv_total_parking" id="mv_total_parking" class="form-control" value="0" readonly>
                </div>
                <input type="hidden" name="mv_toll_parking" id="mv_toll_parking" value="0">
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>

<hr>
<!-- ==========================
INVOICE CALCULATION
========================== -->

<div class="row">

    <!-- Additional Charges -->

    <div class="col-lg-6" id="additionalChargesWrap">

        <div class="card border-info">

            <div class="card-header bg-info text-white">

                <h5 class="mb-0">
                    Additional Charges
                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label>Fuel Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="fuel_charge"
                        id="fuel_charge"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Docket Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="docket_charge"
                        id="docket_charge"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>FOV Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="fov"
                        id="fov"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Packing Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="packing"
                        id="packing"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Loading Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="loading_charge"
                        id="loading_charge"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Unloading Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="unloading_charge"
                        id="unloading_charge"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Other Charge</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="other_charge"
                        id="other_charge"
                        value="0">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Discount</label>

                        <input
                        type="number"
                        class="form-control calc"
                        name="discount"
                        id="discount"
                        value="0">

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Invoice Summary -->

    <div class="col-lg-6">

        <div class="card border-success">

            <div class="card-header bg-success text-white">

                <h5 class="mb-0">
                    Invoice Summary
                </h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <tbody id="vehicleSummaryDetails" style="display:none;">
                    <tr>
                        <th>Fixed Vehicle Charge (<span id="summary_vehicle_days">0</span> Days × ₹<span id="summary_per_day_rate">0.00</span> Per Day)</th>
                        <td class="text-end">₹ <span id="summary_fixed_charge">0.00</span></td>
                    </tr>
                    <tr>
                        <th>Extra KM Charges (<span id="summary_extra_km">0</span> KM × ₹<span id="summary_extra_rate">0.00</span>)</th>
                        <td class="text-end">₹ <span id="summary_extra_amount">0.00</span></td>
                    </tr>
                    <tr>
                        <th>Toll</th>
                        <td class="text-end">₹ <span id="summary_toll">0.00</span></td>
                    </tr>
                    <tr>
                        <th>Parking</th>
                        <td class="text-end">₹ <span id="summary_parking">0.00</span></td>
                    </tr>
                    </tbody>

                    <tr id="podSummaryRow">
                        <th>Total POD Charges</th>
                        <td class="text-end">₹ <span id="summary_pod_charges">0.00</span></td>
                    </tr>
                    <tr id="odaSummaryRow">
                        <th>Total ODA Charges</th>
                        <td class="text-end">₹ <span id="summary_oda_charges">0.00</span></td>
                    </tr>
                    <tr>
                        <th id="subtotal_label">Subtotal</th>
                        <td class="text-end">
                            ₹ <span id="subtotal">0.00</span>
                        </td>
                    </tr>

                    <tr id="cgstRow">
                        <th>CGST @ <span id="cgst_rate">9.00</span>%</th>
                        <td class="text-end">₹ <span id="cgst_amount">0.00</span></td>
                    </tr>
                    <tr id="sgstRow">
                        <th>SGST @ <span id="sgst_rate">9.00</span>%</th>
                        <td class="text-end">₹ <span id="sgst_amount">0.00</span></td>
                    </tr>
                    <tr id="igstRow" style="display:none;">
                        <th>IGST @ <span id="igst_rate">18.00</span>%</th>
                        <td class="text-end">₹ <span id="igst_amount">0.00</span></td>
                    </tr>

                    <tr class="table-success">
                        <th>Grand Total</th>
                        <th class="text-end">₹ <span id="grand_total">0.00</span></th>
                    </tr>

                </table>

                <input type="hidden" name="subtotal" id="subtotal_input">
                <input type="hidden" name="gst_amount" id="gst_input">
                <input type="hidden" name="cgst_amount" id="cgst_input" value="0">
                <input type="hidden" name="sgst_amount" id="sgst_input" value="0">
                <input type="hidden" name="igst_amount" id="igst_input" value="0">
                <input type="hidden" name="grand_total" id="grand_input">

            </div>

        </div>

    </div>

</div>

<div class="row mt-3">

    <div class="col-md-12">

        <label>Remarks</label>

        <textarea
        class="form-control"
        name="remarks"
        rows="3"
        placeholder="Remarks (Optional)"></textarea>

    </div>

    <div class="col-md-12 mt-3">
        <label>Terms & Conditions</label>
        <textarea class="form-control" name="terms_conditions" rows="5">1. Payment is due as per agreed credit terms.
2. Any discrepancy in the invoice must be reported within 7 days.
3. Toll, parking and other approved actual charges are payable as applicable.
4. Delayed payments may attract charges as per agreed terms.
5. Subject to Haridwar jurisdiction.</textarea>
    </div>

</div>

<div class="text-end mt-4">

    <button
    type="submit"
    id="saveInvoiceBtn"
    class="btn btn-success btn-lg">

        <i class="fa fa-save"></i>

        Save Invoice

    </button>

</div>

<hr>
<script>

let subtotal = 0;

function updateCustomerTaxMode(){
    const select=document.getElementById("customer_id");
    const option=select?.options[select.selectedIndex];
    const state=(option?.dataset.state || "").trim();
    const gstin=(option?.dataset.gstin || "").trim().toUpperCase();
    const gstType=(option?.dataset.gstType || "").trim();
    const normalized=state.toLowerCase().replace(/[^a-z]/g,"");

    const isUK =
        normalized==="uttarakhand" ||
        normalized==="uttranchal" ||
        normalized==="uttaranchal" ||
        gstin.startsWith("05");

    document.getElementById("tax_mode").value = isUK ? "CGST_SGST" : "IGST";

    const taxState=document.getElementById("customer_tax_state");
    if(taxState){
        const shownState=state || (gstin.startsWith("05") ? "Uttarakhand" : "State not set");
        taxState.value=shownState+" - "+(isUK ? "CGST + SGST" : "IGST");
    }

    const partyGstin=document.querySelector('input[name="party_gstin"]');
    if(partyGstin) partyGstin.value=gstin;

    const partyStatus=document.querySelector('select[name="party_gst_status"]');
    if(partyStatus && gstType){
        partyStatus.value=(gstType==="Registered" || gstType==="Composition" || gstType==="SEZ")
            ? "Registered" : "Unregistered";
    }
}

function calculateInvoice() {

    subtotal = 0;

    /* Booking Amount */

    document.querySelectorAll(".booking-check:checked").forEach(function(item){

        subtotal += parseFloat(item.dataset.amount || 0);

    });

    /* Manual Billing Amount */
    if((document.getElementById("billing_method")?.value || "") === "VEHICLE"){
        // Monthly Fixed Charge is for 30 days; charge only actual billed days.
        const monthlyFixed = parseFloat(document.getElementById("mv_fixed_charge")?.value || 0);
        const billedDays   = parseFloat(document.getElementById("mv_total_days")?.value || 0);
        const prorataFixed = (monthlyFixed / 30) * billedDays;

        const extraAmount = parseFloat(document.getElementById("mv_total_extra_amount")?.value || 0);
        const totalToll = parseFloat(document.getElementById("mv_total_toll")?.value || 0);
        const totalParking = parseFloat(document.getElementById("mv_total_parking")?.value || 0);
        const extraKm = parseFloat(document.getElementById("mv_total_extra_km")?.value || 0);
        const extraRate = parseFloat(document.getElementById("mv_default_extra_rate")?.value || 0);

        subtotal += prorataFixed;
        subtotal += extraAmount;
        subtotal += totalToll + totalParking;

        const details=document.getElementById("vehicleSummaryDetails");
        if(details) details.style.display="";
        document.getElementById("podSummaryRow").style.display="none";
        document.getElementById("odaSummaryRow").style.display="none";
        document.getElementById("subtotal_label").textContent="Taxable Amount";
        document.getElementById("summary_vehicle_days").textContent=billedDays.toFixed(0);
        const perDayRate = monthlyFixed / 30;
        const perDayEl = document.getElementById("summary_per_day_rate");
        if(perDayEl) perDayEl.textContent = perDayRate.toFixed(2);
        document.getElementById("summary_fixed_charge").textContent=prorataFixed.toFixed(2);
        document.getElementById("summary_extra_km").textContent=extraKm.toFixed(0);
        document.getElementById("summary_extra_rate").textContent=extraRate.toFixed(2);
        document.getElementById("summary_extra_amount").textContent=extraAmount.toFixed(2);
        document.getElementById("summary_toll").textContent=totalToll.toFixed(2);
        document.getElementById("summary_parking").textContent=totalParking.toFixed(2);
    }else{
        const details=document.getElementById("vehicleSummaryDetails");
        if(details) details.style.display="none";
        document.getElementById("podSummaryRow").style.display="";
        document.getElementById("odaSummaryRow").style.display="";
        document.getElementById("subtotal_label").textContent="Subtotal";
        let totalPodCharge=0, totalOdaCharge=0;
        document.querySelectorAll("#manualTable tbody tr").forEach(function(row){
            const freight=parseFloat(row.querySelector(".amount")?.value || 0);
            const pod=parseFloat(row.querySelector(".pod-charge")?.value || 0);
            const oda=parseFloat(row.querySelector(".oda-charge")?.value || 0);
            totalPodCharge += pod;
            totalOdaCharge += oda;
            subtotal += freight + pod + oda;
            const rowTotal=row.querySelector(".shipment-row-total");
            if(rowTotal) rowTotal.value=(freight+pod+oda).toFixed(2);
        });
        const podEl=document.getElementById("summary_pod_charges");
        const odaEl=document.getElementById("summary_oda_charges");
        if(podEl) podEl.textContent=totalPodCharge.toFixed(2);
        if(odaEl) odaEl.textContent=totalOdaCharge.toFixed(2);
    }

    let fuel       = parseFloat(document.getElementById("fuel_charge").value)||0;
    let docket     = parseFloat(document.getElementById("docket_charge").value)||0;
    let fov        = parseFloat(document.getElementById("fov").value)||0;
    let packing    = parseFloat(document.getElementById("packing").value)||0;
    let loading    = parseFloat(document.getElementById("loading_charge").value)||0;
    let unloading  = parseFloat(document.getElementById("unloading_charge").value)||0;
    let other      = parseFloat(document.getElementById("other_charge").value)||0;
    let discount   = parseFloat(document.getElementById("discount").value)||0;
    let gstPercent = parseFloat(document.getElementById("gst_percent").value)||18;

    let gstType=document.getElementById("gst_type").value;

    const isVehicle=(document.getElementById("billing_method")?.value || "") === "VEHICLE";
    if(isVehicle){ fuel=docket=fov=packing=loading=unloading=other=discount=0; }
    let taxable=subtotal+fuel+docket+fov+packing+loading+unloading+other-discount;

    let gst=0;
    let grand=0;

    if(gstType=="Extra"){

        gst=(taxable*gstPercent)/100;
        grand=taxable+gst;

    }else{

        grand=taxable;
        gst=grand-(grand/(1+(gstPercent/100)));
        taxable=grand-gst;

    }

    const taxMode=document.getElementById("tax_mode")?.value || "CGST_SGST";
    let cgst=0, sgst=0, igst=0;
    if(taxMode==="CGST_SGST"){
        cgst=gst/2;
        sgst=gst/2;
        document.getElementById("cgstRow").style.display="";
        document.getElementById("sgstRow").style.display="";
        document.getElementById("igstRow").style.display="none";
    }else{
        igst=gst;
        document.getElementById("cgstRow").style.display="none";
        document.getElementById("sgstRow").style.display="none";
        document.getElementById("igstRow").style.display="";
    }

    document.getElementById("cgst_rate").textContent=(gstPercent/2).toFixed(2);
    document.getElementById("sgst_rate").textContent=(gstPercent/2).toFixed(2);
    document.getElementById("igst_rate").textContent=gstPercent.toFixed(2);
    document.getElementById("cgst_amount").textContent=cgst.toFixed(2);
    document.getElementById("sgst_amount").textContent=sgst.toFixed(2);
    document.getElementById("igst_amount").textContent=igst.toFixed(2);

    document.getElementById("subtotal").innerHTML=taxable.toFixed(2);
    document.getElementById("grand_total").innerHTML=grand.toFixed(2);

    document.getElementById("subtotal_input").value=taxable.toFixed(2);
    document.getElementById("gst_input").value=gst.toFixed(2);
    document.getElementById("cgst_input").value=cgst.toFixed(2);
    document.getElementById("sgst_input").value=sgst.toFixed(2);
    document.getElementById("igst_input").value=igst.toFixed(2);
    document.getElementById("grand_input").value=grand.toFixed(2);

}

/* Billing Type */

document.getElementById("billing_type").addEventListener("change",function(){

    if(this.value=="direct"){

        document.getElementById("bookingSection").style.display="none";
        document.getElementById("directSection").style.display="block";

    }else{

        document.getElementById("bookingSection").style.display="block";
        document.getElementById("directSection").style.display="none";

    }

    if(typeof applyBillingMethodUI === "function") applyBillingMethodUI();
    calculateInvoice();

});

/* Charges */

document.querySelectorAll(".calc").forEach(function(item){

    item.addEventListener("input",calculateInvoice);

});

document.getElementById("gst_type").addEventListener("change",calculateInvoice);
document.getElementById("gst_percent").addEventListener("input",calculateInvoice);
document.getElementById("customer_id").addEventListener("change",function(){
    updateCustomerTaxMode();
    calculateInvoice();
});

/* Select All */

document.getElementById("checkAll").addEventListener("change",function(){

    document.querySelectorAll(".booking-check").forEach(function(ch){

        ch.checked=document.getElementById("checkAll").checked;

    });

    calculateInvoice();

});

document.addEventListener("change",function(e){

    if(e.target.classList.contains("booking-check")){

        calculateInvoice();

    }

});

</script>
<script>

/* ==========================================
   DIRECT MANUAL BILLING - ADD ROW
========================================== */

function addManualRow(){
    const tbody=document.querySelector("#manualTable tbody");
    const row=document.createElement("tr");
    const sr=tbody.children.length+1;
    row.innerHTML=`
      <td class="manual-sr text-center">${sr}</td>
      <td><input type="date" name="manual_date[]" class="form-control" value="<?= date('Y-m-d'); ?>" required></td>
      <td><input type="text" name="gauraa_tracking[]" class="form-control" placeholder="Gauraa Tracking" required></td>
      <td><input type="text" name="third_party_tracking[]" class="form-control" placeholder="AWB / Tracking"></td>
      <td><input type="text" name="sender_name[]" class="form-control" placeholder="Sender"></td>
      <td><input type="text" name="receiver_name[]" class="form-control" placeholder="Receiver" required></td>
      <td><input type="text" name="destination[]" class="form-control" placeholder="Destination" required></td>
      <td><input type="text" name="carrier[]" class="form-control" placeholder="Carrier"></td>
      <td><input type="number" name="actual_weight[]" class="form-control actual-weight" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="length[]" class="form-control dimension" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="width[]" class="form-control dimension" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="height[]" class="form-control dimension" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="volumetric_weight[]" class="form-control volumetric-weight" value="0" step="0.01" readonly></td>
      <td><input type="number" name="chargeable_weight[]" class="form-control chargeable-weight manual-qty" value="0" step="0.01" readonly></td>
      <td><input type="number" name="boxes[]" class="form-control" min="0" step="1" value="1"></td>
      <td><input type="number" name="rate[]" class="form-control manual-rate" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="amount[]" class="form-control amount" value="0" step="0.01" readonly></td>
      <td><input type="number" name="pod_charge[]" class="form-control pod-charge" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="oda_charge[]" class="form-control oda-charge" min="0" step="0.01" value="0"></td>
      <td><input type="number" name="shipment_row_total[]" class="form-control shipment-row-total" step="0.01" value="0" readonly></td>
      <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>`;
    tbody.appendChild(row);
}

function recalcManualRow(row){
    const method=billingMethodMode();
    const actual=parseFloat(row.querySelector(".actual-weight")?.value)||0;
    const l=parseFloat(row.querySelector('input[name="length[]"]')?.value)||0;
    const w=parseFloat(row.querySelector('input[name="width[]"]')?.value)||0;
    const h=parseFloat(row.querySelector('input[name="height[]"]')?.value)||0;
    const boxes=parseFloat(row.querySelector('input[name="boxes[]"]')?.value)||0;
    const divisor=parseFloat(document.getElementById("volumetric_divisor")?.value)||5000;
    const vol=(l*w*h)/divisor;
    const chargeable=Math.max(actual,vol);
    const rate=parseFloat(row.querySelector(".manual-rate")?.value)||0;
    row.querySelector(".volumetric-weight").value=vol.toFixed(2);
    row.querySelector(".chargeable-weight").value=chargeable.toFixed(2);
    row.querySelector(".amount").value=(method === "BOX" ? boxes*rate : chargeable*rate).toFixed(2);
}

function renumberManualRows(){
    document.querySelectorAll("#manualTable tbody tr").forEach((row,i)=>{
        const cell=row.querySelector(".manual-sr");
        if(cell) cell.textContent=i+1;
    });
}

document.getElementById("addRow").addEventListener("click",function(){
    addManualRow();
});

document.addEventListener("input",function(e){
    if(e.target.closest("#manualTable") || e.target.id==="volumetric_divisor"){
        if(e.target.id==="volumetric_divisor"){
            document.querySelectorAll("#manualTable tbody tr").forEach(recalcManualRow);
        }else{
            const row=e.target.closest("tr");
            if(row) recalcManualRow(row);
        }
        calculateInvoice();
    }
});

document.addEventListener("click",function(e){
    const button=e.target.closest(".removeRow");
    if(!button) return;
    const row=button.closest("tr");
    if(row) row.remove();
    renumberManualRows();
    calculateInvoice();
});

document.getElementById("billing_type").addEventListener("change",function(){
    if(this.value==="direct" && document.querySelector("#manualTable tbody").children.length===0){
        addManualRow();
    }
});


/* ==========================================
   LOAD BOOKINGS
========================================== */

document.getElementById("loadBookings")
.addEventListener("click",function(){

    const customer =
        document.getElementById("customer_id").value;

    const from =
        document.getElementById("from_date").value;

    const to =
        document.getElementById("to_date").value;


    if(customer === ""){

        alert("Please select customer.");

        return;

    }


    if(from === "" || to === ""){

        alert("Please select billing date range.");

        return;

    }


    if(from > to){

        alert("From Date cannot be greater than To Date.");

        return;

    }


    const button = this;

    const oldText = button.innerHTML;

    button.disabled = true;

    button.innerHTML =
        '<i class="fa fa-spinner fa-spin"></i> Loading...';


    const url =
        "load_bookings.php"
        + "?customer=" + encodeURIComponent(customer)
        + "&from=" + encodeURIComponent(from)
        + "&to=" + encodeURIComponent(to);


    fetch(url)

    .then(function(response){

        if(!response.ok){

            throw new Error(
                "HTTP Error " + response.status
            );

        }

        return response.text();

    })

    .then(function(html){

        document.getElementById("bookingBody")
        .innerHTML = html;

        document.getElementById("checkAll")
        .checked = false;

        calculateInvoice();

    })

    .catch(function(error){

        console.error(error);

        document.getElementById("bookingBody")
        .innerHTML = `

            <tr>

                <td
                    colspan="9"
                    class="text-center text-danger p-4">

                    Unable to load bookings.

                </td>

            </tr>

        `;

    })

    .finally(function(){

        button.disabled = false;

        button.innerHTML = oldText;

    });

});


/* ==========================================
   CLEAR BOOKINGS
========================================== */

document.getElementById("clearBookings")
.addEventListener("click",function(){

    document.getElementById("bookingBody")
    .innerHTML = `

        <tr>

            <td
                colspan="9"
                class="text-center text-muted p-4">

                Click <strong>Load Bookings</strong>
                to load customer bookings.

            </td>

        </tr>

    `;

    document.getElementById("checkAll").checked = false;

    calculateInvoice();

});


/* ==========================================
   FORM VALIDATION
========================================== */

document.getElementById("billingForm")
.addEventListener("submit",function(e){
    const saveBtn=document.getElementById("saveInvoiceBtn");
    if(saveBtn){
        saveBtn.disabled=true;
        saveBtn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Saving...';
    }

    const methodNow=document.getElementById("billing_method")?.value || "";
    if(methodNow==="VEHICLE"){
        document.getElementById("billing_type").value="direct";
    }

    const billingType =
        document.getElementById("billing_type").value;

    const customer =
        document.getElementById("customer_id").value;


    if(customer === ""){

        e.preventDefault();
        if(saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML='<i class="fa fa-save"></i> Save Invoice'; }

        alert("Please select customer.");

        return;

    }


    /* BOOKING WISE */

    if(billingType === "booking"){

        const selected =
            document.querySelectorAll(
                ".booking-check:checked"
            );

        if(selected.length === 0){

            e.preventDefault();

            alert(
                "Please select at least one booking."
            );

            return;

        }

    }


    /* DIRECT BILLING */

    if(billingType === "direct"){
        const method=document.getElementById("billing_method")?.value || "WEIGHT";

        if(method === "VEHICLE"){
            const rows=document.querySelectorAll("#mvRows tr");
            if(rows.length === 0){
                e.preventDefault();
                alert("Please add at least one date-wise meter reading.");
                return;
            }
            if((document.getElementById("mv_vehicle_no")?.value || "").trim() === ""){
                e.preventDefault();
                alert("Please enter Vehicle No.");
                return;
            }
        }else{
            const rows = document.querySelectorAll("#manualTable tbody tr");
            if(rows.length === 0){
                e.preventDefault();
                alert("Please add at least one manual shipment.");
                return;
            }
            let validItem = false;
            rows.forEach(function(row){
                const tracking = row.querySelector('input[name="gauraa_tracking[]"]');
                const receiver = row.querySelector('input[name="receiver_name[]"]');
                if(tracking && receiver && tracking.value.trim() !== "" && receiver.value.trim() !== ""){
                    validItem = true;
                }
            });
            if(!validItem){
                e.preventDefault();
                alert("Please enter Gauraa Tracking and Receiver in at least one shipment.");
                return;
            }
        }
    }


    const grandTotal =
        parseFloat(
            document.getElementById("grand_input").value
        ) || 0;


    if(grandTotal <= 0){

        e.preventDefault();
        if(saveBtn){ saveBtn.disabled=false; saveBtn.innerHTML='<i class="fa fa-save"></i> Save Invoice'; }

        alert("Invoice amount cannot be zero.");

        return;

    }

});




function billingMethodMode(){
    return document.getElementById("billing_method")?.value || "WEIGHT";
}

function applyBillingMethodUI(){
    const method = billingMethodMode();
    const isVehicle = method === "VEHICLE";
    const isWeight = method === "WEIGHT";
    const isBox = method === "BOX";

    const vehiclePanel = document.getElementById("monthlyVehiclePanel");
    const manualTable = document.getElementById("manualTable");
    const addRowBtn = document.getElementById("addRow");
    const volumetricWrap = document.getElementById("volumetricWrap");
    const additional = document.getElementById("additionalChargesWrap");
    const oldImportWrap = document.getElementById("oldShipmentImportWrap");

    if(vehiclePanel) vehiclePanel.style.display = isVehicle ? "block" : "none";
    if(manualTable) manualTable.style.display = isVehicle ? "none" : "";

    document.querySelectorAll("#manualTable input[required], #manualTable select[required]").forEach(function(el){
        if(isVehicle){
            el.dataset.wasRequired="1";
            el.required=false;
            el.disabled=true;
        }else{
            if(el.dataset.wasRequired==="1") el.required=true;
            el.disabled=false;
        }
    });
    if(addRowBtn) addRowBtn.style.display = isVehicle ? "none" : "";
    if(volumetricWrap) volumetricWrap.style.display = isWeight ? "" : "none";
    if(additional) additional.style.display = isVehicle ? "none" : "";
    if(oldImportWrap) oldImportWrap.style.display = isVehicle ? "none" : "";

    // Box mode: hide weight/dimension columns, keep Boxes + Rate + Amount.
    const hideWeightIndexes = [8,9,10,11,12,13]; // zero-based columns
    if(manualTable){
        manualTable.querySelectorAll("tr").forEach(function(tr){
            Array.from(tr.children).forEach(function(cell,idx){
                if(hideWeightIndexes.includes(idx)){
                    cell.style.display = isBox ? "none" : "";
                }
            });
        });
    }

    if(isVehicle && document.querySelectorAll("#mvRows tr").length === 0){
        addVehicleRow();
    }
    calculateInvoice();
}

function addVehicleRow(){
    const tbody = document.getElementById("mvRows");
    if(!tbody) return;
    const tr = document.createElement("tr");
    const sr = tbody.children.length + 1;
    const free = parseFloat(document.getElementById("mv_free_km_day")?.value) || 100;
    const rate = parseFloat(document.getElementById("mv_default_extra_rate")?.value) || 9;
    tr.innerHTML = `
        <td class="mv-sr text-center">${sr}</td>
        <td><input type="date" name="mv_date[]" class="form-control mv-date" value="<?= date('Y-m-d'); ?>" required></td>
        <td><input type="number" step="1" min="0" name="mv_opening_km[]" class="form-control mv-opening" value="0" required></td>
        <td><input type="number" step="1" min="0" name="mv_closing_km[]" class="form-control mv-closing" value="0" required></td>
        <td><input type="number" step="1" name="mv_total_km_row[]" class="form-control mv-total" value="0" readonly></td>
        <td><input type="number" step="1" min="0" name="mv_free_km[]" class="form-control mv-free" value="${free}"></td>
        <td><input type="number" step="1" name="mv_extra_km[]" class="form-control mv-extra" value="0" readonly></td>
        <td><input type="number" step="0.01" min="0" name="mv_extra_rate[]" class="form-control mv-rate" value="${rate}"></td>
        <td><input type="number" step="0.01" name="mv_extra_amount[]" class="form-control mv-extra-amount" value="0" readonly></td>
        <td><input type="number" step="0.01" min="0" name="mv_toll[]" class="form-control mv-toll" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="mv_parking[]" class="form-control mv-parking" value="0"></td>
        <td><button type="button" class="btn btn-danger btn-sm mv-remove"><i class="fa fa-trash"></i></button></td>`;
    tbody.appendChild(tr);
    calculateMonthlyVehicle();
}

function calculateMonthlyVehicle(){
    let totalDays=0, totalKm=0, totalFree=0, totalExtra=0, totalExtraAmount=0, totalToll=0, totalParking=0;

    document.querySelectorAll("#mvRows tr").forEach(function(row){
        const opening = parseFloat(row.querySelector(".mv-opening")?.value) || 0;
        const closing = parseFloat(row.querySelector(".mv-closing")?.value) || 0;
        const free = parseFloat(row.querySelector(".mv-free")?.value) || 0;
        const rate = parseFloat(row.querySelector(".mv-rate")?.value) || 0;
        const toll = parseFloat(row.querySelector(".mv-toll")?.value) || 0;
        const parking = parseFloat(row.querySelector(".mv-parking")?.value) || 0;

        const km = Math.max(0, closing - opening);
        const extra = Math.max(0, km - free);
        const amount = extra * rate;

        row.querySelector(".mv-total").value = km.toFixed(0);
        row.querySelector(".mv-extra").value = extra.toFixed(0);
        row.querySelector(".mv-extra-amount").value = amount.toFixed(2);

        totalDays++;
        totalKm += km;
        totalFree += free;
        totalExtra += extra;
        totalExtraAmount += amount;
        totalToll += toll;
        totalParking += parking;
    });

    document.getElementById("mv_total_days").value = totalDays;
    document.getElementById("mv_total_km").value = totalKm.toFixed(0);
    document.getElementById("mv_total_free_km").value = totalFree.toFixed(0);
    document.getElementById("mv_total_extra_km").value = totalExtra.toFixed(0);
    document.getElementById("mv_total_extra_amount").value = totalExtraAmount.toFixed(2);
    document.getElementById("mv_total_toll").value = totalToll.toFixed(2);
    document.getElementById("mv_total_parking").value = totalParking.toFixed(2);
    document.getElementById("mv_toll_parking").value = (totalToll + totalParking).toFixed(2);

    calculateInvoice();
}

function renumberVehicleRows(){
    document.querySelectorAll("#mvRows tr").forEach(function(row,i){
        const sr=row.querySelector(".mv-sr");
        if(sr) sr.textContent=i+1;
    });
}

document.getElementById("billing_method")?.addEventListener("change", applyBillingMethodUI);
document.getElementById("mvAddRow")?.addEventListener("click", addVehicleRow);

document.addEventListener("input", function(e){
    if(e.target.closest("#monthlyVehiclePanel")){
        if(e.target.id === "mv_free_km_day"){
            const v=parseFloat(e.target.value)||0;
            document.querySelectorAll(".mv-free").forEach(x=>x.value=v);
        }
        if(e.target.id === "mv_default_extra_rate"){
            const v=parseFloat(e.target.value)||0;
            document.querySelectorAll(".mv-rate").forEach(x=>x.value=v);
        }
        calculateMonthlyVehicle();
    }
});

document.addEventListener("click", function(e){
    const btn=e.target.closest(".mv-remove");
    if(!btn) return;
    btn.closest("tr")?.remove();
    renumberVehicleRows();
    calculateMonthlyVehicle();
});


/* ==========================================
   MONTHLY VEHICLE - CSV METER IMPORT
   CSV columns: Date, Opening KM, Closing KM
   Accepted dates: DD-MM-YYYY, DD/MM/YYYY, YYYY-MM-DD
========================================== */

function mvNormalizeDate(value){
    value=(value || "").trim().replace(/^"|"$/g,"");
    if(!value) return "";

    let m=value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if(m) return `${m[1]}-${String(m[2]).padStart(2,"0")}-${String(m[3]).padStart(2,"0")}`;

    m=value.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/);
    if(m) return `${m[3]}-${String(m[2]).padStart(2,"0")}-${String(m[1]).padStart(2,"0")}`;

    return "";
}

function mvParseCsvLine(line){
    const result=[];
    let current="", quoted=false;
    for(let i=0;i<line.length;i++){
        const ch=line[i];
        if(ch === '"'){
            if(quoted && line[i+1] === '"'){ current+='"'; i++; }
            else quoted=!quoted;
        }else if(ch === "," && !quoted){
            result.push(current.trim());
            current="";
        }else{
            current+=ch;
        }
    }
    result.push(current.trim());
    return result;
}

function addImportedVehicleRow(date, opening, closing){
    const tbody=document.getElementById("mvRows");
    const tr=document.createElement("tr");
    const sr=tbody.children.length+1;
    const free=parseFloat(document.getElementById("mv_free_km_day")?.value)||0;
    const rate=parseFloat(document.getElementById("mv_default_extra_rate")?.value)||0;

    tr.innerHTML=`
        <td class="mv-sr text-center">${sr}</td>
        <td><input type="date" name="mv_date[]" class="form-control mv-date" value="${date}" required></td>
        <td><input type="number" step="1" min="0" name="mv_opening_km[]" class="form-control mv-opening" value="${opening}" required></td>
        <td><input type="number" step="1" min="0" name="mv_closing_km[]" class="form-control mv-closing" value="${closing}" required></td>
        <td><input type="number" step="1" name="mv_total_km_row[]" class="form-control mv-total" value="0" readonly></td>
        <td><input type="number" step="1" min="0" name="mv_free_km[]" class="form-control mv-free" value="${free}"></td>
        <td><input type="number" step="1" name="mv_extra_km[]" class="form-control mv-extra" value="0" readonly></td>
        <td><input type="number" step="0.01" min="0" name="mv_extra_rate[]" class="form-control mv-rate" value="${rate}"></td>
        <td><input type="number" step="0.01" name="mv_extra_amount[]" class="form-control mv-extra-amount" value="0" readonly></td>
        <td><input type="number" step="0.01" min="0" name="mv_toll[]" class="form-control mv-toll" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="mv_parking[]" class="form-control mv-parking" value="0"></td>
        <td><button type="button" class="btn btn-danger btn-sm mv-remove"><i class="fa fa-trash"></i></button></td>`;
    tbody.appendChild(tr);
}

document.getElementById("mvImportBtn")?.addEventListener("click", function(){
    document.getElementById("mvImportFile")?.click();
});

document.getElementById("mvImportFile")?.addEventListener("change", function(){
    const file=this.files && this.files[0];
    if(!file) return;

    const reader=new FileReader();
    reader.onload=function(ev){
        try{
            const raw=String(ev.target.result || "").replace(/^\uFEFF/,"");
            const lines=raw.split(/\r?\n/).filter(x=>x.trim()!=="");
            const imported=[];

            lines.forEach(function(line,index){
                const cols=mvParseCsvLine(line);
                if(cols.length < 3) return;

                const first=(cols[0] || "").toLowerCase();
                if(index===0 && (first.includes("date") || first.includes("दिन"))) return;

                const date=mvNormalizeDate(cols[0]);
                const opening=parseFloat(String(cols[1]).replace(/,/g,""));
                const closing=parseFloat(String(cols[2]).replace(/,/g,""));

                if(!date || !Number.isFinite(opening) || !Number.isFinite(closing)) return;
                imported.push({date,opening,closing});
            });

            if(imported.length===0){
                alert("No valid meter readings found. CSV format: Date, Opening KM, Closing KM");
                return;
            }

            if(!confirm(imported.length+" meter readings found. Existing vehicle rows will be replaced. Continue?")) return;

            document.getElementById("mvRows").innerHTML="";
            imported.forEach(r=>addImportedVehicleRow(r.date,r.opening,r.closing));
            renumberVehicleRows();
            calculateMonthlyVehicle();
            alert(imported.length+" meter readings imported successfully.");
        }catch(err){
            console.error(err);
            alert("CSV import failed. Please check the file format.");
        }finally{
            document.getElementById("mvImportFile").value="";
        }
    };
    reader.readAsText(file);
});

updateCustomerTaxMode();
applyBillingMethodUI();

/* ==========================================
   INITIAL CALCULATION
========================================== */

calculateInvoice();



function gcParseCsvLine(line){
    const out=[]; let cur="", q=false;
    for(let i=0;i<line.length;i++){
        const ch=line[i];
        if(ch === '"'){ if(q && line[i+1] === '"'){cur+='"';i++;} else q=!q; }
        else if(ch === ',' && !q){out.push(cur.trim());cur="";}
        else cur+=ch;
    }
    out.push(cur.trim()); return out;
}
function gcImportDate(v){
    v=(v||"").trim();
    if(/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    const m=v.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/);
    return m ? `${m[3]}-${m[2].padStart(2,"0")}-${m[1].padStart(2,"0")}` : v;
}
function gcSetRow(row, cols){
    const method=billingMethodMode();
    // Common first 7 columns: Date, Tracking, AWB, Sender, Receiver, Destination, Carrier
    const common=["manual_date","gauraa_tracking","third_party_tracking","sender_name","receiver_name","destination","carrier"];
    common.forEach((name,i)=>{
        const el=row.querySelector(`[name*="${name}"]`);
        if(el) el.value = i===0 ? gcImportDate(cols[i]||"") : (cols[i] ?? "");
    });

    if(method === "BOX") {
        // Accept old 15-column CSV too: Weight,L,W,H are ignored in BOX mode.
        // Date,Tracking,AWB,Sender,Receiver,Destination,Carrier,Weight,L,W,H,Boxes,Rate,POD,ODA
        const isOld15 = cols.length >= 15;
        const boxMap = isOld15
            ? {boxes:11, rate:12, pod_charge:13, oda_charge:14}
            : {boxes:7, rate:8, pod_charge:9, oda_charge:10};
        Object.entries(boxMap).forEach(([name,idx])=>{
            const el=row.querySelector(`[name*="${name}"]`);
            if(el) el.value = cols[idx] ?? "0";
        });
        // Weight/dimensions must not affect box billing.
        ["actual_weight","length","width","height"].forEach(name=>{
            const el=row.querySelector(`[name*="${name}"]`); if(el) el.value="0";
        });
    } else {
        const names=["actual_weight","length","width","height","boxes","rate","pod_charge","oda_charge"];
        names.forEach((name,j)=>{
            const el=row.querySelector(`[name*="${name}"]`);
            if(el) el.value = cols[7+j] ?? "0";
        });
    }
    recalcManualRow(row);
}

document.getElementById("importOldShipmentBtn")?.addEventListener("click",()=>{
    const method=document.getElementById("billing_method")?.value || "";
    if(method!=="WEIGHT" && method!=="BOX"){
        alert("Pehle KG / Weight ya Box Billing select karein.");
        return;
    }
    document.getElementById("oldShipmentCsv").click();
});
document.getElementById("oldShipmentCsv")?.addEventListener("change",function(){
    const file=this.files?.[0]; if(!file) return;
    const r=new FileReader();
    r.onload=e=>{
        let lines=String(e.target.result||"").replace(/\r/g,"").split("\n").filter(x=>x.trim());
        if(!lines.length) return alert("CSV empty hai.");
        if(lines[0].toLowerCase().includes("date")) lines.shift();
        const data=lines.map(gcParseCsvLine).filter(a=>a.some(v=>v!==""));
        const tbody=document.querySelector("#manualTable tbody");
        if(!tbody) return alert("Shipment table nahi mili.");
        tbody.innerHTML=""; // Import means replace current shipment rows; prevents extra blank row.
        let rows=[];
        data.forEach((cols,i)=>{
            if(i>=rows.length){ addManualRow(); rows=[...tbody.querySelectorAll("tr")]; }
            gcSetRow(rows[i],cols);
        });
        renumberManualRows();
        applyBillingMethodUI();
        if(typeof calculateInvoice==="function") calculateInvoice();
        alert(data.length+" entries import ho gayi.");
        this.value="";
    };
    r.readAsText(file);
});



/* ==========================================
   MANUAL SHIPMENT EXPORT
   Excel-compatible UTF-8 CSV
   Keeps WEIGHT and BOX data separate.
========================================== */
function gcCsvEscape(value){
    value = String(value ?? "");
    if(/[",\r\n]/.test(value)){
        value = '"' + value.replace(/"/g,'""') + '"';
    }
    return value;
}

function gcDownloadCsv(filename, rows){
    const csv = rows.map(row => row.map(gcCsvEscape).join(",")).join("\r\n");
    const blob = new Blob(["\uFEFF" + csv], {type:"text/csv;charset=utf-8;"});
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(()=>URL.revokeObjectURL(url), 1000);
}

document.getElementById("exportShipmentBtn")?.addEventListener("click", function(){
    const method = billingMethodMode();

    if(method === "VEHICLE"){
        alert("Vehicle billing ka export alag rakha gaya hai. Is button se KG / Weight ya Box shipment export hoga.");
        return;
    }

    const tableRows = [...document.querySelectorAll("#manualTable tbody tr")];
    if(tableRows.length === 0){
        alert("Export ke liye shipment row nahi hai.");
        return;
    }

    let headers, names;

    if(method === "BOX"){
        headers = [
            "Date","Tracking","AWB","Sender","Receiver","Destination","Carrier",
            "Boxes","Rate","POD Charge","ODA Charge"
        ];
        names = [
            "manual_date","gauraa_tracking","third_party_tracking","sender_name",
            "receiver_name","destination","carrier","boxes","rate","pod_charge","oda_charge"
        ];
    }else{
        headers = [
            "Date","Tracking","AWB","Sender","Receiver","Destination","Carrier",
            "Weight","L","W","H","Boxes","Rate","POD Charge","ODA Charge"
        ];
        names = [
            "manual_date","gauraa_tracking","third_party_tracking","sender_name",
            "receiver_name","destination","carrier","actual_weight","length","width",
            "height","boxes","rate","pod_charge","oda_charge"
        ];
    }

    const rows = [headers];

    tableRows.forEach(function(row){
        rows.push(names.map(function(name){
            return row.querySelector(`[name="${name}[]"]`)?.value ?? "";
        }));
    });

    const today = new Date().toISOString().slice(0,10);
    const suffix = method === "BOX" ? "BOX" : "WEIGHT";
    gcDownloadCsv(`Gauraa_Billing_${suffix}_${today}.csv`, rows);
});

document.addEventListener("input",function(e){
    if(e.target && e.target.matches(".pod-charge,.oda-charge")){
        calculateInvoice();
    }
});
</script>


<!-- ==========================
     FORM CLOSE
========================== -->

</form>

</div>

</div>

</div>



<?php include "../layouts/footer.php"; ?>