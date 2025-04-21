/**
 * Script pour la page de test des prompts IA
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Éléments DOM
    const $testForm = $("#ai-redactor-test-form");
    const $testPrompt = $("#test_prompt");
    const $submitButton = $("#ai-redactor-test-submit");
    const $loadingIndicator = $("#ai-redactor-loading");
    let loadingTimer = null;
    let loadingSeconds = 0;

    // Messages de chargement qui s'affichent en séquence avec timing plus précis
    const loadingMessages = [
      "Initialisation de la requête au modèle d'IA...",
      "Envoi du prompt au modèle d'IA...",
      "Modèle en cours d'analyse de votre demande...",
      "Génération de la réponse en cours...",
      "Le modèle traite votre demande complexe...",
      "Analyse approfondie en cours (peut prendre jusqu'à une minute)...",
      "Traitement détaillé de votre prompt en cours...",
      "Formulation d'une réponse élaborée...",
      "Finalisation de la réponse (patience, nous y sommes presque)...",
      "Les modèles complexes peuvent parfois prendre plus de temps...",
      "Nous continuons à traiter votre demande...",
      "Merci de votre patience, la demande est toujours en cours de traitement...",
    ];

    /**
     * Met à jour le message de chargement et le compteur de temps écoulé
     */
    function updateLoadingMessage() {
      loadingSeconds++;

      // Changer le message principal toutes les 4 secondes
      const messageIndex = Math.min(
        Math.floor(loadingSeconds / 4),
        loadingMessages.length - 1
      );
      $loadingIndicator
        .find(".ai-redactor-loading-text")
        .text(loadingMessages[messageIndex]);

      // Mettre à jour le temps écoulé
      let $progress = $loadingIndicator.find(".ai-redactor-loading-progress");
      if ($progress.length === 0) {
        $progress = $('<div class="ai-redactor-loading-progress"></div>');
        $loadingIndicator.append($progress);
      }

      // Formater le temps écoulé
      const minutes = Math.floor(loadingSeconds / 60);
      const seconds = loadingSeconds % 60;
      const timeText =
        minutes > 0
          ? `${minutes}m ${seconds}s écoulées`
          : `${seconds}s écoulées`;

      $progress.text(timeText);

      // Ajouter la barre de progression si elle n'existe pas encore
      if (
        $loadingIndicator.find(".ai-redactor-progress-bar-container").length ===
        0
      ) {
        const $progressBarContainer = $(
          '<div class="ai-redactor-progress-bar-container"></div>'
        );
        const $progressBar = $('<div class="ai-redactor-progress-bar"></div>');
        $progressBarContainer.append($progressBar);
        $loadingIndicator.append($progressBarContainer);
      }
    }

    /**
     * Gestion de la soumission du formulaire
     */
    $testForm.on("submit", function (e) {
      const prompt = $testPrompt.val().trim();

      if (prompt === "") {
        e.preventDefault();
        alert("Veuillez saisir un prompt avant de le tester.");
        return false;
      }

      // Désactiver le bouton et afficher l'indicateur de chargement
      $submitButton.prop("disabled", true);
      loadingSeconds = 0;

      // Réinitialiser l'indicateur de chargement
      $loadingIndicator.empty();

      // Créer le conteneur pour le spinner et le texte
      const $spinnerContainer = $('<div class="spinner-container"></div>');
      const $spinner = $('<span class="spinner is-active"></span>');
      $spinnerContainer.append($spinner);

      // Ajouter le texte de chargement
      const $newLoadingText = $(
        '<span class="ai-redactor-loading-text"></span>'
      ).text(loadingMessages[0]);
      $spinnerContainer.append($newLoadingText);

      // Ajouter au conteneur principal
      $loadingIndicator.append($spinnerContainer);

      // Positionnement plus visible (au-dessus du champ de texte)
      $loadingIndicator.insertBefore($testPrompt);
      $loadingIndicator.show();

      // S'assurer que l'élément est bien visible dans la page
      $("html, body").animate(
        {
          scrollTop: $loadingIndicator.offset().top - 100,
        },
        200
      );

      // Démarrer le timer pour mettre à jour le message de chargement
      loadingTimer = setInterval(updateLoadingMessage, 1000);

      // Continuer la soumission du formulaire
      return true;
    });

    // Force l'activation du bouton au cas où il serait désactivé
    $submitButton.prop("disabled", false);

    // Écouteur de clic explicite sur le bouton (fallback)
    $submitButton.on("click", function (e) {
      if ($testPrompt.val().trim() !== "") {
        $testForm.submit();
      } else {
        alert("Veuillez saisir un prompt avant de le tester.");
      }
    });

    /**
     * Automatiquement ajuster la hauteur du textarea
     */
    function autoResizeTextarea() {
      $testPrompt.css("height", "auto");
      $testPrompt.css("height", $testPrompt[0].scrollHeight + "px");
    }

    // Initialiser la hauteur du textarea
    $testPrompt.on("input", autoResizeTextarea);

    // Ajuster la hauteur initiale si le textarea contient déjà du texte
    if ($testPrompt.val()) {
      setTimeout(autoResizeTextarea, 100);
    }

    /**
     * Permettre de copier la réponse de l'IA
     */
    $(".ai-redactor-test-response.success").each(function () {
      const $responseContainer = $(this);
      const responseText = $responseContainer.text().trim();

      if (responseText) {
        // Ajouter un bouton de copie
        const $copyButton = $("<button>", {
          type: "button",
          class: "button button-secondary ai-redactor-copy-button",
          text: "Copier la réponse",
          css: {
            "margin-top": "10px",
          },
        });

        $responseContainer.append($copyButton);

        // Gérer le clic sur le bouton de copie
        $copyButton.on("click", function () {
          // Créer un élément temporaire pour copier le texte
          const $temp = $("<textarea>");
          $("body").append($temp);
          $temp.val(responseText).select();

          // Copier le texte
          document.execCommand("copy");

          // Supprimer l'élément temporaire
          $temp.remove();

          // Mettre à jour le texte du bouton
          const $this = $(this);
          const originalText = $this.text();

          $this.text("Copié !");

          setTimeout(function () {
            $this.text(originalText);
          }, 2000);
        });
      }
    });

    /**
     * Afficher/masquer la section de diagnostic
     */
    $(".ai-redactor-diagnostic-link, .ai-redactor-hide-diagnostic").on(
      "click",
      function (e) {
        e.preventDefault();
        window.location.href = $(this).attr("href");
      }
    );

    // Si la page vient d'être chargée et qu'un résultat est affiché, arrêter l'animation
    if ($(".ai-redactor-test-response").length > 0) {
      clearInterval(loadingTimer);
      $loadingIndicator.hide();
    }
  });
})(jQuery);
