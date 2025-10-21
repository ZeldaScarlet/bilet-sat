<!DOCTYPE html>
<html>
<?php include 'includes/header.php'?>
<body>
    <?php include 'includes/navbar.php';

    if(isset($_SESSION['user_id'])){
      die(header("Location: ../index.php?error=Yetkisiz"));
    }
    
    ?>

<section class="vh-120 gradient-custom">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-5">
        <div class="card mainblue text-white h-80" style="border-radius: 1rem;">
          <div class="card-body p-5 text-center">

            <div class="mb-md-5 mt-md-4 pb-5">

              <h2 class="fw-bold mb-5">Giriş Yap</h2>
              <form action="backend/login.php" method="post">

               <div data-mdb-input-init class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;" >Email</label>
                <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" required/>
              </div>

             <div data-mdb-input-init class="form-outline form-white mb-4">
                <label class="form-label" style="text-align:left; display:block;">Şifre</label>
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Şifre" required/>
              </div>
              
              <p class="small mb-5 pb-lg-2"><a class="text-white-50" href="#!">Şifremi Unuttum</a></p>

              <button data-mdb-button-init data-mdb-ripple-init class="btn btn-outline-light btn-lg px-5" type="submit">Giriş Yap</button>
              </form>

            </div>

            <div>
              <p class="mb-0">Hesabınız yok mu? <a href="register.php" class="text-white-50 fw-bold">Kayıt olun</a>
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