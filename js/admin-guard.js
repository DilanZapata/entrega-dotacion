// Incluir en toda página bajo /admin/ (excepto login.html) para exigir sesión activa.
(async function () {
  try {
    const res = await fetch('../api/auth.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.logged_in) {
      window.location.href = 'login.html';
    }
  } catch (e) {
    window.location.href = 'login.html';
  }
})();

async function adminLogout() {
  await fetch('../api/auth.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'logout' }),
  });
  window.location.href = 'login.html';
}
