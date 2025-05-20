<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header('Location: login.php');
    exit();
}
$id_calcetto = isset($_GET['id_calcetto']) ? (int)$_GET['id_calcetto'] : 0;
$uid = (int)$_SESSION['id_utente'];

// Connessione
$conn = new mysqli("localhost","root","","sportify");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Verifica partecipazione (creatore o iscritto)
$sql = $conn->prepare("
  SELECT 1
  FROM calcetti c
  LEFT JOIN calcetto_utente cu ON c.id_calcetto = cu.Id_calcetto
  WHERE c.id_calcetto = ? AND (c.id_utente = ? OR cu.Id_utente = ?)
");
$sql->bind_param("iii", $id_calcetto, $uid, $uid);
$sql->execute();
$sql->store_result();
if ($sql->num_rows === 0) {
    die("Non sei autorizzato ad accedere a questa chat.");
}
$sql->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Chat Calcetto #<?= $id_calcetto ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
    .chat-container {
      max-width:600px; margin:2rem auto; background:#fff;
      box-shadow:0 0 10px rgba(0,0,0,0.1); border-radius:6px;
      display:flex; flex-direction:column; height:80vh;
    }
    .header {
      padding:1rem; background:#1976D2; color:#fff; border-top-left-radius:6px; border-top-right-radius:6px;
      display:flex; justify-content:space-between; align-items:center;
    }
    .messages {
      flex:1; padding:1rem; overflow-y:auto; background:#e8eaf6;
    }
    .message {
      margin-bottom:0.75rem; padding:0.5rem;
      background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.1);
    }
    .message .meta {
      font-size:0.8rem; color:#555; margin-bottom:0.3rem;
    }
    .composer {
      display:flex; border-top:1px solid #ccc;
    }
    .composer textarea {
      flex:1; padding:0.75rem; border:none; resize:none; border-bottom-left-radius:6px;
      font-size:1rem;
    }
    .composer button {
      width:80px; background:#1976D2; color:#fff; border:none;
      border-bottom-right-radius:6px; cursor:pointer; transition:background .3s;
    }
    .composer button:hover { background:#0d47a1; }
  </style>
</head>
<body>
  <div class="chat-container">
    <div class="header">
      <div>Chat Calcetto #<?= $id_calcetto ?></div>
      <a href="visualizza_calcetti.php" style="color:#fff;text-decoration:none;">← Torna</a>
    </div>
    <div id="messages" class="messages"></div>
    <div class="composer">
      <textarea id="msgText" placeholder="Scrivi un messaggio..." rows="2"></textarea>
      <button id="sendBtn">Invia</button>
    </div>
  </div>

  <script>
    const calcettoId = <?= $id_calcetto ?>;
    const fetchUrl = 'get_messages.php?id_calcetto=' + calcettoId;
    const postUrl  = 'send_message.php';

    // Carica messaggi ogni 3s
    function loadMessages(){
      fetch(fetchUrl)
        .then(r=>r.json())
        .then(data=>{
          const cont = document.getElementById('messages');
          cont.innerHTML = '';
          data.forEach(m=>{
            const div = document.createElement('div');
            div.className = 'message';
            div.innerHTML = `<div class="meta"><strong>${m.nome} ${m.cognome}</strong> – ${m.data_ora}</div>
                             <div class="text">${m.testo}</div>`;
            cont.appendChild(div);
          });
          cont.scrollTop = cont.scrollHeight;
        });
    }
    setInterval(loadMessages, 3000);
    loadMessages();

    // Invia nuovo messaggio
    document.getElementById('sendBtn').addEventListener('click', ()=>{
      const testo = document.getElementById('msgText').value.trim();
      if (!testo) return;
      fetch(postUrl, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id_calcetto=${calcettoId}&testo=`+encodeURIComponent(testo)
      }).then(r=>r.json()).then(res=>{
        if(res.success){
          document.getElementById('msgText').value = '';
          loadMessages();
        } else {
          alert('Errore invio');
        }
      });
    });
  </script>
</body>
</html>
