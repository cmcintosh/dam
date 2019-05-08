(function($) {
  'use strict';

  Drupal.behaviors.dam_label_settings = {
    attach: function(context, settings) {

      $('button[name="add-group"]').on('click', function() {
        $('.add-group--wrapper').toggle();
      });

      $('button[name="add-label"]').on('click', function() {
        $(this).parent().parent().find('.add-label--wrapper').toggle();
      });

    }
  }

})(jQuery);
