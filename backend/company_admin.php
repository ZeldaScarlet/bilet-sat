<?php
session_start();

$db = new SQLite3(__DIR__ . '/../database.sqlite');
$action = $_POST['action'] ?? '';

if(!isset($_SESSION['user_id'])){
    die(header("Location: ../login.php?error=Yetkisiz"));
}
elseif ($_SESSION['role'] != "company") {
    die(header("Location: ../index.php?error=Yetkisiz"));
}


$company_id = $_SESSION['company_id'];

if (empty($company_id)) {
    die(header("Location: ../company_admin.php?error=Oturum bilgisi bulunamadı."));
}


switch ($action) {
    case 'addTrip':
        addTrip($db, $company_id); break;
    case 'deleteTrip':
        deleteTrip($db, $company_id); break;
    case 'editTrip':
        editTrip($db, $company_id); break;
     case 'addCoupon':
        addCoupon($db, $company_id); break;
    case 'deleteCoupon':
        deleteCoupon($db, $company_id); break;
    case 'editCoupon':
        editCoupon($db, $company_id); break;
    case 'cancelTicket':
        cancelTicket($db, $company_id); break; 
        
    default:
        die(header("Location: ../company_admin.php?error=Bilinmeyen işlem!"));
}

function generateRandomID($length = 24) {
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}


function addTrip($db, $company_id) {
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $arrival_time = $_POST['arrival_time'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $capacity = 40;

    if (empty($departure_city) || empty($destination_city) || empty($arrival_time) || empty($departure_time) || $price <= 0) {
        die(header("Location: ../company_admin.php?error=Tüm sefer alanları eksiksiz doldurulmalıdır!"));
    }

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Trips WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $created_at = date('D-m-y H:i:s');

    $insertStmt = $db->prepare('INSERT INTO Trips (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $company_id, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $destination_city, SQLITE3_TEXT);
    $insertStmt->bindValue(4, $arrival_time, SQLITE3_TEXT);
    $insertStmt->bindValue(5, $departure_time, SQLITE3_TEXT);
    $insertStmt->bindValue(6, $departure_city, SQLITE3_TEXT);
    $insertStmt->bindValue(7, $price, SQLITE3_INTEGER);
    $insertStmt->bindValue(8, $capacity, SQLITE3_INTEGER);
    $insertStmt->bindValue(9, $created_at, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Sefer başarıyla eklendi!");
        exit;
    } else {
        header("Location: ../company_admin.php?error=Sefer eklenirken bir hata oluştu.");
        exit;
    }
}

function editTrip($db, $company_id)  {
    $id = trim($_POST['id'] ?? '');
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $arrival_time = $_POST['arrival_time'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    
    if (empty($id) || empty($departure_city) || empty($destination_city) || empty($arrival_time) || empty($departure_time) || $price <= 0) {
        die(header("Location: ../company_admin.php?error=Tüm sefer alanları eksiksiz doldurulmalıdır!"));
    }
    
    $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM Trips WHERE id = ? AND company_id = ?');
    $checkStmt->bindValue(1, $id, SQLITE3_TEXT);
    $checkStmt->bindValue(2, $company_id, SQLITE3_TEXT);
    if ($checkStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'] == 0) {
        die(header("Location: ../company_admin.php?error=Yetkisiz işlem!"));
    }


    $updateStmt = $db->prepare('UPDATE Trips SET departure_city = ?, destination_city = ?, arrival_time = ?, departure_time = ?, price = ? WHERE id = ? AND company_id = ?');
    $updateStmt->bindValue(1, $departure_city, SQLITE3_TEXT);
    $updateStmt->bindValue(2, $destination_city, SQLITE3_TEXT);
    $updateStmt->bindValue(3, $arrival_time, SQLITE3_TEXT);
    $updateStmt->bindValue(4, $departure_time, SQLITE3_TEXT);
    $updateStmt->bindValue(5, $price, SQLITE3_INTEGER);
    $updateStmt->bindValue(6, $id, SQLITE3_TEXT);
    $updateStmt->bindValue(7, $company_id, SQLITE3_TEXT);

    $result = $updateStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Sefer başarıyla güncellendi!");
        exit;
    } else {
        header("Location: ../company_admin.php?error=Sefer güncellenirken bir hata oluştu.");
        exit;
    }
}

function deleteTrip($db, $company_id)  {
    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        header("Location: ../company_admin.php?error=Sefer ID gönderilmedi.");
        exit;
    }
    
    $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM Trips WHERE id = ? AND company_id = ?');
    $checkStmt->bindValue(1, $id, SQLITE3_TEXT);
    $checkStmt->bindValue(2, $company_id, SQLITE3_TEXT);
    if ($checkStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'] == 0) {
        die(header("Location: ../company_admin.php?error=Yetkisiz işlem!"));
    }

    $deleteStmt = $db->prepare('DELETE FROM Trips Where id = ? AND company_id = ?');
    $deleteStmt->bindValue(1, $id, SQLITE3_TEXT);
    $deleteStmt->bindValue(2, $company_id, SQLITE3_TEXT);

    $result = $deleteStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Sefer başarıyla silindi.");
        exit;
    } else {
        header("Location: ../company_admin.php?error=Sefer silinirken bir hata oluştu!");
        exit;
    }

}

