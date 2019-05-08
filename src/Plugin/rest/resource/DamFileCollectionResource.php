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
 * Provides a resources for File Collection.
 *
 * @RestResource(
 *   id = "file_collection_resource_canonical",
 *   label = @Translation("File Collection Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/dam/file_collection",
 *     "create" = "/api/dam/file_collection",
 *   }
 * )
 */
class DamFileCollectionResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get(Request $request) {
    // Get file collection id from URL.
    $fc_id = $request->query->get('id');
    $fc_data = [];

    $query = \Drupal::entityQuery('file_collection');
    if (isset($fc_id) && is_numeric($fc_id)) {
      $query->condition('id', $fc_id);
    }
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      foreach ($entity_ids as $id) {
        $fc_entity = \Drupal::entityTypeManager()->getStorage('file_collection')->load($id);
        $fc_data[$id] = [
          'id' => $fc_entity->get('id')->getString(),
          'name' => $fc_entity->get('name')->getString(),
          'file' => $fc_entity->get('file')->getValue(),
          'comments' => $fc_entity->get('comments')->getValue(),
        ];
      }
    }
    // Return response as Json response.
    $response = new Response();
    $response->setContent(json_encode($fc_data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Respond to POST request.
   */
  public function post(array $data) {
    try {
      // Name
      if (isset($data['name'])) {
        $file_collection_entity = FileCollection::create([
         'name' => $data['name'],
        ]);
      }
      // File
      if (isset($data['file'])) {
        $file_collection_entity->set('file', ['target_id' => $data['file']]);
      }

      $file_collection_entity->save();
      if ($file_collection_entity->id()) {
        // Save comment.
        if (isset($data['comment_subject'])) {
          $comment_entity = Comment::create([
            'comment_type' => 'file_collection_comment',
            'entity_type' => 'file_collection',
            'entity_id' => $file_collection_entity->id(),
            'field_name' => 'comments',
            'subject' => $data['comment_subject'],
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ]);
          if (isset($data['comment_body'])) {
            $comment_entity->set('comment_body', [
              'summary' => '',
              'value' => $data['comment_body'],
              'format' => 'basic_html',
            ]);
          }
          $comment_entity->save();
        }
      }
      // Return response.
      return new ResourceResponse([
        'status' => 200,
        'file_collection_id' => $file_collection_entity->id(),
      ]);
    }
    catch (\Exception $e) {
      watchdog_exception('dam - file collection', $e);
      return new ResourceResponse([
        'errors' => $e,
      ],
      ResourceResponse::HTTP_BAD_REQUEST
      );
    }
  }

  /**
   * Respond to DELETE request.
   */
  public function delete($data) {
    if (isset($data['id']) && !is_numeric($data['id'])) {
      // Throw an error....
      return new ResourceResponse(
       NULL,
       ResourceResponse::HTTP_BAD_REQUEST
      );
    }
    $file_collection_entity = entity_load('file_collection', $data['id']);

    if (empty($file_collection_entity)) {
      return new ResourceResponse(
       NULL,
       404
      );
    }
    $entity_access = $file_collection_entity->access('delete', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      return new ResourceResponse(
        NULL,
        ResourceResponse::HTTP_FORBIDDEN
      );
    }
    $file_collection_entity->delete();
    // Log deleted File Collection info the DB.
    \Drupal::logger('Dam - File Collection')->info("File Collection #@fc has been deleted", ['@fc' => $data['id']]);
    return new ModifiedResourceResponse(NULL, 204);
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
    $file_collection_entity = entity_load('file_collection', $data['id']);
    if (empty($file_collection_entity)) {
      return new ResourceResponse(
       [
         'error' => t('Resource not found.'),
       ],
       404
      );
    }
    // Name
    if (isset($data['name'])) {
      $file_collection_entity->set('name', $data['name']);
    }
    // File
      if (isset($data['file'])) {
        $file_collection_entity->set('file', ['target_id' => $data['file']]);
      }

    // Save the FileCollection Entity.
    $file_collection_entity->save();
    // Return reponse.
    return new ModifiedResourceResponse(NULL, 202);
  }

}
