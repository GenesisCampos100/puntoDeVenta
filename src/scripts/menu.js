
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
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');
    const content = document.getElementById('content');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.remove('-translate-x-64');
      content.classList.add('pl-64'); // deja espacio al contenido
    });

    closeBtn.addEventListener('click', () => {
      sidebar.classList.add('-translate-x-64');
      content.classList.remove('pl-64');
    });


    // Manejo de submenús
document.querySelectorAll('.submenu-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const submenu = button.nextElementSibling;
    submenu.classList.toggle('hidden');
    const arrow = button.querySelector('svg:last-child');
    arrow.classList.toggle('rotate-180');
  });
});
