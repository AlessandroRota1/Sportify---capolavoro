<?php 
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$campi = [];
$sql = "SELECT id_campo, indirizzo, terreno, spogliatoi, n_giocatori, docce, costo, latitudine, longitudine 
        FROM campi WHERE latitudine IS NOT NULL AND longitudine IS NOT NULL";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) $campi[] = $row;

$amici = [];
if (isset($_SESSION['id_utente'])) {
    $id_utente = $_SESSION['id_utente'];
    $res = $conn->query("
        SELECT u.nome, u.cognome 
        FROM amicizie a
        JOIN utenti u ON 
            (u.id_utente = a.id_mittente AND a.id_destinatario = $id_utente)
            OR (u.id_utente = a.id_destinatario AND a.id_mittente = $id_utente)
        WHERE a.stato = 'accettata' AND u.id_utente != $id_utente
    ");
    while ($row = $res->fetch_assoc()) $amici[] = $row;
}

// Recupero calcetti pubblici// Calcetti recenti visibili pubblicamente
// Calcetti recenti visibili pubblicamente
$calcetti = [];
$res = $conn->query("
  SELECT c.id_calcetto, c.data_ora, ca.indirizzo 
  FROM calcetti c 
  JOIN campi ca ON c.id_campo = ca.id_campo
  WHERE c.visibilita = 1 AND c.data_ora >= NOW()
  ORDER BY c.data_ora ASC LIMIT 5
");
while ($row = $res->fetch_assoc()) $calcetti[] = $row;

// Tornei futuri
$tornei = [];
$res = $conn->query("
  SELECT id_torneo, nome, data_inizio 
  FROM tornei 
  WHERE data_inizio >= CURDATE()
  ORDER BY data_inizio ASC 
  LIMIT 5
");
while ($row = $res->fetch_assoc()) $tornei[] = $row;



$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Sportify – Organizza la tua partita</title>
  <link rel="stylesheet" href="index.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKAaIh5V-pd5cRhw3RbtPz6dlWwTm_gek"></script>
  <script>
    function initMap() {
      const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 42.5, lng: 12.5 },
        zoom: 6
      });
      const campi = <?= json_encode($campi) ?>;
      campi.forEach(campo => {
        const marker = new google.maps.Marker({
          position: { lat: +campo.latitudine, lng: +campo.longitudine },
          map,
          title: campo.indirizzo
        });
        const info = `
          <div>
            <strong>${campo.indirizzo}</strong><br>
            Terreno: ${campo.terreno}<br>
            Giocatori/sq: ${campo.n_giocatori}<br>
            Spogliatoi: ${campo.spogliatoi ? 'Sì' : 'No'}<br>
            Docce: ${campo.docce ? 'Sì' : 'No'}<br>
            Costo: €${campo.costo}<br>
            <a href="aggiungi_calcetto.php?id_campo=${campo.id_campo}" 
               style="display:inline-block;margin-top:8px;padding:6px 10px;background:#4CAF50;color:#fff;border-radius:4px;text-decoration:none;">
              Crea un Calcetto
            </a>
          </div>`;
        const infoWindow = new google.maps.InfoWindow({ content: info });
        marker.addListener('click', () => infoWindow.open(map, marker));
      });
    }

    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      const main = document.querySelector('.main');
      sidebar.classList.toggle('active');
      main.classList.toggle('shifted');
    }
    function toggleRightbar() {
  const rightbar = document.querySelector('.rightbar');
  rightbar.classList.toggle('active');
}

  </script>
</head>
<body onload="initMap()">
<?php if (isset($_SESSION['id_utente'])): ?>
  <div class="sidebar">
    <h3>👥 Amici</h3>
    <ul>
      <?php foreach ($amici as $amico): ?>
        <li><?= htmlspecialchars($amico['nome']) ?> <?= htmlspecialchars($amico['cognome']) ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="amicizie.php">➕ Gestisci Amicizie</a>
  </div>
<?php endif; ?>

