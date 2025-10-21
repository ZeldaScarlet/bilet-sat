<!DOCTYPE html>
<html>
<?php include 'includes/header.php'?>
<style>
    .success-icon {
        color: #28a745;
        font-size: 6rem;
    }
    .ticket-card {
        border-left: 5px solid #5B2C79;
    }
    .ticket-info {
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
</style>

<body class="gradient-custom">
<?php 
include 'includes/navbar.php';

$db = new SQLite3(__DIR__ . '/database.sqlite');
$status = $_GET['status'] ?? '';
$ticket_id = $_GET['ticket_id'] ?? '';

$ticket_details = null;
$error_message = '';

if ($status == 'success' && !empty($ticket_id)) {
    $stmt = $db->prepare("
        SELECT 
            T.*, 
            TR.departure_city, TR.destination_city, TR.departure_time, TR.arrival_time, TR.price as trip_base_price,
            BC.name as company_name,
            BS.seat_number
        FROM Tickets T
        JOIN Trips TR ON T.trip_id = TR.id
        JOIN Bus_Company BC ON TR.company_id = BC.id
        JOIN Booked_Seats BS ON T.id = BS.ticket_id
        WHERE T.id = ? AND T.user_id = ?
    ");
    $stmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
    $stmt->bindValue(2, $_SESSION['user_id'] ?? '', SQLITE3_TEXT);
    $result = $stmt->execute();
    $ticket_details = $result->fetchArray(SQLITE3_ASSOC);

    if (!$ticket_details) {
        $error_message = "Bilet bulunamadı veya bu bileti görme yetkiniz yok.";
    }
} else {
    $error_message = "Geçersiz işlem veya bilet ID'si eksik.";
}

$coupon_discount_amount = 0;
$coupon_percentage = 0;

if ($ticket_details) {
    if ($ticket_details['total_price'] < $ticket_details['trip_base_price']) {
        $discount_amount = $ticket_details['trip_base_price'] - $ticket_details['total_price'];
        $coupon_percentage = round(($discount_amount / $ticket_details['trip_base_price']) * 100);
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if (!empty($error_message)): ?>
                <div class="card border-danger">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-x-circle success-icon" style="color: #dc3545;"></i>
                        <h2 class="text-danger mt-3">İşlem Başarısız!</h2>
                        <p class="lead"><?= htmlspecialchars($error_message) ?></p>
                        <a href="index.php" class="btn btn-primary mt-3">Ana Sayfaya Dön</a>
                    </div>
                </div>

            <?php elseif ($ticket_details): ?>
                <div class="card ticket-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-check-circle-fill success-icon"></i>
                            <h2 class="text-success mt-3">Biletiniz Başarıyla Satın Alındı!</h2>
                            <p class="lead">İyi yolculuklar dileriz. Detaylar aşağıdadır.</p>
                        </div>
                        
                        <h4 class="mb-4 pink-text">Bilet Bilgileri</h4>
                        
                        <div class="row">
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Sefer:</strong>
                                <?= htmlspecialchars($ticket_details['departure_city']) ?> → <?= htmlspecialchars($ticket_details['destination_city']) ?>
                            </div>
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Firma:</strong>
                                <?= htmlspecialchars($ticket_details['company_name']) ?>
                            </div>
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Kalkış Tarihi/Saati:</strong>
                                <?= (new DateTime($ticket_details['departure_time']))->format('d.m.Y H:i') ?>
                            </div>
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Varış Tarihi/Saati:</strong>
                                <?= (new DateTime($ticket_details['arrival_time']))->format('d.m.Y H:i') ?>
                            </div>
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Koltuk Numarası:</strong>
                                <span class="badge bg-success fs-5"><?= htmlspecialchars($ticket_details['seat_number']) ?></span>
                            </div>
                            <div class="col-md-6 ticket-info">
                                <strong class="d-block">Bilet ID:</strong>
                                <small class="text-muted"><?= htmlspecialchars($ticket_details['id']) ?></small>
                            </div>
                        </div>

                        <h4 class="mb-4 mt-4 pink-text">Ödeme Özeti</h4>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between">
                                Sefer Ücreti:
                                <span><?= number_format($ticket_details['trip_base_price'], 2) ?> TL</span>
                            </li>
                            <?php if ($coupon_percentage > 0): ?>
                                <li class="list-group-item d-flex justify-content-between text-success">
                                    Kupon İndirimi:
                                    <span>-%<?= $coupon_percentage ?> (<?= number_format($discount_amount, 2) ?> TL)</span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between fw-bold bg-light">
                                Ödenen Toplam Tutar:
                                <span class="text-danger fs-5"><?= number_format($ticket_details['total_price'], 2) ?> TL</span>
                            </li>
                        </ul>

                        <div class="text-center mt-5">
                            <a href="profile.php#tickets" class="btn btn-warning me-2">Biletlerimi Görüntüle</a>
                            <a href="index.php" class="btn btn-primary">Yeni Sefer Ara</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                 <div class="card shadow-lg border-danger">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-exclamation-triangle success-icon" style="color: #ffc107;"></i>
                        <h2 class="text-warning mt-3">İşlem Tamamlanamadı</h2>
                        <p class="lead">Bilet detaylarına ulaşılamadı. Lütfen bilet ID'sini kontrol edin veya destek ile iletişime geçin.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Ana Sayfaya Dön</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>