(function () {
  const overlay = document.getElementById("tutorial-overlay");
  if (!overlay) return;

  const titleEl = document.getElementById("tutorial-title");
  const textEl = document.getElementById("tutorial-text");
  const stepLabelEl = document.getElementById("tutorial-step-label");
  const btnNext = document.getElementById("tutorial-next");
  const btnPrev = document.getElementById("tutorial-prev");
  const btnClose = document.getElementById("tutorial-close");
  const btnSkip = document.getElementById("tutorial-skip");
  const btnOpen = document.getElementById("btn-open-tutorial");
  const panel = document.getElementById("tutorial-panel");
  const backdrop = overlay.querySelector(".tutorial-backdrop");

  const steps = [
    {
      title: "Bienvenue sur Silver Happy",
      text: "Ce site vous permet de découvrir des services, loisirs et conseils pensés pour les 60 ans et plus.",
      selector: null,
    },
    {
      title: "Découvrir les prestations",
      text: 'Retrouvez ici les principaux services proposés. Cliquez sur "Nos prestations" pour plus de détails.',
      selector: "#prestations",
    },
    {
      title: "Voir les événements",
      text: 'La section "Prochains événements" vous présente les sorties et ateliers organisés pour vous.',
      selector: "#evenements",
    },
    {
      title: "Créer votre compte",
      text: 'En haut à droite, utilisez "Inscription" pour créer votre espace et profiter pleinement des services.',
      selector: 'a[href="signup.php"]',
      noMask: true,
    },
    {
      title: "Se connecter plus tard",
      text: 'Revenez quand vous voulez grâce au bouton "Connexion" qui vous donne accès à votre compte.',
      selector: 'a[href="login.php"]',
      noMask: true,
    },
  ];

  let currentStep = 0;
  let activeElement = null;

  function positionPanel() {
    if (!panel) return;

    panel.style.right = "auto";
    const panelRect = panel.getBoundingClientRect();
    const vw = window.innerWidth || document.documentElement.clientWidth;

    const top = 80;
    let left = (vw - panelRect.width) / 2;
    if (left < 16) left = 16;

    panel.style.top = Math.round(top) + "px";
    panel.style.left = Math.round(left) + "px";
  }

  function setHighlight(selector) {
    if (activeElement) {
      activeElement.classList.remove("tutorial-highlight");
      activeElement = null;
    }
    if (!selector) return;
    const el = document.querySelector(selector);
    if (el) {
      activeElement = el;
      activeElement.classList.add("tutorial-highlight");
      el.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  }

  function renderStep() {
    const step = steps[currentStep];
    if (!step) return;
    titleEl.textContent = step.title;
    textEl.textContent = step.text;
    stepLabelEl.textContent =
      "Étape " + (currentStep + 1) + " sur " + steps.length;

    if (backdrop) {
      backdrop.style.display = step.noMask ? "none" : "block";
    }

    setHighlight(step.selector);
    positionPanel();

    btnPrev.disabled = currentStep === 0;
    btnNext.textContent =
      currentStep === steps.length - 1 ? "Terminer" : "Suivant";
  }

  function openTutorial(force) {
    if (
      !force &&
      window.localStorage &&
      localStorage.getItem("sh_tutorial_done") === "1"
    ) {
      return;
    }
    overlay.classList.remove("d-none");
    panel.classList.remove("d-none");
    document.documentElement.classList.add("tutorial-open");
    currentStep = 0;
    renderStep();
  }

  function closeTutorial(markDone) {
    overlay.classList.add("d-none");
    panel.classList.add("d-none");
    document.documentElement.classList.remove("tutorial-open");
    setHighlight(null);
    if (markDone && window.localStorage) {
      localStorage.setItem("sh_tutorial_done", "1");
    }
  }

  btnNext.addEventListener("click", function () {
    if (currentStep < steps.length - 1) {
      currentStep += 1;
      renderStep();
    } else {
      closeTutorial(true);
    }
  });

  btnPrev.addEventListener("click", function () {
    if (currentStep > 0) {
      currentStep -= 1;
      renderStep();
    }
  });

  btnClose.addEventListener("click", function () {
    closeTutorial(true);
  });

  btnSkip.addEventListener("click", function () {
    closeTutorial(true);
  });

  overlay
    .querySelector(".tutorial-backdrop")
    .addEventListener("click", function () {
      closeTutorial(true);
    });

  if (btnOpen) {
    btnOpen.addEventListener("click", function () {
      openTutorial(true);
    });
  }

  window.addEventListener("resize", function () {
    if (!overlay.classList.contains("d-none")) {
      positionPanel();
    }
  });

  document.addEventListener("DOMContentLoaded", function () {
    openTutorial(false);
  });
})();
