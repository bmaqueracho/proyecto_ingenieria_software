// js/dashboard.js

// Función para el menú de hamburguesa en móviles.
document.addEventListener('DOMContentLoaded', () => {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }
});

// Función "mágica" para cargar contenido en el área principal
const contentArea = document.getElementById('contentArea');
const loaderHTML = `<div class="loading-overlay"><div class="loader"></div></div>`;

async function cargarContenido(pathModulo, clickedLink) {
    // Marcar el link del sidebar como activo
    if (clickedLink) {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
        });
        clickedLink.classList.add('active');
    }

    // Mostrar el spinner de carga
    contentArea.innerHTML = loaderHTML;

    try {
        const response = await fetch(pathModulo);
        if (!response.ok) {
            throw new Error(`Error en la red: ${response.statusText}`);
        }
        
        // Cargar el HTML y EJECUTAR los scripts que contenga
        contentArea.innerHTML = await response.text();

        // El navegador por sí solo no ejecuta los <script> cargados con innerHTML.
        // Este bloque de código los busca y los ejecuta manualmente.
        Array.from(contentArea.querySelectorAll("script")).forEach(oldScript => {
            const newScript = document.createElement("script");
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

    } catch (error) {
        console.error("Error al cargar el módulo:", error);
        contentArea.innerHTML = `<p class="text-center text-danger">Error al cargar el contenido.</p>`;
    }
}