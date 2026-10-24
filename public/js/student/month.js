const { esc, videosOf, progress, state, done, clip } = window.StKit;
const root = document.getElementById("month-gate");
const monthId = root.dataset.month;
const tones = ["peach", "sky", "mint", "amber", "lilac"];

function firstOpen(month) {
  for (const unit of month.units) {
    if ((unit.videos || []).some((video) => !done(video.id))) return unit;
  }
  return month.units[0] || null;
}

function learnUrl(type, id) {
  return `/learn/months/${monthId}/learn?type=${type}&id=${id}`;
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
  let months = [];
  let exams = [];
  try {
    [months, exams] = await Promise.all([Elmo.api("/api/student/months"), Elmo.api("/api/student/exams")]);
  } catch (err) {
    root.innerHTML = `<p class="cv-empty cv-card">${esc(err.message)}</p>`;
    return;
  }
  const month = months.find((row) => String(row.id) === String(monthId));
  if (!month) {
    root.innerHTML = `<p class="cv-empty cv-card">غير موجود.</p>`;
    return;
  }
  const grade = (user.grade && user.grade.title) || "";
  const videos = videosOf(month);
  const value = progress(month);
  const mode = state(month);
  const label = mode === "done" ? "مكتمل" : mode === "on" ? "قيد التقدم" : "لم يبدأ";
  const owned = exams.filter((exam) => String(exam.month_id) === String(month.id));
  const nextVideo = videos.find((video) => !video.locked && !done(video.id)) || videos.find((video) => !video.locked);
  const verb = value >= 100 ? "راجع الشرح" : value > 0 ? "أكمل من حيث توقفت" : "ابدأ التعلم";
  const href = nextVideo ? learnUrl("video", nextVideo.id) : learnUrl("video", "");
  const openUnit = firstOpen(month);
  const finished = videos.filter((video) => done(video.id)).length;

  root.innerHTML = `
    <div class="cv-page">
      <a class="cv-back" href="/learn/months">رجوع لشهور الشرح</a>
      <em class="cv-badge cv-badge--${mode}" style="position:static">${label}</em>
    </div>
    <section class="cv-hero">
      <div class="cv-hero__copy">
        <p class="cv-kicker">شهرك</p>
        <h1>${esc(month.title)}</h1>
        <p class="cv-meta">${esc(grade)} · محمد المغازي</p>
        <p class="cv-clamp">${esc(month.description || "")}</p>
        <div class="cv-hero__acts">
          ${nextVideo ? `<a class="cv-btn" href="${href}">${verb}</a>` : ""}
        </div>
      </div>
      <div class="cv-hero__meter">
        <div class="cv-ring" style="--p:${value}"><b>${value}%</b></div>
        <div class="cv-glass">
          <span><small>مكتمل</small><strong>${finished}/${videos.length}</strong></span>
          <span><small>متبقي</small><strong>${Math.max(videos.length - finished, 0)}</strong></span>
        </div>
      </div>
    </section>
    <div class="cv-split">
      <div>
        <section class="cv-block">
          <h2>المنهج</h2>
          <p class="cv-note">قسم واحد مفتوح. الصف يفتح الشرح.</p>
        </section>
        <div id="month-units" style="display:grid;gap:12px;margin-top:12px"></div>
        ${month.description ? `<section class="cv-block" style="margin-top:12px"><h2>نظرة عامة</h2><p class="cv-pre">${esc(month.description)}</p></section>` : ""}
      </div>
      <aside class="cv-side">
        <article>
          <h2>الخطوة التالية</h2>
          <p class="cv-note">${nextVideo ? esc(nextVideo.title) : "لسه مفيش فيديو مفتوح."}</p>
          ${nextVideo ? `<a class="cv-btn" href="${href}">${verb}</a>` : ""}
        </article>
        <article class="cv-teacher">
          <div style="background:transparent;padding:0"><i class="cv-bubble cv-bubble--peach">م</i><strong>محمد المغازي</strong></div>
          <div><span>المستوى</span><strong>${esc(grade || "—")}</strong></div>
          <div><span>الفيديوهات</span><strong>${videos.length}</strong></div>
          <div><span>الامتحانات</span><strong>${owned.length}</strong></div>
        </article>
      </aside>
    </div>`;

  const units = document.getElementById("month-units");
  if (!month.units.length && !owned.length) {
    units.innerHTML = `<section class="cv-empty cv-card"><i class="cv-bubble cv-bubble--mint">ش</i><p>لسه مفيش منهج في الشهر.</p><a class="cv-btn" href="${href}">فتح التعلم</a></section>`;
    return;
  }
  units.innerHTML = month.units
    .map((unit, index) => {
      const unitDone = (unit.videos || []).filter((video) => done(video.id)).length;
      const total = (unit.videos || []).length;
      const ratio = total ? Math.round((unitDone / total) * 100) : 0;
      const rows = (unit.videos || [])
        .map((video, videoIndex) => {
          const ready = done(video.id);
          return `<a class="cv-row" href="${learnUrl("video", video.id)}">
            <i class="cv-bubble cv-bubble--${tones[(index + videoIndex) % tones.length]}">ف</i>
            <span class="cv-sec__text"><strong>${esc(video.title)}</strong><small>${video.locked ? "مقفول" : "شرح"}</small></span>
            <em class="cv-pill ${ready ? "is-done" : ""}">${ready ? "مكتمل" : video.locked ? "مقفول" : "جاهز للبدء"}</em>
          </a>`;
        })
        .join("");
      return `<section class="cv-sec ${openUnit && openUnit.id === unit.id ? "is-open" : ""}" data-unit="${unit.id}">
        <button type="button" class="cv-sec__head" data-unit-toggle>
          <i class="cv-bubble cv-bubble--${tones[index % tones.length]}">ق</i>
          <span class="cv-sec__text"><strong>${esc(unit.title)}</strong><small>أُنجز ${unitDone} من ${total}</small></span>
          <em class="cv-pill ${ratio === 100 ? "is-done" : ""}">${ratio}%</em>
          <span class="cv-chev" aria-hidden="true">⌃</span>
        </button>
        <div class="cv-sec__body">
          ${unit.description ? `<p class="cv-note">${esc(clip(unit.description, 140))}</p>` : ""}
          ${rows || `<p class="cv-note">لسه مفيش فيديوهات.</p>`}
        </div>
      </section>`;
    })
    .join("");

  if (owned.length) {
    units.insertAdjacentHTML(
      "beforeend",
      `<section class="cv-sec"><button type="button" class="cv-sec__head" data-unit-toggle><i class="cv-bubble cv-bubble--amber">ا</i><span class="cv-sec__text"><strong>الامتحانات</strong><small>${owned.length}</small></span><span class="cv-chev">⌃</span></button><div class="cv-sec__body">${owned
        .map(
          (exam) => `<a class="cv-row" href="${learnUrl("exam", exam.id)}"><i class="cv-bubble cv-bubble--amber">ا</i><span class="cv-sec__text"><strong>${esc(exam.title)}</strong><small>امتحان</small></span><em class="cv-pill ${exam.status === "completed" ? "is-done" : ""}">${exam.locked ? "مقفول" : exam.status === "completed" ? "مكتمل" : "جاهز للبدء"}</em></a>`,
        )
        .join("")}</div></section>`,
    );
  }

  units.addEventListener("click", (event) => {
    const button = event.target.closest("[data-unit-toggle]");
    if (!button) return;
    const section = button.closest(".cv-sec");
    const open = section.classList.contains("is-open");
    units.querySelectorAll(".cv-sec").forEach((node) => node.classList.remove("is-open"));
    section.classList.toggle("is-open", !open);
  });
});
