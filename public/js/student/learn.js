const note = document.getElementById("learn-note");
const lessonView = document.getElementById("lesson-view");

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
  return new Date(value).toLocaleDateString("ar-EG", { day: "numeric", month: "short" });
}

function say(message) {
  note.hidden = !message;
  note.textContent = message || "";
}

function showLearn(subscribed) {
  document.getElementById("subscribe-gate").hidden = subscribed;
  document.getElementById("learn-body").hidden = !subscribed;
}

function paintHero(board) {
  const name = board.student.name || "طالب";
  document.getElementById("stu-hello").textContent = `أهلاً، ${name}`;
  document.getElementById("stu-grade").textContent = board.student.grade || "السنة الدراسية";
  const role = document.getElementById("stRole");
  if (role && board.student.grade) role.textContent = board.student.grade;
  const bar = document.getElementById("stu-progress-bar");
  const label = document.getElementById("stu-progress-label");
  if (!label) return;
  document.getElementById("stu-stat-months").textContent = String(board.activations.length);
  const nearest = board.activations
    .map((row) => row.ends_at)
    .filter(Boolean)
    .sort()[0];
  document.getElementById("stu-stat-until").textContent = nearest ? when(nearest) : "—";
  if (!board.subscribed) {
    if (bar) bar.style.width = "0%";
    label.textContent = "مفيش اشتراك شغّال. ادخل كود الاشتراك.";
    return;
  }
  if (bar) bar.style.width = "100%";
  label.textContent = nearest ? `التفعيل ساري حتى ${when(nearest)}` : "التفعيل ساري";
  document.getElementById("activation-strip").innerHTML = board.activations
    .map((row) => `<span class="stu-chip">${esc(row.course)} · حتى ${when(row.ends_at)}</span>`)
    .join("");
}

async function loadBoard() {
  const board = await Elmo.api("/api/student/board");
  paintHero(board);
  if (board.device_locked) {
    say("اشتراكك متفعّل على جهاز أو متصفح تاني. افتح من نفس الجهاز اللي فعّلت منه، أو كلّم المدرس يصفّر الجهاز. متدخلش الكود تاني.");
  }
  if (!board.subscribed) {
    showLearn(false);
    document.getElementById("stu-stat-exams").textContent = "0";
    document.getElementById("stu-stat-videos").textContent = "0";
    return;
  }
  showLearn(true);
  await loadMonths();
}

async function loadMonths() {
  const months = await Elmo.api("/api/student/months");
  const host = document.getElementById("learn-months");
  host.innerHTML = months.length
    ? months
        .map((month) => {
          const videos = month.units.reduce((sum, unit) => sum + unit.videos.length, 0);
          return `<a class="stu-exam" href="/learn/months/${month.id}">
            <strong>${esc(month.title)}</strong>
            <span>${month.units.length} أقسام · ${videos} فيديو</span>
            <em>افتح الشهر</em>
          </a>`;
        })
        .join("")
    : `<p class="desk-copy">لسه مفيش شهور ظاهرة في صفك.</p>`;
  const videos = months.reduce((sum, month) => sum + month.units.reduce((inner, unit) => inner + unit.videos.length, 0), 0);
  document.getElementById("stu-stat-videos").textContent = String(videos);
  await loadExams();
}

async function loadExams() {
  const host = document.getElementById("learn-exams");
  if (!host) return;
  let rows = [];
  try {
    rows = await Elmo.api("/api/student/exams");
  } catch (err) {
    host.innerHTML = `<p class="desk-copy">${esc(err.message)}</p>`;
    return;
  }
  document.getElementById("stu-stat-exams").textContent = String(rows.length);
  const preview = rows.slice(0, 3);
  host.innerHTML = preview.length
    ? preview
        .map(
          (row) => `<a class="stu-exam" href="/learn/exams/${row.id}">
            <strong>${esc(row.title)}</strong>
            <span>${row.question_count} سؤال${row.permanent ? " · متاح دائمًا" : ""}${row.duration_minutes ? ` · ${row.duration_minutes} دقيقة` : ""}</span>
            <em>${row.locked ? "التفاصيل" : "ابدأ"}</em>
          </a>`,
        )
        .join("")
    : `<p class="desk-copy">لسه مفيش امتحانات منشورة لسنتك.</p>`;
}

