<?php

session_start();

$db = new SQLite3(__DIR__ . '/../database.sqlite');
$action = $_POST['action'] ?? '';

if(!isset($_SESSION['user_id'])){
    die(header("Location: ../login.php?error=Yetkisiz"));
}
elseif ($_SESSION['role'] != "admin") {
    die(header("Location: ../index.php?error=Yetkisiz"));
}

switch ($action) {
    case 'addCompany':
        addCompany($db); break;
    case 'deleteCompany':
        deleteCompany($db); break;
    case 'editCompany':
        editCompany($db); break;
     case 'addCoupon':
        addCoupon($db); break;
    case 'deleteCoupon':
        deleteCoupon($db); break;
    case 'editCoupon':
        editCoupon($db); break;
    case 'assignCompanyAdmin':
        assignCompanyAdmin($db); break;
    case 'editCompanyAdmin':
        editCompanyAdmin($db); break;
    case 'deleteCompanyAdmin':
        deleteCompanyAdmin($db); break;
    case 'assignCoupon':
        assignCoupon($db); break;
    default:
        die(header("Location: ../admin.php?error=Bilinmeyen işlem!"));
}

function generateRandomID($length = 24) {
    return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

function addCompany($db) {
    $name = trim($_POST['companyName'] ?? '');

    if (empty($name) ) {
        die(header("Location: ../admin.php?error=Firma adı boş olamaz!"));
    }

    // --- Logo yükleme ---
    $logoPath = null;
    if (isset($_FILES['companyLogo']) && $_FILES['companyLogo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';

        $ext = strtolower(pathinfo($_FILES['companyLogo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            die(header("Location: ../admin.php?error=Logo JPG, JPEG, PNG formatında değil!"));
        }

        $newFileName = uniqid('logo_', true) . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['companyLogo']['tmp_name'], $destPath)) {
            $logoPath = 'uploads/' . $newFileName;
        } else {
            die(header("Location: ../admin.php?error=Logo kaydedilirken bir hata oluştu!"));
            
        }
    }

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Bus_Company WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $created_at = date('D-m-y H:i:s');

    
    $insertStmt = $db->prepare('INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, ?, ?)');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $name, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $logoPath, SQLITE3_TEXT);
    $insertStmt->bindValue(4, $created_at, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma kaydı başarılı!");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma kaydı başarısız oldu.");
        exit;
    }
}

function deleteCompany($db)  {
    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        header("Location: ../admin.php?error=Firma ID gönderilmedi.");
        exit;
    }

    $insertStmt = $db->prepare('DELETE FROM Bus_Company Where id = ?');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma başarıyla silindi.");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma silinirken bir hata oluştu!");
        exit;
    }

}

function editCompany($db)  {
    $name = trim($_POST['companyName'] ?? '');
    $id = trim($_POST['id'] ?? '');

    if (empty($name )) {
        die(header("Location: ../admin.php?error=Firma adı boş olamaz!"));
    }

    // --- Logo yükleme ---
    $logoPath = null;
    if (isset($_FILES['companyLogo']) && $_FILES['companyLogo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';

        $ext = strtolower(pathinfo($_FILES['companyLogo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            die(header("Location: ../admin.php?error=Logo JPG, JPEG, PNG formatında değil!"));
        }

        $newFileName = uniqid('logo_', true) . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['companyLogo']['tmp_name'], $destPath)) {
            $logoPath = 'uploads/' . $newFileName;
        } else {
            die(header("Location: ../admin.php?error=Logo kaydedilirken bir hata oluştu!"));
            
        }
    }

    $insertStmt = $db->prepare('UPDATE Bus_Company SET name = ?, logo_path = ? WHERE id = ?');
    $insertStmt->bindValue(3, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(1, $name, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $logoPath, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma kaydı başarıyla düzenlendi!");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma kaydı düzenlenirken başarısız oldu.");
        exit;
    }

    
}

function assignCompanyAdmin($db){
    $full_name = trim($_POST['Full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_again = $_POST['password_again'] ?? '';
    $company_id = trim($_POST['company_id']);


    if (empty($full_name) || empty($email) || empty($password) || empty($password_again)) {
        die("Lütfen tüm alanları doldurun.");
    }
    elseif ($password !== $password_again){
        die(header("Location: ../admin.php?error=Şifreler uyuşmuyor."));
    }
    else{
        $checkStmt = $db->prepare('SELECT COUNT(*) as count FROM User WHERE email = ?');
        $checkStmt->bindValue(1, $email, SQLITE3_TEXT);
        $result = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result['count'] > 0) {
            die(header("Location: ../admin.php?error=Bu email adresiyle zaten bir kullanıcı kayıtlı."));
        }
    }

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM User WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $role = 'company';
    $balance = 800;
    $created_at = date('D-m-y H:i:s');

    
    $insertStmt = $db->prepare('INSERT INTO User (id, full_name, email, role, password, company_id, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $full_name, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $email, SQLITE3_TEXT);
    $insertStmt->bindValue(4, $role, SQLITE3_TEXT);
    $insertStmt->bindValue(5, $hashedPassword, SQLITE3_TEXT);
    $insertStmt->bindValue(6, $company_id, SQLITE3_TEXT);
    $insertStmt->bindValue(7, $balance, SQLITE3_INTEGER);
    $insertStmt->bindValue(8, $created_at, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma admin kaydı başarılı!");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma admin kayıt işlemi başarısız oldu.");
        exit;
    }
}

function editCompanyAdmin($db)  {
    $id = trim($_POST['id'] ?? '');
    $full_name = trim($_POST['Full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company_id = ($_POST['company_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_again = $_POST['password_again'] ?? '';

    if ($password !== $password_again){
        die(header("Location: ../register.php?error=Şifreler uyuşmuyor."));
    };

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $db->prepare('UPDATE User SET full_name = ?, email = ?, password = ?, company_id = ? WHERE id = ?');
    $insertStmt->bindValue(1, $full_name, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $email, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $hashedPassword, SQLITE3_TEXT);
    $insertStmt->bindValue(4, $company_id, SQLITE3_TEXT);
    $insertStmt->bindValue(5, $id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma admin kaydı başarıyla düzenlendi!");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma admin kaydı düzenlenirken başarısız oldu.");
        exit;
    }
}

function deleteCompanyAdmin($db)  {
    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        header("Location: ../admin.php?error=Firma admin ID gönderilmedi.");
        exit;
    }

    $insertStmt = $db->prepare('DELETE FROM User Where id = ?');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Firma admin başarıyla silindi.");
        exit;
    } else {
        header("Location: ../admin.php?error=Firma admin silinirken bir hata oluştu!");
        exit;
    }

}

function addCoupon($db)  {
    $code = trim($_POST['code'] ?? '');
    $discount = trim($_POST['discount'] ?? '');
    $usage_limit = $_POST['usage_limit'] ?? '';
    $expire_date = $_POST['expire_date'] ?? '';
    $company_id = trim($_POST['company_id']);


    if (empty($code) || empty($discount) || empty($usage_limit) || empty($expire_date) || empty($company_id)) {
        die("Lütfen tüm alanları doldurun.");
    }

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM Coupons WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $created_at = date('D-m-y H:i:s');

    
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
        header("Location: ../admin.php?success=Kupon başarıyla oluşturuldu.!");
        exit;
    } else {
        header("Location: ../admin.php?error=Kupon oluşturma işlemi başarısız oldu.");
        exit;
    }
    
}

