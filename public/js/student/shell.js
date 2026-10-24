(function () {
  const shell = document.getElementById("stShell");
  const toggle = document.getElementById("stRailToggle");
  const menu = document.getElementById("stNavMenu");
  const backdrop = document.getElementById("stRailBackdrop");
  const account = document.getElementById("stAccount");
  const notes = document.getElementById("stNotes");
  const search = document.getElementById("stSearch");
  if (!shell) return;

  const wide = () => window.matchMedia("(min-width: 901px)").matches;

  function setOpen(open, save) {
    shell.classList.toggle("is-rail-open", open);
    shell.classList.toggle("is-rail-shut", wide() && !open);
    document.body.classList.toggle("st-rail-lock", open && !wide());
    if (backdrop) backdrop.hidden = !open || wide();
    if (save && wide()) localStorage.setItem("st-rail-open", open ? "1" : "0");
  }

  function panel(box, open) {
    const button = box.querySelector("button");
    const drop = box.querySelector(".st-drop__panel");
    if (!button || !drop) return;
    drop.hidden = !open;
    button.setAttribute("aria-expanded", open ? "true" : "false");
  }

  function closeDrops(except) {
    [account, notes].forEach((box) => {
      if (box && box !== except) panel(box, false);
    });
    const drop = document.getElementById("stSearchDrop");
    if (except !== search && drop) drop.hidden = true;
  }

  setOpen(wide() && localStorage.getItem("st-rail-open") !== "0", false);

  toggle?.addEventListener("click", () => setOpen(!shell.classList.contains("is-rail-open"), wide()));
  menu?.addEventListener("click", () => setOpen(!shell.classList.contains("is-rail-open"), false));
  backdrop?.addEventListener("click", () => setOpen(false, false));
  shell.querySelectorAll(".st-rail__link").forEach((link) => {
    link.addEventListener("click", () => {
      if (!wide()) setOpen(false, false);
    });
  });

  account?.querySelector("#stAccountBtn")?.addEventListener("click", () => {
    const open = account.querySelector(".st-drop__panel").hidden;
    closeDrops(account);
    panel(account, open);
  });
  notes?.querySelector("#stNotesBtn")?.addEventListener("click", () => {
    const open = notes.querySelector(".st-drop__panel").hidden;
    closeDrops(notes);
    panel(notes, open);
  });
  document.addEventListener("click", (event) => {
    if (account && !account.contains(event.target)) panel(account, false);
    if (notes && !notes.contains(event.target)) panel(notes, false);
    if (search && !search.contains(event.target)) {
      const drop = document.getElementById("stSearchDrop");
      if (drop) drop.hidden = true;
    }
  });
  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    const dropOpen = [account, notes].some((box) => box && !box.querySelector(".st-drop__panel").hidden);
    closeDrops();
    if (!dropOpen && !wide()) setOpen(false, false);
  });

  let wasWide = wide();
  window.addEventListener("resize", () => {
    const now = wide();
    if (wasWide && !now) setOpen(false, false);
    if (!wasWide && now) setOpen(localStorage.getItem("st-rail-open") !== "0", false);
    wasWide = now;
  });

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
    if (!value) return "";
    return new Date(value).toLocaleDateString("ar-EG", { day: "numeric", month: "short" });
  }

  document.addEventListener("elmo:ready", (event) => {
    const user = event.detail;
    if (!user || user.role === "teacher") return;
    const name = (user.name || "طالب").trim();
    const avatar = document.getElementById("stAvatar");
    const side = document.getElementById("stAvatarSide");
    if (avatar) avatar.textContent = name.slice(0, 1);
    if (side) side.textContent = name.slice(0, 1);
    const grade = user.grade && user.grade.title;
    ["stRole", "stRoleSide"].forEach((id) => {
      const node = document.getElementById(id);
      if (node && grade) node.textContent = grade;
    });
    loadNotes();
    bindSearch();
  });

  let catalog = null;

  async function catalogRows() {
    if (catalog) return catalog;
    const [months, exams] = await Promise.all([
      Elmo.api("/api/student/months"),
      Elmo.api("/api/student/exams"),
    ]);
    catalog = [
      ...months.map((month) => ({ kind: "شهر", title: month.title, href: `/learn/months/${month.id}` })),
      ...exams.map((exam) => ({ kind: "امتحان", title: exam.title, href: `/learn/exams/${exam.id}` })),
    ];
    return catalog;
  }

  function bindSearch() {
    const input = document.getElementById("stSearchInput");
    const drop = document.getElementById("stSearchDrop");
    if (!input || !drop) return;
    let timer = 0;
    input.addEventListener("input", () => {
      clearTimeout(timer);
      timer = setTimeout(() => showSearch(input.value, drop), 180);
    });
    search.addEventListener("submit", async (event) => {
      event.preventDefault();
      await showSearch(input.value, drop);
      const first = drop.querySelector("a");
      if (first) location.href = first.getAttribute("href");
    });
  }

  async function showSearch(value, drop) {
    const query = value.trim();
    if (!query) {
      drop.hidden = true;
      return;
    }
    let rows = [];
    try {
      rows = (await catalogRows()).filter((row) => row.title.includes(query));
    } catch (err) {
      drop.hidden = false;
      drop.innerHTML = `<p class="st-drop__title">${esc(err.message)}</p>`;
      return;
    }
    drop.hidden = false;
    drop.innerHTML = rows.length
      ? rows
          .map((row) => `<a href="${esc(row.href)}"><strong>${esc(row.title)}</strong><small>${row.kind}</small></a>`)
          .join("")
      : `<p class="st-drop__title">مفيش نتائج.</p>`;
  }

  async function loadNotes() {
    const list = document.getElementById("stNotesList");
    const dot = document.getElementById("stNotesDot");
    if (!list) return;
    try {
      const [board, exams] = await Promise.all([
        Elmo.api("/api/student/board"),
        Elmo.api("/api/student/exams"),
      ]);
      const items = [
        ...board.activations.map((row) => ({
          href: "/learn/months",
          title: row.course,
          copy: row.ends_at ? `التفعيل ساري حتى ${when(row.ends_at)}` : "التفعيل ساري",
        })),
        ...exams
          .filter((exam) => exam.status === "available" || exam.status === "in_progress")
          .map((exam) => ({
            href: `/learn/exams/${exam.id}`,
            title: exam.title,
            copy: exam.status === "in_progress" ? "كمّل الامتحان" : "امتحان متاح",
          })),
      ];
      if (dot) dot.hidden = items.length === 0;
      list.innerHTML = items.length
        ? items.map((item) => `<a class="st-note" href="${esc(item.href)}"><strong>${esc(item.title)}</strong><small>${esc(item.copy)}</small></a>`).join("")
        : `<p class="st-drop__title">مفيش إشعارات جديدة.</p>`;
    } catch (err) {
      list.innerHTML = `<p class="st-drop__title">${esc(err.message)}</p>`;
    }
  }
})();
