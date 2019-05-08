<?php

namespace Drupal\dam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\dam\Entity\FileDirectory;
use Drupal\comment\Entity\Comment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\dam\Entity\FileSystemAccess;

/**
 *
 */
class DamController extends ControllerBase {

  /**
   * Callback for assets display.
   */
  public function assets() {

    try {
      $config = \Drupal::config('dam.ftp_settings');
      if ($config->get('dam_root_folder') == '' || $config->get('dam_root_folder') == '/') {
        return [
          '#markup' => $this->t('Please make sure assets folder path is correct.'),
        ];
      }
      FileDirectory::updateDirectory( $config->get('dam_root_folder') );
      $path = $config->get('dam_root_folder');
      // Create the links for switching displays from file or collection view, and links for settings and collapse.
      $control_menu = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => []
      ];

      $action_menu = [];
      $display_menu = [];
      $tree = FileDirectory::getTree( $path, $path, TRUE  );

      // Get current user roles for file permission check
      $userCurrent = \Drupal::currentUser();
      $user = User::load($userCurrent->id());
      $roles = $user->getRoles();

      return [
        '#cache' => ['max-age' => 0,],
        '#attached' => [
          'library' =>[
            'dam/global_style',
            'dam/assets_page'
          ],
          'drupalSettings' => [
            'dam' =>  [
              'folders' => FileDirectory::getTree( $path, $path, FALSE  ),
              'tree' => $tree,
              'activeTree' => $tree,
            ],
            'user' => [
              'roles' => $roles
            ]
          ]
        ],
        'dam_wrapper' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'dam-wrapper'
          ],
          'content' => [
            'dam_view_mode' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-mode-select'
              ],
              'modes' => [
                '#theme' => 'item_list',
                '#items' => [
                    [
                      '#wrapper_attributes' => [
                        'class' => [ 'asset-mode-icons', 'asset-mode', 'active' ]
                      ],
                      '#markup' => '<a href="#thumbnail" data-toggle="tab"><span class="glyphicon glyphicon-th"></span></a>'
                    ],
                    [
                      '#wrapper_attributes' => [
                        'class' => [ 'asset-mode-list', 'asset-mode' ]
                      ],
                      '#markup' => '<a href="#list" data-toggle="tab"><span class="glyphicon glyphicon-list"></span></a>'
                    ],
                ]
              ]
            ],
            'dam_assets_explorer' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-explorer'
              ],
            ],
            'dam_assets_folder_buttons' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-folder-buttons'
              ],
              'modes' => [
                '#theme' => 'item_list',
                '#items' => [
                  [ '#markup' => '<a id="add-folder"><i class="fa fa-plus"></i></a>' ],
                  [ '#markup' => '<a id="delete-folder"><i class="fa fa-minus"></i></a>' ],
                  [ '#markup' => '<a id="move-folder"><i class="fa fa-arrow-right"></i></a>' ]
                ]
              ]
            ],
            'dam_assets_loader' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => 'loader-wrapper'
              ],
              'content' => [
                'dam_assets_load' => [
                  '#type' => 'container',
                  '#attributes' => [
                    'id' => 'loader'
                  ],
                  'content' => [
                    '#markup' => '<img src="../modules/custom/dam/assets/img/Loading_icon.gif">'
                  ]
                ]
              ]
            ],
            'dam_asssets_viewer' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-viewer',
                'title' => t('Select a folder to view files.'),
                'data-view' => ['thumbnail']
              ],
              'content' => [
                '#markup' => '<div id="thumbnail-view"></div><div id="list-view"></div>'
              ]
            ],
            'dam_preview_view_mode' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-preview-view-mode',
              ],
              'modes' => [
                '#theme' => 'item_list',
                '#items' => [
                  [ '#markup' => '<a href="#collapse" data-toggle="tab"><span class="glyphicon glyphicon-forward"></span></a>' ],
                  [ '#markup' => '<a href="#information" data-toggle="tab"><span class="glyphicon glyphicon-info-sign"></span></a>' ],
                  [ '#markup' => '<a href="#comments" data-toggle="tab"><span class="glyphicon glyphicon-comment"></span></a>' ]
                ]
              ]
            ],
            'dam_assets_info_wrapper' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'assets-info-wrapper'
              ],
              'content' => [
                'dam_assets_preview' => [
                  '#type' => 'container',
                  '#attributes' => [
                    'id' => 'dam-assets-preview'
                  ],
                  'content' => [
                    '#markup' => '<h4><a href="#" class="collapse-preview" data-toggle="collapse"><span class="glyphicon glyphicon-triangle-bottom">' . t('Preview') . '</a></h4>' .
                                    '<div class="container preview-pane"><div id="preview-thumbnail"></div>' .
                                      '<div class="preview-details">' .
                                        '<a href="#dam-assets-info" data-toggle="tab">Info</a>' .
                                        '<a href="#dam-assets-share" data-toggle="tab">Download</a>' .
                                        '<a href="#dam-assets-access" data-toggle="tab">Access</a>' .
                                      '</div>' .
                                    '</div>',
                  ]
                ],
                'dam_assets_info' => [
                  '#type' => 'container',
                  '#attributes' => [
                    'id' => 'dam-assets-info',
                  ],
                  'content' => [
                    'dam_assets_info_content' => [
                      '#type' => 'container',
                      '#attributes' => [
                        'id' => 'dam-assets-info-content',
                      ],
                      'content' => [
                        '#markup' => '<div id="info" class="container"></div>',
                      ]
                    ],
                    'dam_assets_label_wrapper' => [
                      '#type' => 'container',
                      '#attributes' => [
                        'id' => 'dam-assets-label-form',
                      ],
                      'content' => DamController::getLabels()
                    ],
                  ]
                ],
                'dam_assets_share' => [
                  '#type' => 'container',
                  '#attributes' => [
                    'id' => 'dam-assets-share'
                  ],
                  'content' => [
                    '#markup' => '<div id="download" class="container"></div>',
                  ]
                ],
                'dam_assets_access' => [
                  '#type' => 'container',
                  '#attributes' => [
                    'id' => 'dam-assets-access'
                  ],
                  'content' => [
                    'dam_users_list' => [
                      '#type' => 'container',
                      '#attributes' => [
                        'id' => 'dam-users-wrapper'
                      ],
                      'content' => DamController::getUserFileAccess()
                    ],
                    'dam_footer_wrapper' => [
                      '#type' => 'container',
                      '#attributes' => [
                        'id' => 'dam-users-footer'
                      ],
                      'content' => [
                        '#markup' => '<a id="add-user" data-toggle="tab"><i class="fa fa-plus"></i> Add User</a>' .
                                     '<a id="add-role" data-toggle="tab"><i class="fa fa-plus"></i> Add Role</a>'
                      ]
                    ],
                  ]
                ],
              ]
            ],
            'dam_assets_comments' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'dam-assets-comments'
              ],
              'content' => [
                '#markup' => '<div id="comments" class="container"></div>'
              ]
            ]
          ]
        ],
      ];
    } catch (\Exception $e) {
      \Drupal::logger('dam')->info("Error : @error", ['@error' => $e->getMessage()]);
      drupal_set_message(t("Data too long for column 'name'"), 'error');
      return [
        '#markup' => $this->t('Some error occurred in getting result.')
      ];
    }
  }

  /**
  * Returns the list of Users and Roles.
  */
  public function getUserFileAccess() {
    $ids = \Drupal::entityQuery('user')
    ->condition('status', 1)
    ->execute();
    $users = User::loadMultiple($ids);

    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();

    $user_list = [];
    foreach ($users as $key => $value) {
      $user_list[$value->id()] = $value->getUsername();
    }

    // Render the list of users in a select element
    $elem['users'] = [
      '#title' => t('Select a User to give permission to this file/folder.'),
      '#type' => 'select',
      '#description' => 'Give permission to a user to access this file/folder.',
      '#options' => $user_list,
      '#attributes' => [
        'id' => 'select-user'
      ]
    ];

    $user_roles = [];
    foreach ($roles as $key => $role) {
      $user_roles[$role->id()] = $role->label();
    }

    // Render the list of roles in a select element
    $elem['roles'] = [
      '#title' => t('Select a Role to give permission to this file/folder.'),
      '#type' => 'select',
      '#description' => 'Give role to a user to access this file/folder.',
      '#options' => $user_roles,
      '#attributes' => [
        'id' => 'select-role'
      ]
    ];

    return [
      'dam_user_form_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'dam-user-form-wrapper'
        ],
        'content' => [
          'user_table' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'dam-users-table'
            ],
            'content' => [
              '#markup' => '<div class="container"></div>'
            ]
          ],
          'add_user' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'dam-add-user'
            ],
            'content' => $elem['users']
          ],
          'add_role' => [
            '#type' => 'container',
            '#attributes' => [
              'id' => 'dam-add-role'
            ],
            'content' => $elem['roles']
          ]
        ]
      ],
    ];
  }

  /**
  * Callback to get the list of available labels
  */
  public function getLabels() {
    $node_storage = \Drupal::entityManager()->getStorage('file_label');
    $output = $node_storage->loadMultiple($nids);

    $labels = [];
    $labels_attributes = [];
    foreach ($output as $key => $label) {
      $labels[$label->get('title')->value] = $label->get('title')->value;
      $labels_attributes[$label->get('title')->value] = array(
        'id' => $key,
        'title' => $label->get('title')->value,
        'color' => $label->get('color')->value
      );

      $elem['labels'][$key] = [
        '#title' => t( $label->get('title')->value ),
        '#type' => 'checkbox',
        '#default_value' => $label->get('color')->value
      ];
    }

    return [
      '#attached' => [
        'drupalSettings' => [
          'dam' =>  [
            'labels' => $labels_attributes
            ]
          ]
      ],
      'dam_labels_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'labels-form-wrapper'
        ],
        'content' => [
            'dam_label_header' => [
              '#type' => 'container',
              'content' => [ '#markup' => '<h5>Select Labels for the File / Folder.</h5>' ]
            ],
            'dam_label_content' => [
              '#type' => 'container',
              '#attributes' => [ 'id' => 'labels-list' ],
              'content' => $elem
            ],
            'dam_label_submit' => [
              '#type' => 'container',
              'content' => ['#markup' => '<a id="submit-labels">Save Labels</a>']
            ]
          ]
      ],
    ];
  }


  /**
   * Callback for rendering the collection display.
   */
  public function collections() {

    return [
      '#markup' => 'collection',
    ];
  }

  /**
   * Returns the FTP log.
   */
  public function log() {
    $config = $this->config('dam.ftp_settings');
    $logFilePath = $config->get('log');
    $header = [
      'sno.' => $this->t('SNo'),
      'log' => $this->t('Log'),
    ];
    $rows = [];
    if (!empty($logFilePath)) {
      $file = file_get_contents($logFilePath);
      if (!empty($file)) {
        $file = explode("\n", $file);
        foreach ($file as $key => $value) {
          $rows[$key] = [
            'sno' => $key + 1,
            'log' => $value,
          ];
        }
      }
    }

    if (count($rows) > 0) {
      $build = [
        '#markup' => $this->t('<a class="button" href="@adminlink">Download FTP logs</a>', [
          '@adminlink' => \Drupal::urlGenerator()
            ->generateFromRoute('dam.ftp_log_zip'),
        ]),
      ];
    }

    $build['log_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No logs found'),
    ];
    // For pagination.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Centralizes the operation of moving files from one directory do another.
   * Additionally, updates all of the related entities.
   */
  public static function moveFiles($old_directory, $new_directory) {}

  /**
   * Centralizes the operation that updates the DAM system based on the physical files.
   */
  public static function refreshSystem() {}

  /**
   * Callback to render the folders for the jqueryFileTree plugin.
   * - @TODO: this is just a placeholder function.
   * Parameters:
   * - dir
   * - multiSelect
   * - onlyFolders
   * - onlyFiles
   * -.
   */
  public function filetree(Request $request) {
    $path = $request->query->get('path');
    $data = dam_generate_file_tree($path);
    $response = new Response();
    $response->setContent($data);
    // $response->headers->set('Content-Type', 'text/xml');.
    return $response;
  }

  /**
   * Create FTP log file Zip.
   */
  public function createFTPLogZip() {
    if (!class_exists('ZipArchive')) {
      throw new \Exception('Requires the "zip" PHP extension to be installed and enabled in order to create ZipArchive of FTP logs.');
      return FALSE;
    }

    $config = $this->config('dam.ftp_settings');
    $logFilePath = $config->get('log');
    $fileName = pathinfo($logFilePath)['basename'];

    // Get real path of file.
    $rootPath = \Drupal::service('file_system')->realpath($logFilePath);

    $filesPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $logFile = $filesPath . '/ftplog.zip';
    $zipFileName = 'ftplog.zip';
    // Initialize archive object.
    $zip = new \ZipArchive();
    // Create the file and throw the error if unsuccessful.
    if ($zip->open($logFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
      exit("cannot open <$logFile>\n");
    }
    $zip->addFile($rootPath, $fileName);
    $zip->close();
    // Send the headers to force download the zip file.
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$zipFileName");
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile("$logFile");
    exit;
  }

  /**
   * Returns Server disk space.
   */
  public function damServerSpace() {
    $dsTotal = disk_total_space("/");
    $dsTotalsBytes = $this->dataSize($dsTotal);
    $dsFree = disk_free_space("/");
    $dsFreeBytes = $this->dataSize($dsFree);
    $dsUsed = $dsTotal - $dsFree;
    $dsUsedBytes = $this->dataSize($dsUsed);
    return [
      '#markup' => $this->t('Total Disk Space: @ds <br/> Remaining Space: @dsfree <br/> Used Space: @dsused', ['@ds' => $dsTotalsBytes, '@dsfree' => $dsFreeBytes, '@dsused' => $dsUsedBytes]),
    ];
  }

  /**
   * Returns the size in readable format.
   */
  public function dataSize($bytes) {
    $Type = ["", "kilo", "mega", "giga", "tera"];
    $counter = 0;
    while ($bytes >= 1024) {
      $bytes /= 1024;
      $counter++;
    }
    return("" . $bytes . " " . $Type[$counter] . "bytes");
  }

  /**
   * Get all junk files.
   */
  public function getDamJunkFiles() {
    $query = \Drupal::database()->select('file_extend', 'fe')
      ->fields('fe', ['id', 'name', 'directory']);
    $query->condition('fe.trash', 1, '=');
    $result = $query->execute()->fetchAll();

    $header = [
      'id' => $this->t('ID'),
      'name' => $this->t('Name'),
      'directory' => $this->t('Directory'),
    ];
    $rows = [];
    foreach ($result as $key => $value) {
      $rows[$key] = [
        'id' => $value->id,
        'name' => $value->name,
        'directory' => $value->directory,
      ];
    }
    $build['junk_files'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No files found'),
    ];
    // For pagination.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Controller for file comments.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function fileComment(Request $request) {
    $file_id = $request->query->get('file');
    $comment = $request->query->get('comment');
    $comment_save = FALSE;
    if (is_numeric($file_id) && isset($comment)) {
      $comment_entity = Comment::create([
        'comment_type' => 'file',
        'entity_type' => 'file',
        'entity_id' => $file_id,
        'field_name' => 'comments',
        'subject' => 'Comment',
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $comment_entity->set('comment_body', [
        'summary' => '',
        'value' => $comment,
        'format' => 'basic_html',
      ]);
      $comment_save = $comment_entity->save();
    }

    $response = [];
    if ($comment_save) {
      $response['message'] = $this->t('Comment is saved.');
      $response['status'] = 200;
    }
    else {
      $response['message'] = $this->t('Not able to save Comment.');
    }

    return new JsonResponse($response);

  }

  /**
   * Get comments of a file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function fileGetComments(Request $request) {
    $file_id = $request->query->get('file');
    $cids = \Drupal::entityQuery('comment')
      ->condition('entity_id', $file_id)
      ->condition('entity_type', 'file')
      ->sort('cid', 'DESC')
      ->execute();
    $comments = [];
    foreach ($cids as $cid) {
      $comment = Comment::load($cid);
      $account = \Drupal\user\Entity\User::load($comment->getOwnerId());
      $username = $account->getUsername();
      $comments[] = [
        'cid' => $cid,
        'uid' => $comment->getOwnerId(),
        'username' => $username,
        'subject' => $comment->get('subject')->value,
        'body' => $comment->get('comment_body')->value,
        'created' => $comment->get('created')->value,
      ];
    }
    return new JsonResponse($comments);
  }

  /**
   * Delete file controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function deleteFile(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $entity_id = $request->query->get('entity_id');
    $entity = entity_load($entity_type, $entity_id);
    if(isset($entity) && $entity->access('delete')) {
      if ($entity_type == 'file') {
        $file = file_load($entity_id);
        $uri = $file->getFileUri();
        $path = drupal_realpath($uri);
        $file_removed = unlink($path);
        if ($file_removed) {
          $response['message'] = $this->t('File deleted successfully.');
          $response['status'] = 200;
          // Delete the file entity as well.
          file_delete($entity_id);
        }
        else {
          $response['message'] = $this->t('Not able to delete the file.');
          $response['status'] = 406;
        }
      }
      elseif($entity_type == 'file_directory') {
        $entity = entity_load('file_directory', $entity_id);
        $path = $entity->dam_path->value;
        $this->damRmdirRecursive($path);
        // Check if folder still exists.
        if (file_exists($path) && is_dir($path)) {
          $response['message'] = $this->t('Not able to delete File Directory');
          $response['status'] = 406;
        }
        else {
          // Delete the entity as well.
          $entity->delete();
          $response['message'] = $this->t('File Directory deleted successfully.');
          $response['status'] = 200;
        }
      }
    }
    else {
      $response['message'] = $this->t('Not Authorized.');
      $response['status'] = 403;
    }

    return new JsonResponse($response);
  }

  /**
   * Delete files and folder recursively.
   *
   * @param string $path
   *   Path of the folder to be deleted.
   */
  public function damRmdirRecursive($dir) {
    $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
    $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $file) {
      if ($file->isDir())
        rmdir($file->getPathname());
      else
        unlink($file->getPathname());
    }
    rmdir($dir);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Json Response.
   */
  public function damUpdateFileLabel(Request $request) {
    $data['entity_type'] = $request->query->get('entity_type');
    $data['entity_id'] = $request->query->get('entity_id');
    $data['label_id'] = $request->query->get('label_id');
    if (strpos($data['label_id'], ':') !== false) {
      $data['label_id'] = explode(':', $data['label_id']);
    }
    // Checking entity_type.
    $validEntityType = ['file', 'file_directory'];
    if (isset($data['entity_type'])  && !in_array($data['entity_type'], $validEntityType) ) {
      // Throw an error.
      $errorMessage = [
        'message' => t('You must provide a valid entity_type when updating a resource.'),
      ];
      return new JsonResponse($errorMessage);
    }

    try {
      $entity = entity_load($data['entity_type'], $data['entity_id']);
      if (empty($entity)) {
        $errorMessage = [
          'message' => t('Resource not found.'),
        ];
        return new JsonResponse($errorMessage);
      }
      // Update file_label id
      $entity->set('file_label', NULL);
      $entity->save();
      if (is_array($data['label_id'])) {
        foreach($data['label_id'] as $label_id) {
          $entity->file_label[] = $label_id;
          $entity->save();
        }
      }
      else {
        $entity->set('file_label', [['target_id' => $data['label_id']]]);
      }
      // Save the entity.
      $entity->save();
      // Update child labels as well if entity is a file directory.
      if($data['entity_type'] == 'file_directory') {
       $this->damUpdateFileLabelDirectory($data);
      }
      $response = [];
      $response['message'] = $this->t('File label updated successfully.');
      $response['status'] = 200;
      // Return reponse.
      return new JsonResponse($response);
    }
    catch (\Exception $e) {
      \Drupal::logger('dam_label')->info("Error : @error", ['@error' => $e->getMessage()]);
      $errorMessage = [
        'message' => t('There is an error in updating the resource.'),
      ];
      watchdog_exception('dam', $e);
      return new JsonResponse($errorMessage);
    }
  }

  /**
   *  Update child files and folders label when parent label is updated.
   *
   * @param $data
   */
  public function damUpdateFileLabelDirectory($data) {
    $query = \Drupal::entityQuery('file')
      ->condition('directory', $data['entity_id']);
    $files = $query->execute();
    $directory_query = \Drupal::entityQuery('file_directory')
      ->condition('directory', $data['entity_id']);
    $file_directories = $directory_query->execute();
    // Update File Labels.
    foreach($files as $fid) {
      $file_entity = entity_load('file', $fid);
      $file_entity->set('file_label', NULL);
      $file_entity->save();
      if (is_array($data['label_id'])) {
        foreach($data['label_id'] as $label_id) {
          $file_entity->file_label[] = $label_id;
          $file_entity->save();
        }
      }
      else {
        $file_entity->set('file_label', [['target_id' => $data['label_id']]]);
      }
      $file_entity->save();
    }
    // Update Directory Labels.
    foreach($file_directories as $directory) {
      $directory_entity = entity_load('file_directory', $directory);
      $directory_entity->set('file_label', NULL);
      $directory_entity->save();
      if (is_array($data['label_id'])) {
        foreach($data['label_id'] as $label_id) {
          $directory_entity->file_label[] = $label_id;
          $directory_entity->save();
        }
      }
      else {
        $directory_entity->set('file_label', [['target_id' => $data['label_id']]]);
      }
      $directory_entity->save();
      $label_data = [
        'entity_type' => 'file_directory',
        'entity_id' => $directory,
        'label_id' => $data['label_id'],
      ];
      $this->damUpdateFileLabelDirectory($label_data);
    }
  }

  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function getFilePermissions(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $entity_id = $request->query->get('entity_id');
    $query = \Drupal::entityQuery('file_system_access')
      ->condition('entity_id', $entity_id);
    if(isset($entity_type) && !empty($entity_type)) {
      $query->condition('entity_type', $entity_type);
    }
    $result = $query->execute();
    if(!empty($result)) {
      foreach ($result as $id) {
        $entity = entity_load('file_system_access', $id);
        if ($entity->get('agent_type')->getString() == 'role') {
          $account_role = user_role_load($entity->get('agent_id')->getString());
          $name = ($account_role) ? $account_role->label() : '';
        }
        elseif ($entity->get('agent_type')->getString() == 'user') {
          $account = user_load($entity->get('agent_id')->getString());
          $name = ($account) ? $account->getUsername() : '';
        }
        $response[$id]['agent_type'] = $entity->get('agent_type')->getString();
        $response[$id]['agent_id'] = $entity->get('agent_id')->getString();
        $response[$id]['agent'] = $name;
        $response[$id]['view'] = $entity->get('can_view')->getString();
        $response[$id]['write'] = $entity->get('can_write')->getString();
        $response[$id]['notify_upload'] = $entity->get('notify_of_upload')->getString();
      }
    }
    else {
      $response['message'] = $this->t('No permissions found for this file');
      $response['status'] = 404;
    }

    return new JsonResponse($response);
  }

  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function setFilePermissions(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $entity_id = $request->query->get('entity_id');
    $agent_type = $request->query->get('agent_type');
    $agent_id = $request->query->get('agent_id');
    $can_view = $request->query->get('can_view');
    $can_write = $request->query->get('can_write');
    $notify = $request->query->get('notify_of_upload');
    // Check if permission already exists for this file.
    $query = \Drupal::entityQuery('file_system_access')
      ->condition('entity_id', $entity_id)
      ->condition('entity_type', $entity_type)
      ->condition('agent_type', $agent_type)
      ->condition('agent_id', $agent_id);
    $result = $query->execute();
    $id = array_shift($result);
    $entity = entity_load('file_system_access', $id);
    if($entity) {
      $previous_view_permission = $entity->get('can_view')->getString();
      $previous_write_permission = $entity->get('can_write')->getString();
      $previous_notify_permission = $entity->get('notify_of_upload')->getString();
      $entity->setAccess('can_view', isset($can_view) ? $can_view : $previous_view_permission);
      $entity->setAccess('can_write', isset($can_write) ? $can_write : $previous_write_permission);
      $entity->setAccess('notify_of_upload', isset($notify) ? $notify : $previous_notify_permission);
      $entity->save();
      $response['message'] = $this->t('File Permissions are updated successfully.');
      $response['status'] = 200;
    }
    else {
      $entity = FileSystemAccess::create([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'agent_type' => $agent_type,
        'agent_id' => $agent_id,
        'can_view' => $can_view,
        'can_write' => $can_write,
        'notify_of_upload' => $notify,
      ]);
      $entity->save();
      if ($entity) {
        $response['message'] = $this->t('File Permissions are saved successfully.');
        $response['status'] = 200;
      }
    }
    return new JsonResponse($response);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return Json Response.
   */
  public function deleteFileSystemAccessEntity(Request $request) {
    $entity_id = $request->query->get('entity_id');

    // Checking entity_id.
    if (!isset($entity_id) && !is_numeric($entity_id)) {
      // Throw an error.
      $errorMessage = [
        'message' => t('You must provide a valid entity_id when deleting a resource.'),
      ];
      return new JsonResponse($errorMessage);
    }
    try {
      $fsaEntity = entity_load('file_system_access', $entity_id);
      if (empty($fsaEntity)) {
        // Throw an error.
        $errorMessage = [
          'message' => t('Entity not found.'),
        ];
        return new JsonResponse($errorMessage);
      }
      $fsaEntity->delete();

      $response = [];
      $response['message'] = $this->t('File system access entity has been deleted successfully.');
      $response['status'] = 200;
      // Return reponse.
      return new JsonResponse($response);
    }
    catch(\Exception $e) {
      \Drupal::logger('dam')->info("Error : @error", ['@error' => $e->getMessage()]);
      $errorMessage = [
        'message' => t('There is an error in deleting the File System Access entity.'),
      ];
      watchdog_exception('dam', $e);
      return new JsonResponse($errorMessage);
    }
  }

  /**
   *  Add file directory endpoint.
   *
   * @param Request $request
   * @return JsonResponse
   * @throws \Exception
   */
  public function addFileDirectory(Request $request) {
    $parent_entity_id = $request->query->get('parent_id');
    $name = $request->query->get('name');
    $parent_entity = entity_load('file_directory', $parent_entity_id);

    $response = [];
    if (!empty($parent_entity)) {
      $parent_path = $parent_entity->dam_path->value;
      $folder_path = $parent_path . '/' . $name;
      // Create directory on file system.
      try {
        mkdir($folder_path, 0777);
        $new_dir = FileDirectory::create([
          'name' => '/' . $name,
          'dam_path' => $folder_path,
        ]);
        $new_dir->save();
        if ($new_dir) {
          $new_dir->directory[] = ['target_id' => $parent_entity_id];
          $new_dir->save();
          $directory = entity_load('file_directory', $new_dir->id());
          $tree = FileDirectory::getTree( $parent_path, $parent_path, FALSE, $directory );

          $response['message'] = $this->t('File directory added successfully.');
          $response['status'] = 200;
          $response['data'] = $tree;
        }
        else {
          $response['message'] = $this->t('Not able to create File directory.');
          $response['status'] = 403;
        }
      }
      catch(\Exception $e) {
        watchdog_exception('dam_directory', $e);
        throw $e;
      }
    }

    return new JsonResponse($response);

  }

}
