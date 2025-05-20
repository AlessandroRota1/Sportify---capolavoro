<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['id_utente'])||!isset($_GET['id_calcetto'])) {
    echo json_encode([]); exit;
}
$id = (int)$_GET['id_calcetto'];

$conn = new mysqli("localhost","root","","sportify");
$stmt = $conn->prepare("
  SELECT m.testo, m.data_ora, u.nome, u.cognome
  FROM messaggi m
  JOIN utenti u ON m.id_utente = u.id_utente
  WHERE m.id_calcetto = ?
  ORDER BY m.data_ora ASC
");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
$msgs = $res->fetch_all(MYSQLI_ASSOC);
echo json_encode($msgs);
