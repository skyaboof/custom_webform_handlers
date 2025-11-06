/*
 * @file
 * Google Maps integration for moving quote form.
 * Created by: skyaboof
 * Created on: 2025-11-06 02:17:13 UTC
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.movingQuoteGoogleMaps = {
    attach: function (context, settings) {
      const initAutocomplete = () => {
        const originInput = document.getElementById('edit-origin-address');
        const destinationInput = document.getElementById('edit-destination-address');
        const distanceField = document.getElementById('edit-calculated-distance');
        
        if (originInput && destinationInput) {
          const originAutocomplete = new google.maps.places.Autocomplete(originInput);
          const destAutocomplete = new google.maps.places.Autocomplete(destinationInput);
          
          const calculateDistance = () => {
            const origin = originInput.value;
            const destination = destinationInput.value;
            
            if (origin && destination) {
              const service = new google.maps.DistanceMatrixService();
              service.getDistanceMatrix({
                origins: [origin],
                destinations: [destination],
                travelMode: google.maps.TravelMode.DRIVING,
              }, (response, status) => {
                if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                  const distance = response.rows[0].elements[0].distance.text;
                  distanceField.value = distance;
                  $(distanceField).trigger('change');
                }
              });
            }
          };

          originAutocomplete.addListener('place_changed', calculateDistance);
          destAutocomplete.addListener('place_changed', calculateDistance);
        }
      };

      if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${drupalSettings.googleMapsApiKey}&libraries=places&callback=initAutocomplete`;
        script.async = true;
        document.head.appendChild(script);
        window.initAutocomplete = initAutocomplete;
      } else {
        initAutocomplete();
      }
    }
  };
})(jQuery, Drupal);