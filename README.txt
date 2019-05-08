CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The DAM or Digital Asset Manager module provides the ability to operate a fully functional and feature rich
digital assets management system from within Drupal 8.  The platform includes many of the features you would find
in a standalone DAM system.

* This module will create system users used by vsFTP to allow for media file uploads.

* For a full description of the module, visit this page:
https://www.drupal.org/project/dam

* To submit bug reports and feature suggestions, or to track changes:
https://github.com/cmcintosh/dam/issues


REQUIREMENTS
------------

This module will require that you setup Drupal 8's private file system. Along with several contrib modules.
  - color_field:color_field
  - comment:comment
  - entity_reference_revisions:entity_reference_revisions



RECOMMENDED MODULES
-------------------

Media - https://www.drupal.org/project/media


INSTALLATION
------------

Creates the ability to manage Digital assets inside of Drupal.
1. You will need to install vsFTP on your server and properly configure it.
2. Configure vsFTPD or other FTP servers to use Pam.d for authentication, example:

```
  listen=YES
  anonymous_enable=NO
  local_enable=YES
  virtual_use_local_privs=YES
  allow_writeable_chroot=YES
  write_enable=YES
  connect_from_port_20=YES
  secure_chroot_dir=/var/run/vsftpd
  pam_service_name=vsftpd
  guest_enable=YES
  user_sub_token=$USER
  local_root={MEDIA UPLOAD DIRECTORY}
  chroot_local_user=YES
  hide_ids=YES
  pasv_min_port=44000
  pasv_max_port=44000
  pasv_enable=YES
  file_open_mode=0777
  local_umask=0002
```

3. Configure Pam.d, update the /etc/pam.d/vsftpd with the following:

```
  auth    required pam_pwdfile.so pwdfile /etc/vsftpd/passwd
  account required pam_permit.so
```

4. You will want to ensure that your Apache2 user has read/write access to the /etc/vsftpd/passwd folder to allow it to update the
  user passwords and accounts.
5. You will want to ensure that your Apache2 user is added to the ftp group on your server and that it has read/write access to the folder
  that your ftpd server is set to upload to.

CONFIGURATION
-------------

1. Navigate to Administration > Assets > Settings.
2. Configure the base directory that will hold the digital assets, the location of your logfile, and the location of the passwd file for vsFTPD.
3. Start using the module.

MAINTAINERS
-----------

* cmcintosh - https://www.drupal.org/u/cmcintosh
