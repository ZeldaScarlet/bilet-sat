<!DOCTYPE html>
<html>
<?php 
include 'includes/header.php'?>

<style>
/* Mevcut CSS Stilleriniz */
.ui-w-80 {
    width: 80px !important;
    height: auto;
}

.btn-default {
    border-color: rgba(24,28,33,0.1);
    background: rgba(0,0,0,0);
    color: #4E5155;
}

label.btn {
    margin-bottom: 0;
}

.btn-outline-primary {
    border-color: #26B4FF;
    background: transparent;
    color: #26B4FF;
}

.btn {
    cursor: pointer;
}

.text-light {
    color: #babbbc !important;
}


.card {
    background-clip: padding-box;
    box-shadow: 0 1px 4px rgba(24,28,33,0.012);
}

.row-bordered {
    overflow: hidden;
}

.account-settings-fileinput {
    position: absolute;
    visibility: hidden;
    width: 1px;
    height: 1px;
    opacity: 0;
}
.account-settings-links .list-group-item.active {
    font-weight: bold !important;
}
html:not(.dark-style) .account-settings-links .list-group-item.active {
    background: transparent !important;
}
.account-settings-multiselect ~ .select2-container {
    width: 100% !important;
}
.light-style .account-settings-links .list-group-item {
    padding: 0.85rem 1.5rem;
    border-color: rgba(24, 28, 33, 0.03) !important;
}
.light-style .account-settings-links .list-group-item.active {
    color: #4e5155 !important;
}
.material-style .account-settings-links .list-group-item {
    padding: 0.85rem 1.5rem;
    border-color: rgba(24, 28, 33, 0.03) !important;
}
.material-style .account-settings-links .list-group-item.active {
    color: #4e5155 !important;
}
.dark-style .account-settings-links .list-group-item {
    padding: 0.85rem 1.5rem;
    border-color: rgba(255, 255, 255, 0.03) !important;
}
.dark-style .account-settings-links .list-group-item.active {
    color: #fff !important;
}
.light-style .account-settings-links .list-group-item.active {
    color: #4E5155 !important;
}
.light-style .account-settings-links .list-group-item {
    padding: 0.85rem 1.5rem;
    border-color: rgba(24,28,33,0.03) !important;
}
</style>

