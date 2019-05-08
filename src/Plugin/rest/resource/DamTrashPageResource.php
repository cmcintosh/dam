<?php

namespace Drupal\dam\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\dam\Entity\FileCollection;
use Drupal\comment\Entity\Comment;

/**
 * Provides a resources for Trash Page.
 * Get list of files and folder in junk.
 *
 * @RestResource(
 *   id = "trash_files_resource_canonical",
 *   label = @Translation("Trash Files Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/dam/trash_files",
 *     "create" = "/api/dam/trash_files",
 *   }
 * )
 */
class DamTrashPageResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get(Request $request) {
    $query = \Drupal::database()->select('file_extend', 'fe')
      ->fields('fe', ['id', 'name', 'directory']);
    $query->condition('fe.trash', 1, '=');
    $result = $query->execute()->fetchAll();
    $data = [];
    foreach ($result as $key => $file) {
      $data[$file->id]['id'] = $file->id;
      $data[$file->id]['name'] = $file->name;
      $data[$file->id]['directory'] = $file->directory;
    }

    // Return response as Json response.
    $response = new Response();
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
