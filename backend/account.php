<?php
session_start();
$db = new SQLite3(__DIR__ . '/../database.sqlite');

require_once __DIR__ . '/../assets/scripts/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;


if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'user') {
    die(header("Location: ../login.php?error=Yetkisiz işlem!"));
}
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'cancelTrip':
        cancelTrip($db); break;
    case 'downloadPDF':
        downloadPDF($db); break;
    default:
    header("Location: ../account.php?error=Bilinmeyen işlem"); break;
}

function cancelTrip($db)  {
    $db->exec('BEGIN'); 
    $ticket_id = trim($_POST['ticket_id'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($ticket_id)) {
    header("Location: ../account.php?error=Bilet ID gönderilmedi.");
    exit;
    }

try {
    $checkTicketStmt = $db->prepare("
        SELECT 
            T.total_price, 
            TR.departure_time,
            T.status 
        FROM Tickets T 
        JOIN Trips TR ON T.trip_id = TR.id 
        WHERE T.id = :ticket_id AND T.user_id = :user_id AND T.status = 'active'
    ");
    $checkTicketStmt->bindValue(':ticket_id', $ticket_id, SQLITE3_TEXT);
    $checkTicketStmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);
    $ticketData = $checkTicketStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$ticketData) {
        throw new Exception("Bilet bulunamadı, aktif değil veya size ait değil.");
    }

    $departure_time = strtotime($ticketData['departure_time']);
    $time_remaining = $departure_time - time();
    $min_cancellation_time = 3600;

    if ($time_remaining < $min_cancellation_time) {
        throw new Exception("Kalkışa 1 saatten az kaldığı için iptal edilemez.");
    }

    $refund_amount = $ticketData['total_price'];
    
    $updateTicketStmt = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ? AND user_id = ?");
    $updateTicketStmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
    $updateTicketStmt->bindValue(2, $user_id, SQLITE3_TEXT);
    if (!$updateTicketStmt->execute()) {
         throw new Exception("Bilet durumu güncellenemedi.");
    }

    $deleteSeatsStmt = $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
    $deleteSeatsStmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
    if (!$deleteSeatsStmt->execute()) {
         throw new Exception("Koltuklar serbest bırakılamadı.");
    }

    $user_balance_stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
    $user_balance_stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $user_row = $user_balance_stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$user_row) {
         throw new Exception("Kullanıcı bakiyesi bulunamadı.");
    }
    
    $current_balance = $user_row['balance'];
    $new_balance = $current_balance + $refund_amount;
    
    $refund_stmt = $db->prepare("UPDATE User SET balance = ? WHERE id = ?");
    $refund_stmt->bindValue(1, $new_balance, SQLITE3_INTEGER);
    $refund_stmt->bindValue(2, $user_id, SQLITE3_TEXT);
    if (!$refund_stmt->execute()) {
        throw new Exception("Para iadesi başarısız oldu.");
    }
    
    $db->exec('COMMIT');
    header("Location: ../account.php?success=Bilet başarıyla iptal edildi ve " . number_format($refund_amount, 2) . " TL bakiyenize iade edildi.");

} catch (Exception $e) {
    $db->exec('ROLLBACK');
    header("Location: ../account.php?error=Bilet iptal edilirken bir hata oluştu: " . $e->getMessage());
    exit;
}
}

function downloadPDF($db)  {
$ticket_id = trim($_POST['ticket_id'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($ticket_id) || empty($user_id)) {
    die("Geçersiz bilet ID'si veya oturum yok.");
}

$stmt = $db->prepare("
    SELECT 
        T.id, T.total_price, T.status, T.created_at,
        TR.departure_city, TR.destination_city, TR.departure_time, TR.arrival_time,
        BC.name as company_name,
        BS.seat_number,
        U.full_name as user_full_name,
        U.email as user_email
    FROM Tickets T
    JOIN Trips TR ON T.trip_id = TR.id
    JOIN Bus_Company BC ON TR.company_id = BC.id
    LEFT JOIN Booked_Seats BS ON T.id = BS.ticket_id
    JOIN User U ON T.user_id = U.id
    WHERE T.id = :ticket_id AND T.user_id = :user_id AND T.status = 'active'
");
$stmt->bindValue(':ticket_id', $ticket_id, SQLITE3_TEXT);
$stmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);
$ticket_data = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$ticket_data) {
    die("Aktif bilet bulunamadı veya size ait değil.");
}

$departure_time = (new DateTime($ticket_data['departure_time']))->format('d.m.Y H:i');
$arrival_time = (new DateTime($ticket_data['arrival_time']))->format('d.m.Y H:i');
$purchase_time = (new DateTime($ticket_data['created_at']))->format('d.m.Y H:i');


$html = '
<html>
<head>
    <style>
    body { font-family: DejaVu Sans, sans-serif; } /* Türkçe karakterler için önemli */
    .container { width: 80%; margin: auto; padding: 20px; border: 2px solid #007bff; border-radius: 10px; }
    h1 { color: #007bff; text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    .ticket-section { margin-top: 20px; padding: 10px; border: 1px dashed #ccc; }
    .info-row { margin-bottom: 10px; }
    .info-row span { font-weight: bold; display: inline-block; width: 150px; }
    .price { font-size: 24px; color: #dc3545; font-weight: bold; margin-top: 20px; text-align: right; }
    .warning { color: #ffc107; text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Online Bilet - ' . htmlspecialchars($ticket_data['company_name']) . '</h1>

        <div class="ticket-section">
            <h3 style="color:#007bff;">Yolcu Bilgileri</h3>
            <div class="info-row"><span>Ad Soyad:</span> ' . htmlspecialchars($ticket_data['user_full_name']) . '</div>
            <div class="info-row"><span>E-posta:</span> ' . htmlspecialchars($ticket_data['user_email']) . '</div>
            <div class="info-row"><span>Bilet ID:</span> ' . htmlspecialchars($ticket_data['id']) . '</div>
        </div>

        <div class="ticket-section">
            <h3 style="color:#007bff;">Sefer Bilgileri</h3>
            <div class="info-row"><span>Güzergah:</span> ' . htmlspecialchars($ticket_data['departure_city']) . ' → ' . htmlspecialchars($ticket_data['destination_city']) . '</div>
            <div class="info-row"><span>Kalkış:</span> ' . $departure_time . '</div>
            <div class="info-row"><span>Varış:</span> ' . $arrival_time . '</div>
            <div class="info-row"><span>Koltuk No:</span> <span style="font-size: 1.2em; color: #28a745;">' . htmlspecialchars($ticket_data['seat_number'] ?? 'N/A') . '</span></div>
        </div>
        
        <div class="price">Ödenen Tutar: ' . number_format($ticket_data['total_price'], 2) . ' TL</div>
        
        <div class="warning">
            <p>Bilet satın alma tarihi: ' . $purchase_time . '</p>
            <p>Lütfen kalkıştan 30 dakika önce terminalde olunuz.</p>
        </div>
    </div>
</body>
</html>';

$options = new Options();

$options->set('defaultFont', 'DejaVu Sans'); 
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream('bilet_' . htmlspecialchars($ticket_data['departure_city']) . '-' . htmlspecialchars($ticket_data['destination_city']) . '.pdf', [
    "Attachment" => true
]);
exit;
}

?>