const lines = ["الفيزياء مش صعبة…", "لو فهمتها صح.", "يلا نبدأ مع المغازي"];
const intro = document.getElementById("intro");
const site = document.getElementById("site");
const line = document.getElementById("intro-line");
let lineIndex = 0;

let entered = false;

function enterSite() {
  if (entered || !intro) return;
  entered = true;
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  site.hidden = false;
  if (reduce) {
    intro.remove();
    return;
  }
  site.classList.add("is-entering");
  if (!intro.style.transform) intro.style.transform = "translateY(0)";
  intro.style.transition = "none";
  window.requestAnimationFrame(() => {
    intro.style.transition = "transform 0.78s cubic-bezier(0.7, 0, 0.18, 1)";
    intro.style.transform = "translateY(-108%)";
    window.setTimeout(() => intro.remove(), 800);
  });
}

function armIntro() {
  document.getElementById("intro-name").hidden = false;
  const enter = document.getElementById("intro-enter");
  enter.hidden = false;
  intro.classList.add("is-armed");
  document.getElementById("intro-go").addEventListener("click", enterSite);

  let startY = 0;
  let startT = 0;
  let lift = 0;
  let dragging = false;

  intro.addEventListener("pointerdown", (event) => {
    if (entered || event.target.closest("#intro-go")) return;
    dragging = true;
    startY = event.clientY;
    startT = event.timeStamp;
    lift = 0;
    intro.classList.add("is-dragging");
    try {
      intro.setPointerCapture(event.pointerId);
    } catch {
      /* synthetic events have no capture */
    }
  });

  intro.addEventListener("pointermove", (event) => {
    if (!dragging) return;
    lift = Math.max(0, Math.min(240, startY - event.clientY));
    intro.style.setProperty("--lift", String(lift));
    if (lift > 24) site.hidden = false;
  });

  function release(event) {
    if (!dragging) return;
    dragging = false;
    intro.classList.remove("is-dragging");
    const dt = Math.max(event.timeStamp - startT, 1);
    const flung = lift > 46 && lift / dt > 0.55;
    if (lift > 110 || flung) {
      intro.style.transform = `translateY(${-lift}px)`;
      enterSite();
      return;
    }
    intro.style.setProperty("--lift", "0");
    if (!entered) site.hidden = true;
  }

  intro.addEventListener("pointerup", release);
  intro.addEventListener("pointercancel", release);
}

function nextLine() {
  if (lineIndex >= lines.length - 1) {
    window.setTimeout(armIntro, 600);
    return;
  }
  window.setTimeout(() => {
    lineIndex += 1;
    line.textContent = lines[lineIndex];
    nextLine();
  }, 1200);
}

nextLine();

const grid = document.getElementById("grade-grid");
const monthsHost = document.getElementById("month-grid");
const title = document.getElementById("months-title");
const copy = document.getElementById("months-copy");
const banner = document.getElementById("continue");
let grades = [];
let months = [];
let monthsLoaded = false;
let selected = null;

const GRADE_ICONS = { prep: "book", "1st": "trend", "2nd": "bolt", "3rd": "flag" };

function gradeIcon(slug) {
  return Elmo.ICONS[GRADE_ICONS[slug] || "cap"];
}

function monthCard(month) {
  const sales = Number(month.sales_count) > 0 ? `<p class="offer__status">${month.sales_count} طلب</p>` : "";
  const thumb = month.has_cover
    ? `<img src="/months/${month.id}/cover?w=720" alt="" loading="lazy" decoding="async">`
    : `<span class="icon-badge">${gradeIcon(month.grade_slug)}</span>`;
  return `<article class="offer surface ${Elmo.tone(month.accent)}">
        <span class="card-light" aria-hidden="true"></span>
        <div class="offer__cover">${thumb}</div>
        <div class="offer__body">
          ${sales}
          <h3 class="offer__title display">${month.title}</h3>
          <p class="offer__grade">${month.grade || ""} · محمد المغازي</p>
          <div class="offer__meta"><span>${Elmo.ICONS.book} ${month.unit_count} وحدات</span><span>${Elmo.ICONS.play} ${month.video_count} فيديو</span></div>
          <div class="offer__actions"><a class="btn btn-primary btn-sm" href="/months/${month.id}">تفاصيل الشهر</a></div>
        </div>
      </article>`;
}

