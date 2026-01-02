<?php

// ASETUKSET


$dataKansio = __DIR__ . "/data/";
if (!is_dir($dataKansio)) {
    mkdir($dataKansio, 0777, true);
}

$viesti = "";
$tulokset = [];
$aktiivinenTab = "tallenna";

// tallennus JSON tiedostoon


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["form"] ?? "") === "tallenna") {

    $aktiivinenTab = "tallenna";

      // Nimimerkki: vain kirjaimet, numerot ja alaviiva + pituusraja
      $nimi = preg_replace("/[^a-zA-Z0-9_]/", "", trim($_POST["nimi"] ?? ""));
      $nimi = substr($nimi, 0, 30); // max 30 merkkiä

      // Päivämäärä: suoraan lomakkeesta (HTML5 date rajoittaa jo)
      $pvm = trim($_POST["pvm"] ?? "");

      // Laji: teksti + pituusraja
      $laji = trim($_POST["laji"] ?? "");
      $laji = substr($laji, 0, 50); // max 50 merkkiä

      // Kesto: vain kokonaisluku + järkevä haarukka
      $kesto = (int)($_POST["kesto"] ?? 0);
      if ($kesto < 1 || $kesto > 1440) { // max 24h
          $kesto = 0;
      }

      // Tuntemukset: vapaa teksti + pituusraja
      $tuntemukset = trim($_POST["tuntemukset"] ?? "");
      $tuntemukset = substr($tuntemukset, 0, 300); // max 300 merkkiä

    if ($nimi && $pvm && $laji && $kesto) {

        $tiedosto = $dataKansio . "liikuntapaivakirja_" . $nimi . ".json";

        // Lue vanha data tai luo uusi
        $data = [];
        if (file_exists($tiedosto)) {
            $data = json_decode(file_get_contents($tiedosto), true) ?? [];
        }

        // Lisää uusi merkintä
        $data[] = [
            "pvm" => $pvm,
            "laji" => $laji,
            "kesto" => (int)$kesto,
            "tuntemukset" => $tuntemukset
        ];

        // Tallenna JSON
        file_put_contents(
            $tiedosto,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        $viesti = "✔️ Merkintä tallennettu nimellä " . htmlspecialchars($nimi);
    } else {
        $viesti = "❌ Täytä kaikki pakolliset kentät!";
    }
}

// PÄIVÄKIRJAN NÄYTTÖ


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["form"] ?? "") === "nayta") {

    $aktiivinenTab = "nayta";

    $nimi = preg_replace("/[^a-zA-Z0-9_]/", "", trim($_POST["nimi"] ?? ""));
    $tiedosto = $dataKansio . "liikuntapaivakirja_" . $nimi . ".json";

    if ($nimi && file_exists($tiedosto)) {
        $data = json_decode(file_get_contents($tiedosto), true);
        if (is_array($data)) {
            $tulokset = array_reverse($data);
        }
    } else {
        $viesti = "❌ Ei löytynyt päiväkirjaa nimimerkillä " . htmlspecialchars($nimi);
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liikuntapäiväkirja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container my-5">
  <div class="neu-box p-4">

    <h2 class="text-center mb-3">🏃 Liikuntapäiväkirja</h2>

    <?php if ($viesti): ?>
      <div class="alert alert-info"><?= $viesti ?></div>
    <?php endif; ?>

    <!-- välilehdet -->
    <ul class="nav nav-tabs mb-3">

      <li class="nav-item">
        <button class="nav-link <?= $aktiivinenTab === "tallenna" ? "active" : "" ?>"
                data-bs-toggle="tab"
                data-bs-target="#tallenna">
          Lisää merkintä
        </button>
      </li>

      <li class="nav-item">
        <button class="nav-link <?= $aktiivinenTab === "nayta" ? "active" : "" ?>"
                data-bs-toggle="tab"
                data-bs-target="#nayta">
          Näytä päiväkirja
        </button>
      </li>

    </ul>

    <div class="tab-content">

      <!-- lisää merkintä lomake -->
      <div class="tab-pane fade <?= $aktiivinenTab === "tallenna" ? "show active" : "" ?>" id="tallenna">

        <form method="POST">
          <input type="hidden" name="form" value="tallenna">

          <div class="mb-3">
            <label>Nimimerkki *</label>
            <input type="text" class="form-control" name="nimi" required>
          </div>

          <div class="mb-3">
            <label>Päivämäärä *</label>
            <input type="date" class="form-control" name="pvm" required>
          </div>

          <div class="mb-3">
            <label>Laji *</label>
            <input type="text" class="form-control" name="laji" required>
          </div>

          <div class="mb-3">
            <label>Kesto (min) *</label>
            <input type="number" class="form-control" name="kesto" min="1" required>
          </div>

          <div class="mb-3">
            <label>Tuntemukset (valinnainen)</label>
            <input type="text" class="form-control" name="tuntemukset">
          </div>

          <button class="btn btn-primary">Tallenna</button>
        </form>
      </div>

      <!-- näytä päiväkirja lomake -->
      <div class="tab-pane fade <?= $aktiivinenTab === "nayta" ? "show active" : "" ?>" id="nayta">

        <form method="POST" class="mb-3">
          <input type="hidden" name="form" value="nayta">

          <label>Nimimerkki</label>
          <input type="text" class="form-control" name="nimi"
                 value="<?= htmlspecialchars($_POST["nimi"] ?? "") ?>">

          <button class="btn btn-secondary mt-2">Näytä päiväkirja</button>
        </form>

        <?php if (!empty($tulokset)): ?>
          <h4>Merkinnät:</h4>
          <ul class="list-group">
            <?php foreach ($tulokset as $r): ?>
              <li class="list-group-item">
                <strong><?= htmlspecialchars($r["pvm"]) ?></strong> |
                <?= htmlspecialchars($r["laji"]) ?> |
                <?= (int)$r["kesto"] ?> min
                <?php if (!empty($r["tuntemukset"])): ?>
                  <br><em><?= htmlspecialchars($r["tuntemukset"]) ?></em>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      </div>

    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>