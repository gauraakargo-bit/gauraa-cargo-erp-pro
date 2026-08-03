<?php

require "../../config/auth.php";

$customer = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');

if ($customer <= 0) {
    exit("<tr><td colspan='9' class='text-center text-danger p-3'>Customer not selected.</td></tr>");
}

$sql = "
    SELECT *
    FROM bookings
    WHERE customer_id = :customer
";

$params = [
    ':customer' => $customer
];

if ($from !== '' && $to !== '') {
    $sql .= " AND booking_date BETWEEN :from AND :to";
    $params[':from'] = $from;
    $params[':to']   = $to;
}

/*
  Load only unbilled bookings.
  If billing_status does not exist in your bookings table,
  this condition will need to be removed/changed.
*/
$sql .= "
    AND (
        billing_status IS NULL
        OR billing_status = 'Unbilled'
        OR billing_status = '0'
    )
    ORDER BY booking_date ASC, id ASC
";

try {

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    exit(
        "<tr><td colspan='9' class='text-center text-danger p-3'>".
        "Unable to load bookings: ".htmlspecialchars($e->getMessage()).
        "</td></tr>"
    );
}

if (!$data) {
    exit("<tr><td colspan='9' class='text-center text-muted p-4'>No Booking Found</td></tr>");
}

foreach ($data as $row) {

    $id = (int)($row['id'] ?? 0);

    $bookingNo = (string)($row['booking_no'] ?? '');
    $bookingDate = (string)($row['booking_date'] ?? '');
    $receiver = (string)($row['receiver_name'] ?? '');
    $destination = (string)($row['destination'] ?? '');
    $partner = (string)($row['partner_name'] ?? '');

    $carrierTracking = (string)($row['awb_no'] ?? '');

    $weight = (float)($row['chargeable_weight'] ?? 0);
    $amount = (float)($row['total_amount'] ?? 0);

    $dateText = '';
    if ($bookingDate !== '') {
        $ts = strtotime($bookingDate);
        $dateText = $ts ? date('d-m-Y', $ts) : $bookingDate;
    }
?>
<tr>

    <td class="text-center">
        <input
            type="checkbox"
            class="booking-check"
            name="booking_ids[]"
            value="<?= $id; ?>"
            data-amount="<?= htmlspecialchars((string)$amount, ENT_QUOTES, 'UTF-8'); ?>"
        >
    </td>

    <td>
        <strong><?= htmlspecialchars($bookingNo, ENT_QUOTES, 'UTF-8'); ?></strong>
    </td>

    <td>
        <?= htmlspecialchars($dateText, ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?= htmlspecialchars($receiver, ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?= htmlspecialchars($partner, ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php if ($carrierTracking !== '') { ?>
            <div>
                <small class="text-muted">Carrier:</small>
                <strong><?= htmlspecialchars($carrierTracking, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php } ?>

        <div>
            <small class="text-muted">GAURAA:</small>
            <strong><?= htmlspecialchars($bookingNo, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
    </td>

    <td class="text-end">
        <?= number_format($weight, 2); ?>
    </td>

    <td class="text-end">
        <strong>₹ <?= number_format($amount, 2); ?></strong>
    </td>

</tr>
<?php
}
?>
