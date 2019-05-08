<?php

namespace Drupal\dam\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides a resources to get file tree.
 *
 * @RestResource(
 *   id = "file_tree_resource_canonical",
 *   label = @Translation("File Tree Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/dam/file_tree",
 *     "create" = "/api/dam/file_tree",
 *   }
 * )
 */
class DamFileTreeResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get(Request $request) {
    $path = $request->query->get('path');
    $data['data'] = dam_generate_file_tree($path);

    // Return response as Json response.
    $response = new Response();
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }



}
