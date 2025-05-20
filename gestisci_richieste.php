<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id_creatore = $_SESSION['id_utente'];
$messaggio = "";

// Gestione accettazione o rifiuto
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_richiesta = (int)$_POST['id_richiesta'];
    $azione = $_POST['azione'];

    if ($azione === "accetta") {
        // Ottieni dati della richiesta
        $query = "SELECT id_utente, id_squadra FROM richieste_squadra WHERE id_richiesta = $id_richiesta";
        $res = $conn->query($query);
        $dati = $res->fetch_assoc();

        // Inserisci nella tabella utente_squadra
        $conn->query("INSERT INTO utente_squadra (id_utente, id_squadra) VALUES ({$dati['id_utente']}, {$dati['id_squadra']})");

        // Aggiorna lo stato
        $conn->query("UPDATE richieste_squadra SET stato = 'accettata' WHERE id_richiesta = $id_richiesta");
        $messaggio = "Richiesta accettata!";
    } elseif ($azione === "rifiuta") {
        $conn->query("UPDATE richieste_squadra SET stato = 'rifiutata' WHERE id_richiesta = $id_richiesta");
        $messaggio = "Richiesta rifiutata.";
    }
}

// Mostra tutte le richieste in attesa per squadre dell'utente
$sql = "SELECT rs.id_richiesta, rs.id_utente, rs.id_squadra, u.nome, u.cognome, s.nome AS nome_squadra
        FROM richieste_squadra rs
        JOIN utenti u ON rs.id_utente = u.id_utente
        JOIN squadre s ON rs.id_squadra = s.Id_squadra
        WHERE rs.stato = 'in_attesa' AND s.id_creatore = $id_creatore";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestisci Richieste - Sportify</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
            padding: 0;
            margin: 0;
        }
        .container {
            width: 80%;
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
            margin: 15px 0;
            color: green;
            font-weight: bold;
        }
        .richiesta {
            padding: 15px;
            margin-bottom: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        form {
            display: inline-block;
            margin-right: 10px;
        }
        .accetta {
            background-color: #4CAF50;
        }
        .rifiuta {
            background-color: #e74c3c;
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

    <h1>Richieste Ricevute</h1>

    <?php if (!empty($messaggio)) echo "<p class='message'>$messaggio</p>"; ?>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="richiesta">
                <p><strong><?= htmlspecialchars($row['nome']) . ' ' . htmlspecialchars($row['cognome']) ?></strong> vuole unirsi alla tua squadra <strong><?= htmlspecialchars($row['nome_squadra']) ?></strong></p>
                <form method="POST">
                    <input type="hidden" name="id_richiesta" value="<?= $row['id_richiesta'] ?>">
                    <input type="hidden" name="azione" value="accetta">
                    <button type="submit" class="btn accetta">✅ Accetta</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="id_richiesta" value="<?= $row['id_richiesta'] ?>">
                    <input type="hidden" name="azione" value="rifiuta">
                    <button type="submit" class="btn rifiuta">❌ Rifiuta</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">Nessuna richiesta in attesa.</p>
    <?php endif; ?>
</div>

</body>
</html>
