
/*const menuBtn = document.getElementById('menu-btn');
    const closeBtn = document.getElementById('close-btn');
    const sidebar = document.getElementById('sidebar');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.remove('w-0');
      sidebar.classList.add('w-64'); // abrir
    });

    closeBtn.addEventListener('click', () => {
      sidebar.classList.remove('w-64');
      sidebar.classList.add('w-0'); // cerrar
    });*/

    // lógica para abrir/cerrar el menú desplazando el contenido
// Referencias
const menuBtn = document.getElementById('menu-btn');
const sidebarMenuBtn = document.getElementById('sidebar-menu-btn');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content'); // 🟢 asegúrate que tu contenido tenga este ID
const logo = sidebar.querySelector('img[alt="Logo"]');
const sidebarText = sidebar.querySelectorAll('span');
const userBlock = document.getElementById('userBlock');

// 🔹 Anchos del sidebar
const anchoCompleto = '16rem'; // 256px
const anchoIconos = '5rem'; // 🟢 un poco más ancho para dar más espacio (~80px)

// Leer estado desde localStorage
let sidebarCompacto = localStorage.getItem('sidebarCompacto') === 'true';

// Aplicar estado guardado
if (sidebarCompacto) {
  sidebar.classList.add('sidebar-cerrado');
  ajustarSidebar(true);
} else {
  ajustarSidebar(false);
}

// Alternar entre completo ↔ compacto
function toggleSidebar() {
  sidebarCompacto = !sidebarCompacto;
  sidebar.classList.toggle('sidebar-cerrado', sidebarCompacto);
  ajustarSidebar(sidebarCompacto);
  localStorage.setItem('sidebarCompacto', sidebarCompacto);
}

// Ajusta visibilidad, ancho y margen del contenido
function ajustarSidebar(cerrar) {
  if (cerrar) {
    logo.style.display = 'none';
    sidebarText.forEach(span => (span.style.display = 'none'));
    userBlock.classList.add('user-mini');

    // 🔹 Reducir ancho cuando solo iconos
    sidebar.style.width = anchoIconos;

    // 🔹 Centrar iconos
    sidebar.querySelectorAll('a').forEach(a => {
      a.style.justifyContent = 'center';
      a.style.padding = '0.75rem 0';
    });

    // 🟢 Desplazar contenido un poco más (ajusta según tu diseño)
    if (content) {
      content.style.marginLeft = anchoIconos;
      content.style.transition = 'margin-left 0.3s ease';
    }

  } else {
    logo.style.display = 'block';
    sidebarText.forEach(span => (span.style.display = 'inline'));
    userBlock.classList.remove('user-mini');

    // 🔹 Restaurar ancho normal
    sidebar.style.width = anchoCompleto;

    sidebar.querySelectorAll('a').forEach(a => {
      a.style.justifyContent = 'flex-start';
      a.style.padding = '1rem';
    });

    // 🟢 Restaurar margen del contenido
    if (content) {
      content.style.marginLeft = anchoCompleto;
      content.style.transition = 'margin-left 0.3s ease';
    }
  }
}

// Botones que controlan el sidebar
menuBtn.addEventListener('click', toggleSidebar);
sidebarMenuBtn.addEventListener('click', toggleSidebar);

// --- Resaltar enlace activo según URL ---
const currentPath = window.location.pathname.split('/').pop();
document.querySelectorAll('#sidebar a').forEach(link => {
  const linkPath = link.getAttribute('href').split('/').pop();
  if (linkPath === currentPath) {
    link.classList.add('bg-red-500', 'text-white');
  } else {
    link.classList.remove('bg-red-500', 'text-white');
  }
});

// --- Manejo de submenús ---
document.querySelectorAll('.submenu-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const submenu = button.nextElementSibling;
    submenu.classList.toggle('hidden');
    const arrow = button.querySelector('svg:last-child');
    arrow.classList.toggle('rotate-180');
  });
});
