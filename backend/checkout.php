<?php

session_start();
$db = new SQLite3(__DIR__ . '/../database.sqlite');

if(!isset($_SESSION['user_id'])){
    die(header("Location: ../login.php?error=Bilet almak için giriş yapınız."));
}
elseif ($_SESSION['role'] != "user") {
    die(header("Location: ../index.php?error=Adminler bilet alamaz!"));
}

$trip_id = $_POST['trip_id'] ?? '';
$selected_seat = $_POST['selected_seat'] ?? '';
$coupon = $_POST['coupon'] ?? '';
$user_id = $_SESSION['user_id'];
$coupon_discount= 0;
$coupon_usage_limit = 0;


if (empty($trip_id) || empty($selected_seat) || empty($user_id)) {
        die("Lütfen tüm alanları doldurun.");
}
else if (!empty($coupon)) {
    $stmt = $db->prepare('SELECT c.* FROM Coupons c JOIN User_Coupons uc ON c.id = uc.coupon_id WHERE uc.user_id = ? AND c.code = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $stmt->bindValue(2, $coupon, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result) {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $coupon_discount = $row['discount'];
    $coupon_code = $row['code'];
    $coupon_usage_limit = $row['usage_limit'];
    $coupon_expire_date = $row['expire_date'];

    $today = date('Y-m-d'); 
    if ($coupon_expire_date < $today || $coupon_usage_limit <= 0) {
        header("Location: ../checkout.php?error=Kuponun son kullanma tarihi geçmiş ya da kullanma limiti dolmuştur.");
    }
    
    } else {
    header("Location: ../checkout.php?error=Seçim şansın olmayan bir yerde sana ait olmayan ya da varolmayan bir kupon girdin.İnanılmaz!");
    }

}

function generateRandomID($length = 24) {
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

$stmt = $db->prepare('SELECT * FROM Trips WHERE id = ?');
$stmt->bindValue(1, $trip_id, SQLITE3_TEXT);
$result = $stmt->execute();
if ($result) {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $trip_price = $row['price'];

    $stmt = $db->prepare('SELECT * FROM User WHERE id = ?');
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $user_balance = $row['balance'];

        $new_ticket_price = $trip_price - (($coupon_discount / 100) * $trip_price);

        if ($new_ticket_price > $user_balance) {
            header("Location: ../checkout.php?error=Yetersiz Bakiye!");
        }else {

            try {
                $db->exec('BEGIN TRANSACTION');

            do {
            $ticket_id = generateRandomID();
            $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Tickets WHERE id = ?');
            $checkIdStmt->bindValue(1, $ticket_id, SQLITE3_TEXT);
            $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
            } while ($idExists > 0);
        
            $created_at = date('D-m-y H:i:s');
            $status = "active";

            $ticket_create = $db->prepare('INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $ticket_create->bindValue(1, $ticket_id, SQLITE3_TEXT);
            $ticket_create->bindValue(2, $trip_id, SQLITE3_TEXT);
            $ticket_create->bindValue(3, $user_id, SQLITE3_TEXT);
            $ticket_create->bindValue(4, $status, SQLITE3_TEXT);
            $ticket_create->bindValue(5, $new_ticket_price, SQLITE3_TEXT);
            $ticket_create->bindValue(6, $created_at, SQLITE3_TEXT);
            if (!$ticket_create->execute()) {
                throw new Exception("Bilet oluşturulamadı.");
            }


            do {
            $seat_id = generateRandomID();
            $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Booked_Seats WHERE id = ?');
            $checkIdStmt->bindValue(1, $seat_id, SQLITE3_TEXT);
            $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
            } while ($idExists > 0);

            $book_seat = $db->prepare('INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, ?)');
            $book_seat->bindValue(1, $seat_id, SQLITE3_TEXT);
            $book_seat->bindValue(2, $ticket_id, SQLITE3_TEXT);
            $book_seat->bindValue(3, $selected_seat, SQLITE3_TEXT);
            $book_seat->bindValue(4, $created_at, SQLITE3_TEXT);
            if (!$book_seat->execute()) {
                throw new Exception("Koltuk kaydı yapılamadı.");
            }

            $new_balance = $user_balance - $new_ticket_price;

            $take_money = $db->prepare('UPDATE User SET balance = ? WHERE id = ?');
            $take_money->bindValue(1, $new_balance, SQLITE3_INTEGER);
            $take_money->bindValue(2, $user_id, SQLITE3_TEXT);
            if (!$take_money->execute()) {
                throw new Exception("Bakiye güncellenemedi.");
            }

            if (!empty($coupon)) {
                $new_limit = $coupon_usage_limit - 1;
                $decrase_limit = $db->prepare('UPDATE Coupons SET usage_limit = ? WHERE code = ?');
                $decrase_limit->bindValue(1, $new_limit, SQLITE3_INTEGER);
                $decrase_limit->bindValue(2, $coupon_code, SQLITE3_TEXT);
                $decrase_limit->execute();
                if (!$decrase_limit->execute()) {
                    throw new Exception("Kupon güncellenemedi.");
                }
            }

            $db->exec('COMMIT');
            die(header("Location: ../checkout.php?status=success&ticket_id=" . urlencode($ticket_id)));

                
            } catch (Exception $e) {
                $db->exec('ROLLBACK');
                error_log("Bilet oluşturma hatası: " . $e->getMessage());
                die(header("Location: ../checkout.php?status=error"));
            }

        }
        
    }
    else {
       header("Location: ../checkout.php?error=Böyle bir kullanıcı yok!");
    }

}
else {
    header("Location: ../checkout.php?error=Böyle bir sefer yok!");
}




?>