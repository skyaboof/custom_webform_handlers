(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.distanceCalculator = {
    attach: function (context, settings) {
      const config = drupalSettings.custom_webform_handlers || {};
      const apiKey = config.google_maps_api_key || ''; // Fallback to hardcoded key
      const saveUrl = config.saveUrl || '/custom_webform_handlers/save';
      const csrfToken = config.csrfToken || '';

      if (!apiKey) {
        console.warn('Google Maps API key is missing in settings. Distance calculation will not work.');
        return;
      }

      // Initialize the Distance Calculator logic after Maps loads.
      function initDistanceCalculator() {
        once('distance-calculator', 'input[name="origin_address"], input[name="destination_address"]', context).forEach((element) => {
          if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.error('Google Maps Places API is not loaded.');
            return;
          }

          const fromInput = document.querySelector('input[name="origin_address"]');
          const toInput = document.querySelector('input[name="destination_address"]');
          const distanceField = document.querySelector('input[name="calculated_distance"]');

          if (!fromInput || !toInput || !distanceField) {
            console.warn('Inputs not found, ensure webform fields are correctly named.');
            return;
          }

          const fromAutocomplete = new google.maps.places.Autocomplete(fromInput);
          const toAutocomplete = new google.maps.places.Autocomplete(toInput);
          const service = new google.maps.DistanceMatrixService();

          function calculateDistance() {
            if (fromInput.value.trim() && toInput.value.trim()) {
              service.getDistanceMatrix({
                origins: [fromInput.value.trim()],
                destinations: [toInput.value.trim()],
                travelMode: 'DRIVING',
                unitSystem: google.maps.UnitSystem.METRIC,
              }, function (response, status) {
                if (
                  status === 'OK' &&
                  response.rows &&
                  response.rows[0] &&
                  response.rows[0].elements &&
                  response.rows[0].elements[0] &&
                  response.rows[0].elements[0].status === 'OK'
                ) {
                  const distanceText = response.rows[0].elements[0].distance.text;
                  distanceField.value = distanceText;
                  debounceSave(fromInput.value.trim(), toInput.value.trim(), distanceText);
                } else {
                  console.warn('DistanceMatrix result not OK', status, response);
                  distanceField.value = 'Error calculating distance';
                }
              });
            }
          }

          let saveTimeout = null;
          function debounceSave(from, to, distance) {
            if (saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => saveRecord(from, to, distance), 600);
          }

          function saveRecord(from, to, distance) {
            const payload = {
              from: from,
              to: to,
              distance: distance,
              token: csrfToken
            };

            fetch(saveUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'same-origin',
              body: JSON.stringify(payload)
            })
              .then(res => res.json())
              .then(data => {
                if (data.status === 'success') {
                  console.log('Distance record saved successfully');
                } else {
                  console.error('Save failed', data);
                }
              })
              .catch(err => console.error('AJAX save error', err));
          }

          fromAutocomplete.addListener('place_changed', calculateDistance);
          toAutocomplete.addListener('place_changed', calculateDistance);

          fromInput.addEventListener('blur', () => calculateDistance());
          toInput.addEventListener('blur', () => calculateDistance());
        });
      }

      // Load the Google Maps script dynamically (once).
      if (typeof google === 'undefined' || !google.maps) {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
        script.async = true;
        script.defer = true;
        script.onload = initDistanceCalculator;
        document.head.appendChild(script);
      } else {
        initDistanceCalculator();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
