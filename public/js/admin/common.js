(function () {
  const note = document.getElementById("desk-note");

  function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[char]));
  }

  function when(value) {
    if (!value) return "—";
    return new Date(value).toLocaleDateString("ar-EG", { day: "numeric", month: "short", year: "numeric" });
  }

  function say(message, ok = false) {
    if (!note) return;
    note.hidden = !message;
    note.textContent = message || "";
    note.classList.toggle("is-ok", Boolean(message) && ok);
    note.classList.toggle("is-bad", Boolean(message) && !ok);
  }

  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.hidden = false;
  }

  function closeModal(modal) {
    if (modal) modal.hidden = true;
  }

  function applyFilters() {
    const parts = [document.querySelector("[data-page-search]")?.value, document.getElementById("cs-search")?.value]
      .map((value) => (value || "").trim().toLowerCase())
      .filter(Boolean);
    const filters = [...document.querySelectorAll("[data-page-filter]")].map((field) => ({
      key: field.dataset.pageFilter,
      value: field.value.trim(),
    }));
    const passes = (row) => {
      const text = (row.dataset.search || "").toLowerCase();
      const queryOk = parts.every((part) => text.includes(part));
      return queryOk && filters.every(({ key, value }) => !value || (row.dataset[key] || "") === value);
    };
    const items = [...document.querySelectorAll("#desk [data-search]")];
    items.forEach((row) => {
      const own = passes(row);
      const childHit = [...row.querySelectorAll("[data-search]")].some(passes);
      const parent = row.parentElement?.closest("[data-search]");
      const shownByParent = Boolean(parent && passes(parent) && filters.every(({ key, value }) => !value || (row.dataset[key] || "") === value));
      row.hidden = !(own || childHit || shownByParent);
    });
    const empty = document.getElementById("page-filter-empty");
    if (empty) empty.hidden = !(items.length && items.every((row) => row.hidden));
  }

  document.addEventListener("click", (event) => {
    const foldToggle = event.target.closest("[data-fold]");
    if (foldToggle) {
      const fold = foldToggle.closest(".fold");
      if (fold) {
        fold.classList.toggle("is-open");
        foldToggle.setAttribute("aria-expanded", fold.classList.contains("is-open") ? "true" : "false");
      }
    }
    const opener = event.target.closest("[data-open-modal]");
    if (opener) openModal(opener.dataset.openModal);
    const closer = event.target.closest("[data-close]");
    if (closer) closeModal(closer.closest(".cs-modal"));
  });

  document.addEventListener("input", (event) => {
    if (event.target.matches("#cs-search, [data-page-search]")) applyFilters();
  });
  document.addEventListener("change", (event) => {
    if (event.target.matches("[data-page-filter]")) applyFilters();
  });

  document.querySelectorAll("[data-logout]").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        await Elmo.api("/api/logout", { method: "POST" });
      } catch {
        /* token may already be gone */
      }
      localStorage.removeItem("elmoghazy_token");
      location.href = "/login?as=teacher";
    });
  });

  function entityCard(card) {
    const rows = card.rows || [card.actions || []];
    const meta = (card.meta || [])
      .map((row) => `<li><span>${esc(row)}</span></li>`)
      .join("");
    const stats = (card.stats || [])
      .map((row) => `<div><strong>${esc(row.value)}</strong><span>${esc(row.label)}</span></div>`)
      .join("");
    const actions = rows
      .map((row) => `<div class="entity-card__actions">${row
        .map((action) => {
          const tone = action.tone || "slate";
          const label = esc(action.label);
          if (action.href) return `<a class="entity-act is-${tone}" href="${esc(action.href)}">${label}</a>`;
          const data = Object.entries(action.data || {})
            .map(([key, value]) => ` data-${key}="${esc(value)}"`)
            .join("");
          return `<button type="button" class="entity-act is-${tone}" data-act="${esc(action.act || "")}"${data}>${label}</button>`;
        })
        .join("")}</div>`)
      .join("");
    return `<article class="entity-card" ${card.attrs || ""}>
      <header class="entity-card__top"><h2>${esc(card.title)}</h2><span class="entity-pill ${card.live ? "is-on" : "is-off"}">${esc(card.badge || (card.live ? "نشط" : "متوقف"))}</span></header>
      <p class="entity-card__copy">${esc(card.copy || "—")}</p>
      ${meta ? `<ul class="entity-card__meta">${meta}</ul>` : ""}
      <div class="entity-card__stats">${stats}</div>
      ${actions}
      ${card.foot || ""}
    </article>`;
  }

  function state(on, yes, no) {
    return `<span class="cs-state ${on ? "is-on" : "is-off"}"><i></i>${esc(on ? yes : no)}</span>`;
  }

  window.Admin = { esc, when, say, openModal, closeModal, applyFilters, entityCard, state };

  Elmo.onReady((user) => {
    if (!user) {
      location.replace("/login?as=teacher");
      return;
    }
    if (user.role !== "teacher") {
      location.replace("/learn");
      return;
    }
    const app = document.getElementById("cs-app");
    if (app) app.hidden = false;
    const name = document.getElementById("cs-name");
    const avatar = document.getElementById("cs-avatar");
    if (name) name.textContent = user.name;
    if (avatar) avatar.textContent = (user.name || "م").trim().charAt(0);
  });
})();
