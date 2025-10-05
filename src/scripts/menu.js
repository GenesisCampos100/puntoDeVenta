// menu.js
(() => {
  const menuBtn = document.getElementById("menu-btn");
  const sidebarMenuBtn = document.getElementById("sidebar-menu-btn");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  // Leer estado del sidebar de localStorage
  const isSidebarOpen = localStorage.getItem("sidebarOpen") === "true";

  if (isSidebarOpen) {
    sidebar.classList.remove("-translate-x-64");
    content?.classList.add("pl-64");
  }

  function toggleSidebar() {
    sidebar.classList.toggle("-translate-x-64");
    content?.classList.toggle("pl-64");

    const isOpen = !sidebar.classList.contains("-translate-x-64");
    localStorage.setItem("sidebarOpen", isOpen);
  }

  menuBtn.addEventListener("click", toggleSidebar);
  sidebarMenuBtn?.addEventListener("click", toggleSidebar);

  // Resaltar opción activa
  const currentPath = window.location.pathname.split("/").pop();
  document.querySelectorAll("#sidebar a").forEach(link => {
    const linkPath = link.getAttribute("href")?.split("/").pop();
    if (linkPath === currentPath) {
      link.classList.add("bg-red-500", "text-white");
    } else {
      link.classList.remove("bg-red-500", "text-white");
    }
  });

  // Submenús
  document.querySelectorAll(".submenu-toggle").forEach(button => {
    button.addEventListener("click", () => {
      const submenu = button.nextElementSibling;
      submenu.classList.toggle("hidden");
      const arrow = button.querySelector("svg:last-child");
      arrow?.classList.toggle("rotate-180");
    });
  });
})();
