<?php
session_start();

if (!isset($_SESSION['id_utente'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$id_utente = $_SESSION['id_utente'];
$id_calcetto = isset($_GET['id_calcetto']) ? (int)$_GET['id_calcetto'] : 0;
$messaggio = "";

if ($id_calcetto > 0) {
    $res = $conn->query("
        SELECT c.id_utente, c.posti_occupati, ca.n_giocatori
        FROM calcetti c
        JOIN campi ca ON c.id_campo = ca.id_campo
        WHERE c.id_calcetto = $id_calcetto
    ");

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $capacity = $row['n_giocatori'] * 2;

        if ($row['id_utente'] == $id_utente) {
            $messaggio = "Sei l'organizzatore del calcetto.";
        } elseif ($row['posti_occupati'] >= $capacity) {
            $messaggio = "Il calcetto è già completo.";
        } else {
            $check = $conn->query("SELECT 1 FROM calcetto_utente WHERE id_calcetto = $id_calcetto AND id_utente = $id_utente");
            if ($check->num_rows > 0) {
                $messaggio = "Sei già iscritto a questo calcetto.";
            } else {
                $conn->query("INSERT INTO calcetto_utente (id_calcetto, id_utente) VALUES ($id_calcetto, $id_utente)");
                $conn->query("UPDATE calcetti SET posti_occupati = posti_occupati + 1 WHERE id_calcetto = $id_calcetto");
                $messaggio = "Ti sei unito con successo!";
            }
        }
    } else {
        $messaggio = "Calcetto non trovato.";
    }
} else {
    $messaggio = "ID calcetto non valido.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Unisciti al Calcetto</title>
    <style>
        body { font-family: Arial; background:#f4f4f4; padding:50px; text-align:center; }
        .box {
            background:#fff; padding:30px; border-radius:10px;
            box-shadow:0 0 10px rgba(0,0,0,0.1); max-width:500px; margin:auto;
        }
        .box p { font-size:1.2rem; margin-bottom:20px; }
        .box a {
            text-decoration: none; background-color: #4CAF50; color: white;
            padding: 10px 20px; border-radius: 6px; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="box">
        <p><?= htmlspecialchars($messaggio) ?></p>
        <a href="index.php">Torna alla Home</a>
    </div>
</body>
</html>
