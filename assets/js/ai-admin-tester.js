/**
 * Script pour la page de test des prompts IA
 */
(function ($) {
  "use strict";

  // Ajouter un message de débogage pour confirmer que le script est chargé
  console.log("Script de test IA chargé");

  $(document).ready(function () {
    // Éléments DOM
    const $testForm = $("#ai-redactor-test-form");
    const $testPrompt = $("#test_prompt");
    const $submitButton = $("#ai-redactor-test-submit");
    const $loadingIndicator = $("#ai-redactor-loading");

    console.log("Formulaire trouvé:", $testForm.length > 0);
    console.log("Bouton trouvé:", $submitButton.length > 0);

    /**
     * Gestion de la soumission du formulaire
     */
    $testForm.on("submit", function (e) {
      console.log("Formulaire soumis");
      const prompt = $testPrompt.val().trim();

      if (prompt === "") {
        e.preventDefault();
        alert("Veuillez saisir un prompt avant de le tester.");
        return false;
      }

      // Empêcher la désactivation du bouton pour le débogage
      // $submitButton.prop("disabled", true);
      $loadingIndicator.show();

      console.log("Soumission du formulaire en cours...");

      // Continuer la soumission du formulaire
      return true;
    });

    // Force l'activation du bouton au cas où il serait désactivé
    $submitButton.prop("disabled", false);

    // Écouteur de clic explicite sur le bouton (fallback)
    $submitButton.on("click", function (e) {
      console.log("Bouton cliqué directement");
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
  });
})(jQuery);
