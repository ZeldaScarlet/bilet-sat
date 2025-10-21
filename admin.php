<!DOCTYPE html>
<html>
<?php include 'includes/header.php'?>

<style>
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
elseif ($_SESSION['role'] != "admin") {
    die(header("Location: ../index.php?error=Yetkisiz"));
}

$db = new SQLite3(__DIR__ . '/database.sqlite');
$companies = $db->query('SELECT * FROM Bus_Company');
$companyadmins = $db->query('SELECT User.*, Bus_company.name as company_name FROM User JOIN Bus_company on user.company_id = bus_company.id WHERE User.role = "company" ');
$coupons = $db->query('SELECT Coupons.*, Bus_company.name as company_name FROM Coupons JOIN Bus_company on Coupons.company_id = bus_company.id');
while ($row = $companies->fetchArray(SQLITE3_ASSOC)) {
    $all_companies_for_js[] = $row;
}
?>

<body class="gradient-custom">
    <?php include 'includes/navbar.php'?>

<div class="container light-style flex-grow-1 container-p-y mt-5">

  <div class="card overflow-hidden">
    <div class="row g-0 border-light">
      <!-- Menü -->
      <div class="col-md-3 pt-0">
        <div class="list-group list-group-flush account-settings-links" id="profileTabs" role="tablist">
          <a class="list-group-item list-group-item-action active" id="general-tab" data-bs-toggle="list" href="#companies" role="tab">Firmalar</a>
          <a class="list-group-item list-group-item-action" id="password-tab" data-bs-toggle="list" href="#add-company" role="tab">Firma Ekle</a>
          <a class="list-group-item list-group-item-action" id="company-admins-tab" data-bs-toggle="list" href="#company-admins" role="tab">Firma Adminleri</a>
          <a class="list-group-item list-group-item-action" id="company-admin-tab" data-bs-toggle="list" href="#company-admin" role="tab">Firma Admin Ata</a>
          <a class="list-group-item list-group-item-action" id="coupons-tab" data-bs-toggle="list" href="#coupons" role="tab">Kuponlar</a>
          <a class="list-group-item list-group-item-action" id="coupon-add-tab" data-bs-toggle="list" href="#coupon-add" role="tab">Kupon Ekle</a>
          <a class="list-group-item list-group-item-action" id="coupon-add-tab" data-bs-toggle="list" href="#coupon-assign" role="tab">Kupon Ata</a>
        </div>
      </div>

      <!-- Sağ İçerik -->
      <div class="col-md-9">
        <div class="tab-content">

          <!-- FİRMALAR -->
          <div class="tab-pane fade show active" id="companies" role="tabpanel">
            <div class="card-body">
              <table class="table table-sm">
               <thead>
                 <tr>
                   <th scope="col">Id</th>
                   <th scope="col">Firma adı</th>
                   <th scope="col">Logo</th>
                   <th scope="col">Oluşturulma tarihi</th>
                   <th scope="col">Düzenle</th>
                   <th scope="col">Sil</th>
                 </tr>
               </thead>
               <tbody>
                  <?php while ($row = $companies->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                      <form action="backend/admin.php" method="POST">
                      <td name="id"><?= htmlspecialchars($row['id']) ?></td>
                      <td><?= htmlspecialchars($row['name']) ?></td>
                      <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                      <td>
                        <?php if (!empty($row['logo_path'])): ?>
                          <img src="<?= htmlspecialchars($row['logo_path']) ?>" alt="Logo" width="60">
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                       <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editCompanyModal" data-action="company" data-id="<?= htmlspecialchars($row['id']) ?>"data-name="<?= htmlspecialchars($row['name']) ?>"data-logo="<?= htmlspecialchars($row['logo_path']) ?>"> Düzenle </button>
                      </td>
                        <td>
                            <button class="btn btn-sm btn-danger" name="action" value="deleteCompany" type="submit">Sil</button>
                        </td>
                        </form>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- FİRMA EKLE -->
          <div class="tab-pane fade" id="add-company" role="tabpanel">
            <div class="card-body pb-2">
              <form action="backend/admin.php" method="POST" enctype="multipart/form-data">
                 <div class="mb-3">
                <label class="form-label">Firma Adı</label>
                <input type="text" class="form-control" name="companyName" required>
              </div>
              <div class="mb-3">
                <label for="exampleFormControlFile1">Firma Logo: </label>
                <input type="file" class="form-control-file" id="logo" name="companyLogo" accept=".png, .jpg, .jpeg">
              </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn mainblue me-5" name="action" value="addCompany" >Kaydet</button>
              </div>
              </form>
            </div>
          </div>

          <!--  FİRMA ADMİNLERİ -->
          <div class="tab-pane fade" id="company-admins" role="tabpanel">
            <div class="card-body">
              <table class="table table-sm">
               <thead>
                 <tr>
                   <th scope="col">Ad soyad</th>
                   <th scope="col">Email</th>
                   <th scope="col">Firma </th>
                   <th scope="col">Oluşturulma tarihi</th>
                   <th scope="col">Düzenle</th>
                   <th scope="col">Sil</th>
                 </tr>
               </thead>
               <tbody>
                  <?php while ($row = $companyadmins->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                      <form action="backend/admin.php" method="POST">
                      <td name="id"><?= htmlspecialchars($row['full_name']) ?></td>
                      <td><?= htmlspecialchars($row['email']) ?></td>
                      <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                      <td><?= htmlspecialchars($row['company_name']) ?></td>
                      <td><?= htmlspecialchars($row['created_at']) ?></td>
                      <td>
                       <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editCompanyModal" data-action="admin" data-id="<?= htmlspecialchars($row['id']) ?>"data-fullname="<?= htmlspecialchars($row['full_name']) ?>"data-email="<?= htmlspecialchars($row['email']) ?>"data-companyid="<?= htmlspecialchars($row['company_id']) ?>">Düzenle</button>
                      </td>
                        <td>
                            <button class="btn btn-sm btn-danger" name="action" value="deleteCompanyAdmin" type="submit">Sil</button>
                        </td>
                        </form>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- FİRMA ADMİN ATA -->
          <div class="tab-pane fade" id="company-admin" role="tabpanel">
            <div class="card-body">
              <form action="backend/admin.php" method="POST">
                <div class="mb-3">
                  <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Ad Soyad</label>
                <input name="Full_name" class="form-control form-control-lg" placeholder="Ad Soyad" required/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Email</label>
                <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" required/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Şifre</label>
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Şifre" required/>
              </div>

              <div class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Şifre Tekrar</label>
                <input type="password" name="password_again" class="form-control form-control-lg" placeholder="Şifre Tekrar" required/>
              </div>
                <div class="mb-3">
                  <label>Firma Seç</label>
                  <select name="company_id" class="form-select" required>
                    <option value="">Seç...</option>
                    <?php 
                    $companies2 = $db->query('SELECT * FROM Bus_Company');
                    while ($comp = $companies2->fetchArray(SQLITE3_ASSOC)): ?>
                      <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="assignCompanyAdmin">Ata</button>
                </div>
              </form>
            </div>
          </div>
          </div>

          <!-- KUPONLAR -->
          <div class="tab-pane fade" id="coupons" role="tabpanel">
            <div class="card-body">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Kod</th>
                    <th>İndirim (%)</th>
                    <th>Firma</th>
                    <th>Kullanım Sınırı</th>
                    <th>Geçerlilik</th>
                    <th>Düzenle</th>
                    <th>Sil</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($coupon = $coupons->fetchArray(SQLITE3_ASSOC)): ?>
                  <tr>
                      <input type="hidden" name="id" value="<?= htmlspecialchars($coupon['id']) ?>">
                      <td><?= htmlspecialchars($coupon['code']) ?></td>
                      <td><?= htmlspecialchars($coupon['discount']) ?></td>
                      <td><?= htmlspecialchars($coupon['company_name']) ?></td>
                      <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                      <td><?= htmlspecialchars($coupon['expire_date']) ?></td>
                      <td><button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCompanyModal" data-action="coupon" 
                      data-id="<?= htmlspecialchars($coupon['id']) ?>"
                      data-discount="<?= htmlspecialchars($coupon['discount']) ?>" 
                      data-company="<?= htmlspecialchars($coupon['company_id']) ?>" 
                      data-expire="<?= htmlspecialchars($coupon['expire_date']) ?>"
                      data-code="<?= htmlspecialchars($coupon['code']) ?>"
                      data-usage="<?= htmlspecialchars($coupon['usage_limit']) ?>"
                      >Düzenle</button></td>
                      <td><button class="btn btn-danger btn-sm" name="action" value="deleteCoupon">Sil</button></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- KUPON EKLE -->
          <div class="tab-pane fade" id="coupon-add" role="tabpanel">
            <div class="card-body pb-2">
              <form action="backend/admin.php" method="POST">
                <div class="mb-3">
                  <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Kod</label>
                <input name="code" class="form-control form-control-lg" placeholder="Kod" required/>
              </div>

              <div  class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >İndirim Yüzdesi</label>
                <input type="number" name="discount" class="form-control form-control-lg" placeholder="İndirim Yüzdesi" required/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Kullanım Limiti (Kişi Sayısı)</label>
                <input type="number" name="usage_limit" class="form-control form-control-lg" placeholder="Kullanım Limiti (Kişi Sayısı)" required/>
              </div>

              <div class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Son Kullanma Tarihi</label>
                <input type="date" name="expire_date" class="form-control form-control-lg expire_date" required/>
              </div>
                <div class="mb-3">
                  <label>Firma Seç</label>
                  <select name="company_id" class="form-select" required>
                    <option value="">Seç...</option>
                    <?php 
                    $companies2 = $db->query('SELECT * FROM Bus_Company');
                    while ($comp = $companies2->fetchArray(SQLITE3_ASSOC)): ?>
                      <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="addCoupon">Kupon Oluştur</button>
                </div>
              </form>
            </div>
          </div>
        </div>

          <!-- KUPON ATA -->
          <div class="tab-pane fade" id="coupon-assign" role="tabpanel">
            <div class="card-body pb-2">
              <form action="backend/admin.php" method="POST">
                <div class="mb-3">
                  <label>Kullanıcı Seç</label>
                  <select name="user_id" class="form-select" required>
                    <option value="">Seç...</option>
                    <?php 
                    $companies2 = $db->query('SELECT * FROM User');
                    while ($comp = $companies2->fetchArray(SQLITE3_ASSOC)): ?>
                      <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['full_name'])?> - <?= htmlspecialchars($comp['email']) ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Kupon Seç</label>
                  <select name="coupon_id" class="form-select" required>
                    <option value="">Seç...</option>
                    <?php 
                    $companies2 = $db->query('SELECT * FROM Coupons');
                    while ($comp = $companies2->fetchArray(SQLITE3_ASSOC)): ?>
                      <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['code'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="assignCoupon">Kupon Oluştur</button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</body>


<!-- Firma Düzenle Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Firma Düzenle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body" id="editModalBody">
        <!-- body -->
      </div>
    </div>
  </div>
</div>

<script>
const companies = <?php echo json_encode($all_companies_for_js); ?>;

window.onload = function() {
  document.querySelector('.expire_date').min = new Date().toISOString().substr(0, 10);
};


document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editCompanyModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;

      const type = button.getAttribute('data-action');
      const modalTitle = editModal.querySelector('.modal-title');
      const modalBody = editModal.querySelector('#editModalBody');

      modalBody.innerHTML = ''; 
      modalTitle.textContent = '';

      if (type === "company") {
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const logo = button.getAttribute('data-logo');

        modalTitle.textContent = 'Firma Düzenle';
        modalBody.innerHTML = `
          <form action="backend/admin.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${id}">
            <div class="mb-3">
              <label class="form-label">Firma Adı</label>
              <input type="text" class="form-control" name="companyName" value="${name}" required>
            </div>
            <div class="mb-3">
              <label>Mevcut Logo:</label><br>
              ${logo ? `<img src="${logo}" alt="Logo" width="80" class="mb-2">` : '-'}
            </div>
            <div class="mb-3">
              <label>Yeni Logo Yükle (isteğe bağlı):</label>
              <input type="file" class="form-control" name="companyLogo" accept=".png, .jpg, .jpeg">
            </div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary" name="action" value="editCompany">Kaydet</button>
            </div>
          </form>
        `;
      } 
      else if (type === "admin") {
        modalTitle.textContent = 'Firma Admin Düzenle';

        const adminId = button.getAttribute('data-id');
        const adminFullName = button.getAttribute('data-fullname');
        const adminEmail = button.getAttribute('data-email');
        const adminCompanyId = button.getAttribute('data-companyid');

        
        let companyOptions = '';
        companies.forEach(company => {
          const isSelected = company.id == adminCompanyId ? 'selected' : '';
          companyOptions += `<option value="${company.id}" ${isSelected}>${company.name}</option>`;
        });

        modalBody.innerHTML = `
          <form action="backend/admin.php" method="POST">
            <input type="hidden" name="id" value="${adminId}">
            <div class="mb-3">
              <label class="form-label">Ad Soyad</label>
              <input name="Full_name" class="form-control" value="${adminFullName}" required/>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="${adminEmail}" required/>
            </div>
            <div class="mb-3">
              <label class="form-label">Yeni Şifre</label>
              <input type="password" name="password" class="form-control" placeholder="Yeni Şifre"/>
            </div>
            <div class="mb-3">
              <label class="form-label">Yeni Şifre (Tekrar)</label>
              <input type="password" name="password_again" class="form-control" placeholder="Yeni Şifre  (Tekrar)"/>
            </div>
            <div class="mb-3">
              <label>Firma Seç</label>
              <select name="company_id" class="form-select" required>
                <option value="">Seç...</option>
                ${companyOptions}
              </select>
            </div>
            <div class="text-end">
              <button type="submit" class="btn btn-primary" name="action" value="editCompanyAdmin">düzenle</button>
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
        const CompanyId = button.getAttribute('data-company');

        
        let companyOptions = '';
        companies.forEach(company => {
          const isSelected = company.id == CompanyId ? 'selected' : '';
          companyOptions += `<option value="${company.id}" ${isSelected}>${company.name}</option>`;
        });

        modalBody.innerHTML = `
        <form action="backend/admin.php" method="POST">
                <input type="hidden" name="id" value="${couponid}">
                <div class="mb-3">
                  <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Kod</label>
                <input name="code" class="form-control form-control-lg" value="${couponcode}" required/>
              </div>

              <div  class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >İndirim Yüzdesi</label>
                <input type="number" name="discount" class="form-control form-control-lg" value="${coupondiscount}" required/>
              </div>

              <div class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Kullanım Limiti (Kişi Sayısı)</label>
                <input type="number" name="usage_limit" class="form-control form-control-lg" value="${couponusage}" required/>
              </div>

              <div class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Son Kullanma Tarihi</label>
                <input type="date" name="expire_date" class="form-control form-control-lg expire_date" value="${couponexpire}" required/>
              </div>
                <div class="mb-3">
                  <label>Firma Seç</label>
                  <select name="company_id" class="form-select" required>
                    <option value="">Seç...</option>
                    ${companyOptions}
                  </select>
                </div>
                <div class="text-end">
                  <button class="btn mainblue" name="action" value="editCoupon">Düzenle</button>
                </div>
              </form>
        `
      }
    });
  }
});
</script>



</html>


