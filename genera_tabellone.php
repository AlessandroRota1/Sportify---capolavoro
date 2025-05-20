<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if (!isset($_GET['id_torneo'])) die("Torneo non specificato.");
$id_torneo = (int)$_GET['id_torneo'];
$id_utente = $_SESSION['id_utente'];

// Verifica che sia il creatore del torneo
$check = $conn->query("SELECT * FROM tornei WHERE Id_torneo = $id_torneo AND id_utente = $id_utente");
if ($check->num_rows === 0) {
    die("Non sei autorizzato a generare il tabellone per questo torneo.");
}

$rowTorneo = $check->fetch_assoc();
if ($rowTorneo['tipologia'] !== 'Eliminazione Diretta') {
    die("Il tabellone può essere generato solo per tornei a eliminazione diretta.");
}

// Recupera le squadre del torneo
$squadre = [];
$res = $conn->query("SELECT Id_squadra FROM squadre WHERE id_torneo = $id_torneo");
while ($row = $res->fetch_assoc()) {
    $squadre[] = $row['Id_squadra'];
}

if (count($squadre) < 2) {
    die("Servono almeno due squadre per generare il tabellone.");
}

// Mescola le squadre per accoppiamenti casuali
shuffle($squadre);

// Verifica se esistono già partite della fase finale
$esistono_finali = $conn->query("SELECT 1 FROM partite WHERE id_torneo = $id_torneo AND fase_finale = 1 LIMIT 1");
if ($esistono_finali->num_rows > 0) {
    die("Il tabellone della fase finale è già stato generato.");
}

// Crea il primo turno
$turno = 1;
for ($i = 0; $i < count($squadre) - 1; $i += 2) {
    $s1 = $squadre[$i];
    $s2 = $squadre[$i + 1];
    $conn->query("INSERT INTO partite (id_torneo, id_squadra1, id_squadra2, fase_finale, turno)
                  VALUES ($id_torneo, $s1, $s2, 1, $turno)");
}

// Gestione del bye (squadra senza avversario)
if (count($squadre) % 2 !== 0) {
    $bye = $squadre[count($squadre) - 1];
    $conn->query("INSERT INTO partite (id_torneo, id_squadra1, id_squadra2, fase_finale, turno, gol_1, gol_2)
                  VALUES ($id_torneo, $bye, NULL, 1, $turno, 1, 0)");
}

$conn->close();

// Reindirizza a mostra_fase_finale
header("Location: mostra_fase_finale.php?id_torneo=$id_torneo");
exit;
?>
