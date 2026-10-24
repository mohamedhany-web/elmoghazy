function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[char]));
}

const words = {
  available: ["متاح", "wait", "ابدأ"],
  in_progress: ["قيد الحل", "on", "كمّل"],
  completed: ["مكتمل", "done", "النتيجة"],
  exhausted: ["خلّصت المحاولات", "done", "النتيجة"],
  locked: ["مقفول", "wait", "التفاصيل"],
};

function paint(rows) {
  const query = document.getElementById("exam-search").value.trim();
  const shown = rows.filter((row) => !query || String(row.title).includes(query) || String(row.month || "").includes(query));
  const host = document.getElementById("exam-list");
  host.innerHTML = shown.length
    ? shown
        .map((row) => {
          const [label, mode, action] = words[row.status] || ["", "wait", "التفاصيل"];
          return `<a class="cv-card" href="/learn/exams/${row.id}">
            <div class="cv-card__media cv-bubble--${mode === "done" ? "mint" : mode === "on" ? "lilac" : "peach"}">
              <span>${esc(row.month || "امتحان")}</span>
              <em class="cv-badge cv-badge--${mode === "done" ? "done" : mode === "on" ? "on" : "wait"}">${label}</em>
            </div>
            <div class="cv-card__body">
              <h2>${esc(row.title)}</h2>
              <p class="cv-meta">${row.question_count} سؤال${row.duration_minutes ? ` · ${row.duration_minutes} دقيقة` : ""}${row.month ? ` · ${esc(row.month)}` : ""}</p>
              ${row.best_score !== null && row.best_score !== undefined ? `<div class="cv-card__row"><span>أعلى درجة</span><span>${row.best_score}</span></div>` : ""}
              ${row.locked ? `<p class="cv-note">${esc(row.message || "")}</p>` : ""}
              <span class="cv-btn">${action}</span>
            </div>
          </a>`;
        })
        .join("")
    : `<p class="cv-empty cv-card">لسه مفيش امتحانات مطابقة.</p>`;
  document.getElementById("exam-count").textContent = `${shown.length} / ${rows.length}`;
}

Elmo.onReady(async (user) => {
  const note = document.getElementById("exam-note");
  if (!user) {
    window.location.href = "/login";
    return;
  }
  if (user.role === "teacher") {
    window.location.href = "/admin/exams";
    return;
  }
  try {
    const rows = await Elmo.api("/api/student/exams");
    const count = (name) => rows.filter((row) => row.status === name).length;
    const icons = {
      peach: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>`,
      lilac: `<svg viewBox="0 0 24 24"><path d="M8 4h8a1 1 0 0 1 1 1v15l-5-2.5L7 20V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>`,
      mint: `<svg viewBox="0 0 24 24"><path d="M6 12.5 10 16l8-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
      amber: `<svg viewBox="0 0 24 24"><rect x="6" y="10" width="12" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9 10V8a3 3 0 0 1 6 0v2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>`,
    };
    document.getElementById("exam-stats").innerHTML = [
      ["متاح", count("available"), "peach"],
      ["قيد الحل", count("in_progress"), "lilac"],
      ["مكتمل", count("completed") + count("exhausted"), "mint"],
      ["مقفول", count("locked"), "amber"],
    ]
      .map(
        ([label, value, tone]) => `<article class="cv-stat"><div><span>${label}</span><strong>${value}</strong></div><span class="cv-bubble cv-bubble--${tone}">${icons[tone]}</span></article>`,
      )
      .join("");
    document.getElementById("exam-search").addEventListener("input", () => paint(rows));
    paint(rows);
  } catch (err) {
    note.hidden = false;
    note.textContent = err.message;
  }
});
