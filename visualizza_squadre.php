<?php
session_start();

if (!isset($_GET['id_torneo'])) {
    die("Torneo non specificato.");
}

$id_torneo = (int)$_GET['id_torneo'];
$id_utente = $_SESSION['id_utente'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$messaggio = "";

// Gestione richiesta di unione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['richiedi_unione'])) {
    $id_squadra = (int)$_POST['id_squadra'];

    $check = $conn->query("SELECT * FROM richieste_squadra WHERE id_utente = $id_utente AND id_squadra = $id_squadra AND stato = 'in_attesa'");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO richieste_squadra (id_utente, id_squadra) VALUES ($id_utente, $id_squadra)");
        $messaggio = "Richiesta inviata con successo!";
    } else {
        $messaggio = "Hai già una richiesta in attesa per questa squadra.";
    }
}

// Controlla se l'utente è già iscritto a una squadra del torneo
$conn_check = new mysqli($servername, $username, $password, $dbname);
$sqlIscrittoTorneo = "
    SELECT us.id_squadra 
    FROM utente_squadra us
    JOIN squadre s ON s.Id_squadra = us.id_squadra
    WHERE us.id_utente = $id_utente AND s.id_torneo = $id_torneo
";
$già_iscritto_torneo = $conn_check->query($sqlIscrittoTorneo)->num_rows > 0;
$conn_check->close();

// Recupera il max giocatori del torneo
$res_max = $conn->query("SELECT max_giocatori FROM tornei WHERE Id_torneo = $id_torneo");
$row_max = $res_max->fetch_assoc();
$max_giocatori_per_squadra = (int)$row_max['max_giocatori'];

// Recupera squadre
$squadre = [];
$sqlSquadre = "SELECT s.Id_squadra, s.nome, s.id_creatore 
               FROM squadre s 
               WHERE s.id_torneo = $id_torneo";
$resultSquadre = $conn->query($sqlSquadre);

while ($row = $resultSquadre->fetch_assoc()) {
    $id_squadra = $row['Id_squadra'];

    // Stato richiesta
    $richiesta = $conn->query("SELECT stato FROM richieste_squadra 
                               WHERE id_utente = $id_utente AND id_squadra = $id_squadra 
                               ORDER BY id_richiesta DESC LIMIT 1");
    $stato_richiesta = $richiesta->num_rows > 0 ? $richiesta->fetch_assoc()['stato'] : null;

    // Già iscritto a quella squadra
    $iscritto = $conn->query("SELECT * FROM utente_squadra WHERE id_utente = $id_utente AND id_squadra = $id_squadra")->num_rows > 0;

    // Conta i giocatori nella squadra
    $res_count = $conn->query("SELECT COUNT(*) as tot FROM utente_squadra WHERE id_squadra = $id_squadra");
    $row_count = $res_count->fetch_assoc();
    $giocatori_attuali = (int)$row_count['tot'];

    $squadre[] = [
        'id' => $id_squadra,
        'nome' => $row['nome'],
        'id_creatore' => $row['id_creatore'],
        'richiesta_stato' => $stato_richiesta,
        'iscritto' => $iscritto,
        'giocatori_attuali' => $giocatori_attuali
    ];
}

// Recupera utenti per ogni squadra
$squadraUtenti = [];
foreach ($squadre as $s) {
    $id_s = $s['id'];
    $res = $conn->query("SELECT u.nome, u.cognome 
                         FROM utenti u 
                         JOIN utente_squadra us ON u.id_utente = us.id_utente 
                         WHERE us.id_squadra = $id_s");
    while ($r = $res->fetch_assoc()) {
        $squadraUtenti[$id_s][] = $r;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Visualizza Squadre - Sportify</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .top-buttons {
            text-align: right;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 15px;
            background-color: #1976D2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-left: 10px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0D47A1;
        }
        .btn.logout {
            background-color: #e74c3c;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .message {
            text-align: center;
            color: green;
            margin: 20px 0;
            font-weight: bold;
        }
        .squadra {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        form {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Pulsanti Top -->
    <div class="top-buttons">
        <a href="index.php" class="btn">🏠 Torna alla Home</a>
        <a href="logout.php" class="btn logout">🚪 Logout</a>
    </div>

    <h1>Squadre del Torneo</h1>

    <?php if (!empty($messaggio)) echo "<p class='message'>$messaggio</p>"; ?>

    <?php foreach ($squadre as $squadra): ?>
        <div class="squadra">
            <h3><?= htmlspecialchars($squadra['nome']) ?></h3>
            <p><strong>Giocatori:</strong> (<?= $squadra['giocatori_attuali'] ?> / <?= $max_giocatori_per_squadra ?>)</p>
            <ul>
                <?php 
                if (!empty($squadraUtenti[$squadra['id']])) {
                    foreach ($squadraUtenti[$squadra['id']] as $giocatore) {
                        echo "<li>" . htmlspecialchars($giocatore['nome']) . " " . htmlspecialchars($giocatore['cognome']) . "</li>";
                    }
                } else {
                    echo "<li>Nessun giocatore iscritto</li>";
                }
                ?>
            </ul>

            <?php
            $gia_creata_da_utente = $squadra['id_creatore'] == $id_utente;
            $richiesta_rifiutata = $squadra['richiesta_stato'] === 'rifiutata';
            $richiesta_in_attesa = $squadra['richiesta_stato'] === 'in_attesa';
            $richiesta_accettata = $squadra['richiesta_stato'] === 'accettata';
            $gia_iscritto = $squadra['iscritto'];
            $squadra_piena = $squadra['giocatori_attuali'] >= $max_giocatori_per_squadra;

            if ($squadra_piena) {
                echo "<p><em>La squadra ha raggiunto il numero massimo di giocatori.</em></p>";
            } elseif ($già_iscritto_torneo) {
                echo "<p><em>Fai già parte di una squadra in questo torneo.</em></p>";
            } elseif ($gia_creata_da_utente) {
                echo "<p><em>Sei il creatore di questa squadra.</em></p>";
            } elseif ($gia_iscritto) {
                echo "<p><em>Fai già parte di questa squadra.</em></p>";
            } elseif ($richiesta_rifiutata) {
                echo "<p><em>La tua richiesta è stata rifiutata. Non puoi reinviarla.</em></p>";
            } elseif ($richiesta_in_attesa) {
                echo "<p><em>Hai già inviato una richiesta. In attesa di approvazione.</em></p>";
            } elseif ($richiesta_accettata) {
                echo "<p><em>Sei già stato accettato nella squadra.</em></p>";
            } else {
                ?>
                <form method="POST" action="visualizza_squadre.php?id_torneo=<?= $id_torneo ?>">
                    <input type="hidden" name="id_squadra" value="<?= $squadra['id'] ?>">
                    <input type="submit" name="richiedi_unione" value="Richiedi di Unirti" class="btn">
                </form>
                <?php
            }
            ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
