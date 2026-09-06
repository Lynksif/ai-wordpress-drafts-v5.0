(function () {
  "use strict";

  function ready(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
      return;
    }
    callback();
  }

  function initAccordions(root) {
    root.querySelectorAll("[data-g3ai-accordion]").forEach(function (button, index) {
      var targetId = button.getAttribute("data-g3ai-target") || button.getAttribute("aria-controls");
      var panel = targetId ? root.querySelector("#" + CSS.escape(targetId)) : button.nextElementSibling;
      if (!panel) return;

      if (!panel.id) panel.id = "g3ai-accordion-panel-" + index;
      button.setAttribute("aria-controls", panel.id);
      button.setAttribute("aria-expanded", button.getAttribute("aria-expanded") === "true" ? "true" : "false");
      panel.hidden = button.getAttribute("aria-expanded") !== "true";

      button.addEventListener("click", function () {
        var expanded = button.getAttribute("aria-expanded") === "true";
        button.setAttribute("aria-expanded", expanded ? "false" : "true");
        panel.hidden = expanded;
      });
    });
  }

  function initFilters(root) {
    var groups = {};
    root.querySelectorAll("[data-g3ai-filter]").forEach(function (button) {
      var group = button.getAttribute("data-g3ai-group") || "default";
      if (!groups[group]) groups[group] = [];
      groups[group].push(button);

      button.addEventListener("click", function () {
        var value = button.getAttribute("data-g3ai-filter") || "all";
        groups[group].forEach(function (item) {
          var active = item === button;
          item.classList.toggle("is-active", active);
          item.setAttribute("aria-pressed", active ? "true" : "false");
        });

        root.querySelectorAll('[data-g3ai-item][data-g3ai-group="' + group + '"]').forEach(function (item) {
          var values = (item.getAttribute("data-g3ai-filter-value") || "").split(",").map(function (entry) {
            return entry.trim();
          });
          item.classList.toggle("g3ai-is-hidden", value !== "all" && values.indexOf(value) === -1);
        });
      });
    });
  }

  function initTabs(root) {
    root.querySelectorAll("[data-g3ai-tabs]").forEach(function (tabs, setIndex) {
      var buttons = Array.prototype.slice.call(tabs.querySelectorAll("[data-g3ai-tab]"));
      var panels = Array.prototype.slice.call(tabs.querySelectorAll("[data-g3ai-panel]"));
      if (!buttons.length || !panels.length) return;

      function activate(button) {
        var target = button.getAttribute("data-g3ai-tab");
        buttons.forEach(function (item) {
          var active = item === button;
          item.classList.toggle("is-active", active);
          item.setAttribute("aria-selected", active ? "true" : "false");
          item.setAttribute("tabindex", active ? "0" : "-1");
        });
        panels.forEach(function (panel) {
          panel.hidden = panel.getAttribute("data-g3ai-panel") !== target;
        });
      }

      buttons.forEach(function (button, buttonIndex) {
        if (!button.id) button.id = "g3ai-tab-" + setIndex + "-" + buttonIndex;
        button.setAttribute("role", "tab");
        button.addEventListener("click", function () { activate(button); });
        button.addEventListener("keydown", function (event) {
          if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
          event.preventDefault();
          var next = event.key === "ArrowRight" ? buttonIndex + 1 : buttonIndex - 1;
          if (next < 0) next = buttons.length - 1;
          if (next >= buttons.length) next = 0;
          buttons[next].focus();
          activate(buttons[next]);
        });
      });

      panels.forEach(function (panel) { panel.setAttribute("role", "tabpanel"); });
      activate(buttons.find(function (button) { return button.classList.contains("is-active"); }) || buttons[0]);
    });
  }

  ready(function () {
    document.querySelectorAll(".g3ai-article").forEach(function (root) {
      initAccordions(root);
      initFilters(root);
      initTabs(root);
    });
  });
}());

