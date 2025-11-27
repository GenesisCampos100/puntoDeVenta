
// CAMBIO DE TEMA GLOBAL (CLARO / OSCURO)
document.addEventListener('DOMContentLoaded', function() {
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  let temaGuardado = localStorage.getItem('tema') || 'claro';
  if (temaGuardado === 'oscuro') {
    document.body.classList.add('dark-mode');
    themeIcon.src = '../public/img/luna-modoOscuro.png';
  } else {
    themeIcon.src = '../public/img/tema.png';
  }
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const esOscuro = document.body.classList.toggle('dark-mode');
      const nuevoTema = esOscuro ? 'oscuro' : 'claro';
      localStorage.setItem('tema', nuevoTema);
      themeIcon.src = esOscuro ? '../public/img/luna-modoOscuro.png' : '../public/img/tema.png';
    });
  }
});

//  CAMBIO DE IDIOMA SIMPLE
const languageToggle = document.getElementById('languageToggle');
const languageCode = document.getElementById('languageCode');

// Cargar idioma guardado
let idioma = localStorage.getItem('idioma') || 'ES';
languageCode.textContent = idioma;

// Detectar clic
languageToggle.addEventListener('click', () => {
  idioma = idioma === 'ES' ? 'EN' : 'ES';
  languageCode.textContent = idioma;
  localStorage.setItem('idioma', idioma);
  
  // Aquí puedes llamar tu función de traducción si ya la tienes
  console.log("Idioma cambiado a:", idioma);
});