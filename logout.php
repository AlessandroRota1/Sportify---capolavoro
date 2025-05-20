<?php
session_start();
session_unset(); // Elimina tutte le variabili di sessione
session_destroy(); // Distrugge la sessione

// Redirect alla home
header("Location: index.php");
exit();
?>
