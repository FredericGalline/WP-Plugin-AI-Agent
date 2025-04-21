/**
 * Script JavaScript amélioré pour la page d'administration des connecteurs IA
 * Compatible avec la nouvelle disposition d'onglets verticaux
 */

(function ($) {
  "use strict";

  // Attendre que la page soit chargée
  $(document).ready(function () {
    // Initialiser le premier onglet comme actif si aucun n'est sélectionné
    if ($(".ai-redactor-tabs-nav a.active").length === 0) {
      $(".ai-redactor-tabs-nav a").first().addClass("active");
      var firstTabId = $(".ai-redactor-tabs-nav a").first().attr("href");
      $(firstTabId).addClass("active").show();
    }

    // Gestion des onglets verticaux avec animation
    $(".ai-redactor-tabs-nav a").on("click", function (e) {
      e.preventDefault();

      // Récupérer l'ID cible
      var targetId = $(this).attr("href");

      // Désactiver tous les onglets et contenus
      $(".ai-redactor-tabs-nav a").removeClass("active");

      // Animer la sortie du contenu actif
      $(".ai-redactor-tab-content.active").fadeOut(150, function () {
        // Masquer tous les contenus
        $(".ai-redactor-tab-content").removeClass("active").hide();

        // Activer l'onglet ciblé
        $(targetId).addClass("active").fadeIn(200);

        // Activer l'onglet dans la navigation
        $('.ai-redactor-tabs-nav a[href="' + targetId + '"]').addClass(
          "active"
        );
      });
    });

    // S'assurer que les modèles sont visibles
    $(".ai-redactor-models-list").each(function () {
      $(this).css("display", "grid");
    });

    // Gestion améliorée de l'affichage/masquage des clés API
    $(".ai-redactor-toggle-api-key").on("click", function () {
      var inputField = $(this).prev("input");
      var icon = $(this).find(".dashicons");

      if (inputField.attr("type") === "password") {
        // Afficher la clé
        inputField.attr("type", "text");
        icon.removeClass("dashicons-visibility").addClass("dashicons-hidden");
        $(this).attr(
          "title",
          aiRedactorAdmin.hideKeyText || "Masquer la clé API"
        );
      } else {
        // Masquer la clé
        inputField.attr("type", "password");
        icon.removeClass("dashicons-hidden").addClass("dashicons-visibility");
        $(this).attr(
          "title",
          aiRedactorAdmin.showKeyText || "Afficher la clé API"
        );
      }
    });

    // Effet visuel lors de la sélection d'un modèle
    $('input[name="ai_redactor_active_model"]').on("change", function () {
      // Retirer la classe selected de toutes les cartes
      $(".ai-redactor-model-option").removeClass("selected");

      // Ajouter la classe selected à la carte du modèle sélectionné
      if ($(this).is(":checked")) {
        $(this).closest(".ai-redactor-model-option").addClass("selected");

        // Activer l'onglet correspondant
        var modelId = $(this).val();
        var providerId = modelId.split(":")[0];
        $(
          '.ai-redactor-tabs-nav a[href="#provider-' + providerId + '"]'
        ).trigger("click");
      }
    });

    // Gestion améliorée des tests de connexion avec animations
    $(".ai-redactor-test-connection").on("click", function () {
      var button = $(this);
      var provider = button.data("provider");
      var statusElement = $("#status-" + provider);

      // Masquer le statut précédent avec animation
      statusElement.fadeOut(200, function () {
        // Réinitialiser le statut
        statusElement.removeClass("success error").css("opacity", 0).show();

        // Désactiver le bouton pendant le test
        button.prop("disabled", true);
        button.html(
          '<span class="dashicons dashicons-update-alt spinning"></span> ' +
            aiRedactorAdmin.testingText
        );

        // Effectuer la requête AJAX
        $.ajax({
          url: aiRedactorAdmin.ajaxUrl,
          type: "POST",
          data: {
            action: "ai_redactor_test_connection",
            nonce: aiRedactorAdmin.testNonce,
            provider: provider,
          },
          success: function (response) {
            // Réactiver le bouton
            button.prop("disabled", false);
            button.html(
              '<span class="dashicons dashicons-update"></span> ' +
                aiRedactorAdmin.testButtonText
            );

            // Traiter la réponse
            if (response.success) {
              statusElement
                .addClass("success")
                .html(
                  '<span class="dashicons dashicons-yes-alt"></span> ' +
                    response.data.message
                );
            } else {
              statusElement
                .addClass("error")
                .html(
                  '<span class="dashicons dashicons-warning"></span> ' +
                    response.data.message
                );
            }

            // Afficher le statut avec animation
            statusElement.animate({ opacity: 1 }, 200);
          },
          error: function (xhr, status, error) {
            // Réactiver le bouton
            button.prop("disabled", false);
            button.html(
              '<span class="dashicons dashicons-update"></span> ' +
                aiRedactorAdmin.testButtonText
            );

            // Afficher l'erreur
            statusElement
              .addClass("error")
              .html(
                '<span class="dashicons dashicons-warning"></span> ' +
                  aiRedactorAdmin.errorText +
                  ": " +
                  error
              );

            // Afficher le statut avec animation
            statusElement.animate({ opacity: 1 }, 200);
          },
        });
      });
    });

    // Debug: Vérifier si les éléments existent dans le DOM
    console.log(
      "Nombre d'onglets dans la sidebar:",
      $(".ai-redactor-tabs-nav a").length
    );
    console.log(
      "Nombre de contenus d'onglets:",
      $(".ai-redactor-tab-content").length
    );
    console.log("Nombre de modèles:", $(".ai-redactor-model-option").length);
  });
})(jQuery);
