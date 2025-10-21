<!DOCTYPE html>
<html>
<?php include 'includes/header.php'; ?>

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

<?php
if(!isset($_SESSION['user_id'])){
    die(header("Location: ../login.php?error=Yetkisiz"));
}
elseif ($_SESSION['role'] != "company") {
    die(header("Location: ../index.php?error=Yetkisiz"));
}

$CURRENT_COMPANY_ID = $_SESSION['company_id'];
$db = new SQLite3(__DIR__ . '/database.sqlite');
$trips_query = $db->prepare('SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC');
$trips_query->bindValue(1, $CURRENT_COMPANY_ID, SQLITE3_TEXT);
$trips = $trips_query->execute();

$coupons_query = $db->prepare('SELECT * FROM Coupons WHERE company_id = ?');
$coupons_query->bindValue(1, $CURRENT_COMPANY_ID, SQLITE3_TEXT);
$coupons = $coupons_query->execute();

$tickets_query = $db->prepare("
    SELECT 
        T.*, 
        TR.departure_city, TR.destination_city, TR.departure_time, TR.arrival_time,
        U.full_name as user_name
    FROM Tickets T
    JOIN Trips TR ON T.trip_id = TR.id
    JOIN User U ON T.user_id = U.id
    WHERE TR.company_id = ?
    ORDER BY T.created_at DESC
");
$tickets_query->bindValue(1, $CURRENT_COMPANY_ID, SQLITE3_TEXT);
$tickets = $tickets_query->execute();

$all_trips_for_js = [];
$trips_for_modal = $db->prepare('SELECT id, departure_city, destination_city FROM Trips WHERE company_id = ?');
$trips_for_modal->bindValue(1, $CURRENT_COMPANY_ID, SQLITE3_TEXT);
$trip_results = $trips_for_modal->execute();
while ($row = $trip_results->fetchArray(SQLITE3_ASSOC)) {
    $all_trips_for_js[] = $row;
}
?>

<body class="gradient-custom">
    <?php include 'includes/navbar.php'?>

<div class="container light-style flex-grow-1 container-p-y mt-5">

  <div class="card overflow-hidden">
    <div class="row g-0 border-light">
      <div class="col-md-3 pt-0">
        <div class="list-group list-group-flush account-settings-links" id="profileTabs" role="tablist">
          <a class="list-group-item list-group-item-action active" id="trips-tab" data-bs-toggle="list" href="#trips" role="tab">Seferler</a>
          <a class="list-group-item list-group-item-action" id="add-trip-tab" data-bs-toggle="list" href="#add-trip" role="tab">Sefer Ekle</a>
          <a class="list-group-item list-group-item-action" id="coupons-tab" data-bs-toggle="list" href="#coupons" role="tab">Kuponlar</a>
          <a class="list-group-item list-group-item-action" id="coupon-add-tab" data-bs-toggle="list" href="#coupon-add" role="tab">Kupon Ekle</a>
          <a class="list-group-item list-group-item-action" id="tickets-tab" data-bs-toggle="list" href="#tickets" role="tab">Biletler / Rezervasyonlar</a>
        </div>
      </div>

      <div class="col-md-9">
        <div class="tab-content">

          <!-- SEFERLER -->
          <div class="tab-pane fade show active" id="trips" role="tabpanel">
            <div class="card-body">
              <table class="table table-sm">
               <thead>
                 <tr>
                   <th scope="col">Kalkış</th>
                   <th scope="col">Varış</th>
                   <th scope="col">Kalkış Saati</th>
                   <th scope="col">Fiyat</th>
                   <th scope="col">Kapasite</th>
                   <th scope="col">Düzenle</th>
                   <th scope="col">Sil</th>
                 </tr>
               </thead>
               <tbody>
                  <?php while ($row = $trips->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                      <form action="backend/company_admin.php" method="POST">
                      <td><?= htmlspecialchars($row['departure_city']) ?></td>
                      <td><?= htmlspecialchars($row['destination_city']) ?></td>
                      <td><?= htmlspecialchars($row['departure_time']) ?></td>
                      <td><?= htmlspecialchars($row['price']) ?> TL</td>
                      <td><?= htmlspecialchars($row['capacity']) ?></td>
                      <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                        <td>
                       <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-action="trip"
                       data-id="<?= htmlspecialchars($row['id']) ?>"
                       data-depcity="<?= htmlspecialchars($row['departure_city']) ?>"
                       data-descity="<?= htmlspecialchars($row['destination_city']) ?>"
                       data-depttime="<?= htmlspecialchars($row['departure_time']) ?>"
                       data-arrtime="<?= htmlspecialchars($row['arrival_time']) ?>"
                       data-price="<?= htmlspecialchars($row['price']) ?>"
                       data-capacity="<?= htmlspecialchars($row['capacity']) ?>"> Düzenle </button>
                      </td>
                        <td>
                            <button class="btn btn-sm btn-danger" name="action" value="deleteTrip" type="submit">Sil</button>
                        </td>
                        </form>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- SEFER EKLE -->
          <div class="tab-pane fade" id="add-trip" role="tabpanel">
            <div class="card-body pb-2">
              <form action="backend/company_admin.php" method="POST">
                 <input type="hidden" name="company_id" value="<?= $CURRENT_COMPANY_ID ?>">
                 <div class="mb-3">
                    <label for="fromCity" class="form-label fw-bold">Kalkış Şehri</label>
                    <select class="form-select select" id="fromCity" name="departure_city" required>
                      <option value="">Şehir seçiniz</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="toCity" class="form-label fw-bold">Varış Şehri</label>
                    <select class="form-select" id="toCity" name="destination_city" required>
                      <option value="">Şehir seçiniz</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Kalkış Zamanı</label>
                    <input type="datetime-local" class="form-control" name="departure_time" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Varış Zamanı</label>
                    <input type="datetime-local" class="form-control" name="arrival_time" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Fiyat (TL)</label>
                    <input type="number" class="form-control" name="price" required min="10">
                  </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn mainblue me-5" name="action" value="addTrip" >Sefer Ekle</button>
              </div>
              </form>
            </div>
          </div>

          <!-- KUPONLAR -->
          <div class="tab-pane fade" id="coupons" role="tabpanel">
            <div class="card-body">
              <h5>Firmanıza Ait Kuponlar</h5>
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Kod</th>
                    <th>İndirim (%)</th>
                    <th>Kullanım Sınırı</th>
                    <th>Geçerlilik</th>
                    <th>Düzenle</th>
                    <th>Sil</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($coupon = $coupons->fetchArray(SQLITE3_ASSOC)): ?>
                  <tr>
                      <form action="backend/company_admin.php" method="POST">
                      <input type="hidden" name="id" value="<?= htmlspecialchars($coupon['id']) ?>">
                      <td><?= htmlspecialchars($coupon['code']) ?></td>
                      <td><?= htmlspecialchars($coupon['discount']) ?></td>
                      <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                      <td><?= htmlspecialchars($coupon['expire_date']) ?></td>
                      <td><button type="button" class="btn btn-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-action="coupon" 
                      data-id="<?= htmlspecialchars($coupon['id']) ?>"
                      data-discount="<?= htmlspecialchars($coupon['discount']) ?>" 
                      data-expire="<?= htmlspecialchars($coupon['expire_date']) ?>"
                      data-code="<?= htmlspecialchars($coupon['code']) ?>"
                      data-usage="<?= htmlspecialchars($coupon['usage_limit']) ?>"
                      >Düzenle</button></td>
                      <td><button class="btn btn-danger btn-sm" name="action" value="deleteCoupon">Sil</button></td>
                      </form>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- KUPON EKLE -->
          <div class="tab-pane fade" id="coupon-add" role="tabpanel">
            <div class="card-body pb-2">
              <form action="backend/company_admin.php" method="POST">
                <input type="hidden" name="company_id" value="<?= $CURRENT_COMPANY_ID ?>">
                <div class="mb-3">
                  <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Kod</label>
                <input name="code" class="form-control form-control-lg" placeholder="Kod" required/>
              </div>

              <div  class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >İndirim Yüzdesi</label>
                <input type="number" name="discount" class="form-control form-control-lg" placeholder="İndirim Yüzdesi" required min="1" max="100"/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Kullanım Limiti (Kişi Sayısı)</label>
                <input type="number" name="usage_limit" class="form-control form-control-lg" placeholder="Kullanım Limiti (Kişi Sayısı)" required min="1"/>
              </div>

              <div class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Son Kullanma Tarihi</label>
                <input type="date" name="expire_date" class="form-control form-control-lg expire_date" required/>
              </div>
                
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="addCoupon">Kupon Oluştur</button>
                </div>
              </form>
            </div>
          </div>
           </div>

          <!-- BİLETLER -->
          <div class="tab-pane fade" id="tickets" role="tabpanel">
            <div class="card-body">
              <h5>Firmanızın Seferlerine Ait Biletler</h5>
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Bilet ID</th>
                    <th>Yolcu</th>
                    <th>Sefer</th>
                    <th>Kalkış Saati</th>
                    <th>Toplam Fiyat</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($ticket = $tickets->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                      <form action="backend/company_admin.php" method="POST">
                      <input type="hidden" name="id" value="<?= htmlspecialchars($ticket['id']) ?>">
                      <td><?= htmlspecialchars(substr($ticket['id'], 0, 8)) ?>...</td>
                      <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                      <td><?= htmlspecialchars($ticket['departure_city']) ?> -> <?= htmlspecialchars($ticket['destination_city']) ?></td>
                      <td><?= htmlspecialchars($ticket['departure_time']) ?></td>
                      <td><?= htmlspecialchars($ticket['total_price']) ?> TL</td>
                      <td>
                        <span class="badge bg-<?= $ticket['status'] == 'active' ? 'success' : ($ticket['status'] == 'canceled' ? 'danger' : 'warning') ?>">
                            <?= htmlspecialchars($ticket['status']) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($ticket['status'] == 'active'): ?>
                            <button class="btn btn-sm btn-danger" name="action" value="cancelTicket" type="submit" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve ilgili koltukları serbest bırakacaktır.')">İptal Et</button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                      </td>
                      </form>
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


<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Düzenle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body" id="editModalBody">
        <!-- mesaj -->
      </div>
    </div>
  </div>
</div>

<script>
window.onload = function() {
  const expireDateInput = document.querySelector('.expire_date');
  if (expireDateInput) {
    expireDateInput.min = new Date().toISOString().substr(0, 10);
  }
};

document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editModal');
  const currentCompanyId = '<?= $CURRENT_COMPANY_ID ?>';

  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const type = button.getAttribute('data-action');
      const modalTitle = editModal.querySelector('.modal-title');
      const modalBody = editModal.querySelector('#editModalBody');

      modalBody.innerHTML = ''; 
      modalTitle.textContent = '';

      if (type === "trip") {
        modalTitle.textContent = 'Sefer Düzenle';

        const id = button.getAttribute('data-id');
        const depCity = button.getAttribute('data-depcity');
        const desCity = button.getAttribute('data-descity');
        const deptTime = button.getAttribute('data-depttime');
        const arrTime = button.getAttribute('data-arrtime');
        const price = button.getAttribute('data-price');
        const capacity = button.getAttribute('data-capacity');

        modalBody.innerHTML = `
          <form action="backend/company_admin.php" method="POST">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="company_id" value="${currentCompanyId}">

            <div class="mb-3">
             <label for="fromCity" class="form-label fw-bold">Kalkış Şehri</label>
             <select class="form-select select" id="fromCity" name="departure_city" value="${depCity}" required>
               <option value="">Şehir seçiniz</option>
             </select>
           </div>
           <div class="mb-3">
             <label for="toCity" class="form-label fw-bold">Varış Şehri</label>
             <select class="form-select" id="toCity" name="destination_city" value="${desCity}" required>
               <option value="">Şehir seçiniz</option>
             </select>
           </div>
            <div class="mb-3">
              <label class="form-label">Kalkış Zamanı</label>
              <input type="datetime-local" class="form-control" name="departure_time" value="${deptTime}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Varış Zamanı</label>
              <input type="datetime-local" class="form-control" name="arrival_time" value="${arrTime}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Fiyat (TL)</label>
              <input type="number" class="form-control" name="price" value="${price}" required min="10">
            </div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary" name="action" value="editTrip">Kaydet</button>
            </div>
          </form>
        `;
      } 
      else if (type === "coupon") {
        modalTitle.textContent = 'Kupon Düzenle';

        const couponid = button.getAttribute('data-id');
        const couponcode = button.getAttribute('data-code');
        const coupondiscount = button.getAttribute('data-discount');
        const couponusage = button.getAttribute('data-usage');
        const couponexpire = button.getAttribute('data-expire');

        modalBody.innerHTML = `
        <form action="backend/company_admin.php" method="POST">
                <input type="hidden" name="id" value="${couponid}">
                <input type="hidden" name="company_id" value="${currentCompanyId}">
                <div class="mb-3">
                  <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Kod</label>
                <input name="code" class="form-control form-control-lg" value="${couponcode}" required/>
              </div>

              <div  class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >İndirim Yüzdesi</label>
                <input type="number" name="discount" class="form-control form-control-lg" value="${coupondiscount}" required min="1" max="100"/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Kullanım Limiti (Kişi Sayısı)</label>
                <input type="number" name="usage_limit" class="form-control form-control-lg" value="${couponusage}" required min="1"/>
              </div>

              <div class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Son Kullanma Tarihi</label>
                <input type="date" name="expire_date" class="form-control form-control-lg expire_date" value="${couponexpire}" required/>
              </div>
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="editCoupon">Düzenle</button>
                </div>
              </form>
        `;
      }
    });
  }
});
</script>

<script>
  const cities = [
    "Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir",
    "Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli","Diyarbakır",
    "Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari","Hatay",
    "Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir","Kocaeli",
    "Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş","Nevşehir","Niğde",
    "Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ","Tokat","Trabzon","Tunceli",
    "Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman","Kırıkkale",
    "Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye","Düzce"
  ];

  const fromSelect = document.getElementById("fromCity");
  const toSelect = document.getElementById("toCity");

  function populateSelect(selectElement) {
    cities.forEach(city => {
      const option = document.createElement("option");
      option.value = city;
      option.textContent = city;
      selectElement.appendChild(option);
    });
  }

  populateSelect(fromSelect);
  populateSelect(toSelect);
</script>

</html>