<?php

namespace Drupal\dam\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\dam\Entity\FileDirectory;
use Drupal\dam\Entity\FileSystemAccess;

/**
 * Provides a resources for File Directory.
 *
 * @RestResource(
 *   id = "file_directory_resource_canonical",
 *   label = @Translation("File Directory Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/dam/file_directory",
 *     "create" = "/api/dam/file_directory",
 *   }
 * )
 */
class DamFileDirectoryResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get(Request $request) {
    $id = $request->query->get('id');
    $directory_id = $request->query->get('directory_id');
    $user_id = $request->query->get('user_id');
    $data = [];

    $query = \Drupal::entityQuery('file_directory');
    if (isset($id) && is_numeric($id)) {
      $query->condition('id', $id);
    }
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      foreach ($entity_ids as $entity_id) {
        $isFileSystemAccess = TRUE;
        if (is_numeric($directory_id) && is_numeric($user_id)) {
          $isFileSystemAccess = FALSE;
          if ($directory_id == $entity_id) {
            $isFileSystemAccess = $this->checkFileSystemAccess($directory_id, $user_id);
          }
        }
        else if(is_numeric($user_id)) {
          $isFileSystemAccess = $this->checkFileSystemAccess($entity_id, $user_id);
        }
        if ($isFileSystemAccess) {
          $fd_entity = \Drupal::entityTypeManager()->getStorage('file_directory')->load($entity_id);
          $data[$entity_id] = [
            'id' => $fd_entity->id(),
            'name' => $fd_entity->get('name')->getString(),
            'path' => $fd_entity->get('path')->getString(),
          ];
        }
      }
    }

    // Return response as Json response.
    $response = new Response();
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Checks the user has access to the files and folders.
   *
   * @param int directory_id
   *   File Directory ID.
   * @param int user_id
   *   User Id.
   *
   * @return boolean
   *   Return access status.
   */
  public function checkFileSystemAccess($directory_id, $user_id) {
    // Query to check user has can view permission.
    $query = \Drupal::database()->select('file_system_access', 'fsa')
      ->fields('fsa', ['id']);
    $query->condition('fsa.directory', $directory_id, '=');
    $query->condition('fsa.user_id', $user_id, '=');
    $query->condition('fsa.can_view', 1, '=');
    $result = $query->execute()->fetchCol();
    if (!empty($result)) {
      return TRUE;
    }
    return FALSE;
  }

}
