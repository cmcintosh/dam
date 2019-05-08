<?php

namespace Drupal\dam\StreamWrapper;

use Drupal\Component\Utility\Html;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StreamWrapper\LocalStream;

/**
* Allows us to utilize the existing files and not duplicate them while rendering
* entities in the UI.  If the file does not exist in the configured Digital Asset Manager
* root folder it is created.  If we are updating a file, the existing file will be
* overwritten.
*
* @ingroup dam
*/
class DamStreamWrapper extends LocalStream implements StreamWrapperInterface {

  // We use this trait in order to get nice system-style links
  // for files stored via our stream wrapper.
  use UrlGeneratorTrait;

  /**
   * Instance URI (stream).
   *
   * These streams will be references as 'session://example_target'
   *
   * @var string
   */
  protected $uri;

  /**
   * Pointer to where we are in a directory read.
   *
   * @var int
   */
  protected $directoryPointer;

  /**
   * List of keys in a given directory.
   *
   * @var string[]
   */
  protected $directoryKeys;

  /**
   * The pointer to the next read or write within the session variable.
   *
   * @var int
   */
  protected $streamPointer;

  /**
   * The mode we are currently in.
   *
   * Possible values are FALSE, 'r', 'w'.
   *
   * @var mixed
   */
  protected $streamMode;

  /**
  * Store a config reference.
  */
  protected $config;

  /**
  * File contents.
  */
  protected $contents;

