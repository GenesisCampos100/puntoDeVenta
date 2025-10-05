document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menu-btn");
  const closeBtn = document.getElementById("close-btn");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  // Persistencia del sidebar
  const isSidebarOpen = localStorage.getItem("sidebarOpen") === "true";
  if (isSidebarOpen) {
    sidebar.classList.remove("-translate-x-64");
    content?.classList.add("pl-64");
  }

  menuBtn?.addEventListener("click", () => {
    sidebar.classList.remove("-translate-x-64");
    content?.classList.add("pl-64");
    localStorage.setItem("sidebarOpen", true);
  });

  closeBtn?.addEventListener("click", () => {
    sidebar.classList.add("-translate-x-64");
    content?.classList.remove("pl-64");
    localStorage.setItem("sidebarOpen", false);
  });

  // Resaltar opción activa
  const currentPath = window.location.pathname.split("/").pop();
  document.querySelectorAll("#sidebar a").forEach(link => {
    const linkPath = link.getAttribute("href")?.split("/").pop();
    if (linkPath === currentPath) link.classList.add("bg-red-500", "text-white");
    else link.classList.remove("bg-red-500", "text-white");
  });
});
