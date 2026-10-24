(function () {
  const links = [...document.querySelectorAll(".nav__links a[href*='#']")];
  if (!links.length) return;
  const map = new Map();
  links.forEach((link) => {
    const id = link.getAttribute("href").split("#")[1];
    const section = id && document.getElementById(id);
    if (section) map.set(section, link);
  });
  if (!map.size) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        const link = map.get(entry.target);
        if (!link) return;
        if (entry.isIntersecting) {
          links.forEach((item) => item.classList.remove("is-active"));
          link.classList.add("is-active");
        }
      });
    },
    { rootMargin: "-40% 0px -50% 0px", threshold: 0 },
  );

  map.forEach((_, section) => observer.observe(section));
})();