function renderGrades() {
  if (!grades.length) {
    grid.innerHTML = '<p class="grade-note">لسه مفيش سنوات دراسية. بتظهر هنا لما المدرس يضيفها من اللوحة.</p>';
    return;
  }
  grid.innerHTML = grades
    .map((grade) => {
      const active = selected === grade.slug;
      return `<button type="button" class="grade-card ${Elmo.tone(grade.accent)}${active ? " is-selected" : ""}" data-slug="${grade.slug}" aria-pressed="${active}">
        <span class="card-light" aria-hidden="true"></span>
        <div class="grade-card__top"><span class="icon-badge">${gradeIcon(grade.slug)}</span><span class="grade-card__count">${months.filter((month) => month.grade_slug === grade.slug).length} شهور</span></div>
        <h3 class="display">${grade.title}</h3>
        <p class="grade-card__sub">${grade.subtitle}</p>
        <div class="grade-card__foot"><span class="grade-card__go">${active ? "الشهور ظاهرة" : "اعرض شهور السنة"}</span></div>
      </button>`;
    })
    .join("");
  grid.querySelectorAll("[data-slug]").forEach((button) => {
    button.addEventListener("click", () => choose(button.dataset.slug));
  });
}

function renderStudent() {
  const quick = document.getElementById("quick");
  const user = Elmo.user;
  const grade = grades.find((item) => user && user.grade && item.slug === user.grade.slug);
  const pick = grade || grades[0];
  if (!quick) return;
  if (!user || !pick) {
    quick.hidden = true;
    return;
  }
  quick.hidden = false;
  const name = user.name.split(" ")[0];
  quick.innerHTML = `<p>أهلاً يا ${name}. جاهز نكمّل في ${pick.title}؟</p>
        <div class="stack">
          <a class="btn btn-primary btn-sm" href="/learn">كمّل التعلم</a>
          <a class="btn btn-ghost btn-sm" href="/grades/${pick.slug}">شهور السنة</a>
        </div>`;
}

function renderMonths() {
  const grade = grades.find((item) => item.slug === selected);
  const user = Elmo.user;
  const own = Boolean(user && grade && user.grade && user.grade.slug === grade.slug);
  const visible = grade ? months.filter((month) => month.grade_slug === grade.slug) : months.slice(0, 6);
  if (!monthsLoaded) {
    monthsHost.className = "";
    monthsHost.innerHTML = '<p class="grade-note">جاري تحميل الشهور...</p>';
    return;
  }
  banner.hidden = !own;
  banner.innerHTML = own
    ? `<div><p class="continue__kicker">Continue Learning</p><p class="continue__title display">كمّل من حيث وقفت في ${grade.title}</p></div><a class="btn btn-primary btn-sm" href="/learn">كمّل التعلم</a>`
    : "";
  if (!grade) {
    title.textContent = "الأكثر تسجيلاً";
    copy.textContent = "ترتيب الشهور من طلبات التسجيل الفعلية.";
  } else {
    title.textContent = `شهور ${grade.title}`;
    copy.textContent = "المحتوى الظاهر هنا خاص بالصف اللي اخترته.";
  }
  if (!visible.length) {
    monthsHost.className = "";
    monthsHost.innerHTML = '<p class="grade-note">لسه مفيش شهور منشورة هنا.</p>';
    return;
  }
  monthsHost.className = "offer-grid";
  monthsHost.innerHTML = visible.map(monthCard).join("");
}

function choose(slug) {
  selected = slug;
  renderGrades();
  renderMonths();
  const card = grid.querySelector(`[data-slug="${CSS.escape(slug)}"]`);
  if (card) card.scrollIntoView({ behavior: "smooth", block: "start" });
}

Elmo.onReady((eventUser) => {
  if (eventUser && eventUser.grade) {
    selected = eventUser.grade.slug;
    const start = document.querySelector("[data-start]");
    if (start) {
      start.textContent = "كمّل التعلم";
      start.href = "#months";
    }
  }
  renderGrades();
  renderMonths();
  renderStudent();
});

Elmo.api("/api/grades")
  .then((rows) => {
    grades = rows;
    renderGrades();
    renderMonths();
    renderStudent();
  })
  .catch(() => {
    grid.innerHTML = '<p class="grade-note">مش قادرين نحمّل الصفوف دلوقتي.</p>';
  });

Elmo.api("/api/months")
  .then((rows) => {
    months = rows;
    monthsLoaded = true;
    renderGrades();
    renderMonths();
  })
  .catch(() => {
    monthsLoaded = true;
    monthsHost.innerHTML = '<p class="grade-note">مش قادرين نحمّل الشهور دلوقتي.</p>';
  });
