
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
    const menuBtn = document.getElementById('menu-btn');
    const sidebarMenuBtn = document.getElementById('sidebar-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    // Leer estado del sidebar de localStorage
    const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';

// Si estaba abierto lo mantenemos abierto
if (isSidebarOpen) {
  sidebar.classList.remove('-translate-x-64');
  content?.classList.add('pl-64');
}

function toggleSidebar() {
  sidebar.classList.toggle('-translate-x-64');
  content?.classList.toggle('pl-64');

  // Guardar estado en localStorage
  const isOpen = !sidebar.classList.contains('-translate-x-64');
  localStorage.setItem('sidebarOpen', isOpen);
}

menuBtn.addEventListener('click', toggleSidebar);
sidebarMenuBtn.addEventListener('click', toggleSidebar);

<<<<<<< Updated upstream
// Resaltar opción activa según URL 
=======
/* Resaltar opción activa según URL 
>>>>>>> Stashed changes
const currentPath = window.location.pathname.split('/').pop();

document.querySelectorAll('#sidebar a').forEach(link => {
  const linkPath = link.getAttribute('href').split('/').pop();

  if (linkPath === currentPath) {
    link.classList.add('bg-red-500', 'text-white'); // Activo con el color rojo predeterminado
  } else {
    link.classList.remove('bg-red-500', 'text-white');
  }
});
<<<<<<< Updated upstream


=======
*/
>>>>>>> Stashed changes
    // Manejo de submenús
document.querySelectorAll('.submenu-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const submenu = button.nextElementSibling;
    submenu.classList.toggle('hidden');
    const arrow = button.querySelector('svg:last-child');
    arrow.classList.toggle('rotate-180');
  });
<<<<<<< Updated upstream
});
=======
});
>>>>>>> Stashed changes
