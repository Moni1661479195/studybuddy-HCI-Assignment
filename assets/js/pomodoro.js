document.addEventListener('DOMContentLoaded', function(){
  const startBtn = document.getElementById('pom-start');
  const stopBtn = document.getElementById('pom-stop');
  let sessionId = null;
  if (startBtn) startBtn.addEventListener('click', function(){
    fetch('/study_session_start.php', {method:'POST'}).then(r=>r.json()).then(d=>{
      sessionId = d.session_id;
    });
  });
  if (stopBtn) stopBtn.addEventListener('click', function(){
    if(!sessionId) return;
    fetch('/study_session_end.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({session_id: sessionId})
    }).then(()=>{ sessionId = null; });
  });
});