function addCoupon($db, $company_id)  {
    $code = trim($_POST['code'] ?? '');
    $discount = (int)trim($_POST['discount'] ?? 0);
    $usage_limit = (int)($_POST['usage_limit'] ?? 0);
    $expire_date = $_POST['expire_date'] ?? '';

    if (empty($code) || $discount <= 0 || $usage_limit <= 0 || empty($expire_date)) {
        die(header("Location: ../company_admin.php?error=Lütfen tüm alanları doldurun."));
    }
    

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Coupons WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $created_at = date('Y-m-d H:i:s');

    
    $insertStmt = $db->prepare('INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $code, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $discount, SQLITE3_INTEGER);
    $insertStmt->bindValue(4, $usage_limit, SQLITE3_INTEGER);
    $insertStmt->bindValue(5, $expire_date, SQLITE3_TEXT);
    $insertStmt->bindValue(6, $company_id, SQLITE3_TEXT);
    $insertStmt->bindValue(7, $created_at, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Kupon başarıyla oluşturuldu.!");
        exit;
    } else {
        header("Location: ../company_admin.php?error=Kupon oluşturma işlemi başarısız oldu.");
        exit;
    }
    
}

function editCoupon($db, $company_id) {
    $id = trim($_POST['id'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $discount = (int)trim($_POST['discount'] ?? 0);
    $usage_limit = (int)($_POST['usage_limit'] ?? 0);
    $expire_date = $_POST['expire_date'] ?? '';

    if (empty($id) || empty($code) || $discount <= 0 || $usage_limit <= 0 || empty($expire_date)) {
        die(header("Location: ../company_admin.php?error=Tüm kupon alanları eksiksiz doldurulmalıdır!"));
    }
    
    $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM Coupons WHERE id = ? AND company_id = ?');
    $checkStmt->bindValue(1, $id, SQLITE3_TEXT);
    $checkStmt->bindValue(2, $company_id, SQLITE3_TEXT);
    if ($checkStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'] == 0) {
        die(header("Location: ../company_admin.php?error=Yetkisiz işlem!"));
    }


    $insertStmt = $db->prepare('UPDATE Coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ? WHERE id = ? AND company_id = ?');
    $insertStmt->bindValue(1, $code, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $discount, SQLITE3_INTEGER);
    $insertStmt->bindValue(3, $usage_limit, SQLITE3_INTEGER);
    $insertStmt->bindValue(4, $expire_date, SQLITE3_TEXT);
    $insertStmt->bindValue(5, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(6, $company_id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Kupon başarıyla güncellendi!",);
        exit;
    } else {
        header("Location: ../company_admin.php?error=Kupon güncellenirken bir hata oluştu.");
        exit;
    }

}

function deleteCoupon($db, $company_id)  {
    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        header("Location: ../company_admin.php?error=Kupon ID gönderilmedi.");
        exit;
    }
    
    $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM Coupons WHERE id = ? AND company_id = ?');
    $checkStmt->bindValue(1, $id, SQLITE3_TEXT);
    $checkStmt->bindValue(2, $company_id, SQLITE3_TEXT);
    if ($checkStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'] == 0) {
        die(header("Location: ../company_admin.php?error=Yetkisiz işlem!"));
    }


    $insertStmt = $db->prepare('DELETE FROM Coupons Where id = ? AND company_id = ?');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $company_id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../company_admin.php?success=Kupon başarıyla silindi.");
        exit;
    } else {
        header("Location: ../company_admin.php?error=Kupon silinirken bir hata oluştu!");
        exit;
    }

}

function cancelTicket($db, $company_id) {
    $ticket_id = trim($_POST['id'] ?? '');

    if (empty($ticket_id)) {
        header("Location: ../company_admin.php?error=Bilet ID gönderilmedi.");
        exit;
    }

    $db->exec('BEGIN'); 
    try {
        $checkTicketStmt = $db->prepare("
            SELECT 
                T.total_price, 
                T.user_id, 
                TR.company_id,
                T.status 
            FROM Tickets T 
            JOIN Trips TR ON T.trip_id = TR.id 
            WHERE T.id = :ticket_id AND TR.company_id = :company_id AND T.status = 'active'
        ");
        $checkTicketStmt->bindValue(':ticket_id', $ticket_id, SQLITE3_TEXT);
        $checkTicketStmt->bindValue(':company_id', $company_id, SQLITE3_TEXT);
        $ticketData = $checkTicketStmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$ticketData) {
            throw new Exception("Bilet bulunamadı, aktif değil veya yetkiniz yok.");
        }

        $refund_amount = $ticketData['total_price'];
        $user_id_to_refund = $ticketData['user_id'];
        
        $updateTicketStmt = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
        $updateTicketStmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
        if (!$updateTicketStmt->execute()) {
             throw new Exception("Bilet durumu güncellenemedi.");
        }

        $deleteSeatsStmt = $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
        $deleteSeatsStmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
        if (!$deleteSeatsStmt->execute()) {
             throw new Exception("Koltuklar serbest bırakılamadı.");
        }

        $user_balance_stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
        $user_balance_stmt->bindValue(1, $user_id_to_refund, SQLITE3_TEXT);
        $user_row = $user_balance_stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if (!$user_row) {
             throw new Exception("Kullanıcı bakiyesi bulunamadı.");
        }
        
        $current_balance = $user_row['balance'];
        $new_balance = $current_balance + $refund_amount;
        
        $refund_stmt = $db->prepare("UPDATE User SET balance = ? WHERE id = ?");
        $refund_stmt->bindValue(1, $new_balance, SQLITE3_INTEGER);
        $refund_stmt->bindValue(2, $user_id_to_refund, SQLITE3_TEXT);
        if (!$refund_stmt->execute()) {
            throw new Exception("Para iadesi başarısız oldu.");
        }
        
        $db->exec('COMMIT');
        header("Location: ../company_admin.php?success=Bilet başarıyla iptal edildi ve " . number_format($refund_amount, 2) . " TL iade edildi.");
        exit;

    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        
        header("Location: ../company_admin.php?error=Bilet iptal edilirken bir hata oluştu: " . $e->getMessage());
        exit;
    }
}
?>