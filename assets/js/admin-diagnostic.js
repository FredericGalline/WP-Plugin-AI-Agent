/**
 * Script JavaScript pour la page de diagnostic des API IA
 *
 * Gère les animations, les interactions utilisateur et les fonctionnalités
 * dynamiques de la page de diagnostic.
 *
 * @package AI_Redactor
 * @subpackage Services\AI\UI
 */

(function ($) {
  "use strict";

  // Initialisation quand le DOM est chargé
  $(document).ready(function () {
    initStatusCircles();
    initExportTabs();
    initTestForms();
    initCopyButtons();
    initViewDetailsButtons();
  });

  /**
   * Initialise les cercles d'état avec animation
   */
  function initStatusCircles() {
    $(".ai-status-circle").each(function () {
      const percentage = $(this).data("percentage");

      // Mise à jour de la variable CSS pour le gradient circulaire
      $(this).css("--percentage", percentage);

      // Animation du cercle
      $({ animValue: 0 }).animate(
        { animValue: percentage },
        {
          duration: 1000,
          step: function () {
            $(this).css("--percentage", this.animValue);
          }.bind($(this)),
        }
      );
    });
  }

  /**
   * Initialise les onglets d'export
   */
  function initExportTabs() {
    $(".ai-export-tab").on("click", function () {
      const tabId = $(this).data("tab");

      // Activer l'onglet sélectionné
      $(".ai-export-tab").removeClass("active");
      $(this).addClass("active");

      // Afficher le contenu correspondant
      $(".ai-export-content").removeClass("active");
      $("#" + tabId + "-content").addClass("active");
    });
  }

  /**
   * Initialise les formulaires de test
   */
  function initTestForms() {
    $(".ai-test-form").on("submit", function () {
      const $form = $(this);
      const $loader = $form.find(".ai-loader-container");

      // Afficher l'indicateur de chargement
      $loader.css("display", "flex");

      // Désactiver le bouton de soumission
      $form.find('button[type="submit"]').prop("disabled", true);

      // Le formulaire sera soumis normalement
      return true;
    });
  }

  /**
   * Initialise les boutons de copie
   */
  function initCopyButtons() {
    // Bouton de copie des résultats
    $("#copy-results-btn").on("click", function () {
      const text = $("#export-text").val();
      copyToClipboard(text);
      showCopySuccessMessage($(this));
    });

    // Fonction pour copier vers le presse-papier
    function copyToClipboard(text) {
      // Créer un élément temporaire
      const $temp = $("<textarea>");
      $("body").append($temp);
      $temp.val(text).select();

      // Exécuter la commande de copie
      document.execCommand("copy");

      // Supprimer l'élément temporaire
      $temp.remove();
    }

    // Afficher un message de succès
    function showCopySuccessMessage($button) {
      const originalText = $button.html();
      $button.html('<span class="dashicons dashicons-yes"></span> Copié!');

      // Restaurer le texte original après un délai
      setTimeout(function () {
        $button.html(originalText);
      }, 2000);
    }
  }

  /**
   * Initialise les boutons pour afficher/masquer les détails
   */
  function initViewDetailsButtons() {
    $(".ai-view-details-btn").on("click", function () {
      const $details = $(this).closest("tr").next(".ai-details-row");
      $details.toggleClass("expanded");

      if ($details.hasClass("expanded")) {
        $(this).html(
          '<span class="dashicons dashicons-arrow-up-alt2"></span> Masquer les détails'
        );
      } else {
        $(this).html(
          '<span class="dashicons dashicons-arrow-down-alt2"></span> Voir les détails'
        );
      }
    });
  }

  /**
   * Initialise l'animation des graphiques (à implémenter ultérieurement)
   */
  function initCharts() {
    // Cette fonction pourra être développée ultérieurement pour ajouter
    // des graphiques dynamiques si nécessaire
  }
})(jQuery);
