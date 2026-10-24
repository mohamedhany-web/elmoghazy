const TOKEN_KEY = "elmoghazy_token";
const THEME_KEY = "elmoghazy_theme";

const ICONS = {
  atom: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="1.6" fill="currentColor"/><g stroke="currentColor" stroke-width="1.35" fill="none"><ellipse cx="12" cy="12" rx="8" ry="3"/><ellipse cx="12" cy="12" rx="8" ry="3" transform="rotate(60 12 12)"/><ellipse cx="12" cy="12" rx="8" ry="3" transform="rotate(120 12 12)"/></g></svg>',
  login: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>',
  menu: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
  close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>',
  sun: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  moon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>',
  up: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 14l6-6 6 6"/></svg>',
  cap: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c2 1.5 4 2 6 2s4-.5 6-2v-5"/></svg>',
  book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
  play: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M10 8l6 4-6 4V8z"/></svg>',
  clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>',
  tick: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"/></svg>',
  spark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3z"/></svg>',
  pen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
  check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
  trend: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>',
  chat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-8 8H7l-4 3V12a8 8 0 1 1 18 0z"/></svg>',
  user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
  mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>',
  lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
  eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
  eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l18 18"/><path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-1.2"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6 0 10 7 10 7a18 18 0 0 1-4.1 4.6"/><path d="M6.1 6.1C3.6 7.9 2 12 2 12a18.4 18.4 0 0 0 5.1 5.3"/></svg>',
  back: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
  bolt: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/></svg>',
  flag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 21V4"/><path d="M5 4h11l-1.5 4L16 12H5"/></svg>',
  route: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="M8 6h7a3 3 0 0 1 3 3v7"/></svg>',
  home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/></svg>',
  userPlus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="8" r="3.2"/><path d="M3.5 20a6.5 6.5 0 0 1 11.2-4.4"/><path d="M18 11v6M15 14h6"/></svg>',
};

const MODES = { online: "Online", recorded: "Recorded", live: "Live" };
const TONES = { cyan: "tone-cyan", amber: "tone-amber", emerald: "tone-emerald", violet: "tone-violet" };

function paintIcons(root = document) {
  root.querySelectorAll("[data-icon]").forEach((node) => {
    if (node.dataset.ready) return;
    node.innerHTML = ICONS[node.dataset.icon] || "";
    node.dataset.ready = "1";
  });
}

function csrfToken() {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
  return match ? decodeURIComponent(match[1]) : "";
}

function authHeaders() {
  const headers = new Headers();
  headers.set("Accept", "application/json");
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  const xsrf = csrfToken();
  if (xsrf) headers.set("X-XSRF-TOKEN", xsrf);
  return headers;
}

function plainError(body) {
  const first = body.errors ? Object.values(body.errors).flat()[0] : body.message;
  if (first === "Too Many Attempts.") return "محاولات كتير ورا بعض. استنى دقيقة وحاول تاني.";
  return first || "حصل خطأ، حاول تاني";
}

async function readError(response) {
  const body = await response.json().catch(() => ({}));
  return plainError(body);
}

async function upload(path, formData) {
  const headers = authHeaders();
  const response = await fetch(path, { method: "POST", headers, body: formData, credentials: "same-origin" });
  if (!response.ok) throw new Error(await readError(response));
  return response.json();
}

function uploadWithProgress(path, formData, onProgress) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", path);
    const token = localStorage.getItem(TOKEN_KEY);
    xhr.setRequestHeader("Accept", "application/json");
    if (token) xhr.setRequestHeader("Authorization", `Bearer ${token}`);
    const xsrf = csrfToken();
    if (xsrf) xhr.setRequestHeader("X-XSRF-TOKEN", xsrf);
    xhr.withCredentials = true;
    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable && onProgress) onProgress(event.loaded / event.total);
    };
    xhr.onload = () => {
      let body = {};
      try {
        body = JSON.parse(xhr.responseText || "{}");
      } catch {
        body = {};
      }
      if (xhr.status >= 200 && xhr.status < 300) resolve(body);
      else {
        reject(new Error(plainError(body)));
      }
    };
    xhr.onerror = () => reject(new Error("تعذر رفع الفيديو."));
    xhr.send(formData);
  });
}

function sendFile(url, file, contentType, onProgress) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("PUT", url);
    if (contentType) xhr.setRequestHeader("Content-Type", contentType);
    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable && onProgress) onProgress(event.loaded / event.total);
    };
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) resolve();
      else reject(new Error("تعذر رفع الفيديو."));
    };
    xhr.onerror = () => reject(new Error("تعذر الوصول إلى كلاود فلير."));
    xhr.send(file);
  });
}

async function fetchBlob(path) {
  const headers = authHeaders();
  const response = await fetch(path, { headers, credentials: "same-origin", cache: "no-store" });
  if (!response.ok) throw new Error(await readError(response));
  return response.blob();
}

