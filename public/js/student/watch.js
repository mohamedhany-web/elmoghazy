(function () {
  const root = document.getElementById("watch");
  if (!root || !window.StKit || !window.Elmo) {
    const main = document.getElementById("watch-main");
    if (main) main.innerHTML = `<p class="cv-empty cv-card">تعذّر تحميل الصفحة. حدّث المتصفح.</p>`;
    return;
  }

  const { esc, percent, time, save, done, videosOf, progress } = window.StKit;
  const monthId = root.dataset.month || "";
  const params = new URLSearchParams(location.search);

  let month = null;
  let exams = [];
  let items = [];
  let current = null;
  let covered = false;
  let booted = false;
  let shieldReady = false;
  const openUnits = new Set();

  function ensureShield() {
    if (shieldReady) return;
    shieldReady = true;
    const veil = document.createElement("div");
    veil.id = "cv-shield-veil";
    veil.className = "cv-shield-veil";
    veil.setAttribute("aria-hidden", "true");
    document.body.appendChild(veil);
    let veilTimer = 0;
    const blackout = () => {
      veil.classList.add("is-on");
      clearTimeout(veilTimer);
      veilTimer = setTimeout(() => veil.classList.remove("is-on"), 2000);
    };
    document.addEventListener("keydown", (event) => {
      const key = event.key || "";
      const lower = key.toLowerCase();
      const combo = event.metaKey || event.ctrlKey;
      const shot =
        key === "PrintScreen" ||
        (combo && event.shiftKey && ["3", "4", "5"].includes(lower)) ||
        (event.metaKey && event.shiftKey && ["3", "4", "5", "s"].includes(lower));
      if (!shot) return;
      event.preventDefault();
      blackout();
    });
    document.addEventListener("keyup", (event) => {
      if (event.key === "PrintScreen") blackout();
    });
    document.addEventListener("contextmenu", (event) => {
      if (event.target.closest(".cv-player, #watch-video")) event.preventDefault();
    });
  }

  function protectPlayer(video, user) {
    if (!video) return;
    ensureShield();
    const box = video.closest(".cv-player");
    if (!box) return;
    const mark = (user && (user.watch_mark || `MG-${user.id}`)) || "";
    if (mark) {
      let badge = box.querySelector(".cv-shield-badge");
      if (!badge) {
        badge = document.createElement("span");
        badge.className = "cv-shield-badge";
        badge.setAttribute("aria-hidden", "true");
        box.appendChild(badge);
      }
      if (badge.textContent !== mark) badge.textContent = mark;
    }
    video.setAttribute("controlsList", "nodownload noplaybackrate noremoteplayback");
    try {
      video.disablePictureInPicture = true;
    } catch {
      /* ignore */
    }
    if (video.dataset.shielded === "1") return;
    video.dataset.shielded = "1";
    video.addEventListener("contextmenu", (event) => event.preventDefault());
    video.addEventListener("dragstart", (event) => event.preventDefault());
  }

  function fail(message) {
    const msg = esc(message);
    const main = document.getElementById("watch-main");
    const tree = document.getElementById("watch-tree");
    if (main) main.innerHTML = `<p class="cv-empty cv-card">${msg}</p>`;
    if (tree) {
      tree.innerHTML = `<div class="cv-tree__top"><a class="cv-tree__back" href="/learn/months">رجوع للشهور</a></div><p class="cv-fold__empty">${msg}</p>`;
    }
  }

  function flat(row) {
    const videos = (row.units || []).flatMap((unit) =>
      (unit.videos || []).map((video) => ({
        type: "video",
        id: video.id,
        title: video.title,
        description: video.description,
        locked: video.locked,
        lock_message: video.lock_message,
        files: video.files || [],
        unit,
      })),
    );
    const tests = exams
      .filter((exam) => String(exam.month_id) === String(row.id))
      .map((exam) => ({
        type: "exam",
        id: exam.id,
        title: exam.title,
        locked: exam.locked,
        lock_message: exam.message,
        unit: { id: "exams", title: "الامتحانات", description: "" },
        exam,
      }));
    return videos.concat(tests);
  }

  function href(item) {
    return `/learn/months/${monthId}/learn?type=${item.type}&id=${item.id}`;
  }

  function indexOf(item) {
    return items.findIndex((row) => row.type === item.type && String(row.id) === String(item.id));
  }

  function unitKey(unit) {
    return String(unit.id ?? unit.title);
  }

  function leaf(item) {
    const currentOn = current && current.type === item.type && String(current.id) === String(item.id);
    const ready = item.type === "video" && done(item.id);
    const mark = item.locked ? "قفل" : ready ? "✓" : currentOn ? "●" : "";
    return `<a class="cv-leaf ${currentOn ? "is-current" : ""} ${item.locked ? "is-lock" : ""} ${ready ? "is-done" : ""}" href="${href(item)}">
      <span class="cv-leaf__mark" aria-hidden="true">${mark}</span>
      <span class="cv-leaf__text"><strong>${esc(item.title)}</strong><small>${item.type === "exam" ? "امتحان" : "شرح"}</small></span>
    </a>`;
  }

  function sectionHtml(title, key, rows, open) {
    return `<div class="cv-fold ${open ? "is-open" : ""}" data-unit="${esc(key)}">
      <button type="button" class="cv-fold__head" data-branch aria-expanded="${open ? "true" : "false"}">
        <span class="cv-fold__dot" aria-hidden="true"></span>
        <span class="cv-fold__meta"><strong>${esc(title)}</strong><small>${rows.length} عنصر</small></span>
        <span class="cv-fold__chev" aria-hidden="true"></span>
      </button>
      <div class="cv-fold__body" ${open ? "" : "hidden"}>${rows.length ? rows.map(leaf).join("") : `<p class="cv-fold__empty">مفيش عناصر هنا.</p>`}</div>
    </div>`;
  }

  function treeHtml(needle) {
    const parts = [];
    (month.units || []).forEach((unit) => {
      const rows = (unit.videos || [])
        .filter((video) => !needle || video.title.includes(needle))
        .map((video) => items.find((item) => item.type === "video" && String(item.id) === String(video.id)))
        .filter(Boolean);
      if (!rows.length && needle) return;
      const key = unitKey(unit);
      const open = openUnits.has(key) || (!!needle && rows.length > 0);
      parts.push(sectionHtml(unit.title, key, rows, open));
    });
    const tests = items.filter((item) => item.type === "exam" && (!needle || item.title.includes(needle)));
    if (tests.length || (!needle && exams.some((exam) => String(exam.month_id) === String(month.id)))) {
      const key = "exams";
      const open = openUnits.has(key) || (!!needle && tests.length > 0);
      parts.push(sectionHtml("الامتحانات", key, tests, open));
    }
    return parts.join("") || `<p class="cv-fold__empty">مفيش نتائج.</p>`;
  }

  function renderTree() {
    if (!month) return;
    if (current) openUnits.add(unitKey(current.unit || { id: "exams", title: "الامتحانات" }));
    const tree = document.getElementById("watch-tree");
    if (!tree) return;
    const videos = videosOf(month);
    const finished = videos.filter((video) => done(video.id)).length;
    const value = progress(month);
    tree.innerHTML = `
      <div class="cv-tree__top">
        <a class="cv-tree__back" href="/learn/months/${monthId}">رجوع للشهر</a>
        <div class="cv-tree__brand"><strong>المنهج</strong><small>${esc(month.title)}</small></div>
        <div class="cv-tree__progress"><span>${finished} / ${videos.length}</span><i><b style="width:${value}%"></b></i></div>
        <label class="cv-tree__search"><span class="visually-hidden">بحث</span>
          <input id="tree-search" type="search" placeholder="ابحث في العناوين" autocomplete="off">
        </label>
      </div>
      <div class="cv-tree__scroll" id="tree-scroll">${treeHtml("")}</div>`;
    const scroll = document.getElementById("tree-scroll");
    document.getElementById("tree-search").addEventListener("input", (event) => {
      scroll.innerHTML = treeHtml(event.target.value.trim());
    });
    scroll.addEventListener("click", (event) => {
      const button = event.target.closest("[data-branch]");
      if (!button) return;
      const fold = button.closest(".cv-fold");
      const key = fold.dataset.unit;
      const open = !fold.classList.contains("is-open");
      fold.classList.toggle("is-open", open);
      button.setAttribute("aria-expanded", open ? "true" : "false");
      fold.querySelector(".cv-fold__body").hidden = !open;
      if (open) openUnits.add(key);
      else openUnits.delete(key);
    });
  }

  function canNext() {
    if (!current || current.type !== "video" || current.locked) return true;
    return done(current.id);
  }

  function go(step) {
    if (!current) return;
    const spot = indexOf(current);
    const target = items[spot + step];
    if (!target) return;
    if (step > 0 && !canNext()) return;
    location.href = href(target);
  }

  function panel(item) {
    if (item.locked) {
      return `<section class="cv-block cv-lock"><i class="cv-bubble cv-bubble--peach">قفل</i><p>${esc(item.lock_message || "الفيديو اتقفل. راجع المدرس عشان تكمل.")}</p></section>`;
    }
    if (item.type === "exam") {
      return `<section class="cv-block"><h2>${esc(item.title)}</h2><p class="cv-note">${item.exam.question_count || 0} سؤال</p><a class="cv-btn" href="/learn/exams/${item.id}">افتح الامتحان</a></section>`;
    }
    const seen = percent(item.id);
    const spot = indexOf(item);
    const next = items[spot + 1];
    const prev = items[spot - 1];
    const files = (item.files || [])
      .map(
        (file) =>
          `<div class="cv-file"><i class="cv-bubble cv-bubble--sky">م</i><span>${esc(file.name || "ملف")}</span><button type="button" class="cv-btn" data-file="${file.id}">تنزيل</button></div>`,
      )
      .join("");
    const note = localStorage.getItem(`elmo-note:${item.id}`) || "";
    return `
      <section class="cv-block cv-stage">
        <div class="cv-player"><video id="watch-video" controls playsinline controlsList="nodownload"></video></div>
        <div class="cv-card__row"><span>المشاهدة</span><span id="watch-seen">${seen}%</span></div>
        <div class="cv-track"><b id="watch-bar" style="width:${seen}%"></b></div>
      </section>
      <div class="cv-nav2">
        <button type="button" class="cv-btn-dark" data-step="-1" ${prev ? "" : "disabled"}>السابق</button>
        <button type="button" class="cv-btn" data-step="1" ${next && canNext() ? "" : "disabled"}>التالي</button>
      </div>
      ${canNext() ? "" : `<p class="cv-note">كمّل المشاهدة لحد ٩٠٪ عشان اللي بعده يتفتح من الزر.</p>`}
      ${item.unit && item.unit.description ? `<section class="cv-block"><h2>وصف القسم</h2><p class="cv-pre">${esc(item.unit.description)}</p></section>` : ""}
      ${files ? `<section class="cv-block"><h2>مواد الشرح</h2><div class="cv-files">${files}</div></section>` : ""}
      <section class="cv-block" id="watch-board">
        <div class="cv-tabs">
          <button type="button" data-tab="look" class="is-on">نظرة عامة</button>
          <button type="button" data-tab="files">المصادر</button>
          <button type="button" data-tab="notes">ملاحظاتي</button>
          <button type="button" data-tab="talk">النقاش</button>
          <button type="button" data-tab="ask">أسئلة</button>
        </div>
        <div data-pane="look"><p class="cv-pre">${esc(item.description || "شرح الفيديو ظاهر فوق.")}</p></div>
        <div data-pane="files" hidden>${files || `<p class="cv-note">مفيش ملفات مع الشرح.</p>`}</div>
        <div data-pane="notes" hidden><textarea id="watch-note" rows="5" style="width:100%;border:0;border-radius:16px;background:#f7fafb;padding:12px;font:inherit">${esc(note)}</textarea></div>
        <div data-pane="talk" hidden><p class="cv-note">قريباً</p></div>
        <div data-pane="ask" hidden><p class="cv-note">قريباً</p></div>
      </section>`;
  }

  function maybeCover() {
    if (covered) return;
    const spot = indexOf(current);
    const next = items[spot + 1];
    if (!next || next.locked) return;
    covered = true;
    const hold = document.createElement("div");
    hold.className = "cv-cover";
    hold.innerHTML = `<article class="cv-card cv-card__body"><h2>اكتمل</h2><p>${esc(next.title)}</p><p class="cv-ring cv-ring--light" style="--p:100"><b>٥</b></p><div class="cv-nav2"><button type="button" class="cv-btn" id="cover-go">ابدأ الآن</button><button type="button" class="cv-btn-dark" id="cover-stop">إلغاء</button></div></article>`;
    document.body.appendChild(hold);
    let left = 5;
    const tick = setInterval(() => {
      left -= 1;
      const badge = hold.querySelector("b");
      if (badge) badge.textContent = String(left);
      if (left <= 0) {
        clearInterval(tick);
        location.href = href(next);
      }
    }, 1000);
    hold.querySelector("#cover-go").addEventListener("click", () => {
      clearInterval(tick);
      location.href = href(next);
    });
    hold.querySelector("#cover-stop").addEventListener("click", () => {
      clearInterval(tick);
      hold.remove();
    });
  }

  function renderMain() {
    const main = document.getElementById("watch-main");
    document.getElementById("watch-title").textContent = current ? current.title : "اختَر من المنهج";
    document.getElementById("watch-kicker").textContent = current && current.unit ? current.unit.title : month.title;
    if (!current) {
      main.innerHTML = `<section class="cv-empty cv-card"><i class="cv-bubble cv-bubble--mint">ش</i><h2>اختر عنصراً من المنهج</h2><button type="button" class="cv-btn" id="open-tree">المنهج</button></section>`;
      document.getElementById("open-tree").addEventListener("click", () => root.classList.add("is-tree"));
      return;
    }
    main.innerHTML = panel(current);
    if (current.type === "video" && !current.locked) {
      const video = document.getElementById("watch-video");
      protectPlayer(video, window.Elmo.user);
      const resumeAt = time(current.id);
      let lastSaved = 0;
      const paintProgress = () => {
        const seen = document.getElementById("watch-seen");
        const bar = document.getElementById("watch-bar");
        const value = percent(current.id);
        if (seen) seen.textContent = `${value}%`;
        if (bar) bar.style.width = `${value}%`;
      };
      const storeProgress = (force = false) => {
        if (!video.duration || !Number.isFinite(video.duration) || video.duration <= 0) return;
        const now = Date.now();
        if (!force && now - lastSaved < 700) return;
        lastSaved = now;
        const before = percent(current.id);
        const value = Math.min(100, Math.round((video.currentTime / video.duration) * 100));
        save(current.id, value, video.currentTime);
        paintProgress();
        const after = percent(current.id);
        if (after >= 90) maybeCover();
        if (force || (before < 90 && after >= 90) || after === 100) renderTree();
      };
      const seekResume = () => {
        if (!resumeAt || !video.duration) return;
        const target = Math.min(resumeAt, Math.max(0, video.duration - 1.5));
        if (target >= 2) video.currentTime = target;
      };
      Elmo.play(video, `/api/student/videos/${current.id}/stream`)
        .then(() => {
          if (video.readyState >= 1) seekResume();
          else video.addEventListener("loadedmetadata", seekResume, { once: true });
        })
        .catch((err) => {
          main.insertAdjacentHTML("afterbegin", `<p class="cv-note">${esc(err.message)}</p>`);
        });
      video.addEventListener("timeupdate", () => storeProgress(false));
      video.addEventListener("pause", () => storeProgress(true));
      video.addEventListener("ended", () => {
        save(current.id, 100, video.duration || time(current.id));
        paintProgress();
        maybeCover();
        renderTree();
      });
      window.addEventListener("pagehide", () => storeProgress(true), { once: true });
    }
    const note = document.getElementById("watch-note");
    if (note) {
      let timer = 0;
      note.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(() => localStorage.setItem(`elmo-note:${current.id}`, note.value), 500);
      });
    }
    main.querySelectorAll("[data-tab]").forEach((button) => {
      button.addEventListener("click", () => {
        main.querySelectorAll("[data-tab]").forEach((node) => node.classList.toggle("is-on", node === button));
        main.querySelectorAll("[data-pane]").forEach((pane) => {
          pane.hidden = pane.dataset.pane !== button.dataset.tab;
        });
      });
    });
    main.querySelectorAll("[data-file]").forEach((button) => {
      button.addEventListener("click", async () => {
        try {
          const blob = await Elmo.fetchBlob(`/api/student/videos/${current.id}/files/${button.dataset.file}`);
          const url = URL.createObjectURL(blob);
          const link = document.createElement("a");
          link.href = url;
          link.download = button.parentElement.querySelector("span").textContent || "file";
          link.click();
          setTimeout(() => URL.revokeObjectURL(url), 1500);
        } catch (err) {
          fail(err.message || "تعذر تنزيل الملف.");
        }
      });
    });
  }

  async function boot(user) {
    if (booted) return;
    booted = true;
    try {
      if (!user) {
        location.href = "/login";
        return;
      }
      if (user.role === "teacher") {
        location.href = "/admin";
        return;
      }
      const months = await Elmo.api("/api/student/months");
      month = (Array.isArray(months) ? months : []).find((row) => String(row.id) === String(monthId)) || null;
      if (!month) {
        fail("الشهر غير متاح أو التفعيل منتهي.");
        return;
      }
      items = flat(month);
      const type = params.get("type");
      const id = params.get("id");
      current = items.find((item) => item.type === type && String(item.id) === String(id)) || null;
      renderTree();
      renderMain();
      Elmo.api("/api/student/exams")
        .then((rows) => {
          exams = Array.isArray(rows) ? rows : [];
          items = flat(month);
          current = items.find((item) => item.type === type && String(item.id) === String(id)) || current;
          renderTree();
        })
        .catch(() => {});
    } catch (err) {
      booted = false;
      fail(err.message || "تعذّر تحميل المحتوى.");
    }
  }

  document.addEventListener("click", (event) => {
    const step = event.target.closest("[data-step]");
    if (step) go(Number(step.dataset.step));
  });
  document.getElementById("watch-prev")?.addEventListener("click", () => go(-1));
  document.getElementById("watch-next")?.addEventListener("click", () => go(1));
  document.getElementById("watch-menu")?.addEventListener("click", () => root.classList.toggle("is-tree"));
  document.getElementById("watch-menu-mobile")?.addEventListener("click", () => root.classList.toggle("is-tree"));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") root.classList.remove("is-tree");
  });

  Elmo.onReady(boot);
  // احتياط لو الحدث اتعدى قبل التسجيل
  setTimeout(() => {
    if (!booted) {
      Elmo.currentUser()
        .then(boot)
        .catch((err) => fail(err.message || "تعذّر تحميل المحتوى."));
    }
  }, 50);
})();
