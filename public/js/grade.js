const page = document.querySelector("[data-slug]");
const root = document.getElementById("grade-root");

function card(month) {
  const thumb = month.has_cover
    ? `<img src="/months/${month.id}/cover?w=720" alt="" loading="lazy" decoding="async">`
    : `<span class="icon-badge">${Elmo.ICONS.book}</span>`;
  return `<a class="course-card surface" href="/months/${month.id}">
    <div class="course-card__top">${thumb}</div>
    <h2 class="display">${month.title}</h2>
    <div class="course-card__foot"><span>${month.unit_count} وحدات · ${month.video_count} فيديو</span>${Number(month.sales_count) > 0 ? `<strong>${month.sales_count} طلب</strong>` : ""}</div>
  </a>`;
}

Elmo.onReady(async (user) => {
  try {
    const grade = await Elmo.api(`/api/grades/${page.dataset.slug}`);
    const months = await Elmo.api(`/api/months?grade=${encodeURIComponent(grade.slug)}`);
    root.innerHTML = `
      <a class="back-link" href="/#grades">${Elmo.ICONS.back} رجوع للصفوف</a>
      <div class="grade-hero ${Elmo.tone(grade.accent)}">
          <p class="grade-hero__kicker">شهور السنة</p>
        <h1 class="display">${grade.title}</h1>
        <p class="grade-hero__sub">${grade.subtitle || ""}</p>
        <div class="grade-hero__meta"><span>${months.length} شهور</span></div>
      </div>
      ${months.length ? `<div class="course-grid">${months.map(card).join("")}</div>` : '<p class="grade-note">لسه مفيش شهور منشورة في الصف ده.</p>'}
      <div class="grade-actions stack">
        ${
          user
            ? `<p class="muted">داخل بحساب ${user.name}${user.grade ? ` — ${user.grade.title}` : ""}</p>`
            : '<a class="btn btn-primary" href="/signup">سجّل وافتح المحتوى</a><a class="btn btn-ghost" href="/login">عندي حساب — دخول</a>'
        }
      </div>`;
  } catch {
    root.innerHTML = '<p class="grade-note">الصف ده مش موجود.</p>';
  }
});