<main class="main">
  <header>
    <div class="header-left">
      <?php if (isset($_SESSION['id_utente'])): ?>
        <button class="btn-toggle" onclick="toggleSidebar()">👥 Visualizza amici</button>
        <button class="btn-toggle" onclick="toggleRightbar()">📢 Attività</button>

        <span>Benvenuto <?= htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']) ?></span>
      <?php endif; ?>
    </div>
    <div class="header-links">
      <?php if (isset($_SESSION['id_utente'])): ?>
        <a href="recensisci.php">Recensisci</a>
        <a href="commenta.php">Commenta</a>
        <a href="area_personale.php">Profilo</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Accedi</a>
        <a href="registrazione.php">Registrati</a>
      <?php endif; ?>
    </div>
  </header>

  <section class="title">
    <h1>Sportify</h1>
  </section>


  <section class="how-it-works">
    <h2>Come funziona</h2>
    <div class="steps">
      <div class="step">
        <div class="step-icon">🔍</div>
        <h3>Trova un Campo</h3>
        <p>Cerca facilmente i campi da calcetto disponibili nella tua zona direttamente sulla mappa.</p>
      </div>
      <div class="step">
        <div class="step-icon">⚽</div>
        <h3>Crea una Partita</h3>
        <p>Organizza la tua partita con pochi clic, scegliendo campo, data e numero di giocatori.</p>
      </div>
      <div class="step">
        <div class="step-icon">📨</div>
        <h3>Invita Amici</h3>
        <p>Invita i tuoi amici o scopri nuovi compagni di gioco tra gli utenti della community.</p>
      </div>
      <div class="step">
        <div class="step-icon">🏆</div>
        <h3>Gioca e Divertiti</h3>
        <p>Presentati in campo e goditi il divertimento! Sportify pensa al resto.</p>
      </div>
    </div>
  </section>

  <section class="nav-section">
    <div class="nav-grid">
      <a href="<?= isset($_SESSION['id_utente']) ? 'aggiungi_campo.php' : 'login.php' ?>">
        <div class="icon">🏟️</div>
        <div>Aggiungi Campo</div>
      </a>
      <a href="<?= isset($_SESSION['id_utente']) ? 'aggiungi_calcetto.php' : 'login.php' ?>">
        <div class="icon">➕⚽</div>
        <div>Crea Calcetto</div>
      </a>
      <a href="<?= isset($_SESSION['id_utente']) ? 'visualizza_calcetti.php' : 'login.php' ?>">
        <div class="icon">🤝</div>
        <div>Unisciti Calcetto</div>
      </a>
    </div>
    <div class="nav-grid">
      <a href="<?= isset($_SESSION['id_utente']) ? 'aggiungi_torneo.php' : 'login.php' ?>">
        <div class="icon">🏆</div>
        <div>Crea Torneo</div>
      </a>
      <a href="<?= isset($_SESSION['id_utente']) ? 'visualizza_tornei.php' : 'login.php' ?>">
        <div class="icon">📋</div>
        <div>Unisciti Torneo</div>
      </a>
      <a href="<?= isset($_SESSION['id_utente']) ? 'i_miei_impegni.php' : 'login.php' ?>">
        <div class="icon">📅</div>
        <div>I Miei Impegni</div>
      </a>
    </div>
  </section>

  <div class="map-container">
    <div id="map"></div>
  </div>

  <section class="contacts">
    <h2>Contatti</h2>
    <p><strong>Indirizzo:</strong> Via Esempio 123, Bergamo (BG), Italia</p>
    <p><strong>Email:</strong> info@sportify.com</p>
    <p><strong>Telefono:</strong> +39 123 456 789</p>
  </section>
  <div class="rightbar" id="rightbar">
  <h3>⚽ Calcetti Disponibili</h3>
  <ul>
    <?php if ($calcetti): ?>
      <?php foreach ($calcetti as $c): ?>
        <li>
          <strong><?= htmlspecialchars($c['indirizzo']) ?></strong><br>
          <small><?= date('d/m H:i', strtotime($c['data_ora'])) ?></small><br>
          <a href="visualizza_calcetti.php?id=<?= $c['id_calcetto'] ?>" style="color: var(--primary); text-decoration: underline;">Dettagli</a>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li>Nessun calcetto disponibile.</li>
    <?php endif; ?>
  </ul>

  <h3>🏆 Tornei Disponibili</h3>
  <ul>
    <?php if ($tornei): ?>
      <?php foreach ($tornei as $t): ?>
        <li>
          <strong><?= htmlspecialchars($t['nome']) ?></strong><br>
          <small><?= date('d/m', strtotime($t['data_inizio'])) ?></small><br>
          <a href="visualizza_tornei.php?id=<?= $t['id_torneo'] ?>" style="color: var(--primary); text-decoration: underline;">Dettagli</a>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li>Nessun torneo disponibile.</li>
    <?php endif; ?>
  </ul>
</div>

</main>
</body>
</html>
