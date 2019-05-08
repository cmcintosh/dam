<?php

namespace Drupal\dam\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 */
class PathProcessorFiles implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/system/dam/') === 0) {
      $file_path = preg_replace('|^\/system\/dam\/|', '', $path);
      $updated_url = str_replace('/',';', $file_path);
      return "/system/dam/$updated_url";
    }
    return $path;
  }

}
