/**
 * JavaScript pour la page de diagnostic IA
 *
 * Gère les interactions utilisateur, animations et tests AJAX
 * Version: 1.0.0
 */

jQuery(document).ready(function ($) {
  // Gérer les formulaires de test avec spinner
  $(".ai-test-form").on("submit", function () {
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    const $loader = $form.find(".ai-loader-container");

    $btn.prop("disabled", true);
    $loader.css("display", "inline-flex");

    // Pour les tests longs, on ajoute un timeout qui ajoute une indication
    if (!$loader.hasClass("small")) {
      setTimeout(function () {
        if ($loader.is(":visible")) {
          $(".ai-loader-text").text(aiDiagnosticL10n.longWait);
        }
      }, 15000); // 15 secondes
    }
  });

  // Gestion des onglets d'export
  $(".ai-export-tab").on("click", function () {
    const tab = $(this).data("tab");

    // Activer l'onglet
    $(".ai-export-tab").removeClass("active");
    $(this).addClass("active");

    // Afficher le contenu correspondant
    $(".ai-export-content").removeClass("active");
    $("#" + tab + "-content").addClass("active");
  });

  // Copier les résultats
  $("#copy-results-btn").on("click", function () {
    // On copie le contenu de l'onglet actif
    const $activeContent = $(".ai-export-content.active textarea");
    $activeContent.select();
    document.execCommand("copy");

    // Animation de confirmation
    $(this).addClass("copy-success");
    $(this).html(
      '<span class="dashicons dashicons-yes"></span> ' + aiDiagnosticL10n.copied
    );

    setTimeout(() => {
      $(this).removeClass("copy-success");
      $(this).html(
        '<span class="dashicons dashicons-clipboard"></span> ' +
          aiDiagnosticL10n.copyResults
      );
    }, 2000);
  });

  // Auto-sélection du textarea lorsqu'on clique dedans
  $(".ai-export-textarea").on("click", function () {
    $(this).select();
  });
});