document.querySelector(".cv")?.addEventListener("click", (event) => {
  const toggle = event.target.closest("[data-fold]");
  if (!toggle) return;
  const fold = toggle.closest(".stu-block");
  fold.classList.toggle("is-open");
  toggle.setAttribute("aria-expanded", fold.classList.contains("is-open") ? "true" : "false");
});

document.getElementById("learn-months").addEventListener("click", async (event) => {
  const watch = event.target.closest("[data-watch]");
  if (!watch) return;
  const player = document.getElementById("month-player");
  player.hidden = false;
  document.getElementById("month-player-title").textContent = watch.dataset.title || "";
  const files = JSON.parse(watch.dataset.files || "[]");
  document.getElementById("month-player-files").innerHTML = files
    .map((item) => `<button type="button" class="btn btn-ghost btn-sm" data-file="${item.id}" data-video="${watch.dataset.watch}" data-name="${esc(item.name)}">${esc(item.name)}</button>`)
    .join("");
  try {
    await Elmo.play(document.getElementById("student-player"), `/api/student/videos/${watch.dataset.watch}/stream`);
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("month-player").addEventListener("click", async (event) => {
  const file = event.target.closest("[data-file]");
  if (!file) return;
  try {
    const blob = await Elmo.fetchBlob(`/api/student/videos/${file.dataset.video}/files/${file.dataset.file}`);
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = file.dataset.name || "file";
    link.click();
    URL.revokeObjectURL(url);
  } catch (err) {
    say(err.message);
  }
});

function openCode() {
  const gate = document.getElementById("subscribe-gate");
  gate.hidden = false;
  gate.scrollIntoView({ behavior: "smooth", block: "start" });
  gate.querySelector("[name=code]").focus();
}

document.getElementById("open-redeem")?.addEventListener("click", openCode);
document.getElementById("open-redeem-card")?.addEventListener("click", openCode);

document.getElementById("subscribe-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const input = event.currentTarget.querySelector("[name=code]");
  try {
    await Elmo.api("/api/student/codes", { method: "POST", body: JSON.stringify({ code: input.value }) });
    input.value = "";
    say("الشهر اتفعل.");
    lessonView.hidden = true;
    await loadBoard();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("lesson-view").addEventListener("click", async (event) => {
  const done = event.target.closest("[data-done]");
  if (!done) return;
  try {
    await Elmo.api(`/api/student/lessons/${done.dataset.done}/complete`, { method: "POST" });
    say("اتسجل إنك خلّصت الحصة.");
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("learn-body").addEventListener("click", async (event) => {
  const open = event.target.closest("[data-open]");
  const lessonButton = event.target.closest("[data-lesson]");

  if (open) {
    const row = open.closest("[data-id]");
    const box = row.querySelector(".desk-lessons");
    if (!box.hidden) {
      box.hidden = true;
      return;
    }
    const lessons = await Elmo.api(`/api/student/courses/${row.dataset.id}/lessons`);
    box.hidden = false;
    box.innerHTML = lessons.length
      ? lessons
          .map(
            (lesson) => `<button type="button" data-lesson="${lesson.id}">${esc(lesson.title)}${lesson.open ? "" : " · مقفولة"}</button>`,
          )
          .join("")
      : `<p class="desk-copy">مفيش حصص منشورة.</p>`;
  }

  if (lessonButton) {
    try {
      const lesson = await Elmo.api(`/api/student/lessons/${lessonButton.dataset.lesson}`);
      lessonView.hidden = false;
      lessonView.innerHTML = `<h2>${esc(lesson.title)}</h2>${
        lesson.kind === "text" && lesson.body
          ? `<div class="desk-body">${esc(lesson.body).replaceAll("\n", "<br>")}</div>`
          : `<p class="desk-copy">الحصة دي من نوع ${esc(lesson.kind)}. المحتوى النصي بس اللي بيظهر هنا.</p>`
      }<button type="button" class="btn btn-primary" data-done="${lesson.id}">خلّصت الحصة</button>`;
      lessonView.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (err) {
      say(err.message);
    }
  }
});

Elmo.onReady(async (user) => {
  if (!user) {
    location.href = "/login";
    return;
  }
  if (user.role === "teacher") {
    location.href = "/admin";
    return;
  }
  await loadBoard();
});
