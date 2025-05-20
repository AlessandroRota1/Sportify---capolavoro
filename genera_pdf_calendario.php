<?php
require('fpdf.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if (!isset($_GET['id_torneo'])) die("Torneo non specificato.");

$id_torneo = (int)$_GET['id_torneo'];

// Info torneo
$resTorneo = $conn->query("SELECT nome FROM tornei WHERE Id_torneo = $id_torneo");
$nome_torneo = $resTorneo->fetch_assoc()['nome'] ?? 'Torneo';

// Partite
$partite = [];
$res = $conn->query("SELECT p.*, s1.nome AS squadra1, s2.nome AS squadra2 
                     FROM partite p
                     JOIN squadre s1 ON p.id_squadra1 = s1.Id_squadra
                     JOIN squadre s2 ON p.id_squadra2 = s2.Id_squadra
                     WHERE p.id_torneo = $id_torneo
                     ORDER BY p.data_partita IS NULL, p.data_partita ASC, p.orario ASC");
while ($row = $res->fetch_assoc()) {
    $partite[] = $row;
}

// Crea PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Calendario - ' . $nome_torneo, 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(10, 10, '#', 1);
$pdf->Cell(40, 10, 'Squadra 1', 1);
$pdf->Cell(20, 10, 'Gol', 1);
$pdf->Cell(40, 10, 'Squadra 2', 1);
$pdf->Cell(20, 10, 'Gol', 1);
$pdf->Cell(25, 10, 'Data', 1);
$pdf->Cell(25, 10, 'Ora', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);
foreach ($partite as $i => $p) {
    $pdf->Cell(10, 10, $i + 1, 1);
    $pdf->Cell(40, 10, $p['squadra1'], 1);
    $pdf->Cell(20, 10, $p['gol_1'], 1);
    $pdf->Cell(40, 10, $p['squadra2'], 1);
    $pdf->Cell(20, 10, $p['gol_2'], 1);
    $pdf->Cell(25, 10, $p['data_partita'] ?? '-', 1);
    $pdf->Cell(25, 10, $p['orario'] ?? '-', 1);
    $pdf->Ln();
}

$pdf->Output('I', 'Calendario_' . $nome_torneo . '.pdf');
?>
