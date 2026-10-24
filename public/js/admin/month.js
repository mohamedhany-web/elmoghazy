const { esc, when, say, openModal, closeModal, state, applyFilters } = Admin;

const monthId = document.getElementById("month-builder").dataset.month;
let month = null;
let videos = [];
const openUnits = new Set();
const openVideos = new Set();
let panel = "sections";

function lockSentence(own, inherited) {
  if (own) return `يتقفل ${when(own)} ويفضل ظاهر`;
  if (inherited) return `ياخد قفل ${inherited}`;
  return "مفتوح من غير تاريخ قفل";
}

function lockValue(value) {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (part) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function videoRows(unit) {
  if (!unit.videos.length) {
    return `<p class="outline-empty">لا توجد فيديوهات.</p>`;
  }
  return unit.videos
    .map((video, index) => {
      const inherited = video.locks_at ? "" : unit.locks_at ? "القسم" : month.locks_at ? "الشهر" : "";
      const files = video.files.length
        ? `<div class="outline-files">${video.files
            .map(
              (file) => `<span class="outline-file"><button type="button" data-act="file-get" data-video="${video.id}" data-file="${file.id}" data-name="${esc(file.name)}">${esc(file.name)}</button><button type="button" data-act="file-delete" data-video="${video.id}" data-file="${file.id}" aria-label="حذف الملف">×</button></span>`,
            )
            .join("")}</div>`
        : "";
      const open = openVideos.has(String(video.id));
      return `<article class="outline-video outline-fold ${open ? "is-open" : ""}" data-video-row="${video.id}" data-status="${video.published ? "published" : "hidden"}" data-search="${esc(video.title)} ${esc(video.description || "")}">
        <button type="button" class="outline-toggle" data-act="toggle-video" data-video="${video.id}" aria-expanded="${open ? "true" : "false"}">
          <span class="month-index">${index + 1}</span>
          <span class="month-main"><strong>${esc(video.title)}</strong><small>${video.published ? lockSentence(video.locks_at, inherited) : "مخفي عن الطالب"}</small></span>
          ${state(!!video.published, "ظاهر", "مخفي")}
        </button>
        <div class="outline-body">
          ${video.description ? `<p>${esc(video.description)}</p>` : ""}
          ${files}
          <div class="month-tools">
            <button type="button" class="cs-btn cs-btn--ghost" data-act="video-play" data-video="${video.id}">تشغيل</button>
            <button type="button" class="cs-btn cs-btn--ghost" data-act="video-edit" data-video="${video.id}" data-unit="${unit.id}" data-name="${esc(unit.title)}">تعديل</button>
            <span class="month-order">
              <button type="button" class="cs-btn cs-btn--ghost" data-act="move-video" data-unit="${unit.id}" data-video="${video.id}" data-dir="-1" ${index === 0 ? "disabled" : ""}>فوق</button>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="move-video" data-unit="${unit.id}" data-video="${video.id}" data-dir="1" ${index === unit.videos.length - 1 ? "disabled" : ""}>تحت</button>
            </span>
            <button type="button" class="cs-btn cs-btn--danger" data-act="video-delete" data-video="${video.id}">حذف</button>
          </div>
        </div>
      </article>`;
    })
    .join("");
}

async function openMonth() {
  const spot = window.scrollY;
  const player = document.getElementById("admin-player");
  player.hidden = true;
  document.getElementById("month-detail-body").before(player);
  month = await Elmo.api(`/api/teacher/months/${monthId}`);
  videos = month.units.flatMap((unit) => unit.videos);
  document.getElementById("month-detail-title").textContent = month.title;
  document.getElementById("month-crumb").textContent = month.title;
  const lock = month.locks_at ? `فيديوهات الشهر تتقفل ${when(month.locks_at)} وتفضل ظاهرة` : "من غير تاريخ قفل";
  document.getElementById("month-detail-copy").innerHTML = `${state(!!month.published, "ظاهر", "مخفي")}<span>${esc(lock)}</span>`;
  if (panel === "videos") month.units.forEach((unit) => openUnits.add(String(unit.id)));
  paintStats();
  document.getElementById("month-identity").innerHTML = month.description
    ? `<div class="month-identity"><p>${esc(month.description)}</p></div>`
    : "";
  const sections = month.units.length
    ? month.units
        .map((unit, index) => {
          const inherited = unit.locks_at ? "" : month.locks_at ? "الشهر" : "";
          const open = openUnits.has(String(unit.id));
          return `<section class="outline-section outline-fold ${open ? "is-open" : ""}" data-status="${unit.published ? "published" : "hidden"}" data-search="${esc(unit.title)} ${esc(unit.description || "")}">
            <button type="button" class="outline-toggle" data-act="toggle-unit" data-unit="${unit.id}" aria-expanded="${open ? "true" : "false"}">
              <span class="month-index">${index + 1}</span>
              <span class="month-main">
                <strong>${esc(unit.title)}</strong>
                <span class="month-meta">${state(!!unit.published, "ظاهر", "مخفي")}<span>${unit.videos.length} فيديو</span></span>
              </span>
            </button>
            <div class="outline-body">
              ${unit.description ? `<p>${esc(unit.description)}</p>` : ""}
              <p class="month-lock">${esc(lockSentence(unit.locks_at, inherited))}</p>
              <div class="month-section-acts">
                <button type="button" class="cs-btn cs-btn--ghost" data-act="unit-edit" data-unit="${unit.id}">تعديل</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="unit-publish" data-unit="${unit.id}" data-published="${unit.published ? "1" : "0"}">${unit.published ? "إخفاء" : "إظهار"}</button>
                <span class="month-order">
                  <button type="button" class="cs-btn cs-btn--ghost" data-act="move-unit" data-unit="${unit.id}" data-dir="-1" ${index === 0 ? "disabled" : ""}>فوق</button>
                  <button type="button" class="cs-btn cs-btn--ghost" data-act="move-unit" data-unit="${unit.id}" data-dir="1" ${index === month.units.length - 1 ? "disabled" : ""}>تحت</button>
                </span>
                <button type="button" class="cs-btn cs-btn--danger" data-act="unit-delete" data-unit="${unit.id}">حذف</button>
              </div>
              <div class="month-inner">
                <div class="month-inner__head">
                  <strong>الفيديوهات</strong>
                  <button type="button" class="cs-btn cs-btn--ghost" data-act="video-add" data-unit="${unit.id}" data-name="${esc(unit.title)}">إضافة فيديو</button>
                </div>
                <div class="outline-videos">${videoRows(unit)}</div>
              </div>
            </div>
          </section>`;
        })
        .join("")
    : `<p class="outline-empty">لا توجد أقسام.</p>`;
  document.getElementById("month-detail-body").innerHTML = `<div class="month-outline">${sections}</div>`;
  const statusFilter = document.getElementById("month-status-filter");
  if (statusFilter) statusFilter.value = panel === "published" || panel === "hidden" ? panel : "";
  applyFilters();
  const query = (document.getElementById("cs-search")?.value || "").trim();
  if (query) {
    document.querySelectorAll(".outline-fold").forEach((fold) => {
      const hit = [...fold.querySelectorAll("[data-search]")].some((row) => !row.hidden) || !fold.hidden;
      if (hit && !fold.hidden) fold.classList.add("is-open");
    });
  }
  window.scrollTo(0, spot);
}

document.getElementById("month-build").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  try {
    await Elmo.api(`/api/teacher/months/${monthId}`, {
      method: "PATCH",
      body: JSON.stringify({
        title: data.get("title"),
        description: data.get("description") || null,
        published: form.querySelector('[name="published"]').checked,
        locks_at: data.get("locks_at") || null,
      }),
    });
    const cover = data.get("cover");
    if (cover && cover.size) {
      const fileData = new FormData();
      fileData.append("cover", cover);
      await Elmo.upload(`/api/teacher/months/${monthId}/cover`, fileData);
    }
    closeModal(form.closest(".cs-modal"));
    say("اتحفظ ظهور الشهر.", true);
    await openMonth();
  } catch (err) {
    say(err.message);
    openModal("modal-month");
  }
});