  /**
  * File pointer for writting.
  */
  protected $fp;

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   *   See StreamWrapperInterface for permissible values.
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * Constructor method.
   *
   * Note this cannot take any arguments; PHP's stream wrapper users
   * do not know how to supply them.
   */
  public function __construct() {
    $this->config = \Drupal::config('dam.ftp_settings');
    $this->streamMode = FALSE;
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName() {
    return t('Digital Asset Manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Manages digital assets for Drupal utilizing real files on the server.');
  }

  /**
   * Implements setUri().
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Implements getUri().
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Overrides getExternalUrl().
   *
   * We have set up a helper function and menu entry to provide access to this
   * key via HTTP; normally it would be accessible some other way.
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());

    return $this->url('dam.get_file_download', [
      'filepath' =>  $path,
    ], [
      'absolute' => TRUE,
    ]);
  }

  /**
  * Returns canonical, path for the resource.
  */
  public function realpath() {
    return $this->getLocalPath();
  }

  /**
   * Returns the local path.
   *
   * In our case, the local path is the URI minus the wrapper type. So a URI
   * like 'dam://one/two/three.txt' becomes 'one/two/three.txt'.
   *
   * @param string $uri
   *   Optional URI, supplied when doing a move or rename.
   *
   * @return string
   *   The local path.
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $rootFolder = $this->config->get('dam_root_folder');
    $path = str_replace('dam:/', $this->config->get('dam_root_folder'), $uri);

    return $path;
  }

  /**
   * Opens a stream, as for fopen(), file_get_contents(), file_put_contents().
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   * @param string $mode
   *   The file mode ("r", "wb" etc.).
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string &$opened_path
   *   A string containing the path actually opened.
   *
   * @return bool
   *   Returns TRUE if file was opened successfully. (Always returns TRUE).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
   public function stream_open($uri, $mode, $options, &$opened_path) {
     $this->uri = $uri;
    $path = $this->getLocalPath();
    $this->handle = $options & STREAM_REPORT_ERRORS ? fopen($path, $mode) : @fopen($path, $mode);
    if ((bool) $this->handle && $options & STREAM_USE_PATH) {
      $opened_path = $path;
    }
    return (bool) $this->handle;
   }

   /**
    * Retrieve the underlying stream resource.
    *
    * This method is called in response to stream_select().
    *
    * @param int $cast_as
    *   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling
    *   stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for
    *   other uses.
    *
    * @return resource|false
    *   The underlying stream resource or FALSE if stream_select() is not
    *   supported.
    *
    * @see stream_select()
    * @see http://php.net/manual/streamwrapper.stream-cast.php
    */
    // @codingStandardsIgnoreStart
   public function stream_cast($cast_as) {
     // @codingStandardsIgnoreEnd
    return $this->handle ? $this->handle : FALSE;
   }

   /**
    * Sets metadata on the stream.
    *
    * @param string $path
    *   A string containing the URI to the file to set metadata on.
    * @param int $option
    *   One of:
    *   - STREAM_META_TOUCH: The method was called in response to touch().
    *   - STREAM_META_OWNER_NAME: The method was called in response to chown()
    *     with string parameter.
    *   - STREAM_META_OWNER: The method was called in response to chown().
    *   - STREAM_META_GROUP_NAME: The method was called in response to chgrp().
    *   - STREAM_META_GROUP: The method was called in response to chgrp().
    *   - STREAM_META_ACCESS: The method was called in response to chmod().
    * @param mixed $value
    *   If option is:
    *   - STREAM_META_TOUCH: Array consisting of two arguments of the touch()
    *     function.
    *   - STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner
    *     user/group as string.
    *   - STREAM_META_OWNER or STREAM_META_GROUP: The value of the owner
    *     user/group as integer.
    *   - STREAM_META_ACCESS: The argument of the chmod() as integer.
    *
    * @return bool
    *   Returns TRUE on success or FALSE on failure. If $option is not
    *   implemented, FALSE should be returned.
    *
    * @see http://www.php.net/manual/streamwrapper.stream-metadata.php
    */
   public function stream_metadata($path, $option, $value) {
     $target = $this
      ->getLocalPath($uri);
    $return = FALSE;
    switch ($option) {
      case STREAM_META_TOUCH:
        if (!empty($value)) {
          $return = touch($target, $value[0], $value[1]);
        }
        else {
          $return = touch($target);
        }
        break;
      case STREAM_META_OWNER_NAME:
      case STREAM_META_OWNER:
        $return = chown($target, $value);
        break;
      case STREAM_META_GROUP_NAME:
      case STREAM_META_GROUP:
        $return = chgrp($target, $value);
        break;
      case STREAM_META_ACCESS:
        $return = chmod($target, $value);
        break;
    }
    if ($return) {

      // For convenience clear the file status cache of the underlying file,
      // since metadata operations are often followed by file status checks.
      clearstatcache(TRUE, $target);
    }
    return $return;
   }

   /**
    * Change stream options.
    *
    * This method is called to set options on the stream.
    *
    * @param int $option
    *   One of:
    *   - STREAM_OPTION_BLOCKING: The method was called in response to
    *     stream_set_blocking().
    *   - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
    *     stream_set_timeout().
    *   - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
    *     stream_set_write_buffer().
    * @param int $arg1
    *   If option is:
    *   - STREAM_OPTION_BLOCKING: The requested blocking mode:
    *     - 1 means blocking.
    *     - 0 means not blocking.
    *   - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
    *   - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
    *     STREAM_BUFFER_FULL.
    * @param int $arg2
    *   If option is:
    *   - STREAM_OPTION_BLOCKING: This option is not set.
    *   - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
    *   - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
    *
    * @return bool
    *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
    *   should be returned.
    */
 // @codingStandardsIgnoreStart
   public function stream_set_option($option, $arg1, $arg2) {
     trigger_error('stream_set_option() not supported for local file based stream wrappers', E_USER_WARNING);
     return FALSE;
   }


   /**
    * Truncate stream.
    *
    * Will respond to truncation; e.g., through ftruncate().
    *
    * @param int $new_size
    *   The new size.
    *
    * @return bool
    *   TRUE on success, FALSE otherwise.
    *
    * @todo
    *   Allow truncating the stream.
    *   https://www.drupal.org/project/examples/issues/2992398
    */
   public function stream_truncate($new_size) {
     return ftruncate($this->handle, $new_size);
   }

   /**
    * Support for flock().
    *
    * The session object has no locking capability, so return TRUE.
    *
    * @param int $operation
    *   One of the following:
    *   - LOCK_SH to acquire a shared lock (reader).
    *   - LOCK_EX to acquire an exclusive lock (writer).
    *   - LOCK_UN to release a lock (shared or exclusive).
    *   - LOCK_NB if you don't want flock() to block while locking (not
    *     supported on Windows).
    *
    * @return bool
    *   Always returns TRUE at the present time. (no support)
    *
    * @see http://php.net/manual/en/streamwrapper.stream-lock.php
    */
   public function stream_lock($operation) {
     if (in_array($operation, array(
        LOCK_SH,
        LOCK_EX,
        LOCK_UN,
        LOCK_NB,
      ))) {
        return flock($this->handle, $operation);
      }
      return TRUE;
   }

   /**
    * Support for fread(), file_get_contents() etc.
    *
    * @param int $count
    *   Maximum number of bytes to be read.
    *
    * @return string
    *   The string that was read, or FALSE in case of an error.
    *
    * @see http://php.net/manual/en/streamwrapper.stream-read.php
    */
      public function stream_read($count) {
         return fread($this->handle, $count);
      }

      /**
       * Support for fwrite(), file_put_contents() etc.
       *
       * @param string $data
       *   The string to be written.
       *
       * @return int
       *   The number of bytes written (integer).
       *
       * @see http://php.net/manual/en/streamwrapper.stream-write.php
       */
      public function stream_write($data) {
        return fwrite($this->handle, $data);
      }

      /**
       * Support for feof().
       *
       * @return bool
       *   TRUE if end-of-file has been reached.
       *
       * @see http://php.net/manual/en/streamwrapper.stream-eof.php
       */
      public function stream_eof() {
        return feof($this->handle);
      }

        /**
         * Support for fseek().
         *
         * @param int $offset
         *   The byte offset to got to.
         * @param int $whence
         *   SEEK_SET, SEEK_CUR, or SEEK_END.
         *
         * @return bool
         *   TRUE on success.
         *
         * @see http://php.net/manual/en/streamwrapper.stream-seek.php
         */
        public function stream_seek($offset, $whence = SEEK_SET) {
          // fseek returns 0 on success and -1 on a failure.
          // stream_seek   1 on success and  0 on a failure.
          return !fseek($this->handle, $offset, $whence);
        }

        /**
         * Support for fflush().
         *
         * @return bool
         *   TRUE if data was successfully stored (or there was no data to store).
         *   This always returns TRUE, as this example provides and needs no
         *   flush support.
         *
         * @see http://php.net/manual/en/streamwrapper.stream-flush.php
         */
        public function stream_flush() {
          return fflush($this->handle);
        }

        /**
         * Support for ftell().
         *
         * @return int
         *   The current offset in bytes from the beginning of file.
         *
         * @see http://php.net/manual/en/streamwrapper.stream-tell.php
         */
        public function stream_tell() {
          return ftell($this->handle);
        }

        /**
         * Support for fstat().
         *
         * @return array
         *   An array with file status, or FALSE in case of an error - see fstat()
         *   for a description of this array.
         *
         * @see http://php.net/manual/en/streamwrapper.stream-stat.php
         */
        public function stream_stat() {
          return fstat($this->handle);
        }

        /**
         * Support for fclose().
         *
         * @return bool
         *   TRUE if stream was successfully closed.
         *
         * @see http://php.net/manual/en/streamwrapper.stream-close.php
         */
        public function stream_close() {
          return fclose($this->handle);
        }

        /**
        * @return string.
        */
        public function getDirectoryPath() {
          return $this->config->get('dam_root_folder');
        }

        public function getViaUri($uri) {
          $scheme = file_uri_scheme($uri);
          return $this->getWrapper($scheme, $uri);
        }
}
