<?php

  namespace Drupal\custom_webform_handlers\Entity;

  use Drupal\Core\Entity\ContentEntityBase;
  use Drupal\Core\Entity\EntityTypeInterface;
  use Drupal\Core\Field\BaseFieldDefinition;

  /**
   * Defines the Distance Record entity.
   *
   * @ContentEntityType(
   *   id = "distance_record",
   *   label = @Translation("Distance Record"),
   *   base_table = "distance_record",
   *   entity_keys = {
   *     "id" = "id",
   *     "uuid" = "uuid",
   *     "label" = "from_address"
   *   },
   *   handlers = {
   *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
   *     "form" = {
   *       "default" = "Drupal\Core\Entity\ContentEntityForm"
   *     }
   *   },
   *   admin_permission = "administer distance records",
   *   links = {
   *     "canonical" = "/admin/distance-record/{distance_record}",
   *     "edit-form" = "/admin/distance-record/{distance_record}/edit",
   *     "delete-form" = "/admin/distance-record/{distance_record}/delete",
   *     "collection" = "/admin/distance-records"
   *   }
   * )
   */
  class DistanceRecord extends ContentEntityBase {

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
      $fields = parent::baseFieldDefinitions($entity_type);

      $fields['from_address'] = BaseFieldDefinition::create('string')
        ->setLabel(t('From Address'))
        ->setRequired(TRUE);

      $fields['to_address'] = BaseFieldDefinition::create('string')
        ->setLabel(t('To Address'))
        ->setRequired(TRUE);

      $fields['distance'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Distance'))
        ->setDescription(t('Calculated driving distance.'));

      $fields['created'] = BaseFieldDefinition::create('created')
        ->setLabel(t('Created'));

      return $fields;
    }

  }