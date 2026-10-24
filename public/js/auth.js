const form = document.getElementById("auth-form");
const error = document.getElementById("auth-error");
const done = document.getElementById("auth-done");
const toggle = document.getElementById("toggle-pass");
const password = form.querySelector('[name="password"]');
const login = form.dataset.mode === "login";
let role = new URLSearchParams(location.search).get("as") === "teacher" ? "teacher" : "student";

function paintRole() {
  if (!login) return;
  document.querySelectorAll("[data-role]").forEach((button) => {
    const on = button.dataset.role === role;
    button.classList.toggle("is-on", on);
    button.setAttribute("aria-selected", on ? "true" : "false");
  });
  document.querySelectorAll("[data-role-copy]").forEach((block) => {
    block.hidden = block.dataset.roleCopy !== role;
  });
  document.querySelectorAll("[data-login]").forEach((field) => {
    const on = field.dataset.login === role;
    field.hidden = !on;
    field.querySelector("input").required = on;
  });
  const copy = document.getElementById("auth-card-copy");
  if (copy) copy.textContent = role === "teacher" ? "ادخل ببريد المدرس وكلمة المرور" : "ادخل برقم تليفونك وكلمة المرور";
  const signup = document.getElementById("auth-switch");
  if (signup) signup.hidden = role === "teacher";
}

document.querySelectorAll("[data-role]").forEach((button) => {
  button.addEventListener("click", () => {
    role = button.dataset.role;
    const url = new URL(location.href);
    if (role === "teacher") url.searchParams.set("as", "teacher");
    else url.searchParams.delete("as");
    history.replaceState(null, "", url);
    paintRole();
  });
});
paintRole();

if (login) {
  Elmo.onReady((user) => {
    if (!user) return;
    location.replace(user.role === "teacher" ? "/admin" : "/learn");
  });
}

toggle.addEventListener("mousedown", (event) => {
  event.preventDefault();
});
toggle.addEventListener("click", (event) => {
  event.preventDefault();
  event.stopPropagation();
  const show = password.type === "password";
  password.type = show ? "text" : "password";
  toggle.innerHTML = Elmo.ICONS[show ? "eyeOff" : "eye"];
  toggle.dataset.ready = "1";
  toggle.setAttribute("aria-label", show ? "إخفاء كلمة المرور" : "إظهار كلمة المرور");
});

const gradeSelect = form.querySelector('[name="grade"]');
if (gradeSelect) {
  Elmo.api("/api/grades")
    .then((rows) => {
      rows.forEach((grade) => {
        const option = document.createElement("option");
        option.value = grade.slug;
        option.textContent = grade.title;
        gradeSelect.append(option);
      });
    })
    .catch(() => {});
}

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  error.hidden = true;
  const data = new FormData(form);
  const button = form.querySelector('[type="submit"]');
  const label = button.querySelector("[data-label]");
  button.disabled = true;
  label.textContent = "جارٍ التنفيذ...";
  try {
    const login = form.dataset.mode === "login";
    const result = await Elmo.api(login ? "/api/login" : "/api/register", {
      method: "POST",
      body: JSON.stringify(
        login
          ? (() => {
              const secret = data.get("password");
              const email = String(data.get("email") || "").trim();
              const phone = String(data.get("phone") || "").trim();
              if (role === "teacher" || email.includes("@") || phone.includes("@")) {
                return { email: email || phone, password: secret };
              }
              return { phone, password: secret };
            })()
          : {
              first_name: data.get("first_name"),
              second_name: data.get("second_name"),
              phone: data.get("phone"),
              parent_phone: data.get("parent_phone"),
              password: data.get("password"),
              grade: data.get("grade"),
            },
      ),
    });
    localStorage.setItem("elmoghazy_token", result.token);
    location.href = result.user?.role === "teacher" ? "/admin" : "/learn";
  } catch (err) {
    error.hidden = false;
    error.textContent = err.message;
    button.disabled = false;
    label.textContent = form.dataset.mode === "login" ? "دخول" : "إنشاء الحساب";
  }
});
