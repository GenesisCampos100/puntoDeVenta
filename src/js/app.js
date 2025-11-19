document.addEventListener('DOMContentLoaded', function () {

    navegacionFija();
    crearGaleria();
    resaltarEnlace();
    scrollNav();
})

function navegacionFija() {
    const header = document.querySelector('.header')
    const sobreFestival = document.querySelector('.sobre-festival')

    window.addEventListener('scroll', function() {
        // console.log(sobreFestival.getBoundingClientRect().bottom)
        //* El anterior codigo ayuda a detectar si ya se pasó alguna parte especifica del codigo para brindar sus coordenadas
        if(sobreFestival.getBoundingClientRect().bottom < 1) {
            header.classList.add('fixed')
        } else {
            header.classList.remove('fixed')
        }
    })
}

function crearGaleria() {

    const Cantidad_Imagenes = 16;
    const galeria = document.querySelector('.galeria-imagenes');

    for (let i = 1; i <= Cantidad_Imagenes; i++) {
        const imagen = document.createElement('IMG')
        imagen.loading = 'lazy'
        imagen.width = "300"
        imagen.height = "200"
        imagen.src = `src/img/gallery/thumb/${i}.jpg`
        imagen.alt = 'Imagen galeria'

        // Event Handler
        imagen.onclick = function () {
            mostrarImagen(i);
        }

        galeria.appendChild(imagen)
    }
}

function mostrarImagen(i) {
    const imagen = document.createElement('IMG')
    imagen.src = `src/img/gallery/full/${i}.jpg`
    imagen.alt = 'Imagen galeria'

    //* Generar Modal
    const modal = document.createElement('DIV');
    modal.classList.add('modal');
    modal.onclick = cerrarModal;

    //* Botón cerrar modal
    const cerrarModalBtn = document.createElement('BUTTON')
    cerrarModalBtn.textContent = 'X'
    cerrarModalBtn.classList.add('btn-cerrar')
    cerrarModalBtn.onclick = cerrarModal

    modal.appendChild(cerrarModalBtn)
    modal.appendChild(imagen)

    //* Agregar al HTML
    const body = document.querySelector('body');
    body.classList.add('overflow-hidden')
    body.appendChild(modal)
}

function cerrarModal() {
    const modal = document.querySelector('.modal');
    modal.classList.add('fade-out')

    setTimeout( () => {
        modal?.remove()

        const body = document.querySelector('body');
        body.classList.remove('overflow-hidden')
    }, 500);
    
}

function resaltarEnlace() {
    document.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section')
        const navLinks = document.querySelectorAll('.navegacion-principal a')

        let actual = '';
        sections.forEach( section => {
            const sectionTop = section.offsetTop
            const sectionHeigth = section.clientHeight
            if(window.scrollY >= (sectionTop - sectionHeigth / 3) ) {
                actual = section.id
            }
        })

        navLinks.forEach(link => {
            link.classList.remove('active')
            if(link.getAttribute('href') === '#' + actual) {
                link.classList.add('active')
            }
        })
    } )
}

function scrollNav() {
    const navLinks = document.querySelectorAll('.navegacion-principal a')

    navLinks.forEach( link => {
        link.addEventListener('click', e => {
            e.preventDefault()
            const sectionScroll = e.target.getAttribute('href')
            const section = document.querySelector(sectionScroll)

            section.scrollIntoView({behavior: 'smooth'})
        })
    })
}