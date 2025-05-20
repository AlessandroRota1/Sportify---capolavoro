<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['id_utente'], $_POST['id_calcetto'], $_POST['testo'])) {
    echo json_encode(['success'=>false]); exit;
}
$id   = (int)$_POST['id_calcetto'];
$uid  = (int)$_SESSION['id_utente'];
$testo= trim($_POST['testo']);

$conn = new mysqli("localhost","root","","sportify");
$stmt = $conn->prepare("
  INSERT INTO messaggi (id_calcetto, id_utente, testo)
  VALUES (?,?,?)
");
$stmt->bind_param("iis",$id,$uid,$testo);
$ok = $stmt->execute();
echo json_encode(['success'=>$ok]);
