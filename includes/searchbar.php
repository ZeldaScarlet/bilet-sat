  <link href="assets/css/searchbar.css" rel="stylesheet">
  
  <div class="container">
  <form action="search.php" method="POST">
    <div class="search-box d-flex align-items-center justify-content-between flex-wrap gap-3">
      
      <div class="form-section flex-grow-1">
        <label for="fromCity" class="form-label fw-bold">Nereden</label>
        <select class="form-select select" id="fromCity" name="fromCity" required>
          <option value="">Şehir seçiniz</option>
        </select>
      </div>

      <button type="button" class="swap-btn" id="swapBtn" title="Yer değiştir">
        <i class="bi bi-arrow-left-right"></i>
      </button>

      <div class="form-section flex-grow-1">
        <label for="toCity" class="form-label fw-bold">Nereye</label>
        <select class="form-select" id="toCity" name="toCity" required>
          <option value="">Şehir seçiniz</option>
        </select>
      </div>

      <div class="form-section text-center">
        <label for="date" class="form-label fw-bold">Gidiş Tarihi</label>
        <input type="date" id="date" name="date" class="form-control" required>
      </div>

      <div class="d-flex flex-column justify-content-center">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="dateOption" id="today" value="today">
          <label class="form-check-label" style="color: white;" for="today">Bugün</label>
        </div>
        <div class="form-check">
          <input class="form-check-input"  type="radio" name="dateOption" id="tomorrow" value="tomorrow">
          <label class="form-check-label" style="color: white;" for="tomorrow">Yarın</label>
        </div>
      </div>

      <button type="submit" class="btn-search">Otobüs Ara</button>

    </div>
  </form>
</div>

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

  document.getElementById("swapBtn").addEventListener("click", function() {
    const temp = fromSelect.value;
    fromSelect.value = toSelect.value;
    toSelect.value = temp;
  });

  document.getElementById("today").addEventListener("click", function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("date").value = today;
  });

  document.getElementById("tomorrow").addEventListener("click", function() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById("date").value = tomorrow.toISOString().split('T')[0];
  });
</script>