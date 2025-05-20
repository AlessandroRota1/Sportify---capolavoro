<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header('Location: login.php');
    exit();
}

// Connessione al database
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";

// Preleva liste per i dropdown
$campi = $conn->query("SELECT id_campo, indirizzo FROM campi");
$calcetti = $conn->query("
    SELECT c.id_calcetto,
           CONCAT(DATE_FORMAT(c.data_ora,'%d/%m/%Y %H:%i'),' @ ',ca.indirizzo) AS descrizione
    FROM calcetti c
    JOIN campi ca ON c.id_campo = ca.id_campo
    ORDER BY c.data_ora ASC
");

// Gestione inserimento commento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['commento'])) {
    $type      = $_POST['type'];               // "campo" o "calcetto"
    $testo     = trim($_POST['commento']);
    $id_utente = (int)$_SESSION['id_utente'];
    $data_comm = date('Y-m-d H:i:s');

    if ($testo === "") {
        $messaggio = "❌ Il commento non può essere vuoto.";
    } else {
        if ($type === 'campo' && isset($_POST['id_campo'])) {
            $id_campo = (int)$_POST['id_campo'];
            $stmt = $conn->prepare("
                INSERT INTO commenti (testo, data_commento, id_utente, id_campo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $testo, $data_comm, $id_utente, $id_campo);
        } elseif ($type === 'calcetto' && isset($_POST['id_calcetto'])) {
            $id_calcetto = (int)$_POST['id_calcetto'];
            $stmt = $conn->prepare("
                INSERT INTO commenti (testo, data_commento, id_utente, id_calcetto)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $testo, $data_comm, $id_utente, $id_calcetto);
        } else {
            $stmt = null;
            $messaggio = "❌ Tipo o ID non valido.";
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $messaggio = "✅ Commento salvato con successo.";
            } else {
                $messaggio = "❌ Errore: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Preleva tutte le recensioni (commenti)
$recRes = $conn->query("
    SELECT cm.testo, cm.data_commento, u.nome, u.cognome,
           c.indirizzo AS campo_indirizzo,
           CONCAT(ca.indirizzo,' — ',DATE_FORMAT(cal.data_ora,'%d/%m/%Y %H:%i')) AS calcetto_descr
    FROM commenti cm
    JOIN utenti u ON cm.id_utente = u.id_utente
    LEFT JOIN campi c    ON cm.id_campo    = c.id_campo
    LEFT JOIN calcetti cal ON cm.id_calcetto = cal.id_calcetto
    LEFT JOIN campi ca   ON cal.id_campo    = ca.id_campo
    ORDER BY cm.data_commento DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Commenti - Sportify</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    :root {
      --primary: #1e88e5;
      --primary-dark: #1565c0;
      --success: #4caf50;
      --danger: #f44336;
      --light: #f5f5f5;
      --dark: #333;
      --gray: #757575;
      --light-gray: #e0e0e0;
      --white: #ffffff;
      --shadow: 0 2px 10px rgba(0,0,0,0.1);
      --radius: 8px;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
      background: #f0f2f5;
      margin: 0;
      padding: 0;
      color: var(--dark);
      line-height: 1.6;
    }
    
    .container {
      max-width: 1000px;
      margin: 2rem auto;
      background: var(--white);
      padding: 2rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .header h1 {
      color: var(--primary);
      font-size: 2rem;
      margin: 0;
    }
    
    .nav-buttons {
      display: flex;
      gap: 1rem;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      background: var(--primary);
      color: var(--white);
      border: none;
      border-radius: var(--radius);
      cursor: pointer;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn-home {
      background: var(--white);
      color: var(--primary);
      border: 1px solid var(--primary);
    }
    
    .btn-home:hover {
      background: var(--light);
    }
    
    .btn-logout {
      background: var(--white);
      color: var(--danger);
      border: 1px solid var(--danger);
    }
    
    .btn-logout:hover {
      background: var(--light);
      color: var(--danger);
    }
    
    .message {
      padding: 1rem;
      margin: 1rem 0;
      border-radius: var(--radius);
      text-align: center;
      font-weight: 500;
    }
    
    .message.success {
      background-color: rgba(76, 175, 80, 0.1);
      color: var(--success);
      border: 1px solid var(--success);
    }
    
    .message.error {
      background-color: rgba(244, 67, 54, 0.1);
      color: var(--danger);
      border: 1px solid var(--danger);
    }
    
    .tabs {
      display: flex;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .tab {
      padding: 0.8rem 1.5rem;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .tab.active {
      color: var(--primary);
      border-bottom: 3px solid var(--primary);
    }
    
    .form-container {
      margin-bottom: 2.5rem;
      padding: 1.5rem;
      background: var(--light);
      border-radius: var(--radius);
    }
    
    .form-section {
      display: none;
    }
    
    .form-section.active {
      display: block;
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--dark);
    }
    
    .form-control {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid var(--light-gray);
      border-radius: var(--radius);
      font-size: 1rem;
      transition: border 0.2s ease;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.25);
    }
    
    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }
    
    .comments-section h2 {
      color: var(--primary);
      margin-bottom: 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid var(--light-gray);
    }
    
    .comment-list {
      border-radius: var(--radius);
      overflow: hidden;
    }
    
    .comment-item {
      padding: 1.2rem;
      border-left: 4px solid var(--primary);
      background: var(--light);
      margin-bottom: 1rem;
      border-radius: 0 var(--radius) var(--radius) 0;
    }
    
    .comment-item:nth-child(even) {
      background: var(--white);
      border-left: 4px solid var(--success);
    }
    
    .comment-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      border-bottom: 1px dashed var(--light-gray);
      padding-bottom: 0.5rem;
    }
    
    .comment-user {
      font-weight: bold;
      color: var(--primary-dark);
    }
    
    .comment-date {
      color: var(--gray);
      font-size: 0.85rem;
    }
    
    .comment-target {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      margin-bottom: 0.5rem;
      color: var(--gray);
      font-size: 0.9rem;
    }
    
    .comment-text {
      padding-top: 0.5rem;
      white-space: pre-line;
    }
    
    .comment-type {
      display: inline-block;
      padding: 0.2rem 0.5rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .comment-type.campo {
      background-color: rgba(30, 136, 229, 0.15);
      color: var(--primary-dark);
    }
    
    .comment-type.calcetto {
      background-color: rgba(76, 175, 80, 0.15);
      color: var(--success);
    }
    
    .empty-state {
      text-align: center;
      padding: 2rem;
      color: var(--gray);
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 1rem;
        margin: 1rem;
      }
      
      .header {
        flex-direction: column;
        gap: 1rem;
      }
      
      .nav-buttons {
        width: 100%;
        justify-content: center;
      }
      
      .tabs {
        overflow-x: auto;
        white-space: nowrap;
      }
      
      .comment-header {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1><i class="fas fa-comments"></i> Commenti</h1>
      <div class="nav-buttons">
        <a href="index.php" class="btn btn-home"><i class="fas fa-home"></i> Home</a>
        <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    
    <?php if ($messaggio): ?>
      <div class="message <?= strpos($messaggio, '✅') === 0 ? 'success' : 'error' ?>">
        <?= htmlspecialchars($messaggio) ?>
      </div>
    <?php endif; ?>
    
    <div class="tabs">
      <div class="tab active" data-target="campo-form">
        <i class="fas fa-map-marker-alt"></i> Commenta un Campo
      </div>
      <div class="tab" data-target="calcetto-form">
        <i class="fas fa-futbol"></i> Commenta un Calcetto
      </div>
    </div>
    
    <div class="form-container">
      <!-- Commenta un Campo -->
      <div class="form-section active" id="campo-form">
        <form method="POST">
          <input type="hidden" name="type" value="campo">
          <div class="form-group">
            <label for="id_campo"><i class="fas fa-map-marker-alt"></i> Seleziona Campo:</label>
            <select id="id_campo" name="id_campo" class="form-control" required>
              <option value="">-- Scegli un campo --</option>
              <?php while ($r = $campi->fetch_assoc()): ?>
                <option value="<?= $r['id_campo'] ?>">
                  <?= htmlspecialchars($r['indirizzo']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="commento"><i class="fas fa-comment"></i> Il tuo commento:</label>
            <textarea id="commento" name="commento" class="form-control" placeholder="Scrivi qui il tuo commento sul campo..." required></textarea>
          </div>
          <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Invia Commento</button>
        </form>
      </div>

      <!-- Commenta un Calcetto -->
      <div class="form-section" id="calcetto-form">
        <form method="POST">
          <input type="hidden" name="type" value="calcetto">
          <div class="form-group">
            <label for="id_calcetto"><i class="fas fa-futbol"></i> Seleziona Calcetto:</label>
            <select id="id_calcetto" name="id_calcetto" class="form-control" required>
              <option value="">-- Scegli un calcetto --</option>
              <?php while ($r = $calcetti->fetch_assoc()): ?>
                <option value="<?= $r['id_calcetto'] ?>">
                  <?= htmlspecialchars($r['descrizione']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="commento2"><i class="fas fa-comment"></i> Il tuo commento:</label>
            <textarea id="commento2" name="commento" class="form-control" placeholder="Scrivi qui il tuo commento sul calcetto..." required></textarea>
          </div>
          <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Invia Commento</button>
        </form>
      </div>
    </div>

    <!-- Tutti i Commenti -->
    <div class="comments-section">
      <h2><i class="fas fa-comments"></i> Tutti i Commenti</h2>
      <?php if ($recRes->num_rows): ?>
        <div class="comment-list">
          <?php while ($r = $recRes->fetch_assoc()): ?>
            <div class="comment-item">
              <div class="comment-header">
                <div class="comment-user">
                  <i class="fas fa-user"></i> <?= htmlspecialchars($r['nome'].' '.$r['cognome']) ?>
                </div>
                <div class="comment-date">
                  <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($r['data_commento'])) ?>
                </div>
              </div>
              <div class="comment-target">
                <span class="comment-type <?= $r['campo_indirizzo'] ? 'campo' : 'calcetto' ?>">
                  <?= $r['campo_indirizzo'] ? 'Campo' : 'Calcetto' ?>
                </span>
                <span>
                  <?= $r['campo_indirizzo']
                    ? '<i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($r['campo_indirizzo'])
                    : '<i class="fas fa-futbol"></i> ' . htmlspecialchars($r['calcetto_descr']); ?>
                </span>
              </div>
              <div class="comment-text">
                <?= nl2br(htmlspecialchars($r['testo'])) ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="far fa-comment-dots fa-3x"></i>
          <p>Nessun commento disponibile.</p>
          <p>Sii il primo a commentare!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Gestione tab
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', function() {
        // Rimuovi classe active da tutti i tab
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        // Aggiungi classe active al tab cliccato
        this.classList.add('active');
        
        // Rimuovi classe active da tutte le sezioni
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        // Attiva la sezione corrispondente
        document.getElementById(this.dataset.target).classList.add('active');
      });
    });
  </script>
</body>
</html>