<?php

namespace Drupal\custom_webform_handlers\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Csrf\CsrfTokenGenerator;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles AJAX saving of Distance Records.
 */
class DistanceSaveController {

  /**
   * Constructs a DistanceSaveController object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly CsrfTokenGenerator $csrfToken,
  ) {}

  /**
   * Factory method for dependency injection.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('csrf_token')
    );
  }

  /**
   * Saves a DistanceRecord entity from an AJAX request.
   */
  public function save(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (!is_array($data)) {
        return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON.'], 400);
      }

      $token = $data['token'] ?? '';
      if (empty($token) || !$this->csrfToken->validate($token, 'custom_webform_handlers.save')) {
        throw new AccessDeniedHttpException('Invalid CSRF token.');
      }

      $from = Xss::filter(trim((string) ($data['from'] ?? '')));
      $to = Xss::filter(trim((string) ($data['to'] ?? '')));
      $distance = Xss::filter(trim((string) ($data['distance'] ?? '')));

      if ($from === '' || $to === '' || $distance === '') {
        return new JsonResponse(['status' => 'error', 'message' => 'Missing required fields.'], 400);
      }

      $record_storage = $this->entityTypeManager->getStorage('distance_record');
      $record = $record_storage->create([
        'from_address' => $from,
        'to_address' => $to,
        'distance' => $distance,
      ]);
      $record->save();

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Record saved successfully.',
        'id' => $record->id(),
      ]);
    }
    catch (AccessDeniedHttpException $e) {
      return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 403);
    }
    catch (\Throwable $e) {
      // Log and hide internal error.
      $this->loggerFactory->get('custom_webform_handlers')
        ->error('Failed to save distance record: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['status' => 'error', 'message' => 'Server error.'], 500);
    }
  }

}