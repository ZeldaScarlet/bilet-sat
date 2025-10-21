<nav class="navbar navbar-expand-lg mainblue">
  <div class="container">
    <!-- Sol tarafta logo -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="../assets/img/logo.png" alt="Logo" height="50" class="me-2">
    </a>

    <!-- Hamburger menü (mobil için) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Sağ menü -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
        <?php if(!isset($_SESSION['user_id'])) : ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
        <?php endif; ?>
        <?php if(isset($_SESSION['user_id'])) : ?>
          <?php if($_SESSION['role'] == "company") : ?>
            <li class="nav-item"><a class="nav-link" href="company_admin.php">Firma Paneli</a></li>
          <?php endif; ?>
          <?php if($_SESSION['role'] == "admin") : ?>
            <li class="nav-item"><a class="nav-link" href="admin.php">Admin Paneli</a></li>
          <?php endif; ?>
          <?php if($_SESSION['role'] != "admin") : ?>
            <li class="nav-item"><a class="nav-link" href="account.php">Hesabım</a></li>
          <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="backend/logout.php">Çıkış Yap</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
