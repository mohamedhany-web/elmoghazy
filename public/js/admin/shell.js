(function () {
  const app = document.getElementById("cs-app");
  if (!app) return;

  const mobile = window.matchMedia("(max-width: 1023px)");

  function applyRail() {
    app.classList.remove("is-rail");
    localStorage.removeItem("cs-side-rail");
    if (!mobile.matches) app.classList.remove("is-drawer-open");
    document.body.style.overflow = app.classList.contains("is-drawer-open") ? "hidden" : "";
  }

  applyRail();
  mobile.addEventListener("change", applyRail);

  document.querySelectorAll("[data-side-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
      if (mobile.matches) {
        app.classList.toggle("is-drawer-open");
        document.body.style.overflow = app.classList.contains("is-drawer-open") ? "hidden" : "";
      }
    });
  });

  app.querySelector("[data-side-backdrop]")?.addEventListener("click", () => {
    app.classList.remove("is-drawer-open");
    document.body.style.overflow = "";
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      app.classList.remove("is-drawer-open");
      document.body.style.overflow = "";
      document.querySelectorAll(".cs-modal").forEach((modal) => {
        modal.hidden = true;
      });
      document.querySelectorAll("[data-menu]").forEach((menu) => {
        menu.hidden = true;
      });
    }
  });

  app.querySelectorAll("[data-nav-link]").forEach((link) => {
    link.addEventListener("click", () => {
      app.classList.remove("is-drawer-open");
      document.body.style.overflow = "";
    });
  });

  app.querySelectorAll("[data-group]").forEach((button) => {
    button.addEventListener("click", () => {
      const group = button.closest(".cs-nav-group");
      if (app.classList.contains("is-rail") && !mobile.matches) {
        localStorage.setItem("cs-side-rail", "0");
        applyRail();
        group.classList.add("is-open");
        return;
      }
      group.classList.toggle("is-open");
    });
  });

  const apps = document.querySelector("[data-apps]");
  apps?.querySelector("[data-apps-btn]")?.addEventListener("click", () => {
    const menu = apps.querySelector("[data-menu]");
    menu.hidden = !menu.hidden;
  });
})();