let playingUrl = "";

async function play(video, path) {
  if (playingUrl) {
    URL.revokeObjectURL(playingUrl);
    playingUrl = "";
  }
  video.hidden = false;
  video.removeAttribute("controlslist");
  video.setAttribute("controlsList", "nodownload noplaybackrate noremoteplayback");
  video.disablePictureInPicture = true;

  const studentMatch = String(path).match(/\/api\/student\/videos\/(\d+)\/stream/);
  if (studentMatch) {
    const load = async () => {
      const ticket = await api(`/api/student/videos/${studentMatch[1]}/ticket`, { method: "POST" });
      // مسار المنصة → تحويل مؤقت لـ R2 (من غير ما PHP يمرّر الملف)
      video.src = `${path}${path.includes("?") ? "&" : "?"}t=${encodeURIComponent(ticket.token)}`;
    };
    // لو الرابط المؤقت انتهى أو النت فصل: تذكرة جديدة ونكمّل من نفس الثانية
    if (video._elmoRecover) video.removeEventListener("error", video._elmoRecover);
    let retries = 0;
    video._elmoRecover = async () => {
      if (retries >= 3 || !video.error) return;
      retries += 1;
      const at = video.currentTime || 0;
      const resume = !video.paused || video.autoplay;
      try {
        await load();
        video.addEventListener("loadedmetadata", () => {
          if (at > 1) video.currentTime = at;
          if (resume) video.play().catch(() => {});
        }, { once: true });
      } catch {
        // الرسالة الأصلية بتفضل ظاهرة في المشغّل
      }
    };
    video.addEventListener("error", video._elmoRecover);
    await load();
    return;
  }

  if (/\/api\/teacher\/videos\/\d+\/stream/.test(String(path))) {
    const link = await api(`${path}${path.includes("?") ? "&" : "?"}url=1`);
    video.src = link.url;
    return;
  }

  const blob = await fetchBlob(path);
  playingUrl = URL.createObjectURL(blob);
  video.src = playingUrl;
}

window.addEventListener("pagehide", () => {
  if (playingUrl) {
    URL.revokeObjectURL(playingUrl);
    playingUrl = "";
  }
});

async function api(path, options = {}) {
  const { timeoutMs = 30000, signal: outerSignal, ...fetchOptions } = options;
  const headers = new Headers(fetchOptions.headers || {});
  headers.set("Accept", "application/json");
  if (fetchOptions.body) headers.set("Content-Type", "application/json");
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  const xsrf = csrfToken();
  if (xsrf) headers.set("X-XSRF-TOKEN", xsrf);
  const ctrl = outerSignal ? null : new AbortController();
  const wait = Number(timeoutMs);
  const timer = ctrl && Number.isFinite(wait) && wait > 0 ? setTimeout(() => ctrl.abort(), wait) : 0;
  try {
    const response = await fetch(path, {
      ...fetchOptions,
      headers,
      credentials: "same-origin",
      signal: outerSignal || ctrl.signal,
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
      if (response.status === 419) {
        throw new Error("انتهت الجلسة. حدّث الصفحة وجرب تاني.");
      }
      throw new Error(plainError(body));
    }
    return body;
  } catch (err) {
    if (err && err.name === "AbortError") {
      throw new Error("الاتصال اتأخر. تأكد إن السيرفر شغال وحدّث الصفحة.");
    }
    throw err;
  } finally {
    if (timer) clearTimeout(timer);
  }
}

function money(price) {
  return `${Number(price).toLocaleString("ar-EG")} ج.م`;
}

function tone(accent) {
  return TONES[accent] || "tone-cyan";
}

function isNight(date = new Date()) {
  const hour = date.getHours();
  return hour < 6 || hour >= 18;
}

function applyTheme(dark, manual = false) {
  document.documentElement.classList.toggle("dark", dark);
  if (manual) {
    localStorage.setItem(THEME_KEY, dark ? "dark" : "light");
    localStorage.setItem("elmoghazy_theme_part", isNight() ? "night" : "day");
  }
  document.querySelectorAll("[data-theme]").forEach((button) => {
    button.classList.add("is-turn");
    button.innerHTML = dark ? ICONS.moon : ICONS.sun;
    button.setAttribute("aria-label", dark ? "تفعيل الوضع الفاتح" : "تفعيل الوضع الداكن");
    window.setTimeout(() => button.classList.remove("is-turn"), 320);
  });
}

function bindTheme() {
  const dark = document.documentElement.classList.contains("dark");
  applyTheme(dark);
  document.querySelectorAll("[data-theme]").forEach((button) => {
    button.addEventListener("click", () => {
      applyTheme(!document.documentElement.classList.contains("dark"), true);
    });
  });
}

async function currentUser() {
  if (!localStorage.getItem(TOKEN_KEY)) return null;
  try {
    return await api("/api/user");
  } catch (err) {
    const msg = String(err && err.message ? err.message : err);
    // مهلة الشبكة لا تمسح التوكن — وإلا صفحة الشرح تفضل معلّقة بعد تعليق السيرفر
    if (err && err.name === "AbortError") return null;
    if (msg.includes("اتأخر") || msg.includes("Failed to fetch") || msg.includes("NetworkError")) {
      return null;
    }
    localStorage.removeItem(TOKEN_KEY);
    return null;
  }
}

function bindNav(user) {
  document.querySelectorAll("[data-nav]").forEach((nav) => {
    const progress = nav.querySelector("[data-progress] span");
    const onScroll = () => {
      nav.classList.toggle("is-scrolled", window.scrollY > 8);
      if (!progress) return;
      const max = document.documentElement.scrollHeight - window.innerHeight;
      const ratio = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
      progress.style.transform = `scaleX(${ratio})`;
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);

    const menu = nav.querySelector("[data-menu]");
    const drawer = nav.querySelector("[data-drawer]");
    if (menu && drawer) {
      menu.innerHTML = ICONS.menu;
      menu.addEventListener("click", () => {
        const open = drawer.hasAttribute("hidden");
        drawer.toggleAttribute("hidden", !open);
        menu.innerHTML = open ? ICONS.close : ICONS.menu;
        document.body.style.overflow = open ? "hidden" : "";
      });
      drawer.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
          drawer.hidden = true;
          menu.innerHTML = ICONS.menu;
          document.body.style.overflow = "";
        });
      });
    }

    nav.querySelectorAll("[data-guest]").forEach((node) => {
      node.hidden = Boolean(user);
    });
    nav.querySelectorAll("[data-user]").forEach((node) => {
      if (!user) return;
      node.hidden = false;
      node.textContent = user.name;
      node.href = user.role === "teacher" ? "/admin" : "/learn";
    });
    nav.querySelectorAll("[data-logout]").forEach((node) => {
      node.hidden = !user;
      node.addEventListener("click", async () => {
        try {
          await api("/api/logout", { method: "POST" });
        } catch {
          /* token may already be gone */
        }
        localStorage.removeItem(TOKEN_KEY);
        location.href = "/";
      });
    });
  });
}

