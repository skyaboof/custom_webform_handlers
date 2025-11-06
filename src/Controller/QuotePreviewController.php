<?php

namespace Drupal\custom_webform_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_webform_handlers\Service\QuoteCalculatorService;

/**
 * Returns an AJAX quote preview for moving requests.
 */
class QuotePreviewController extends ControllerBase {

  protected QuoteCalculatorService $quoteCalculator;

  public function __construct(QuoteCalculatorService $quoteCalculator) {
    $this->quoteCalculator = $quoteCalculator;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('custom_webform_handlers.quote_calculator'));
  }

  public function preview(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON'], 400);
    }

    try {
      $quote = $this->quoteCalculator->calculateQuote($data);
      return new JsonResponse([
        'status' => 'success',
        'quote' => $quote,
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
