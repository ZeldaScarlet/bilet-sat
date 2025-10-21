<!DOCTYPE html>
<html>
<?php include 'includes/header.php'?>
<body>
<?php include 'includes/navbar.php';

if(isset($_SESSION['user_id'])){
    die(header("Location: ../login.php?error=Yetkisiz"));
}

?>
<section class="vh-120 gradient-custom">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-5">
        <div class="card mainblue text-white h-80" style="border-radius: 1rem;">
          <div class="card-body p-5 text-center">

            <div class="mb-md-5 mt-md-4 pb-5">

              <h2 class="fw-bold mb-5">Kayıt Ol</h2>

              <form action="backend/register.php" method="POST">

              <div data-mdb-input-init class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Ad Soyad</label>
                <input name="Full_name" class="form-control form-control-lg" placeholder="Ad Soyad" required/>
              </div>

              <div data-mdb-input-init class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Email</label>
                <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" required/>
              </div>

              <div data-mdb-input-init class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Şifre</label>
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Şifre" required/>
              </div>

              <div data-mdb-input-init class="form-outline form-white mb-5">
                <label class="form-label" style="text-align:left; display:block;" >Şifre Tekrar</label>
                <input type="password" name="password_again" class="form-control form-control-lg" placeholder="Şifre Tekrar" required/>
              </div>

              <button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-light btn-lg px-5" type="submit">Kayıt Ol</button>
              </form>

            </div>

            <div>
              <p class="mb-0">Hesabınız var mı? <a href="login.php" class="text-white-50 fw-bold">Giriş Yapın</a>
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>

</body>
</html>