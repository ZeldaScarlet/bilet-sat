<?php

$db = new SQLite3(__DIR__ . '/../database.sqlite');

if(isset($_SESSION['user_id'])){
    die(header("Location: ../index.php?error=Yetkisiz"));
}
elseif ($_SESSION['role'] != "admin") {
    die(header("Location: ../index.php?error=Yetkisiz"));
}


$full_name = trim($_POST['Full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_again = $_POST['password_again'] ?? '';


if (empty($full_name) || empty($email) || empty($password) || empty($password_again)) {
    die("Lütfen tüm alanları doldurun.");
}
elseif ($password !== $password_again){
    die(header("Location: ../register.php?error=Şifreler uyuşmuyor."));
}
else{
    $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM User WHERE email = ?');
    $checkStmt->bindValue(1, $email, SQLITE3_TEXT);
    $result = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result['count'] > 0) {
        die(header("Location: ../register.php?error=Bu email adresiyle zaten bir kullanıcı kayıtlı."));
    }
}

function generateRandomID($length = 24) {
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

do {
    $id = generateRandomID();
    $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM User WHERE id = ?');
    $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
    $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
} while ($idExists > 0);

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$role = 'user';
$balance = 800;
$created_at = date('D-m-y H:i:s');

// Kullanıcıyı ekle
$insertStmt = $db->prepare('INSERT INTO User (id, full_name, email, role, password, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insertStmt->bindValue(1, $id, SQLITE3_TEXT);
$insertStmt->bindValue(2, $full_name, SQLITE3_TEXT);
$insertStmt->bindValue(3, $email, SQLITE3_TEXT);
$insertStmt->bindValue(4, $role, SQLITE3_TEXT);
$insertStmt->bindValue(5, $hashedPassword, SQLITE3_TEXT);
$insertStmt->bindValue(6, $balance, SQLITE3_INTEGER);
$insertStmt->bindValue(7, $created_at, SQLITE3_TEXT);

$result = $insertStmt->execute();

if ($result) {
    header("Location: ../login.php?success=Kayıt başarılı! Giriş yapabilirsiniz.");
} else {
    header("Location: ../register.php?error=Kayıt işlemi başarısız oldu.");
}
?>
