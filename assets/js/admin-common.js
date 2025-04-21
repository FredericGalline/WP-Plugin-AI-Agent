/**
 * Scripts communs pour l'administration d'AI Redactor
 *
 * Ces fonctionnalités sont utilisées dans plusieurs pages du plugin
 */

(function ($) {
  "use strict";

  // Fonctions utilitaires communes
  var AIRedactorAdmin = {
    /**
     * Initialise les onglets
     * @param {string} container Sélecteur du conteneur des onglets
     */
    initTabs: function (container) {
      var $container = $(container || ".ai-redactor-provider-tabs");
      if (!$container.length) return;

      // Si les onglets sont déjà initialisés avec des URLs, ne rien faire
      if ($container.find('.ai-redactor-tabs-nav a[href*="tab="]').length) {
        return;
      }

      // Gestion des clics sur les onglets
      $container.on("click", '.ai-redactor-tabs-nav a[href="#"]', function (e) {
        e.preventDefault();

        var $tab = $(this).parent();
        var tabId = $tab.data("tab");

        // Activer l'onglet
        $tab.addClass("active").siblings().removeClass("active");

        // Afficher le contenu de l'onglet
        $("#" + tabId)
          .addClass("active")
          .siblings(".ai-redactor-tab")
          .removeClass("active");

        // Sauvegarder l'onglet actif dans localStorage si nécessaire
        if ($container.data("remember-tab")) {
          localStorage.setItem(
            "ai_redactor_active_tab_" + window.location.pathname,
            tabId
          );
        }
      });

      // Restaurer l'onglet actif depuis localStorage si nécessaire
      if ($container.data("remember-tab")) {
        var savedTab = localStorage.getItem(
          "ai_redactor_active_tab_" + window.location.pathname
        );
        if (savedTab) {
          $container
            .find('.ai-redactor-tabs-nav [data-tab="' + savedTab + '"] a')
            .trigger("click");
        }
      }
    },

    /**
     * Initialise les accordéons
     * @param {string} container Sélecteur du conteneur des accordéons
     * @param {boolean} singleOpen Ouvrir un seul accordéon à la fois (défaut: true)
     */
    initAccordion: function (container, singleOpen) {
      var $container = $(container || ".ai-redactor-accordion");
      if (!$container.length) return;

      // Par défaut, un seul accordéon ouvert à la fois
      if (singleOpen === undefined) singleOpen = true;

      $container.on("click", ".ai-redactor-accordion-header", function (e) {
        var $header = $(this);
        var $item = $header.closest(".ai-redactor-accordion-item");
        var $content = $item.find(".ai-redactor-accordion-content");
        var $icon = $header.find(".ai-redactor-accordion-toggle");

        // Empêcher qu'un clic sur un élément interactif dans l'en-tête ferme l'accordéon
        if (
          $(e.target).is(
            "a, button, input, select, .toggle-password, .dashicons-visibility, .dashicons-hidden"
          )
        ) {
          return;
        }

        // Si l'accordéon est déjà ouvert et qu'un seul doit rester ouvert, ne pas le fermer
        if ($item.hasClass("open") && singleOpen) {
          return;
        }

        // Si on veut un seul accordéon ouvert à la fois
        if (singleOpen) {
          // Fermer tous les autres accordéons
          $container
            .find(".ai-redactor-accordion-item")
            .not($item)
            .removeClass("open");
          $container
            .find(".ai-redactor-accordion-content")
            .not($content)
            .slideUp(200);
          $container
            .find(".ai-redactor-accordion-toggle")
            .not($icon)
            .removeClass("dashicons-arrow-up-alt2")
            .addClass("dashicons-arrow-down-alt2");
        }

        // Basculer l'accordéon actuel
        $item.toggleClass("open");
        $content.slideToggle(200);
        $icon.toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
      });

      // Ouvrir le premier accordéon par défaut si aucun n'est ouvert
      if ($container.find(".ai-redactor-accordion-item.open").length === 0) {
        var $firstItem = $container.find(
          ".ai-redactor-accordion-item:first-child"
        );
        $firstItem.addClass("open");
        $firstItem.find(".ai-redactor-accordion-content").show();
        $firstItem
          .find(".ai-redactor-accordion-toggle")
          .removeClass("dashicons-arrow-down-alt2")
          .addClass("dashicons-arrow-up-alt2");
      }
    },

    /**
     * Initialise les basculeurs de visibilité pour les champs de mot de passe
     */
    initPasswordToggles: function () {
      $(document).on("click", ".toggle-password", function () {
        var targetId = $(this).data("target");
        var $input = $("#" + targetId);
        var $icon = $(this).find(".dashicons");

        if ($input.attr("type") === "password") {
          $input.attr("type", "text");
          $icon
            .removeClass("dashicons-visibility")
            .addClass("dashicons-hidden");
        } else {
          $input.attr("type", "password");
          $icon
            .removeClass("dashicons-hidden")
            .addClass("dashicons-visibility");
        }
      });
    },

    /**
     * Initialise les composants UI communs
     */
    init: function () {
      $(document).ready(function () {
        AIRedactorAdmin.initTabs();
        AIRedactorAdmin.initAccordion();
        AIRedactorAdmin.initPasswordToggles();
      });
    },
  };

  // Exposer l'objet pour une utilisation externe
  window.AIRedactorAdmin = AIRedactorAdmin;

  // Initialiser automatiquement
  AIRedactorAdmin.init();
})(jQuery);