let ready = false;

function onReady(fn) {
  let done = false;
  const run = (user) => {
    if (done) return;
    done = true;
    fn(user);
  };
  if (ready) {
    Promise.resolve().then(() => run(window.Elmo.user));
    return;
  }
  document.addEventListener("elmo:ready", (event) => run(event.detail), { once: true });
  if (ready) {
    Promise.resolve().then(() => run(window.Elmo.user));
  }
}

function openPop({ title, copy, html, onSubmit }) {
  const pop = document.getElementById("site-pop");
  if (!pop) return;
  document.getElementById("pop-title").textContent = title || "";
  document.getElementById("pop-copy").textContent = copy || "";
  const body = document.getElementById("pop-body");
  const note = document.getElementById("pop-note");
  body.innerHTML = html || "";
  note.hidden = true;
  note.textContent = "";
  note.classList.remove("is-ok");
  pop.hidden = false;
  document.body.style.overflow = "hidden";
  const form = body.querySelector("form");
  if (form && onSubmit) {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      note.hidden = true;
      try {
        await onSubmit(new FormData(form), form, note);
      } catch (err) {
        note.hidden = false;
        note.textContent = err.message || "حصل خطأ.";
      }
    });
  }
  body.querySelector("input, textarea, select")?.focus();
}

function closePop() {
  const pop = document.getElementById("site-pop");
  if (!pop || pop.hidden) return;
  pop.hidden = true;
  document.body.style.overflow = "";
}

document.addEventListener("click", (event) => {
  if (event.target.closest("[data-pop-close]")) closePop();
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closePop();
});

window.Elmo = { api, upload, uploadWithProgress, sendFile, fetchBlob, play, money, tone, MODES, ICONS, currentUser, paintIcons, onReady, openPop, closePop };

function fillFooterGrades() {
  const host = document.getElementById("footer-grades");
  if (!host) return;
  api("/api/grades")
    .then((rows) => {
      host.innerHTML = rows
        .map((grade) => `<a href="/grades/${encodeURIComponent(grade.slug)}">${String(grade.title).replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char]))}</a>`)
        .join("");
    })
    .catch(() => {});
}

paintIcons();
bindTheme();
fillFooterGrades();
currentUser().then((user) => {
  window.Elmo.user = user;
  ready = true;
  try {
    bindNav(user);
  } catch {
    /* شريط التنقل لا يوقف باقي الصفحة */
  }
  document.dispatchEvent(new CustomEvent("elmo:ready", { detail: user }));
});
