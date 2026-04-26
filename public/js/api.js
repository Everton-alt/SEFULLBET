// === Utilitário de comunicação com a API ===
// Todas as páginas usam esse arquivo

async function apiFetch(path, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };

  const res = await fetch(path, { 
    ...options, 
    headers, 
    credentials: 'include' // envia cookies de sessão
  });

  if (res.status === 401) {
    // Sessão expirada ou inválida → redireciona para login
    window.location.href = '/login.html';
    return null;
  }

  try {
    return await res.json();
  } catch {
    return null;
  }
}

function logout() {
  // Invalida sessão no servidor
  fetch('/api/auth/logout', { method: 'POST', credentials: 'include' })
    .finally(() => { window.location.href = '/login.html'; });
}

// Escapa HTML para prevenir XSS
function esc(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = String(str);
  return div.innerHTML;
}