document.getElementById("unit-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const unitId = data.get("unit_id");
  const payload = {
    title: data.get("title"),
    description: data.get("description") || null,
    published: form.querySelector('[name="published"]').checked,
    locks_at: data.get("locks_at") || null,
  };
  try {
    const saved = await Elmo.api(unitId ? `/api/teacher/units/${unitId}` : `/api/teacher/months/${data.get("month_id")}/units`, {
      method: unitId ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    if (!unitId && saved.id) openUnits.add(String(saved.id));
    form.reset();
    form.querySelector('[name="month_id"]').value = monthId;
    closeModal(form.closest(".cs-modal"));
    say(unitId ? "اتحفظ القسم." : "اتضاف القسم.", true);
    await openMonth();
  } catch (err) {
    say(err.message);
    openModal("modal-unit");
  }
});

function showUpload(name, ratio, note, failed = false) {
  const dock = document.getElementById("upload-dock");
  dock.hidden = false;
  dock.classList.toggle("is-bad", failed);
  document.getElementById("upload-label").textContent = name || "رفع الفيديو";
  document.getElementById("upload-percent").textContent = `${Math.round(ratio * 100)}%`;
  document.getElementById("upload-fill").style.width = `${Math.round(ratio * 100)}%`;
  document.getElementById("upload-note").textContent = note;
}

async function pushToCloud(file) {
  const maxBytes = 2 * 1024 * 1024 * 1024;
  if (!file || !file.size) throw new Error("اختار ملف الفيديو.");
  if (file.size > maxBytes) throw new Error("حجم الفيديو أكبر من 2 جيجا.");

  // 5 ميجا للجزء — يدعم لحد 2 جيجا في أقل من 512 جزء
  const chunk = 5 * 1024 * 1024;
  const uploadId = crypto.randomUUID();
  const total = Math.max(1, Math.ceil(file.size / chunk));
  if (total > 512) throw new Error("حجم الفيديو أكبر من المسموح.");

  for (let index = 0; index < total; index += 1) {
    const piece = file.slice(index * chunk, Math.min(file.size, (index + 1) * chunk), file.type);
    let attempt = 0;
    while (true) {
      const body = new FormData();
      body.append("upload_id", uploadId);
      body.append("index", String(index));
      body.append("total", String(total));
      body.append("size", String(file.size));
      body.append("name", file.name);
      body.append("mime", file.type || "video/mp4");
      body.append("file", piece, file.name);
      try {
        await Elmo.uploadWithProgress("/api/teacher/uploads/parts", body, (ratio) => {
          showUpload(file.name, (index + ratio) / total, `رفع الجزء ${index + 1} من ${total}`);
        });
        break;
      } catch (err) {
        attempt += 1;
        if (attempt >= 3) throw err;
        showUpload(file.name, index / total, `إعادة محاولة الجزء ${index + 1}…`, true);
        await new Promise((resolve) => setTimeout(resolve, 800 * attempt));
      }
    }
  }
  showUpload(file.name, 1, "الحفظ على كلاود فلير… قد ياخد وقت مع الفيديوهات الكبيرة");
  const saved = await Elmo.api("/api/teacher/uploads/parts/finish", {
    method: "POST",
    body: JSON.stringify({ upload_id: uploadId }),
    timeoutMs: 15 * 60 * 1000,
  });
  return saved.media_id;
}

document.getElementById("video-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const videoId = data.get("video_id");
  const file = data.get("video");
  const unitId = data.get("unit_id");
  const attachments = [...form.querySelector('[name="attachments[]"]').files];
  const payload = {
    title: data.get("title"),
    description: data.get("description") || null,
    published: form.querySelector('[name="published"]').checked,
    locks_at: data.get("locks_at") || null,
  };
  if (!videoId && (!file || !file.size)) {
    say("اختار ملف الفيديو.");
    openModal("modal-video");
    return;
  }
  form.reset();
  closeModal(form.closest(".cs-modal"));
  try {
    if (videoId) {
      await Elmo.api(`/api/teacher/videos/${videoId}`, { method: "PATCH", body: JSON.stringify(payload) });
      if (file && file.size) {
        showUpload(file.name, 0, "الرفع على كلاود فلير");
        const mediaId = await pushToCloud(file);
        await Elmo.api(`/api/teacher/videos/${videoId}/replace`, {
          method: "POST",
          body: JSON.stringify({ media_id: mediaId }),
        });
      }
      if (attachments.length) {
        showUpload(file?.name || "المرفقات", 1, "رفع المرفقات");
        const fileData = new FormData();
        attachments.forEach((item) => fileData.append("attachments[]", item));
        await Elmo.upload(`/api/teacher/videos/${videoId}/attachments`, fileData);
      }
    } else {
      showUpload(file.name, 0, "الرفع على كلاود فلير");
      const mediaId = await pushToCloud(file);
      const saved = await Elmo.api(`/api/teacher/units/${unitId}/videos`, {
        method: "POST",
        body: JSON.stringify({ media_id: mediaId, ...payload }),
      });
      if (saved?.id) openVideos.add(String(saved.id));
      openUnits.add(String(unitId));
      if (attachments.length && saved?.id) {
        showUpload(file.name, 1, "رفع المرفقات");
        const fileData = new FormData();
        attachments.forEach((item) => fileData.append("attachments[]", item));
        await Elmo.upload(`/api/teacher/videos/${saved.id}/attachments`, fileData);
      }
    }
    showUpload(file?.name || "الفيديو", 1, "اكتمل الرفع");
    say("اترفع الفيديو.", true);
    await openMonth();
    document.getElementById("upload-dock").hidden = true;
  } catch (err) {
    showUpload(file?.name || "الفيديو", 0, err.message, true);
    say(err.message);
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  if (button.dataset.act === "toggle-unit" || button.dataset.act === "toggle-video") {
    const fold = button.closest(".outline-fold");
    fold.classList.toggle("is-open");
    const opened = fold.classList.contains("is-open");
    button.setAttribute("aria-expanded", opened ? "true" : "false");
    const bucket = button.dataset.act === "toggle-unit" ? openUnits : openVideos;
    const id = button.dataset.act === "toggle-unit" ? button.dataset.unit : button.dataset.video;
    if (opened) bucket.add(String(id));
    else bucket.delete(String(id));
    return;
  }
  if (button.dataset.act === "month-edit" || button.dataset.act === "unit-add") {
    if (!month) return;
  }
  if (button.dataset.act === "month-edit") {
    const form = document.getElementById("month-build");
    form.querySelector('[name="title"]').value = month.title || "";
    form.querySelector('[name="description"]').value = month.description || "";
    form.querySelector('[name="locks_at"]').value = lockValue(month.locks_at);
    form.querySelector('[name="published"]').checked = !!month.published;
    form.querySelector('[name="cover"]').value = "";
    openModal("modal-month");
    return;
  }
  if (button.dataset.act === "unit-add" || button.dataset.act === "unit-edit") {
    const form = document.getElementById("unit-form");
    form.reset();
    form.querySelector('[name="month_id"]').value = monthId;
    form.querySelector('[name="unit_id"]').value = "";
    form.querySelector('[name="published"]').checked = true;
    document.getElementById("unit-form-title").textContent = "قسم جديد";
    document.getElementById("unit-month-name").textContent = month.title || "";
    if (button.dataset.act === "unit-edit") {
      const unit = month.units.find((item) => String(item.id) === button.dataset.unit);
      if (!unit) return;
      form.querySelector('[name="unit_id"]').value = unit.id;
      form.querySelector('[name="title"]').value = unit.title || "";
      form.querySelector('[name="description"]').value = unit.description || "";
      form.querySelector('[name="locks_at"]').value = lockValue(unit.locks_at);
      form.querySelector('[name="published"]').checked = !!unit.published;
      document.getElementById("unit-form-title").textContent = "تعديل القسم";
    }
    openModal("modal-unit");
    return;
  }
  if (button.dataset.act === "video-add" || button.dataset.act === "video-edit") {
    const form = document.getElementById("video-form");
    form.reset();
    form.querySelector('[name="unit_id"]').value = button.dataset.unit || "";
    form.querySelector('[name="video_id"]').value = "";
    document.getElementById("video-unit-name").textContent = button.dataset.name || "";
    document.getElementById("video-form-title").textContent = "فيديو جديد";
    if (button.dataset.act === "video-edit") {
      const video = videos.find((item) => String(item.id) === button.dataset.video);
      if (!video) return;
      form.querySelector('[name="video_id"]').value = video.id;
      form.querySelector('[name="title"]').value = video.title || "";
      form.querySelector('[name="description"]').value = video.description || "";
      form.querySelector('[name="locks_at"]').value = lockValue(video.locks_at);
      form.querySelector('[name="published"]').checked = !!video.published;
      document.getElementById("video-form-title").textContent = "تعديل الفيديو";
    }
    openModal("modal-video");
    return;
  }
  if (button.dataset.act === "video-play") {
    try {
      const player = document.getElementById("admin-player");
      const row = button.closest("[data-video-row]");
      row?.classList.add("is-open");
      openVideos.add(String(button.dataset.video));
      row?.querySelector(".outline-body")?.appendChild(player);
      player.hidden = false;
      await Elmo.play(player, `/api/teacher/videos/${button.dataset.video}/stream`);
    } catch (err) {
      say(err.message);
    }
    return;
  }
  if (button.dataset.act === "file-get") {
    try {
      const blob = await Elmo.fetchBlob(`/api/teacher/videos/${button.dataset.video}/files/${button.dataset.file}`);
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = button.dataset.name || "file";
      link.click();
      URL.revokeObjectURL(url);
    } catch (err) {
      say(err.message);
    }
    return;
  }
  try {
    if (button.dataset.act === "unit-publish") {
      await Elmo.api(`/api/teacher/units/${button.dataset.unit}`, {
        method: "PATCH",
        body: JSON.stringify({ published: button.dataset.published !== "1" }),
      });
    } else if (button.dataset.act === "unit-delete") {
      await Elmo.api(`/api/teacher/units/${button.dataset.unit}`, { method: "DELETE" });
    } else if (button.dataset.act === "video-delete") {
      await Elmo.api(`/api/teacher/videos/${button.dataset.video}`, { method: "DELETE" });
    } else if (button.dataset.act === "file-delete") {
      await Elmo.api(`/api/teacher/videos/${button.dataset.video}/files/${button.dataset.file}`, { method: "DELETE" });
    } else if (button.dataset.act === "move-unit" || button.dataset.act === "move-video") {
      const dir = Number(button.dataset.dir);
      const list = button.dataset.act === "move-unit"
        ? month.units
        : month.units.find((item) => String(item.id) === button.dataset.unit)?.videos || [];
      const index = list.findIndex((item) => String(item.id) === (button.dataset.unit && button.dataset.act === "move-unit" ? button.dataset.unit : button.dataset.video));
      const next = index + dir;
      if (index < 0 || next < 0 || next >= list.length) return;
      const order = list.slice();
      const [item] = order.splice(index, 1);
      order.splice(next, 0, item);
      const path = button.dataset.act === "move-unit" ? "/api/teacher/units/" : "/api/teacher/videos/";
      await Promise.all(order.map((row, place) => Elmo.api(`${path}${row.id}`, {
        method: "PATCH",
        body: JSON.stringify({ sort: place + 1 }),
      })));
    } else {
      return;
    }
    say("تم.", true);
    await openMonth();
  } catch (err) {
    say(err.message);
  }
});

function paintStats() {
  const units = month?.units || [];
  const videoCount = units.reduce((sum, unit) => sum + unit.videos.length, 0);
  const visible = units.filter((unit) => unit.published).length + units.reduce((sum, unit) => sum + unit.videos.filter((video) => video.published).length, 0);
  const hidden = units.filter((unit) => !unit.published).length + units.reduce((sum, unit) => sum + unit.videos.filter((video) => !video.published).length, 0);
  const cards = [
    ["sections", "أقسام", units.length],
    ["videos", "فيديوهات", videoCount],
    ["published", "ظاهرة", visible],
    ["hidden", "مخفية", hidden],
  ];
  document.getElementById("month-stats").innerHTML = cards
    .map(([key, label, value]) => `<button type="button" class="cs-stat${panel === key ? " is-on" : ""}" data-panel="${key}"><span>${label}</span><strong>${value}</strong></button>`)
    .join("");
}

document.addEventListener("input", (event) => {
  if (event.target.id !== "cs-search" || !event.target.value.trim()) return;
  document.querySelectorAll("#month-builder .outline-fold").forEach((fold) => {
    if (fold.hidden) return;
    const hit = [...fold.querySelectorAll("[data-search]")].some((row) => !row.hidden);
    if (!hit && fold.hidden) return;
    fold.classList.add("is-open");
    fold.querySelector(".outline-toggle")?.setAttribute("aria-expanded", "true");
  });
});

document.getElementById("month-stats").addEventListener("click", (event) => {
  const card = event.target.closest("[data-panel]");
  if (!card || !month) return;
  panel = card.dataset.panel;
  if (panel === "videos") month.units.forEach((unit) => openUnits.add(String(unit.id)));
  paintStats();
  if (panel === "videos") {
    document.querySelectorAll(".outline-section").forEach((section) => {
      section.classList.add("is-open");
      section.querySelector(".outline-toggle")?.setAttribute("aria-expanded", "true");
    });
  }
  const statusFilter = document.getElementById("month-status-filter");
  statusFilter.value = panel === "published" || panel === "hidden" ? panel : "";
  statusFilter.dispatchEvent(new Event("change", { bubbles: true }));
  document.getElementById("month-builder").scrollIntoView({ behavior: "smooth", block: "start" });
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  try {
    await openMonth();
  } catch (err) {
    say(err.message);
  }
});
