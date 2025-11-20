// Referencias a los modales
const modalIngreso = document.getElementById('modalIngreso');
const modalRetiro = document.getElementById('modalRetiro');
const modalCorte = document.getElementById('modalCorte'); // Nuevo modal

// Botones principales
const btnIngreso = document.querySelector('.ingreso');
const btnRetiro = document.querySelector('.retiro');
const btnCorte = document.querySelector('.corte'); //  Nuevo botón

// Botones de cierre y cancelar dentro de cada modal
const closeBtns = document.querySelectorAll('.close');
const cancelarBtns = document.querySelectorAll('.cancelar');

// Abrir modales
btnIngreso?.addEventListener('click', () => {
  modalIngreso.style.display = 'flex';
});

btnRetiro?.addEventListener('click', () => {
  modalRetiro.style.display = 'flex';
});

btnCorte?.addEventListener('click', () => {
  modalCorte.style.display = 'flex';
});

// Cerrar al dar clic en (x) o Cancelar
closeBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    btn.closest('.modal').style.display = 'none';
  });
});

cancelarBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    btn.closest('.modal').style.display = 'none';
  });
});

//  Cerrar al hacer clic fuera del modal
window.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal')) {
    e.target.style.display = 'none';
  }
});