(function () {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) return;
  document.documentElement.classList.add("has-motion");

  const seen = new WeakSet();
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-in");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.18 },
  );

  function watch(root) {
    const scope = root && root.matches && root.matches(".stagger") ? [root] : (root || document).querySelectorAll(".stagger");
    scope.forEach((node) => {
      if (seen.has(node)) return;
      seen.add(node);
      observer.observe(node);
    });
  }

  window.Elmo = window.Elmo || {};
  window.Elmo.reveal = watch;
  watch(document);
})();
