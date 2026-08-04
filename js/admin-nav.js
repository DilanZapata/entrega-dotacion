// Barra de navegación compartida para todas las páginas del panel administrativo.
function renderAdminNav(active) {
  const items = [
    { key: 'dashboard', href: 'dashboard.html', label: 'Inicio', icon: 'dashboard' },
    { key: 'periodos', href: 'periodos.html', label: 'Periodos', icon: 'event' },
    { key: 'entrega', href: 'entrega.html', label: 'Entregar', icon: 'assignment_turned_in' },
    { key: 'entregas', href: 'entregas.html', label: 'Historial', icon: 'history' },
    { key: 'epp', href: 'epp.html', label: 'EPP', icon: 'shield' },
    { key: 'informe', href: 'informe.html', label: 'Informes', icon: 'summarize' },
    { key: 'empleados', href: 'empleados.html', label: 'Empleados', icon: 'groups' },
    { key: 'catalogo', href: 'catalogo.html', label: 'Catálogo', icon: 'checkroom' },
  ];

  const el = document.getElementById('adminNav');
  if (!el) return;

  el.innerHTML = `
    <header class="bg-slate-900 text-white sticky top-0 z-20">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 shrink-0">
          <img src="../assets/logo.png" class="h-8 w-auto bg-white rounded p-0.5" alt="logo"/>
          <span class="font-bold text-sm hidden sm:inline">Panel Administrativo</span>
        </div>
        <nav class="flex items-center gap-1 overflow-x-auto no-scrollbar">
          ${items.map(it => `
            <a href="${it.href}" class="flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors ${active === it.key ? 'bg-brand-primary text-white' : 'text-slate-300 hover:bg-slate-800'}">
              <span class="material-symbols-outlined text-base">${it.icon}</span>
              <span class="hidden md:inline">${it.label}</span>
            </a>`).join('')}
        </nav>
        <button onclick="adminLogout()" class="shrink-0 flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-bold text-slate-300 hover:bg-red-600 hover:text-white transition-colors">
          <span class="material-symbols-outlined text-base">logout</span>
          <span class="hidden md:inline">Salir</span>
        </button>
      </div>
    </header>`;
}
