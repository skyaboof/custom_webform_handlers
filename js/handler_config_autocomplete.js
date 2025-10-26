(function ($, Drupal) {
    'use strict';
  
    Drupal.behaviors.webformHandlerAutocomplete = {
      attach: function (context) {
        $('.webform-select-autocomplete', context).each(function () {
          if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
              placeholder: Drupal.t('Search for a field...'),
              allowClear: true,
              width: '100%'
            });
          }
        });
      }
    };
  })(jQuery, Drupal);