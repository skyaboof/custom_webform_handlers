/**
 * @file
 * Live quote preview integration for the moving quote webform.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';
  
    Drupal.behaviors.quotePreview = {
      attach: function (context, settings) {
        // Ensure we only attach once
        once('quote-preview', 'form.webform-submission-form', context).forEach(function (formEl) {
          const $form = $(formEl);
          const serviceInput = $form.find('select[name="service_type"]');
          const fromInput = $form.find('input[name="origin_address"]');
          const toInput = $form.find('input[name="destination_address"]');
          const previewContainer = $('<div class="quote-preview"><em>Enter addresses and select service to see an estimated quote.</em></div>');
          $form.append(previewContainer);
  
          const endpoint = drupalSettings.custom_webform_handlers?.quotePreviewUrl || '/custom_webform_handlers/quote-preview';
  
          let debounceTimer;
          function debounce(fn, delay) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fn, delay);
          }
  
          function collectData() {
            return {
              service_type: serviceInput.val(),
              origin_address: fromInput.val(),
              destination_address: toInput.val(),
              shipment_weight: $form.find('input[name="shipment_weight"]').val() || '',
            };
          }
  
          function updatePreview() {
            const data = collectData();
            if (!data.service_type || !data.origin_address || !data.destination_address) {
              previewContainer.html('<em>Please select service type and both addresses.</em>');
              return;
            }
  
            previewContainer.html('<em>Calculating quote...</em>');
  
            fetch(endpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(data),
            })
              .then((res) => res.json())
              .then((result) => {
                if (result.status === 'success' && result.quote) {
                  const q = result.quote;
                  previewContainer.html(`
                    <div class="quote-result">
                      <p><strong>Service:</strong> ${q.service_type || 'N/A'}</p>
                      <p><strong>Distance:</strong> ${q.distance_mi} miles</p>
                      <p><strong>Estimated Cost:</strong> $${q.estimated_cost}</p>
                    </div>
                  `);
                } else {
                  previewContainer.html('<p class="error">Could not calculate quote.</p>');
                }
              })
              .catch((err) => {
                console.error('Quote preview error:', err);
                previewContainer.html('<p class="error">Error fetching quote.</p>');
              });
          }
  
          // Trigger updates when these fields change
          serviceInput.on('change', () => debounce(updatePreview, 600));
          fromInput.on('blur keyup change', () => debounce(updatePreview, 800));
          toInput.on('blur keyup change', () => debounce(updatePreview, 800));
        });
      }
    };
  })(jQuery, Drupal, drupalSettings);
  