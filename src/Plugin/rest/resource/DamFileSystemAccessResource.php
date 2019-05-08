<?php

namespace Drupal\dam\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\dam\Entity\FileSystemAccess;

/**
 * Provides a resources for File System Access.
 *
 * @RestResource(
 *   id = "file_system_access_resource_canonical",
 *   label = @Translation("File System Access Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/dam/file_system_access",
 *     "create" = "/api/dam/file_system_access",
 *   }
 * )
 */
class DamFileSystemAccessResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get(Request $request) {
    // Get file collection id from URL.
    $id = $request->query->get('id');
    $data = [];

    $query = \Drupal::entityQuery('file_system_access');
    if (isset($id) && is_numeric($id)) {
      $query->condition('id', $id);
    }
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      foreach ($entity_ids as $id) {
        $fsa_entity = \Drupal::entityTypeManager()->getStorage('file_system_access')->load($id);
        $data[$id] = [
          'id' => $fsa_entity->get('id')->getString(),
          'entity_id' => $fsa_entity->get('entity_id')->getString(),
          'entity_type' => $fsa_entity->get('entity_type')->getString(),
          'can_view' => $fsa_entity->get('can_view')->getValue(),
          'can_write' => $fsa_entity->get('can_write')->getValue(),
          'notify_of_upload' => $fsa_entity->get('notify_of_upload')->getValue(),
        ];
      }
    }
    // Return response as Json response.
    $response = new Response();
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Respond to POST request.
   */
  public function post(array $data) {
    try {
      $file_system_access_entity = FileSystemAccess::create();

      if (isset($data['entity_id']) && is_numeric($data['entity_id'])) {
        $file_system_access_entity->set('entity_id', $data['entity_id']);
      }
      if (isset($data['entity_type'])) {
        $file_system_access_entity->set('entity_type', $data['entity_type']);
      }
      if (isset($data['can_view']) && in_array($data['can_view'], [0, 1])) {
        $file_system_access_entity->set('can_view', $data['can_view']);
      }
      if (isset($data['can_write']) && in_array($data['can_write'], [0, 1])) {
        $file_system_access_entity->set('can_write', $data['can_write']);
      }
      if (isset($data['notify_of_upload']) && in_array($data['notify_of_upload'], [0, 1])) {
        $file_system_access_entity->set('notify_of_upload', $data['notify_of_upload']);
      }
      if (isset($data['user_id'])) {
        $file_system_access_entity->set('user_id', ['target_id' => $data['user_id']]);
      }

      $file_system_access_entity->save();
      // Return response.
      return new ResourceResponse([
        'status' => 200,
        'file_system_access_id' => $file_system_access_entity->id(),
      ]);
    }
    catch (\Exception $e) {
      watchdog_exception('dam - file system access', $e);
      return new ResourceResponse([
        'errors' => $e,
      ],
      ResourceResponse::HTTP_BAD_REQUEST
      );
    }
  }

  /**
   * Respond to PATCH request.
   */
  public function patch($data) {
    if (isset($data['id']) && !is_numeric($data['id'])) {
      // Throw an error....
      return new ResourceResponse(
       [
         'error' => t('You must provide an valid ID when updating a resource.'),
       ],
       ResourceResponse::HTTP_BAD_REQUEST
      );
    }
    $file_system_access_entity = entity_load('file_system_access', $data['id']);
    if (empty($file_system_access_entity)) {
      return new ResourceResponse(
       [
         'error' => t('Resource not found.'),
       ],
       404
      );
    }
    if (isset($data['entity_id']) && is_numeric($data['entity_id'])) {
      $file_system_access_entity->set('entity_id', $data['entity_id']);
    }
    if (isset($data['entity_type'])) {
      $file_system_access_entity->set('entity_type', $data['entity_type']);
    }
    if (isset($data['can_view']) && in_array($data['can_view'], [0, 1])) {
      $file_system_access_entity->set('can_view', $data['can_view']);
    }
    if (isset($data['can_write']) && in_array($data['can_write'], [0, 1])) {
      $file_system_access_entity->set('can_write', $data['can_write']);
    }
    if (isset($data['notify_of_upload']) && in_array($data['notify_of_upload'], [0, 1])) {
      $file_system_access_entity->set('notify_of_upload', $data['notify_of_upload']);
    }
    if (isset($data['user_id'])) {
      $file_system_access_entity->set('user_id', ['target_id' => $data['user_id']]);
    }
    // Save the FileSystemAccess Entity.
    $file_system_access_entity->save();
    // Return reponse.
    return new ModifiedResourceResponse(NULL, 202);
  }

}
