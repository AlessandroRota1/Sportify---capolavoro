<?php
session_start();

require 'sendgrid-php/vendor/autoload.php'; // Assicurati che SendGrid sia installato via Composer

$SENDGRID_API_KEY = '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'; // <-- Inserisci la tua vera API Key
$mittente_nome = $_SESSION['nome'] . ' ' . $_SESSION['cognome'];
$mittente_id = $_SESSION['id_utente'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_destinatario'], $_POST['id_calcetto'])) {
    $id_destinatario = (int)$_POST['id_destinatario'];
    $id_calcetto = (int)$_POST['id_calcetto'];

    $res = $conn->query("SELECT email, nome FROM utenti WHERE id_utente = $id_destinatario");
    if ($res->num_rows) {
        $row = $res->fetch_assoc();
        $email = $row['email'];
        $nome_dest = $row['nome'];

        $res2 = $conn->query("SELECT data_ora FROM calcetti WHERE id_calcetto = $id_calcetto");
        $info = $res2->fetch_assoc();

        $host = "http://localhost/sportify"; // Cambia con il tuo dominio se online
        $link_unisciti = "$host/unisciti_calcetto.php?id_calcetto=$id_calcetto";

        $email_obj = new \SendGrid\Mail\Mail();
        $email_obj->setFrom("rotaale05102006@gmail.com", "Sportify");
        $email_obj->setSubject("Invito a Calcetto da $mittente_nome");
        $email_obj->addTo($email, $nome_dest);

        $email_obj->addContent("text/plain", "$mittente_nome ti ha invitato a un calcetto il {$info['data_ora']}. Unisciti: $link_unisciti");
        $email_obj->addContent("text/html", "
            <strong>$mittente_nome</strong> ti ha invitato a partecipare a un calcetto il <strong>{$info['data_ora']}</strong>.<br><br>
            <a href='$link_unisciti' style='padding: 10px 15px; background: #4CAF50; color: white; border-radius: 5px; text-decoration: none;'>➕ Unisciti ora</a>
        ");

        $sendgrid = new \SendGrid($SENDGRID_API_KEY);
        try {
            $sendgrid->send($email_obj);
            header("Location: amicizie.php?msg=invito-ok");
        } catch (Exception $e) {
            echo 'Errore invio email: ', $e->getMessage();
        }
    }
}
?>
