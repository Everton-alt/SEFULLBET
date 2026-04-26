const API_BASE = 'https://sefullbet.onrender.com';

async function apiFetch(path, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 15000);

  const isFormData = options.body instanceof FormData;

  const headers = {
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...(options.headers || {})
  };

  try {
    const res = await fetch(API_BASE + path, {
      ...options,
      headers,
      credentials: 'include',
      signal: controller.signal
    });

    clearTimeout(timeout);

    if (res.status === 401) {
      if (!window.location.pathname.includes('login')) {
        window.location.href = '/login.html';
      }
      return null;
    }

    if (!res.ok) {
      let errorMsg = `Erro HTTP ${res.status}`;

      try {
        const errJson = await res.json();
        errorMsg = errJson.erro || errJson.message || errorMsg;
      } catch {}

      throw new Error(errorMsg);
    }

    const text = await res.text();
    if (!text) return null;

    try {
      return JSON.parse(text);
    } catch {
      return null;
    }

  } catch (err) {
    if (err.name === 'AbortError') {
      alert('Servidor demorou para responder.');
    } else {
      alert(err.message || 'Erro na comunicação com servidor');
    }
    return null;
  }
}
