/**
 * CDG Core Admin Scripts
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Toggle sub-options visibility based on parent checkbox

    // Post Rename toggle
    $('input[name="enable_post_rename"]')
      .on("change", function () {
        $(this)
          .closest("td")
          .find('.cdg-sub-options, div[style*="margin"]')
          .toggle(this.checked);
      })
      .trigger("change");

    // Documentation toggle
    $('input[name="enable_documentation"]')
      .on("change", function () {
        $(this)
          .closest("td")
          .find('.cdg-sub-options, div[style*="margin"]')
          .toggle(this.checked);
      })
      .trigger("change");

    // CPT Widgets toggle
    $('input[name="enable_cpt_widgets"]')
      .on("change", function () {
        $(this)
          .closest("td")
          .find('.cdg-sub-options, div[style*="margin"]')
          .toggle(this.checked);
      })
      .trigger("change");

    // Project Rename toggle
    $('input[name="enable_project_rename"]')
      .on("change", function () {
        $(this)
          .closest("td")
          .find('.cdg-sub-options, div[style*="margin"]')
          .toggle(this.checked);
      })
      .trigger("change");

    // Enable limited input when limited radio is selected
    $('input[name="post_revisions_mode"]')
      .on("change", function () {
        var limitInput = $('input[name="post_revisions_limit"]');
        limitInput.prop("disabled", this.value !== "limited");
      })
      .filter(":checked")
      .trigger("change");

    // Toggle SVG admin-only option visibility
    $('input[name="enable_svg_uploads"]')
      .on("change", function () {
        var adminOnlyRow = $('input[name="svg_admin_only"]').closest("tr");
        if (this.checked) {
          adminOnlyRow.show();
        } else {
          adminOnlyRow.hide();
        }
      })
      .trigger("change");

    // Hide Projects disables Project Rename options (visual feedback only)
    $('input[name="hide_divi_projects"]')
      .on("change", function () {
        var projectRenameCheckbox = $('input[name="enable_project_rename"]');
        var projectRenameSection = projectRenameCheckbox.closest("td").find('div[style*="margin"]');

        if (this.checked) {
          // Visually disable the rename options when hiding
          projectRenameCheckbox.prop("disabled", true);
          projectRenameSection.css("opacity", "0.5");
        } else {
          // Re-enable when not hiding
          projectRenameCheckbox.prop("disabled", false);
          projectRenameSection.css("opacity", "1");
        }
      })
      .trigger("change");
  });
})(jQuery);
