(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.distanceCalculator = {
    attach: function (context, settings) {
      const config = drupalSettings.custom_webform_handlers || {};
      const apiKey = config.google_maps_api_key || 'AIzaSyC1zwxDNvmWXrn6Z5nh7bLYSRDJQlhvFvM';
      const saveUrl = config.saveUrl || '';
      const csrfToken = config.csrfToken || '';
      const fieldNames = config.field_names || {};

      if (!apiKey || !saveUrl || !csrfToken || !fieldNames.origin || !fieldNames.destination || !fieldNames.distance) {
        console.warn('Missing required settings for distance calculator.');
        return;
      }

      const elements = once('distance-calculator', `input[name="${fieldNames.origin}"], input[name="${fieldNames.destination}"]`, context);
      if (elements.length === 0) {
        return;
      }

      const fromInput = document.querySelector(`input[name="${fieldNames.origin}"]`);
      const toInput = document.querySelector(`input[name="${fieldNames.destination}"]`);
      const distanceField = document.querySelector(`input[name="${fieldNames.distance}"]`);

      if (!fromInput || !toInput || !distanceField) {
        console.warn('Inputs not found.');
        return;
      }

      // Ensure browser autocomplete is enabled for the fields
      fromInput.setAttribute('autocomplete', 'on');
      toInput.setAttribute('autocomplete', 'on');

      function initAutocompleteAndService() {
        const fromAutocomplete = new google.maps.places.Autocomplete(fromInput, {types: ['address']});
        const toAutocomplete = new google.maps.places.Autocomplete(toInput, {types: ['address']});
        const service = new google.maps.DistanceMatrixService();

        function calculateDistance() {
          const fromValue = fromInput.value.trim();
          const toValue = toInput.value.trim();
          if (fromValue && toValue) {
            service.getDistanceMatrix({
              origins: [fromValue],
              destinations: [toValue],
              travelMode: 'DRIVING',
              unitSystem: google.maps.UnitSystem.METRIC,
            }, (response, status) => {
              if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                const distanceText = response.rows[0].elements[0].distance.text;
                distanceField.value = distanceText;
                debounceSave(fromValue, toValue, distanceText);
              } else {
                console.warn('Distance calculation failed:', status, response);
                distanceField.value = '';
              }
            });
          }
        }

        let saveTimeout;
        function debounceSave(from, to, distance) {
          clearTimeout(saveTimeout);
          saveTimeout = setTimeout(() => saveRecord(from, to, distance), 600);
        }

        function saveRecord(from, to, distance) {
          const payload = { from, to, distance, token: csrfToken };
          fetch(saveUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          })
            .then(res => res.json())
            .then(data => {
              if (data.status !== 'success') {
                console.error('Save failed:', data);
              }
            })
            .catch(err => console.error('Save error:', err));
        }

        fromAutocomplete.addListener('place_changed', () => {
          const place = fromAutocomplete.getPlace();
          if (place.formatted_address) {
            fromInput.value = place.formatted_address;
          }
          calculateDistance();
        });

        toAutocomplete.addListener('place_changed', () => {
          const place = toAutocomplete.getPlace();
          if (place.formatted_address) {
            toInput.value = place.formatted_address;
          }
          calculateDistance();
        });

        fromInput.addEventListener('blur', calculateDistance);
        toInput.addEventListener('blur', calculateDistance);
      }

      if (typeof google !== 'undefined' && google.maps && google.maps.places) {
        initAutocompleteAndService();
        return;
      }

      function waitForGoogleMaps(callback, timeout = 5000) {
        const start = Date.now();
        const interval = setInterval(() => {
          if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            clearInterval(interval);
            callback();
          } else if (Date.now() - start > timeout) {
            clearInterval(interval);
            console.warn('Google Maps API timed out.');
          }
        }, 200);
      }

      if (document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) {
        // Script is loading or loaded elsewhere; wait and try init
        waitForGoogleMaps(initAutocompleteAndService);
        return;
      }

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
      script.async = true;
      script.defer = true;
      script.onload = initAutocompleteAndService;
      document.head.appendChild(script);
    }
  };
})(jQuery, Drupal, drupalSettings);