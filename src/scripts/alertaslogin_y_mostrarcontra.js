// ===============================
//   ALERTAS LOGIN (AJAX)
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById("loginFormulario");

    if (!form) {
        console.warn("[Login] Formulario #loginFormulario no encontrado");
        return;
    }

    console.log("[Login] Sistema de login AJAX inicializado");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        // Referencias a elementos UI (opcional: deshabilitar botón)
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append("ajax", "1");

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            // Intentar leer como texto primero para depuración si falla el JSON
            const text = await response.text();
            let data;

            try {
                data = JSON.parse(text);
            } catch (jsonError) {
                console.error("[Login] Error parseando JSON:", jsonError);
                console.error("[Login] Respuesta recibida:", text);
                showErrorModal("Error del servidor: Respuesta inválida. Revisa la consola.");
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            if (data.success) {
                console.log("[Login] Éxito:", data);
                showSuccessModal(data.redirect);
            } else {
                console.warn("[Login] Fallo:", data.message);
                const msg = window.TR_LOGIN?.error_invalid_credentials || "Usuario o contraseña incorrectos.";
                showErrorModal(msg);
                if (submitBtn) submitBtn.disabled = false;
            }

        } catch (error) {
            console.error("[Login] Error de red:", error);
            showErrorModal("Error de conexión. Intenta nuevamente.");
            if (submitBtn) submitBtn.disabled = false;
        }
    });
});

// ===============================
//   MODAL ERROR
// ===============================
function showErrorModal(msg) {
    const modal = document.getElementById("errorModal");
    const messageEl = document.getElementById("errorMessage");
    const closeBtn = document.getElementById("closeErrorModal");

    if (!modal || !messageEl) {
        alert(msg); // Fallback nativo
        return;
    }

    messageEl.textContent = msg;
    modal.classList.add("visible");

    // Cerrar al hacer click en botón o fuera
    const closeModal = () => modal.classList.remove("visible");

    if (closeBtn) {
        closeBtn.textContent = window.TR_LOGIN?.close_btn || "Cerrar";
        closeBtn.onclick = closeModal;
    }
    modal.onclick = (e) => {
        if (e.target === modal) closeModal();
    };
}

// ===============================
//   MODAL ÉXITO + REDIRECCIÓN
// ===============================
function showSuccessModal(redirectUrl) {
    const modal = document.getElementById("successModal");
    const msgEl = document.getElementById("successMessage");

    if (msgEl) {
        msgEl.textContent = (window.TR_LOGIN?.success_login_message) || "Inicio exitoso";
    }

    if (modal) {
        modal.classList.add("visible");
    } else {
        console.log("Login exitoso, redirigiendo...");
    }

    setTimeout(() => {
        window.location.href = redirectUrl || '../index.php';
    }, 2000);
}

// ===============================
//   MOSTRAR / OCULTAR CONTRASEÑA
// ===============================
const toggleContainer = document.querySelector('.js-password-toggle');
if (toggleContainer) {
    const passwordInput = document.getElementById('password');

    toggleContainer.addEventListener('click', () => {
        if (passwordInput) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Alternar clases visuales para los iconos
            toggleContainer.classList.toggle('active');

            // Alternar visibilidad de iconos SVG específicos si tienen clases
            const iconClosed = toggleContainer.querySelector('.toggle-closed');
            const iconOpen = toggleContainer.querySelector('.toggle-open');

            if (iconClosed && iconOpen) {
                if (type === 'text') {
                    iconClosed.style.display = 'none';
                    iconOpen.style.display = 'block';
                } else {
                    iconClosed.style.display = 'block';
                    iconOpen.style.display = 'none';
                }
            }
        }
    });
}