function editCoupon($db) {
    $id = trim($_POST['id'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $discount = trim($_POST['discount'] ?? '');
    $usage_limit = $_POST['usage_limit'] ?? '';
    $expire_date = $_POST['expire_date'] ?? '';
    $company_id = trim($_POST['company_id']);

    $insertStmt = $db->prepare('UPDATE Coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ?, company_id = ? WHERE id = ?');
    $insertStmt->bindValue(1, $code, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $discount, SQLITE3_INTEGER);
    $insertStmt->bindValue(3, $usage_limit, SQLITE3_INTEGER);
    $insertStmt->bindValue(4, $expire_date, SQLITE3_TEXT);
    $insertStmt->bindValue(5, $company_id, SQLITE3_TEXT);
    $insertStmt->bindValue(6, $id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Kupon başarıyla güncellendi!",);
        exit;
    } else {
        header("Location: ../admin.php?error=Kupon güncellenirken bir hata oluştu.");
        exit;
    }

}

function deleteCoupon($db)  {
    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        header("Location: ../admin.php?error=ID gönderilmedi.");
        exit;
    }

    $insertStmt = $db->prepare('DELETE FROM Coupons Where id = ?');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Kupon başarıyla silindi.");
        exit;
    } else {
        header("Location: ../admin.php?error=Kupon silinirken bir hata oluştu!");
        exit;
    }

}

function assignCoupon($db){
    $coupon_id = trim($_POST['coupon_id'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');


    if (empty($coupon_id) || empty($user_id)) {
        die("Lütfen tüm alanları doldurun.");
    }

    do {
        $id = generateRandomID();
        $checkIdStmt = $db->prepare('SELECT COUNT(*) as count FROM User_Coupons WHERE id = ?');
        $checkIdStmt->bindValue(1, $id, SQLITE3_TEXT);
        $idExists = $checkIdStmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    } while ($idExists > 0);

    $created_at = date('D-m-y H:i:s');

    
    $insertStmt = $db->prepare('INSERT INTO User_Coupons (id, coupon_id, user_id, created_at) VALUES (?, ?, ?, ?)');
    $insertStmt->bindValue(1, $id, SQLITE3_TEXT);
    $insertStmt->bindValue(2, $coupon_id, SQLITE3_TEXT);
    $insertStmt->bindValue(3, $user_id, SQLITE3_TEXT);
    $insertStmt->bindValue(4, $created_at, SQLITE3_TEXT);

    $result = $insertStmt->execute();

    if ($result) {
        header("Location: ../admin.php?success=Kupon başarıyla atandı!");
        exit;
    } else {
        header("Location: ../admin.php?error=Kupon atama işlemi başarısız oldu.");
        exit;
    }
}

?>