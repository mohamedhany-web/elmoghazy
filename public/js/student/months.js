const { esc, videosOf, progress, state } = window.StKit;

const labels = { on: "قيد التقدم", done: "مكتمل", wait: "لم يبدأ" };

function card(month, grade) {
  const videos = videosOf(month);
  const value = progress(month);
  const mode = state(month);
  const media = month.has_cover
    ? `<img src="/months/${month.id}/cover" alt="">`
    : `<span>${esc(grade || "الشرح")}</span>`;
  return `<a class="cv-card" href="/learn/months/${month.id}" data-state="${mode}" data-title="${esc(month.title)}">
    <div class="cv-card__media">${media}<em class="cv-badge cv-badge--${mode}">${labels[mode]}</em></div>
    <div class="cv-card__body">
      <h2>${esc(month.title)}</h2>
      <p class="cv-meta">${esc(grade || "السنة")} · محمد المغازي · ${videos.length} فيديو</p>
      <div class="cv-track" aria-hidden="true"><b style="width:${value}%"></b></div>
      <div class="cv-card__row"><span>التقدم</span><span>${value}%</span></div>
      <span class="cv-btn">${value >= 100 ? "راجع الشرح" : value > 0 ? "أكمل التعلم" : "ابدأ التعلم"}</span>
    </div>
  </a>`;
}

function paint(months, grade) {
  const on = document.getElementById("month-on").checked;
  const done = document.getElementById("month-done").checked;
  const query = document.getElementById("month-search").value.trim();
  const rows = months.filter((month) => {
    const mode = state(month);
    if (on && mode !== "on") return false;
    if (done && mode !== "done") return false;
    const blob = `${month.title} ${grade || ""} المغازي`;
    return !query || blob.includes(query);
  });
  const host = document.getElementById("month-grid");
  host.innerHTML = rows.length
    ? rows.map((month) => card(month, grade)).join("")
    : `<p class="cv-empty cv-card">مفيش شهور مطابقة.</p>`;
  document.getElementById("month-count").textContent = `${rows.length} / ${months.length}`;
}

Elmo.onReady(async (user) => {
  if (!user) {
    location.href = "/login";
    return;
  }
  if (user.role === "teacher") {
    location.href = "/admin/months";
    return;
  }
  const grade = user.grade && user.grade.title;
  let months = [];
  try {
    months = await Elmo.api("/api/student/months");
  } catch (err) {
    document.getElementById("month-grid").innerHTML = `<p class="cv-empty cv-card">${esc(err.message)}</p>`;
    return;
  }
  if (!months.length) {
    const board = await Elmo.api("/api/student/board").catch(() => null);
    if (board && board.device_locked) {
      document.getElementById("month-grid").innerHTML = `<p class="cv-empty cv-card">اشتراكك متفعّل على جهاز أو متصفح تاني. افتح من نفس الجهاز اللي فعّلت منه، أو كلّم المدرس يصفّر الجهاز. متدخلش الكود تاني.</p>`;
      return;
    }
  }
  const active = months.filter((month) => state(month) === "on").length;
  const complete = months.filter((month) => state(month) === "done").length;
  const videos = months.reduce((sum, month) => sum + videosOf(month).length, 0);
  const average = months.length
    ? Math.round(months.reduce((sum, month) => sum + progress(month), 0) / months.length)
    : 0;
  const icons = {
    lilac: `<svg viewBox="0 0 24 24"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-3-7 3V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>`,
    mint: `<svg viewBox="0 0 24 24"><path d="M6 12.5 10 16l8-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
    sky: `<svg viewBox="0 0 24 24"><path d="M9 8.5v7l6-3.5z" fill="currentColor"/></svg>`,
    amber: `<svg viewBox="0 0 24 24"><path d="M5 16.5 9 8l3 5 2-3 5 6.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>`,
  };
  document.getElementById("month-stats").innerHTML = [
    ["نشطة", active, "lilac"],
    ["مكتملة", complete, "mint"],
    ["فيديوهات", videos, "sky"],
    ["متوسط التقدم", `${average}%`, "amber"],
  ]
    .map(
      ([label, value, tone]) => `<article class="cv-stat"><div><span>${label}</span><strong>${value}</strong></div><span class="cv-bubble cv-bubble--${tone}" aria-hidden="true">${icons[tone]}</span></article>`,
    )
    .join("");
  const draw = () => paint(months, grade);
  ["month-search", "month-on", "month-done"].forEach((id) => {
    document.getElementById(id).addEventListener("input", draw);
  });
  draw();
});
