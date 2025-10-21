<?php

if(isset($_SESSION["user_id"]) ){
    die(header("Location: ../index.php?error=Zaten giriş yapmış durumdasınız!"));
}

$db = new SQLite3(__DIR__ . '/../database.sqlite');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);


if (empty($email) || empty($password)) {
    die(header("Location: ../login.php?error=Boş alan bırakmayınız."));
}

$checkStmt = $db->prepare('SELECT * FROM User WHERE email = :email');
$checkStmt->bindValue(':email', $email, SQLITE3_TEXT);
$result = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$result) {
    die(header("Location: ../login.php?error=Şifre ya da email adresi hatalı."));
}
elseif(password_verify($password, $result['password'])){
    session_start();
    $_SESSION['user_id'] = $result['id'];
    $_SESSION['full_name'] = $result['full_name'];
    $_SESSION['email'] = $result['email'];
    $_SESSION['role'] = $result['role'];
    $_SESSION['company_id'] = $result['company_id'];

    header("Location: ../index.php?success=Giriş başarılı. Hoşgeldiniz" );
}
else {
    die(header("Location: ../login.php?error=Şifre ya da email adresi hatalı."));
}

?>