<body class="gradient-custom">
  <?php 
  include 'includes/navbar.php';

  if(!isset($_SESSION['user_id'])){
    // Yönlendirme header'dan önce çıktı olmaması için
    header("Location: login.php?error=Yetkisiz");
    exit;
  }
  
  // $_SESSION['role'] kontrolü, navbar dahil edildikten sonra (header() çağrısı yapılmadan önce)
  if (($_SESSION['role'] ?? '') == "admin") {
      header("Location: index.php?error=Yetkisiz");
      exit;
  }
  
  $db = new SQLite3(__DIR__ . '/database.sqlite');
  $user_id = $_SESSION['user_id']; // ID ile çekmek daha güvenlidir.

  $checkStmt = $db->prepare('SELECT * FROM User WHERE id = :user_id');
  $checkStmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);
  $results = $checkStmt->execute();
  $user_data = $results->fetchArray(SQLITE3_ASSOC);
  
  if ($user_data === false) {
      session_destroy();
      header("Location: login.php?error=Kullanıcı bilgileri bulunamadı.");
      exit;
  }

  $full_name = htmlspecialchars($user_data["full_name"] ?? '');
  $email = htmlspecialchars($user_data["email"] ?? '');
  $balance = number_format($user_data["balance"] ?? 0, 2) . ' TL';
  
  
  // BİLETLERİ ÇEKME SORGUSU
  $tickets_query = $db->prepare("
      SELECT 
          T.id, T.total_price, T.status, T.created_at,
          TR.departure_city, TR.destination_city, TR.departure_time, TR.arrival_time,
          BS.seat_number
      FROM Tickets T
      JOIN Trips TR ON T.trip_id = TR.id
      LEFT JOIN Booked_Seats BS ON T.id = BS.ticket_id
      WHERE T.user_id = :user_id
  ");
  $tickets_query->bindValue(':user_id', $user_id, SQLITE3_TEXT);
  $tickets_result = $tickets_query->execute();

  // KUPONLARI ÇEKME SORGUSU
  $coupons_query = $db->prepare("
      SELECT 
          C.code, C.discount, C.usage_limit, C.expire_date,
          BC.name as company_name
      FROM User_Coupons UC
      JOIN Coupons C ON UC.coupon_id = C.id
      LEFT JOIN Bus_Company BC ON C.company_id = BC.id
      WHERE UC.user_id = :user_id -- Sadece kullanıcının kuponları
        AND C.expire_date >= DATE('now') -- Sadece aktif kuponlar
        AND C.usage_limit > 0 -- Sadece kullanılabilir kuponlar
      ORDER BY C.expire_date ASC
  ");
  $coupons_query->bindValue(':user_id', $user_id, SQLITE3_TEXT);
  $coupons_result = $coupons_query->execute();
  
  ?>

<div class="container light-style flex-grow-1 container-p-y mt-5">

  <div class="card overflow-hidden">
    <div class="row g-0 border-light">
      <!-- Sol Menü -->
      <div class="col-md-3 pt-0">
        <div class="list-group list-group-flush account-settings-links" id="profileTabs" role="tablist">
          <a class="list-group-item list-group-item-action active" id="general-tab" data-bs-toggle="list" href="#account-general" role="tab">Genel Bilgiler</a>
          <a class="list-group-item list-group-item-action" id="password-tab" data-bs-toggle="list" href="#account-change-password" role="tab">Şifre Değiştir</a>
          <a class="list-group-item list-group-item-action" id="tickets-tab" data-bs-toggle="list" href="#tickets" role="tab">Biletlerim</a>
          <a class="list-group-item list-group-item-action" id="coupons-tab" data-bs-toggle="list" href="#coupons" role="tab">Kuponlarım</a>
        </div>
      </div>

      <!-- Sağ İçerik -->
      <div class="col-md-9">
        <div class="tab-content">

          <!-- GENEL BİLGİLER -->
          <div class="tab-pane fade show active" id="account-general" role="tabpanel">
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" class="form-control" value="<?= $full_name ?>" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" class="form-control" value="<?= $email ?>" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Bakiye</label>
                <input type="text" class="form-control" value="<?= $balance ?>" disabled>
              </div>
              <div class="text-end">
                  <button type="submit" class="btn btn-primary" disabled>Güncelle</button>
              </div>
            </div>
          </div>

          <!-- ŞİFRE DEĞİŞTİR -->
          <div class="tab-pane fade" id="account-change-password" role="tabpanel">
            <div class="card-body pb-2">
               <form action="backend/password_change.php" method="POST">
                <div class="mb-3">
                  <label class="form-label">Şuanki Şifre</label>
                  <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Yeni Şifre</label>
                  <input type="password" class="form-control" name="new_password" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Yeni Şifre (Tekrar)</label>
                  <input type="password" class="form-control" name="new_password_confirm" required>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
                </div>
              </form>
            </div>
          </div>

          <!-- BİLETLERİM -->
          <div class="tab-pane fade" id="tickets" role="tabpanel">
            <div class="card-body">
               <h5 class="mb-3">Satın Aldığınız Biletler</h5>
               
               <table class="table table-sm">
                 <thead>
                   <tr>
                     <th>Sefer</th>
                     <th>Kalkış Zamanı</th>
                     <th>Koltuk No</th>
                     <th>Fiyat</th>
                     <th>Durum</th>
                     <th>İşlem</th>
                   </tr>
                 </thead>
                 <tbody>
                   <?php while ($ticket = $tickets_result->fetchArray(SQLITE3_ASSOC)): 
                     $departure_datetime = new DateTime($ticket['departure_time']);
                     $time_until_departure = $departure_datetime->getTimestamp() - time();
                     $is_active = $ticket['status'] == 'active';
                     // Kalkışa 1 saatten fazla var mı? (3600 saniye = 1 saat)
                     $can_cancel = $is_active && ($time_until_departure > 3600);
                   ?>
                     <tr>
                       <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></td>
                       <td><?= htmlspecialchars($departure_datetime->format('d.m.Y H:i')) ?></td>
                       <td><span class="badge bg-primary"><?= htmlspecialchars($ticket['seat_number'] ?? 'N/A') ?></span></td>
                       <td><?= number_format($ticket['total_price'], 2) ?> TL</td>
                       <td>
                           <span class="badge bg-<?= $ticket['status'] == 'active' ? 'success' : ($ticket['status'] == 'canceled' ? 'danger' : 'warning') ?>">
                               <?= htmlspecialchars($ticket['status']) ?>
                           </span>
                       </td>
                       <td>
                            <?php if ($can_cancel): ?>
                              <div class="mb-2">
                              <form action="backend/account.php" method="post">
                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                   <button type="submit" class="btn btn-sm btn-danger" name="action" value="downloadPDF">
                                       PDF İndir
                                   </button>
                              </form>
                            </div>
                               <!-- İptal Formu -->
                               <form action="backend/account.php" method="POST" style="display:inline-block;">
                                   <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                   <button type="submit" class="btn btn-sm btn-danger" name="action" value="cancelTrip"
                                           onclick="return confirm('Emin misiniz? Bilet iptal edilecek ve para iadeniz yapılacaktır.')">
                                       İptal Et
                                   </button>
                               </form>
                           <?php elseif ($is_active): ?>
                            <div class="mb-2">
                              <form action="backend/account.php" method="post">
                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                   <button type="submit" class="btn btn-sm btn-danger" name="action" value="downloadPDF">
                                       PDF İndir
                                   </button>
                              </form>
                            </div>
                               <button class="btn btn-sm btn-warning" disabled title="Kalkışa 1 saatten az kaldı. İptal edilemez.">İptal Edilemez</button>
                           <?php else: ?>
                               -
                           <?php endif; ?>
                       </td>
                     </tr>
                   <?php endwhile; ?>
                 </tbody>
               </table>
            </div>
          </div>

          <!-- KUPONLARIM -->
          <div class="tab-pane fade" id="coupons" role="tabpanel">
            <div class="card-body">
              <h5 class="mb-3">Size Ait Aktif Kuponlar</h5>
               
               <table class="table table-sm">
                 <thead>
                   <tr>
                     <th>Kod</th>
                     <th>İndirim (%)</th>
                     <th>Firma</th>
                     <th>Kalan Kullanım</th>
                     <th>Son Geçerlilik</th>
                   </tr>
                 </thead>
                 <tbody>
                   <?php while ($coupon = $coupons_result->fetchArray(SQLITE3_ASSOC)): ?>
                     <tr>
                       <td class="fw-bold"><?= htmlspecialchars($coupon['code']) ?></td>
                       <td>%<?= htmlspecialchars($coupon['discount']) ?></td>
                       <td><?= htmlspecialchars($coupon['company_name'] ?? 'Genel (Tüm Firmalar)') ?></td>
                       <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                       <td><?= htmlspecialchars((new DateTime($coupon['expire_date']))->format('d.m.Y')) ?></td>
                     </tr>
                   <?php endwhile; ?>
                 </tbody>
               </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>