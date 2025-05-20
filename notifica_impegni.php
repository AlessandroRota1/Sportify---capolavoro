
<?php
require 'sendgrid-php/vendor/autoload.php'; // Assicurati che SendGrid sia installato

use SendGrid\Mail\Mail;

// Configurazione SendGrid
$sendgridApiKey = 'SG.sportifyyy'; // ← la tua API key qui
$sendgrid = new \SendGrid($sendgridApiKey);

// Connessione al DB
$mysqli = new mysqli("localhost", "root", "", "sportify");
if ($mysqli->connect_error) {
    die("Errore DB: " . $mysqli->connect_error);
}

$domani = date('Y-m-d', strtotime('+1 day'));

// === EMAIL PER CALCETTI ===
$sql_calcetti = "
    SELECT u.email, u.nome, c.data_ora, ca.indirizzo
    FROM calcetto_utente cu
    JOIN utenti u ON cu.Id_utente = u.id_utente
    JOIN calcetti c ON cu.Id_calcetto = c.id_calcetto
    JOIN campi ca ON c.id_campo = ca.id_campo
    WHERE DATE(c.data_ora) = '$domani'
";

$res1 = $mysqli->query($sql_calcetti);
while ($r = $res1->fetch_assoc()) {
    $email = new Mail();
    $email->setFrom("notifiche@sportify.it", "Sportify");
    $email->setSubject("📅 Promemoria Calcetto per domani");
    $email->addTo($r['email'], $r['nome']);

    $body = "Ciao " . $r['nome'] . ",\n\nHai un calcetto in programma per domani alle " .
        date('H:i', strtotime($r['data_ora'])) . " presso:\n" .
        $r['indirizzo'] . "\n\nNon dimenticare! ⚽\n\n- Team Sportify";

    $email->addContent("text/plain", $body);
    $email->addContent("text/html", nl2br($body));

    try {
        $response = $sendgrid->send($email);
    } catch (Exception $e) {
        error_log("Errore invio calcetto: " . $e->getMessage());
    }
}

// === EMAIL PER TORNEI ===
$sql_tornei = "
    SELECT u.email, u.nome, t.nome AS torneo, t.data_inizio
    FROM utente_squadra us
    JOIN utenti u ON us.id_utente = u.id_utente
    JOIN squadre s ON us.id_squadra = s.Id_squadra
    JOIN tornei t ON s.id_torneo = t.Id_torneo
    WHERE t.data_inizio = '$domani'
";

$res2 = $mysqli->query($sql_tornei);
while ($r = $res2->fetch_assoc()) {
    $email = new Mail();
    $email->setFrom("notifiche@sportify.it", "Sportify");
    $email->setSubject("📢 Il torneo \"" . $r['torneo'] . "\" inizia domani!");
    $email->addTo($r['email'], $r['nome']);

    $body = "Ciao " . $r['nome'] . ",\n\nIl torneo \"" . $r['torneo'] . "\" a cui sei iscritto inizia domani (" .
        $r['data_inizio'] . ").\nPreparati e buona fortuna! 🏆\n\n- Team Sportify";

    $email->addContent("text/plain", $body);
    $email->addContent("text/html", nl2br($body));

    try {
        $sendgrid->send($email);
    } catch (Exception $e) {
        error_log("Errore invio torneo: " . $e->getMessage());
    }
}

echo "Email inviate con successo!";
?>
