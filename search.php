<!DOCTYPE html>
<html>
<?php 
include 'includes/header.php'?>
<link href="assets/css/search.css" rel="stylesheet">

<body class="gradient-custom">

<?php 
include 'includes/navbar.php';
include 'includes/searchbar.php';

$db = new SQLite3(__DIR__ . '/database.sqlite');

$departure_city = trim($_POST['fromCity'] ?? '');
$destination_city = trim($_POST['toCity'] ?? '');
$date = $_POST['date'] ?? '';

$USER_ID = $_SESSION['user_id'] ?? null;
$user_info = null;

if ($USER_ID && ($_SESSION['role'] ?? '') == "user") {

    $user_stmt = $db->prepare('SELECT full_name, email, balance FROM User WHERE id = ? AND role = "user"');
    $user_stmt->bindValue(1, $USER_ID, SQLITE3_TEXT);
    $user_result = $user_stmt->execute();
    $user_info = $user_result->fetchArray(SQLITE3_ASSOC);
}


$checkStmt = $db->prepare('SELECT 
    T.*, 
    BC.name as company_name, 
    BC.logo_path 
    FROM Trips T
    JOIN Bus_Company BC ON T.company_id = BC.id
    WHERE T.departure_city = ? AND T.destination_city = ? AND T.departure_time LIKE ?
    ORDER BY T.departure_time ASC');
$checkStmt->bindValue(1, $departure_city, SQLITE3_TEXT);
$checkStmt->bindValue(2, $destination_city, SQLITE3_TEXT);
$checkStmt->bindValue(3, $date . '%', SQLITE3_TEXT);
$trips_query = $checkStmt->execute();

function render_seat_dynamic($seat_num, $booked_seats) {
    $class = "seat";

    if (in_array($seat_num, $booked_seats)) {
        $class .= " booked";
    }
    
    return "<div class=\"{$class}\" data-seat-number=\"{$seat_num}\">{$seat_num}</div>";
}
?>
                    
<div class="container py-4">

<?php 
if ($trips_query): 
    while ($trip = $trips_query->fetchArray(SQLITE3_ASSOC)): 
        $booked_seats_list = [];
        $booked_seats_query = $db->prepare("
            SELECT 
                BS.seat_number
            FROM Booked_Seats BS
            JOIN Tickets T ON BS.ticket_id = T.id
            WHERE T.trip_id = ? AND T.status = 'active'
        ");
        $booked_seats_query->bindValue(1, $trip['id'], SQLITE3_TEXT);
        $booked_results = $booked_seats_query->execute();

        while ($booked_seat = $booked_results->fetchArray(SQLITE3_ASSOC)) {
            $booked_seats_list[] = (int)$booked_seat['seat_number'];
        }

        $departure_datetime = new DateTime($trip['departure_time']);
        $arrival_datetime = new DateTime($trip['arrival_time']);
        $departure_time_str = $departure_datetime->format('H:i');
        $arrival_time_str = $arrival_datetime->format('H:i');
        $arrival_date_str = $arrival_datetime->format('d.m.Y');
        $departure_date_str = $departure_datetime->format('d.m.Y');
        
        $collapse_id = 'sefer_' . htmlspecialchars($trip['id']);

        $applicable_coupons_for_trip = [];
        if ($USER_ID && $user_info) {
            $coupon_stmt = $db->prepare("
                SELECT 
                    C.code, 
                    C.discount,
                    C.usage_limit
                FROM Coupons C
                JOIN User_Coupons UC ON C.id = UC.coupon_id
                WHERE UC.user_id = :user_id
                  AND (C.company_id IS NULL OR C.company_id = :company_id) -- Genel veya firma kuponu
                  AND C.expire_date >= DATE('now')
            ");
            $coupon_stmt->bindValue(':user_id', $USER_ID, SQLITE3_TEXT);
            $coupon_stmt->bindValue(':company_id', $trip['company_id'], SQLITE3_TEXT); 
            $coupon_results = $coupon_stmt->execute();
    
            while ($row = $coupon_results->fetchArray(SQLITE3_ASSOC)) {
                $applicable_coupons_for_trip[] = $row;
            }
        }
?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= htmlspecialchars($trip['logo_path'] ?? 'placeholder_logo.png') ?>" height="40" alt="<?= htmlspecialchars($trip['company_name']) ?> Logo">
                <div>
                    <h5 class="mb-1 fw-bold"><?= htmlspecialchars($trip['company_name']) ?></h5>
                    <div class="text-muted small">
                        <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <div class="fw-bold fs-5 text-dark"><?= $departure_time_str ?></div>
                <div class="text-muted small">Kalkış</div>
                <div class="text-muted small fw-bold mt-1">
                    <?= $departure_date_str ?>
                </div>
            </div>
            
            <div class="text-center">
                <div class="fw-bold fs-5 text-dark"><?= $arrival_time_str ?></div>
                <div class="text-muted small">Varış</div>
                <div class="text-muted small fw-bold mt-1">
                    <?= $arrival_date_str ?>
                </div>
            </div>

            <div class="text-end">
                <div class="fw-bold fs-5 purple-text"><?= number_format($trip['price'], 2) ?> TL</div>
                <div class="text-muted small">Kapasite: <?= (int)$trip['capacity'] ?></div>
            </div>
            <button class="btn mainblue ms-3" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>">KOLTUK SEÇ</button>
        </div>

        <div id="<?= $collapse_id ?>" class="collapse border-top">
            <div class="p-4">
                <h6 class="fw-bold mb-3">Sefer Detayları</h6>
                <p class="small text-muted mb-4">
                    Sefer ID: <?= htmlspecialchars($trip['id']) ?> | Otobüs Kapasitesi: <?= htmlspecialchars($trip['capacity']) ?>
                </p>

                <form method="POST" action="backend/checkout.php" class="seat-selection-form" data-trip-id="<?= htmlspecialchars($trip['id']) ?>"> 

                    <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">
                    <input type="hidden" name="selected_seat" id="selected_seat_<?= htmlspecialchars($trip['id']) ?>" required> 

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="bg-white border rounded p-3">
                                <div class="seat-map-container">
                                    <div class="bus-front">ŞOFÖR</div>
                                    
                                    <?php
                                    $seat_number = 1;
                                    
                                    for ($i = 0; $i < 4; $i++) {
                                        echo "<div class=\"bus-row\">";
                                        echo "<div class=\"seat-group\">";

                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo "</div>";
                                        echo "<div class=\"aisle-space\"></div>";
                                        echo "<div class=\"seat-group\">";

                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo "</div>";
                                        echo "</div>";
                                    }

                                    echo "<div class=\"bus-row\"><div class=\"door-space\"></div><div class=\"aisle-space\"></div><div class=\"door-space\"></div></div>";

                                    for ($i = 0; $i < 6; $i++) {
                                        echo "<div class=\"bus-row\">";
                                        echo "<div class=\"seat-group\">";

                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo "</div>";
                                        echo "<div class=\"aisle-space\"></div>";
                                        echo "<div class=\"seat-group\">";

                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo render_seat_dynamic($seat_number++, $booked_seats_list);
                                        echo "</div>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="p-3 bg-light border rounded">
                                <p class="mb-1"><span class="badge bg-warning">Sarı</span> Dolu</p>
                                <p class="mb-3"><span class="badge bg-success">Yeşil</span> Seçilen</p>

                                <?php if ($USER_ID && ($_SESSION['role'] ?? '') == "user" && $user_info): ?>

                                    <h5 class="mt-4 mb-3">Kullanıcı Bilgileri</h5>
                                    <ul class="list-group mb-4">
                                      <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Ad Soyad:
                                        <span><?= htmlspecialchars($user_info['full_name']) ?></span>
                                      </li>
                                      <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Email:
                                        <span><?= htmlspecialchars($user_info['email']) ?></span>
                                      </li>
                                      <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Bakiyeniz:
                                        <span class="fw-bold"><?= number_format($user_info['balance'], 2) ?> TL</span>
                                      </li>
                                      <li class="list-group-item d-flex justify-content-between align-items-center bg-info">
                                        Bilet Fiyat:
                                        <span class="fw-bold"><?= number_format($trip['price'], 2) ?> TL</span>
                                      </li>
                                    </ul>
                                
                                    <h5 class="mt-4 mb-3">Seferinize Uygun Kuponlar</h5>

                                    <?php if (!empty($applicable_coupons_for_trip)): ?>
                                        <div class="list-group mb-3 coupon-list">
                                            <?php foreach ($applicable_coupons_for_trip as $coupon): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1 coupon-radio" type="radio" name="coupon" value="<?= htmlspecialchars($coupon['code']) ?>">
                                                     <?= htmlspecialchars($coupon['code']) ?> (%<?= htmlspecialchars($coupon['discount']) ?> İndirim)
                                                    <small class="text-muted d-block">Kullanım Limiti: <?= htmlspecialchars($coupon['usage_limit']) ?></small>
                                                </label>
                                            <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Bu sefere uygun, size atanmış aktif bir kuponunuz bulunamadı.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button type="submit" class="btn btn-success w-100 mt-2 submit-button" disabled>Onayla ve satın al</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php 
    endwhile; 
else: 
?>
    <div class="alert alert-warning text-center">Belirtilen kriterlere uygun sefer bulunamadı.</div>
<?php 
endif; 
?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    
    document.querySelectorAll('.seat-map-container').forEach(mapContainer => {
      mapContainer.addEventListener('click', (event) => {
        const seat = event.target.closest('.seat');
        
        if (seat && seat.dataset.seatNumber && !seat.classList.contains('booked')) { 
          
          const currentForm = seat.closest('form.seat-selection-form');
          if (!currentForm) return; 

          const tripId = currentForm.dataset.tripId;
          const hiddenSeatInput = currentForm.querySelector('input[name="selected_seat"]');
          const submitButton = currentForm.querySelector('.submit-button');

          let isSelected = seat.classList.contains('selected');

          currentForm.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
          
          if (isSelected) {
            hiddenSeatInput.value = ''; 
            submitButton.disabled = true;

          } else {
            seat.classList.add('selected');
            hiddenSeatInput.value = seat.dataset.seatNumber;
            submitButton.disabled = false;
          }
        }
      });
    });

    document.querySelectorAll('form.seat-selection-form').forEach(form => {
        const couponInput = form.querySelector('.coupon-input');
        const couponRadios = form.querySelectorAll('.coupon-radio');
        couponRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    if(couponInput) couponInput.value = this.value;
                }
            });
        });

        if (couponInput) {
            couponInput.addEventListener('input', function() {
                couponRadios.forEach(radio => radio.checked = false);
            });
        }
    });

  });
</script>

</body>
</html>