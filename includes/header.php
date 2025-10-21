<?php session_start();

?>


<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Enver Geçgel' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="assets/css/color.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="liveToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage">
        <!-- mesaj buraya gelecek -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const params = new URLSearchParams(window.location.search);
  const error = params.get("error");
  const success = params.get("success");
  
  const toastEl = document.getElementById('liveToast');
  const toastMessage = document.getElementById('toastMessage');
  const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

  if (error) {
    toastEl.classList.remove('text-bg-primary');
    toastEl.classList.add('text-bg-danger');
    toastMessage.textContent = error;
    toast.show();
  }

  if (success) {
    toastEl.classList.remove('text-bg-danger');
    toastEl.classList.add('text-bg-success');
    toastMessage.textContent = success;
    toast.show();
  }
});
</script>
