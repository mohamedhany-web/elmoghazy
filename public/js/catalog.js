function esc(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function card(month) {
  const thumb = month.has_cover
    ? `<img src="/months/${month.id}/cover?w=720" alt="" loading="lazy" decoding="async">`
    : "";
  return `<article class="cat-card surface">
    <a href="/months/${month.id}">
      <div class="cat-card__thumb${month.has_cover ? "" : " is-plain"}">${thumb}<span class="tag">${esc(month.grade || "شهر")}</span></div>
      <div class="cat-card__body">
        <h2 class="display">${esc(month.title)}</h2>
        <p>${month.unit_count === 1 ? "وحدة واحدة" : `${month.unit_count} وحدات`} · ${month.video_count === 1 ? "فيديو واحد" : `${month.video_count} فيديو`}${Number(month.sales_count) > 0 ? ` · ${month.sales_count} طلب` : ""}</p>
        <span class="cat-card__more">عرض التفاصيل</span>
      </div>
    </a>
  </article>`;
}

const catalog = document.getElementById("catalog-root");
const catalogGrid = document.getElementById("catalog-grid");
if (catalog && catalogGrid) {
  const chips = document.getElementById("catalog-chips");
  const stages = document.getElementById("catalog-stages");
  const count = document.getElementById("catalog-count");
  const sort = document.getElementById("catalog-sort");
  const search = document.getElementById("catalog-search");
  let months = [];
  let grades = [];
  let picked = new Set();
  let query = "";

  function paintCatalog() {
    const q = query.trim();
    let rows = months.filter((month) => {
      const stageOk = picked.size === 0 || picked.has(month.grade_slug);
      const text = `${month.title} ${month.grade || ""}`;
      return stageOk && (!q || text.includes(q));
    });
    rows = rows.slice().sort((a, b) => {
      if (sort.value === "new") return b.id - a.id;
      return b.sales_count - a.sales_count || a.id - b.id;
    });
    count.textContent = `${rows.length} شهر`;
    catalogGrid.innerHTML = rows.length
      ? rows.map(card).join("")
      : '<p class="grade-note">مفيش شهور مطابقة للبحث.</p>';
  }

  function paintStages() {
    chips.innerHTML = `<button type="button" class="catalog__chip${picked.size ? "" : " is-on"}" data-stage="">الكل</button>${grades
      .map(
        (grade) =>
          `<button type="button" class="catalog__chip${picked.size === 1 && picked.has(grade.slug) ? " is-on" : ""}" data-stage="${esc(grade.slug)}">${esc(grade.title)}</button>`,
      )
      .join("")}`;
    stages.innerHTML = `<label class="catalog__check"><input type="checkbox" data-stage="" ${picked.size ? "" : "checked"}> الكل</label>${grades
      .map(
        (grade) =>
          `<label class="catalog__check"><input type="checkbox" data-stage="${esc(grade.slug)}" ${picked.has(grade.slug) ? "checked" : ""}> ${esc(grade.title)}</label>`,
      )
      .join("")}`;
  }

  function choose(slug, only) {
    if (!slug) {
      picked = new Set();
    } else if (only) {
      picked = new Set([slug]);
    } else if (picked.has(slug)) {
      picked.delete(slug);
    } else {
      picked.add(slug);
    }
    paintStages();
    paintCatalog();
  }

  chips.addEventListener("click", (event) => {
    const button = event.target.closest("[data-stage]");
    if (!button) return;
    choose(button.dataset.stage, true);
  });
  stages.addEventListener("change", (event) => {
    const input = event.target.closest("[data-stage]");
    if (!input) return;
    if (!input.dataset.stage) {
      choose("", true);
      return;
    }
    if (picked.size === 0 && input.checked) picked = new Set();
    choose(input.dataset.stage, false);
  });
  sort.addEventListener("change", paintCatalog);
  search.addEventListener("submit", (event) => {
    event.preventDefault();
    query = new FormData(search).get("q") || "";
    paintCatalog();
  });
  search.querySelector("input").addEventListener("input", (event) => {
    query = event.target.value;
    paintCatalog();
  });

  Promise.all([Elmo.api("/api/months"), Elmo.api("/api/grades")])
    .then(([monthRows, gradeRows]) => {
      months = monthRows;
      grades = gradeRows;
      paintStages();
      paintCatalog();
    })
    .catch(() => {
      catalogGrid.innerHTML = '<p class="grade-note">مش قادرين نحمّل الشهور دلوقتي.</p>';
    });
}

const monthRoot = document.getElementById("month-root");
const monthPage = document.querySelector("[data-month]");
if (monthRoot && monthPage) {
  const id = monthPage.dataset.month;
  monthRoot.addEventListener("click", (event) => {
    const toggle = event.target.closest("[data-fold]");
    if (!toggle) return;
    const fold = toggle.closest(".fold");
    fold.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", fold.classList.contains("is-open") ? "true" : "false");
  });

  function paint(month, user) {
    const units = month.units.length
      ? month.units
          .map(
            (unit, index) => `<article class="month-unit surface fold ${index === 0 ? "is-open" : ""}">
              <button type="button" class="fold__toggle" data-fold aria-expanded="${index === 0 ? "true" : "false"}">
                <h3 class="display">${esc(unit.title)}</h3>
                <span>${unit.videos.length} فيديو</span>
              </button>
              <div class="fold__body">
                ${unit.description ? `<p>${esc(unit.description)}</p>` : ""}
                ${
                  unit.videos.length
                    ? `<ul>${unit.videos.map((video) => `<li>${esc(video.title)}</li>`).join("")}</ul>`
                    : "<p class=\"muted\">لسه مفيش فيديوهات ظاهرة في القسم ده.</p>"
                }
              </div>
            </article>`,
          )
          .join("")
      : '<p class="grade-note">لسه مفيش وحدات ظاهرة في الشهر ده.</p>';

    const codeButton = user
      ? `<button type="button" class="btn btn-primary" id="open-code">تفعيل بكود</button>`
      : `<a class="btn btn-ghost" href="/login">دخول للتفعيل</a>`;

    const cover = month.has_cover ? `<img class="month-banner__img" src="/months/${month.id}/cover?w=1400" alt="" decoding="async" fetchpriority="high">` : "";
    const unitsLabel = month.unit_count === 1 ? "وحدة واحدة" : `${month.unit_count} وحدات`;
    const videosLabel = month.video_count === 1 ? "فيديو واحد" : `${month.video_count} فيديو`;

    monthRoot.innerHTML = `
      <div class="month-top">
        <a class="back-link" href="/months">${Elmo.ICONS.back} رجوع للشهور</a>
      </div>
      <section class="month-banner${cover ? "" : ` is-plain ${Elmo.tone(month.accent)}`}">
        ${cover}
        <div class="month-banner__copy">
          <p class="month-banner__kicker">${esc(month.grade || "شهر")}</p>
          <h1 class="display">${esc(month.title)}</h1>
          ${month.description ? `<p class="month-banner__sub">${esc(month.description)}</p>` : ""}
          <div class="month-banner__meta"><span>${unitsLabel}</span><span>${videosLabel}</span>${Number(month.sales_count) > 0 ? `<span>${month.sales_count} طلب</span>` : ""}</div>
        </div>
      </section>
      <div class="month-layout">
        <section class="month-main">
          <h2 class="display">محتوى الشهر</h2>
          ${units}
        </section>
        <aside class="month-side">
          <article class="surface month-card">
            <h2 class="display">الاشتراك</h2>
            <p class="muted">الكود من المدرس بعد التأكيد، أو ابعت طلب شراء يظهر في لوحة المدرس. مفيش دفع جوّه المنصة.</p>
            <div class="month-actions">
              ${codeButton}
              <button type="button" class="btn btn-ghost" id="open-buy">طلب شراء</button>
            </div>
          </article>
        </aside>
      </div>`;

    document.getElementById("open-code")?.addEventListener("click", () => {
      Elmo.openPop({
        title: "تفعيل بكود",
        copy: "الكود من المدرس بعد ما يتأكد من الدفع بره المنصة.",
        html: `<form class="stack">
          <label class="field"><span>كود التفعيل</span><input name="code" required maxlength="32" autocomplete="off"></label>
          <button class="btn btn-primary" type="submit">تفعيل</button>
        </form>`,
        onSubmit: async (data) => {
          await Elmo.api("/api/student/codes", { method: "POST", body: JSON.stringify({ code: data.get("code") }) });
          window.location.href = "/learn";
        },
      });
    });

    document.getElementById("open-buy").addEventListener("click", () => {
      Elmo.openPop({
        title: "طلب شراء",
        copy: "الطلب يظهر في لوحة المدرس. مفيش دفع جوّه المنصة.",
        html: `<form class="stack">
          <label class="field"><span>الاسم</span><input name="name" required value="${esc(user?.name || "")}"></label>
          <label class="field"><span>الموبايل</span><input name="phone" required inputmode="tel" value="${esc(user?.phone || "")}"></label>
          <label class="field"><span>ملاحظة</span><input name="note" maxlength="500"></label>
          <button class="btn btn-primary" type="submit">ابعت الطلب</button>
        </form>`,
        onSubmit: async (data, form, note) => {
          await Elmo.api(`/api/months/${id}/requests`, {
            method: "POST",
            body: JSON.stringify({
              name: data.get("name"),
              phone: data.get("phone"),
              note: data.get("note"),
            }),
          });
          form.reset();
          note.classList.add("is-ok");
          note.hidden = false;
          note.textContent = "الطلب وصل للمدرس. هيراجعك بعد التأكيد.";
        },
      });
    });
  }

  Elmo.onReady(async (user) => {
    try {
      const month = await Elmo.api(`/api/months/${id}`);
      paint(month, user);
    } catch {
      monthRoot.innerHTML = '<p class="grade-note">الشهر ده مش موجود.</p>';
    }
  });
}
