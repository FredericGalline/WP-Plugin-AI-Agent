/**
 * Script pour la page des paramètres éditoriaux
 */
(function ($) {
  "use strict";

  // Initialisation au chargement du document
  $(document).ready(function () {
    // Initialisation des onglets
    initTabs();

    // Initialisation des échelles de ton
    initToneScales();

    // Initialisation de l'aperçu
    initPreview();

    // Gestion de la réinitialisation
    initReset();
  });

  /**
   * Initialise la navigation par onglets
   */
  function initTabs() {
    $(".ai-redactor-settings-nav a").on("click", function (e) {
      e.preventDefault();

      // Mise à jour de la navigation
      $(".ai-redactor-settings-nav a").removeClass("active");
      $(this).addClass("active");

      // Affichage de la section correspondante
      var targetId = $(this).attr("href").substring(1);
      $(".ai-redactor-form-section").removeClass("active");
      $('.ai-redactor-form-section[data-tab="' + targetId + '"]').addClass(
        "active"
      );
    });
  }

  /**
   * Initialise les curseurs d'échelles de ton
   */
  function initToneScales() {
    // Mettre à jour les valeurs des échelles
    $(".ai-redactor-scale-slider").on("input", function () {
      var id = $(this).attr("id");
      var value = $(this).val();

      // Mettre à jour l'affichage
      $("#" + id + "_value").text(value);

      // Mettre à jour les données dans le champ caché
      updateToneScalesData();

      // Mettre à jour l'aperçu
      updatePreview();
    });
  }

  /**
   * Met à jour le champ caché avec les valeurs des échelles
   */
  function updateToneScalesData() {
    var scalesData = {
      formality: $("#formality_scale").val(),
      humor: $("#humor_scale").val(),
      enthusiasm: $("#enthusiasm_scale").val(),
    };

    $("#tone_scales_data_input").val(JSON.stringify(scalesData));
  }

  /**
   * Initialise la fonctionnalité d'aperçu
   */
  function initPreview() {
    // Écouter les changements sur tous les champs
    $("select, textarea").on("change input", function () {
      updatePreview();
    });

    // Affichage/masquage de l'aperçu complet
    $("#show_complete_prompt").on("click", function () {
      var $preview = $("#complete_editorial_preview");
      var $button = $(this);

      if ($preview.is(":visible")) {
        $preview.slideUp(200);
        $button.text("Afficher la ligne éditoriale complète");
      } else {
        generateCompletePreview();
        $preview.slideDown(200);
        $button.text("Masquer la ligne éditoriale complète");
      }
    });

    // Générer l'aperçu initial
    updatePreview();
  }

  /**
   * Met à jour les aperçus
   */
  function updatePreview() {
    // Aperçu ton et style
    var toneStyleHTML =
      "<p><strong>Ton:</strong> " +
      $("#tone_type option:selected").text() +
      "</p>";
    toneStyleHTML +=
      "<p><strong>Style:</strong> " +
      $("#writing_style_type option:selected").text() +
      "</p>";
    toneStyleHTML +=
      "<p><strong>Longueur:</strong> " +
      $("#text_length option:selected").text() +
      "</p>";

    // Aperçu audience
    var audienceHTML =
      "<p><strong>Niveau d'expertise:</strong> " +
      $("#expertise_level option:selected").text() +
      "</p>";
    if ($("#target_audience").val()) {
      audienceHTML +=
        "<p>" + $("#target_audience").val().substring(0, 100) + "...</p>";
    } else {
      audienceHTML += "<p><em>Aucune information d'audience spécifiée</em></p>";
    }

    // Aperçu structure
    var structureHTML =
      "<p><strong>Paragraphes:</strong> " +
      $("#paragraph_style option:selected").text() +
      "</p>";
    if ($("#structure").val()) {
      structureHTML +=
        "<p>" + $("#structure").val().substring(0, 100) + "...</p>";
    } else {
      structureHTML += "<p><em>Aucune consigne de structure définie</em></p>";
    }

    // Mettre à jour les conteneurs
    $("#tone_style_preview").html(toneStyleHTML);
    $("#audience_preview").html(audienceHTML);
    $("#structure_preview").html(structureHTML);
  }

  /**
   * Génère l'aperçu complet de la ligne éditoriale
   */
  function generateCompletePreview() {
    var promptText = "";

    // Ton et style
    promptText += "== TON ET STYLE ==\n";
    promptText +=
      "Type de ton: " + $("#tone_type option:selected").text() + "\n";

    // Échelles de ton
    promptText += "Formalité: " + $("#formality_scale").val() + "/10\n";
    promptText += "Humour: " + $("#humor_scale").val() + "/10\n";
    promptText += "Enthousiasme: " + $("#enthusiasm_scale").val() + "/10\n";

    if ($("#tone").val()) {
      promptText += "Instructions personnalisées: " + $("#tone").val() + "\n";
    }
    promptText += "\n";

    // Style d'écriture
    promptText += "== STYLE D'ÉCRITURE ==\n";
    promptText +=
      "Style principal: " +
      $("#writing_style_type option:selected").text() +
      "\n";
    promptText +=
      "Longueur préférée: " + $("#text_length option:selected").text() + "\n";

    if ($("#style").val()) {
      promptText += "Instructions complémentaires: " + $("#style").val() + "\n";
    }
    promptText += "\n";

    // Audience
    promptText += "== PUBLIC CIBLE ==\n";
    promptText +=
      "Niveau d'expertise: " +
      $("#expertise_level option:selected").text() +
      "\n";

    if ($("#target_audience").val()) {
      promptText += $("#target_audience").val() + "\n";
    }
    promptText += "\n";

    // Structure
    promptText += "== STRUCTURE ==\n";
    promptText +=
      "Style de paragraphes: " +
      $("#paragraph_style option:selected").text() +
      "\n";

    if ($("#structure").val()) {
      promptText += $("#structure").val() + "\n";
    }
    promptText += "\n";

    // Mots à privilégier
    if ($("#preferred_keywords").val().trim()) {
      promptText += "== MOTS/EXPRESSIONS À PRIVILÉGIER ==\n";
      promptText += $("#preferred_keywords").val() + "\n\n";
    }

    // Mots à éviter
    if ($("#avoided_keywords").val().trim()) {
      promptText += "== MOTS/EXPRESSIONS À ÉVITER ==\n";
      promptText += $("#avoided_keywords").val() + "\n\n";
    }

    // Instructions avancées
    if ($("#advanced_instructions").val().trim()) {
      promptText += "== INSTRUCTIONS SUPPLÉMENTAIRES ==\n";
      promptText += $("#advanced_instructions").val();
    }

    // Mettre à jour l'aperçu
    $("#complete_editorial_preview pre").text(promptText);
  }

  /**
   * Initialise la fonctionnalité de réinitialisation
   */
  function initReset() {
    $("#reset_editorial_settings").on("click", function () {
      if (
        confirm(
          "Êtes-vous sûr de vouloir réinitialiser tous les paramètres de ligne éditoriale ?"
        )
      ) {
        // Réinitialiser le formulaire
        $(".ai-redactor-editorial-form")[0].reset();

        // Réinitialiser les curseurs
        $("#formality_scale, #humor_scale, #enthusiasm_scale")
          .val(5)
          .trigger("input");

        // Mettre à jour l'aperçu
        updatePreview();
      }
    });
  }
})(jQuery);
