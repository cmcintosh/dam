(function($) {
  'use strict';

  Drupal.behaviors.dam_assets = {
    attach: function(context, settings) {
      var selectedEntity, currentFolder;

      // Get the current user role and id.
      var user = {
        id: drupalSettings.user.uid,
        roles: drupalSettings.user.roles
      }

      // Set the first to expanded
      drupalSettings.dam.folders.state.expanded = true;

      $('#assets-explorer').treeview({
        levels: 5,
        data: getTree(),
        expandIcon: 'fa fa-caret-right',
        collapseIcon: 'fa fa-caret-down',
        nodeIcon: 'fa fa-bookmark',
      });

      $('#assets-explorer').on('nodeSelected', function(event, directory){

        $('.loader-wrapper').fadeIn();

        $('.dam-asset-item, table.list-item').remove();
        if (directory.state.expanded == false) {
          drupalSettings.dam.activeTree = ActiveTree;
          $('#assets-explorer').treeview('expandNode', [directory.nodeId, { levels: 1 }]);
        }
        else {
        //  $('#assets-explorer').treeview('collapseNode', [directory.nodeId, { levels: 1 }]);
        }
        var ActiveTree = findTree(directory.text, [drupalSettings.dam.tree]);
        currentFolder = ActiveTree;
        for (var i in ActiveTree.nodes) {
          ActiveTree.parent = ActiveTree.text;
          renderTree(ActiveTree.nodes[i], directory);
          renderList(ActiveTree.nodes[i], directory);
        }
        $('.loader-wrapper').fadeOut();
      });

      /**
      * Returns the directory name.
      */
      var findTree = function(directoryName, tree) {
        var treeFound = false;
        for (var i in tree) {
          if (directoryName == tree[i].text) {
            return tree[i]
          }
          // recurse down...
          for (var j in tree[i].nodes) {
            if( treeFound = findTree(directoryName, tree[i].nodes) ) {
              return treeFound;
            }
          }
        }
        return treeFound;
      }

      // Render Message Alert
      function alertMessage(status, message) {
        var element = '<div id="message-alert" class="alert alert-' +status+ '"><strong>'+ status +'!</strong>'+message+'</div>';
        $(element).prependTo('body');

        // Remove after 5 secs
        setTimeout(function() {
          $('div#message-alert').remove();
        }, 10000);
      }

      /**
      * Check if current user has permission to view file / folder
      */
      var checkCurrentUserPermission = function(entity_id, entity_type, user, callback) {
        var access = false;
        $.ajax({
          type: "GET",
          url: '/api/file_access?_format=json&entity_id=' + entity_id + '&entity_type=' + entity_type,
          data: entity_id,
          async: false,
          success: callback,
          error: function(error) {
            return access = false;
            alertMessage('danger', 'There was an issue checking access permission for files and folders');
          }
        });
      }

      // Check the View mode set by user.
      $('li.asset-mode a').on('click', function() {
        $('li.asset-mode').removeClass('active');
        $(this).parent().addClass('active');
        var href = $(this).attr('href');
        var viewmode = href.replace('#', '');
        $('#assets-viewer').attr('data-view', viewmode);
      });

      /**
      * Render File / Folder information
      */
      var fileInformation = function(tree) {
        var date = (new Date(1000*tree.info.created));
            date = (date.getMonth()+1) + '/' + date.getDate() + '/' + date.getFullYear() + ' ' + date.getHours() + ':' + date.getMinutes();
        var type = (tree.href.indexOf('file_directory') > -1) ? 'Delete Folder' : 'Delete File';
        var template = '<div class="information-wrapper">' +
                  '<label>Name</label><span id="name">' + tree.info.name + '</span>' +
                  '<label>Uploaded</label><span id="date">' + date + '</span>' +
                  '<label>Uploaded By</label><span id="owner">' + tree.info.owner + '</span>' +
                  '<label>Type</label><span id="type">' + tree.info.type + '</span>' +
                  '<label>Size</label><span id="size">' + tree.info.size + '</span>' +
                  '<button id="delete-file" data-file="' + tree.info.name + '" data-path="'+ tree.info.download +'">'+ type +'</button>' +
                '</div>';
        return template;
      }

      // Function to delete file / folder.
      $(document).delegate('button#delete-file', 'click', function() {
        var data = {
          entity_type: (selectedEntity.type) ? selectedEntity.type : 'file',
          entity_id: selectedEntity.info.id
        }
        $.ajax({
          type: "POST",
          url: '/dam/file/delete?_format=json&entity_id=' + data.entity_id + '&entity_type=' + data.entity_type,
          data: data,
          success: function(response) {
            if(response.status != undefined && response.status == 200) {
              alertMessage('success', response.message);
              var item = $('#assets-viewer');
              // Remove the item from the list.
              if(selectedEntity.type == 'file_directory') {
                $(item).find('div[data-folder-path="'+ selectedEntity.text +'"]').remove();
              } else if(selectedEntity.type == 'file') {
                $(item).find('div[data-file-path="'+ selectedEntity.text +'"]').remove();
              }
            } else {
              alertMessage('danger', response.message)
            }
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue deleting ' + data.entity_type);
          }
        });
      });

      /**
      * Render File Download Information
      */
      var fileDownload = function(file) {
        var template = '<div class="download-wrapper">' +
                          '<label>Original File</label>' +
                          '<a download="'+ file.text +'" href="' + file.info.download + '">Download</a>' +
                          '<button name="clipboard" onclick="'+ copyToClipboard(file.info.download) +'">Copy To Clipboard</button>' +
                          '<div id="description" class="small">Choose Original file if you want to use the file in the highest possible quality.</div>' +
                        '</div>';
        return template;
      }

      /**
      * Copy to Clipboard function
      */
      function copyToClipboard(path) {
        var aux = document.createElement("input");
        aux.setAttribute("value", path);
        document.body.appendChild(aux);
        aux.select();
        document.execCommand("copy");
        document.body.removeChild(aux);
      }

      var fileIcon = function(filename) {
        var icon = 'fa-file-o';
        if(filename.indexOf('pdf') > -1) {
          icon = 'fa-file-pdf-o';
        }
        if(filename.indexOf('doc') > -1) {
          icon = 'fa-file-word-o';
        }
        if(filename.indexOf('mp3') > -1 || filename.indexOf('wma') > -1 || filename.indexOf('mid') > -1 || filename.indexOf('oga') > -1 || filename.indexOf('wav') > -1) {
          icon = 'fa-music';
        }
        if(filename.indexOf('mp4') > -1 || filename.indexOf('m4v') > -1 || filename.indexOf('avi') > -1 || filename.indexOf('flv') > -1 || filename.indexOf('3gp') > -1) {
          icon = 'fa-file-video-o';
        }
        if(filename.indexOf('jpeg') > -1 || filename.indexOf('jpg') > -1 || filename.indexOf('png') > -1 || filename.indexOf('gif') > -1) {
          icon = 'fa-file-image-o';
        }
        if(filename.indexOf('txt') > -1 || filename.indexOf('csv') > -1) {
          icon = 'fa-file-text-o';
        }
        if(filename.indexOf('zip') > -1 || filename.indexOf('tar') > -1 || filename.indexOf('gz') > -1) {
          icon = 'fa-file-archive-o';
        }
        return icon;
      }

      /**
      * Render Tree.
      */
      var renderTree = function (tree, directory) {
          if (tree.href.indexOf('file_directory') > -1) {

            $('table#comments-list').remove();

            // render item as a folder...
            var folder = $('<div></div>')
              .attr('data-folder', directory.nodeId)
              .attr('data-folder-path', tree.text)
              .addClass('dam-asset-item')
              .append(
                // Thumbnail container.
                $('<div></div>')
                .addClass('thumbnail-wrapper')
                .append(
                  $('<span class="glyphicon glyphicon-folder-open"></span>')
                )
              )
              .append(
                $('<div></div>')
                  .addClass('asset-name')
                  .html(tree.text)
              )
              .on('click', function(e){
                selectedEntity = tree;
                selectedEntity.type = 'file_directory';
                getFileAccess();
                getFileLabel(tree.info.label_name);

                // Hide comments for folders.
                $('a[href="#comments"]').parent().addClass('hide');
                $('#dam-assets-comments').addClass('hide');

                $('.dam-asset-item').css('background', 'transparent').removeClass('selectedItem');
                $(this).addClass('selectedItem');

                var folder = fileInformation(tree);
                $('div#preview-thumbnail').empty().append(
                  $('<span class="glyphicon glyphicon-folder-open"></span>')
                );

                $('#dam-assets-info').css('display', 'block');
                $('#dam-assets-share, #dam-assets-access').css('display', 'none');
                $('a[href="#dam-assets-info"]').addClass('active');
                $('a[href="#dam-assets-share"], a[href="#dam-assets-access"]').removeClass('active');

                $('div#info')
                  .empty()
                  .append($(folder));
                $('div#download').empty();
              })
              .on('dblclick', function(e){
                var nodeId = $(e.currentTarget).attr('data-folder-path');
                var target = $('.list-group-item:contains("' + nodeId +'")');
                $(target).trigger('click');
              });

            // Add label wrapper if directory has label set.
            if(tree.info.label_name) {
              wrapLabels(tree.info.label_name, folder);
            }

            // Check permission
            checkCurrentUserPermission(tree.info.id, 'file_directory', user, function(response) {
              if(response.status != undefined && response.status == 404) {
                if(user.id == 1) {
                  $('#assets-viewer #thumbnail-view').append(folder);
                  return;
                }
                return;
              } else {
                // Super admin user should be able to view all.
                if(user.id == 1) {
                  $('#assets-viewer #thumbnail-view').append(folder);
                  return;
                }
                for(var i in response) {
                  // Check if current user has permission via user id
                  if(response[i].agent_id == user.id) {
                    $('#assets-viewer #thumbnail-view').append(folder);
                  }
                  // Check if current user has permission via user roles
                  for(var x in user.roles) {
                    if(user.roles[x] == response[i].agent_id) {
                      $('#assets-viewer #thumbnail-view').append(folder);
                    }
                  }
                }
              }
            });
          }
          else {
            var fileicon = fileIcon(tree.info.name);
            var file;

            // Display comments tab.
            $('table#comments-list').remove();
            $('a[href="#comments"]').parent().removeClass('hide');
            $('#dam-assets-comments').removeClass('hide');

            // Initialize Element
            file = $('<div></div>')
              .attr('data-file-path', tree.text)
              .addClass('dam-asset-item');

            // If file is a video type, only display the video icon.
            // Videos should only be played on the preview tab.
            if((tree.info.name).indexOf('mp4') > -1 || (tree.info.name).indexOf('m4v') > -1 || (tree.info.name).indexOf('avi') > -1 || (tree.info.name).indexOf('flv') > -1 || (tree.info.name).indexOf('3gp') > -1) {
              $(file).append(
                $('<div></div>')
                .addClass('thumbnail-wrapper')
                .append($('<span class="fa fa-file-video-o"></span>'))
              )
              .on('click', function(e){
                selectedEntity = tree;
                getFileAccess();
                getComments();
                getFileLabel(tree.info.label_name);

                // Show comments for folders.
                $('a[href="#comments"]').parent().removeClass('hide');
                $('#dam-assets-comments').removeClass('hide');

                $('.dam-asset-item').css('background', 'transparent').removeClass('selectedItem');
                $(this).addClass('selectedItem');
                var fileinfo = fileInformation(tree);
                var filedownload = fileDownload(tree);

                $('div#preview-thumbnail').empty().append(
                  $('<video>')
                    .attr('controls', '')
                    .append(
                      $('<source></source')
                        .attr('src', tree.info.download)
                        .attr('type', tree.info.type)
                    )
                    .on('error', function(error) {
                      $(this).addClass('hide');
                      $(this).parent().append('<span class="fa '+ fileicon +'"></span>');
                    })
                );

                $('#dam-assets-info').css('display', 'block');
                $('#dam-assets-share, #dam-assets-access').css('display', 'none');
                $('a[href="#dam-assets-info"]').addClass('active');
                $('a[href="#dam-assets-share"], a[href="#dam-assets-access"]').removeClass('active');

                $('div#info').empty().append($(fileinfo));
                $('div#download').empty().append($(filedownload));
              });

            }
            // If file is music type, only display music icon.
            // Music will be played on the preview tab only.
            else if((tree.info.name).indexOf('mp3') > -1 || (tree.info.name).indexOf('wma') > -1 || (tree.info.name).indexOf('mid') > -1 || (tree.info.name).indexOf('oga') > -1 || (tree.info.name).indexOf('wav') > -1) {
              $(file).append(
                $('<div></div>')
                .addClass('thumbnail-wrapper')
                .append($('<span class="fa fa-music"></span>'))
              )
              .on('click', function(e){
                selectedEntity = tree;
                getFileAccess();
                getComments();
                getFileLabel(tree.info.label_name);

                // Show comments for folders.
                $('a[href="#comments"]').parent().removeClass('hide');
                $('#dam-assets-comments').removeClass('hide');

                $('.dam-asset-item').css('background', 'transparent').removeClass('selectedItem');
                $(this).addClass('selectedItem');
                var fileinfo = fileInformation(tree);
                var filedownload = fileDownload(tree);

                $('div#preview-thumbnail').empty().append(
                  $('<audio>')
                    .attr('controls', '')
                    .append(
                      $('<source></source')
                        .attr('src', tree.info.download)
                        .attr('type', tree.info.type)
                      // Uncomment below to try video
                      // $('<source></source')
                      //   .attr('src', 'https://www.w3schools.com/html/horse.ogg')
                      //   .attr('type', 'audio/ogg')
                    )
                    .on('error', function(error) {
                      $(this).addClass('hide');
                      $(this).parent().append('<span class="fa '+ fileicon +'"></span>');
                    })
                );

                $('#dam-assets-info').css('display', 'block');
                $('#dam-assets-share, #dam-assets-access').css('display', 'none');
                $('a[href="#dam-assets-info"]').addClass('active');
                $('a[href="#dam-assets-share"], a[href="#dam-assets-access"]').removeClass('active');

                $('div#info').empty().append($(fileinfo));
                $('div#download').empty().append($(filedownload));
              });
            }
            else {
              $(file)
                .append(
                  $('<div></div>')
                  .addClass('thumbnail-wrapper')
                  .append(
                    $('<img></img>')
                    .on('error', function(error) {
                      $(this).addClass('hide');
                      $(this).parent().append('<span class="fa '+ fileicon +'"></span>');
                    })
                    .attr('src', tree.info.download)
                  )
                )
                .on('click', function(e){
                  selectedEntity = tree;
                  getFileAccess();
                  getFileLabel(tree.info.label_name);

                  // Show comments for folders.
                  $('a[href="#comments"]').parent().removeClass('hide');
                  $('#dam-assets-comments').removeClass('hide');

                  // Get the list of comments for the selected file
                  $('#dam-assets-comments #comments').remove('table#comments-list');
                  getComments();

                  $('.dam-asset-item').css('background', 'transparent').removeClass('selectedItem');
                  $(this).addClass('selectedItem');
                  var fileinfo = fileInformation(tree);
                  var filedownload = fileDownload(tree);
                  $('div#preview-thumbnail').empty().append(
                    $('<img>').attr('src', tree.info.download)
                      .on('error', function(error) {
                        $(this).addClass('hide');
                        $(this).parent().append('<span class="fa '+ fileicon +'"></span>');
                      })
                  );

                  $('#dam-assets-info').css('display', 'block');
                  $('#dam-assets-share, #dam-assets-access').css('display', 'none');
                  $('a[href="#dam-assets-info"]').addClass('active');
                  $('a[href="#dam-assets-share"], a[href="#dam-assets-access"]').removeClass('active');

                  $('div#info').empty().append($(fileinfo));
                  $('div#download').empty().append($(filedownload));
                });
            }

            // Adding Filename
            $(file).append(
              $('<div></div>')
                .addClass('asset-name')
                .html(tree.text)
            )
            .on('dblclick', function(e){
              var target = $('.list-group-item:contains("' + currentFolder.text +'")');
              $(target).trigger('click');
            });

            // Add label wrapper if directory has label set.
            if(tree.info.label_name) {
              wrapLabels(tree.info.label_name, file);
            }

            // We do not display the file / folder is user does not have permission to.
            checkCurrentUserPermission(tree.info.id, 'file', user, function(response) {
              if(response.status != undefined && response.status == 404) {
                if(user.id == 1) {
                  $('#assets-viewer #thumbnail-view').append(file);
                  return;
                }
                return;
              } else {
                if(user.id == 1) {
                  $('#assets-viewer #thumbnail-view').append(file);
                  return;
                }
                for(var i in response) {
                  // Check if current user has permission via user id
                  if(response[i].agent_id == user.id) {
                    $('#assets-viewer #thumbnail-view').append(file);
                  }
                  // Check if current user has permission via user roles
                  for(var x in user.roles) {
                    if(user.roles[x] == response[i].agent_id) {
                      $('#assets-viewer #thumbnail-view').append(file);
                    }
                  }
                }
              }
            });
          }
      }

      /**
      * Render List
      */
      var renderList = function(tree, directory) {
        var item = '<table class="list-item">';

        var date = new Date(1000*tree.info.created);
        item += '<tr data-value="'+ tree.info.name +'" style="background: '+ tree.backColor[0] +'">';
        item += '<td><a download href="' + tree.info.download + '" title="Click to download file">' + tree.text + '</a></td>';
        item += '<td>' + (date.getMonth()+1) + '/' + date.getDate() + '/' + date.getFullYear() + '</td>';
        item += '<td>' + tree.info.owner + '</td>';
        item += '<td>' + tree.info.size + '</td>';
        item += '<td><span>' + tree.info.label_name + '</span></td>';
        item += '</tr></table>';

        $('table#comments-list').remove();

        // Add it to the viewer area.
        $('#assets-viewer #list-view').append(item);
      }

      $(document).delegate('table.list-item tr', 'click', function(e) {
        var nodeId = $(e.currentTarget).attr('data-value');
        $('#thumbnail-view').find('[data-file-path="'+ nodeId +'"]').trigger('click');
      });

      var userTable = function(data) {
        if(data == undefined) { return; }
        if(data.status == 404) { return; }

        var table = '<div class="user-table-wrapper"><table>';
            // Head
            table += '<thead><tr>';
            table += '<th>User/Role</th>';
            table += '<th>View Access</th>';
            table += '<th>Write Access</th>';
            table += '<th>Upload Notify</th>';
            table += '<th>Delete</th>';
            table += '</tr></thead>';
            // Body
            table += '<tbody>';
        for(var i in data) {
          if(data[i] == undefined) { return; }
          table += '<tr data-id="'+ i +'">';
          table += '<td data-value="' + data[i].agent_id + '" data-role="'+ data[i].agent_type +'">' + data[i].agent + '</td>';
          table += '<td><input type="checkbox" id="view" ' + ((data[i].view == '1') ? "checked" : "") + '></td>';
          table += '<td><input type="checkbox" id="write" ' + ((data[i].write == '1') ? "checked" : "") + '></td>';
          table += '<td><input type="checkbox" id="notify_upload" ' + ((data[i].notify_upload == '1') ? "checked" : "") + '></td>';
          table += '<td><button id="delete-access">Delete</button></td>';
          table += '</tr>';
        }
        table += '</tbody></table>';
        table += '<button id="save-permission">Save Permission</button></div>';
        return table;
      }

      /**
      * AJAX call to get the list of Users + Roles that have access to selected entity.
      */
      function getFileAccess() {
        var entity_id = selectedEntity.info.id,
        entity_type = (selectedEntity.type) ? selectedEntity.type : 'file';
        $.ajax({
          type: "GET",
          url: '/api/file_access?_format=json&entity_id=' + entity_id + '&entity_type=' + entity_type,
          data: entity_id,
          success: function(response) {
            if(response.status != undefined && response.status == 404) {
              $('#dam-users-table .container span.description').remove();
              $('#dam-users-table .container').append('<span class="description" style="font-style:italic">No Permissions found for this ' + entity_type + '.</span>');
              return;
            }
            var result = userTable(response);
            $('#dam-users-table .container span.description').remove();
            $('#dam-users-table .user-table-wrapper').remove();
            $('#dam-users-table').append(result);
            $('#assets-viewer #loader').remove();
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue getting file access for this entity.');
          }
        });
      }

      /**
      * Add user/role permission to access file/folder.
      *  - Render the User Table.
      *  - Allow user to set the table permissions.
      */
      $('#dam-users-footer a').on('click', function() {
        var id = $(this).attr('id');
        var data = {};
            data.agent_id = (id == 'add-user') ? $('select#select-user').val() : $('select#select-role').val();
            data.agent = data.agent_id;
            data.agent_type = (id == 'add-user') ? 'user' : 'role';
            data.view = '0';
            data.write = '0';
            data.notify_upload = '0';
            var result = userTable([data]);
            if($('#dam-users-table').find('table').length == 0) {
              $('#dam-users-table').append(result);
            }
            else {
              var newRow = '<tr>';
              newRow += '<td data-value="'+ data.agent_id +'" data-role="'+ data.agent_type +'">' + data.agent_id + '</td>';
              newRow += '<td><input type="checkbox" id="view"></td>';
              newRow += '<td><input type="checkbox" id="write"></td>';
              newRow += '<td><input type="checkbox" id="notify_upload"></td>';
              newRow += '<td><button id="delete-access">Delete</button></td>';
              newRow += '</tr>';
              // If table is already created, we append the new data to the table
              $('#dam-users-table table tbody').append(newRow);
            }
      });

      // Delete Permission
      $(document).delegate('#delete-access', 'click', function() {
        var id = $(this).parent().parent().attr('data-id');
        if(id == undefined) {
          $(this).parent().parent().remove();
        } else {
          $.ajax({
            type: "POST",
            url: '/dam/access/delete?_format=json&entity_id=' + id,
            data: { entity_id : id },
            success: function(response) {
              if(response.status != undefined && response.status == 200) {
                alertMessage('success', 'Successfully deleted permission');
                getFileAccess();
              } else {
                alertMessage('danger', 'There was an issue deleting permission.');
              }
            },
            error: function(error) {
              alertMessage('danger', 'There was an issue deleting permission.');
            }
          });
        }
      });

      // Save File/Folder Permissions
      $(document).delegate('#save-permission', 'click', function() {
        var id = $(this).attr('id');

        // Loop through all the list data in the list to save new and update old value.
        $('.user-table-wrapper table tr').each(function(i, obj) {
          var item_id = $(this).attr('data-id'),
              name = $(this).find('td[data-value]').attr('data-value'),
              role = $(this).find('td[data-value]').attr('data-role'),
              can_view = ($(this).find('input#view').is(':checked') == true) ? '1' : '0' ,
              can_write = ($(this).find('input#write').is(':checked') == true) ? '1' : '0' ,
              notify_upload = ($(this).find('input#notify_upload').is(':checked') == true) ? '1' : '0' ;

          var data = {
            entity_type: (selectedEntity.type) ? selectedEntity.type : 'file',
            entity_id: selectedEntity.info.id,
            agent_type: role,
            agent_id: (name == 'new') ? ((id == 'add-user') ? $('select#select-user').val() : $('select#select-role').val()) : name,
            can_view: can_view,
            can_write: can_write,
            notify_of_upload: notify_upload
          }

          if(data.agent_id != undefined) {
            $.ajax({
              type: "POST",
              url: '/api/file_add_access?_format=json&entity_type='+ data.entity_type +'&entity_id='+ data.entity_id +'&agent_type='+ data.agent_type +'&agent_id='+ data.agent_id +'&can_view='+ data.can_view +'&can_write='+ data.can_write +'&notify_of_upload='+ data.notify_of_upload,
              data: data,
              success: function(response) {
                if(response.status != undefined && response.status == 200) {
                  alertMessage('success', response.message);
                  getFileAccess();
                } else {
                  alertMessage('danger', 'There was an issue saving access permission.');
                }
              },
              error: function(error) {
                alertMessage('danger', 'There was an issue adding permission.');
              }
            });
          }
        });
      });

      var getFileLabel = function(data) {
        $('#labels-list input').prop('checked', false);
        for(var i in data) {
          $('#labels-list').find('input').each(function(e, value) {
            var _ = $(this);
            var label = _.next().text();

            if(data[i].label_name == label) {
              $(this).prop('checked', true);
            }
          });
        }
      }

      // Wrap elements
      function wrapLabels(data, element) {
        var selectedElem = (element !== undefined) ? $(element) : $('.dam-asset-item.selectedItem');
        var label_color = drupalSettings.dam.labels;
        $('.dam-asset-item.selectedItem .with-label #label').remove();
        $('.dam-asset-item.selectedItem .with-label').remove();

        // Re-create / Create
        selectedElem.append('<div class="with-label"></div>');

        if(!(data instanceof Array)) {
          data = [data];
        }

        for(var i in data) {
          if(label_color[data[i]] !== undefined) {
            selectedElem.find('.with-label')
            .append(
              $('<span id="label">'+ data[i] +'</span>')
              .attr('style', 'background-color: '+ label_color[data[i]].color)
            );
          }
        }
      }

      // Check label checkbox when label is clicked
      $('#labels-list label').click(function() {
        var _ = $(this);
        var input = _.prev().trigger('click');
      });

      /**
      * Add label to element
      */
      $(document).delegate('#submit-labels', 'click', function() {
        var wrapper = $('#labels-list');
        var value = $(this).val();
        var selectedElem = $('.dam-asset-item.selectedItem');
        var label_color = drupalSettings.dam.labels;
        var itemLabels = [];

        var data = {
          entity_type: (selectedEntity.type) ? selectedEntity.type : 'file',
          entity_id: parseInt(selectedEntity.info.id),
          label_id: ''
        };

        wrapper.find('input').each(function(e, value) {
          var _ = $(this);
          var label = _.next().text();

          if(_.is(':checked')) {
            data.label_id += (data.label_id == '') ? label_color[label].id : (':' + label_color[label].id);
            itemLabels.push(label);
          }
        });

        wrapLabels(itemLabels);

        // Update data, we set the child elements' label
        var setLabels = findTree(selectedEntity.text, [drupalSettings.dam.tree]);
        for(var x in setLabels.nodes) {
          (setLabels.nodes[x]).info.label_name = itemLabels;
        }


        // Send data to the backend to update entity with the selected label.
        $.ajax({
          type: "POST",
          url: '/api/file_label?_format=json&entity_type=' + data.entity_type + '&entity_id=' + data.entity_id + '&label_id=' + data.label_id,
          data: data,
          success: function(response) {
            if(response.status != undefined && response.status == 200) {
              alertMessage('success', 'Successfully added label');
            } else {
              alertMessage('danger', 'There was an issue updating label for this file or folder');
            }
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue adding label to this file or folder.');
          }
        });
      });

      /**
      * Toggle animation on the Right Pane ( Preview Panel )
      */
      $('.preview-pane a').on('click', function() {
        var element = $(this).attr('href');
        $('#dam-assets-info, #dam-assets-share, #dam-assets-access').css('display', 'none');
        $('.preview-pane a').removeClass('active');
        $(this).addClass('active');
        $(element).toggle();
      });

      // Get the list of comments for a file.
      function getComments() {
        $('#dam-assets-comments table').remove(); // Remove table to be recreated based on result.
        $.ajax({
          type: "GET",
          url: '/dam/file/get-comments?format=json&file=' + selectedEntity.info.id,
          success: function(response) {
            $('#comments-list').remove();
            var table = '<table id="comments-list">';
            for(var i in response) {
              var date = new Date(1000*response[i].created);
              table += '<tr>';
              table += '<td>'+ response[i].cid +'</td>';
              table += '<td>'+ response[i].body +'</td>';
              table += '<td>'+ response[i].username +'</td>';
              table += '<td>'+ (date.getMonth()+1) +'/' + date.getDate() + '/' + date.getFullYear() +'</td>';
              table += '</tr>';
            }
            table += '</table>';
            $(table).prependTo('#dam-assets-comments #comments');
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue getting comments.');
          }
        });
      }

      /**
      * Toggle animation on the Preview View mode Pane ( inner elements )
      */
      $('#assets-preview-view-mode a').on('click', function() {
        var href = $(this).attr('href');
        $('#assets-preview-view-mode li').removeClass('collapsed');
        $(this).parent().addClass('collapsed');

        switch (href) {
          case '#collapse':
            $('#assets-info-wrapper, #assets-preview-view-mode, #dam-assets-comments').toggleClass('collapsed');
            if($('#assets-info-wrapper').hasClass('collapsed')) {
              $('#dam-wrapper').css('grid-template-columns', '25% 25% 45% 5%');
            } else {
              $('#dam-wrapper').css('grid-template-columns', '25% 25% 20% 30%');
            }
            break;
          case '#information':
            $('#dam-assets-comments').css('display', 'none');
            $('#assets-info-wrapper').css('display', 'block');
            break;
          case '#comments':
            $('#dam-assets-comments').css('display', 'block');
            $('#assets-info-wrapper').css('display', 'none');

            // Add form to add new comment.
            if($('#dam-assets-comments #comments').find('input').length == 0) {
              $('#dam-assets-comments #comments')
              .append('<input type="text" id="add-comment" placeholder="Add a comment.."><button id="save-comment">Save</button>')
            }
            break;
        }
      });

      // Function to save comment.
      $(document).delegate('button#save-comment', 'click', function(){
        var comment = $('input#add-comment');
        $.ajax({
          type: "POST",
          url: '/dam/file/comment?_format=json&file=' + parseInt(selectedEntity.info.id) + '&comment=' + comment.val(),
          data: {
            file: parseInt(selectedEntity.info.id),
            comment: comment.val()
          },
          success: function(response) {
            if(response.status != undefined && response.status == 200) {
              alertMessage('success', response.message);
              comment.val('');
              getComments();
            } else {
              alertMessage('danger', response.message);
            }
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue saving comment.');
          }
        });
      });

      // Folder Control buttons
      $('#assets-folder-buttons a').on('click', function() {
        var id = $(this).attr('id');
        switch (id) {
          case 'add-folder':
            // Remove form if created already. Works like a toggle.
            if($(this).parent().parent().find('li.new-folder').length > 0) {
              $(this).parent().parent().find('li.new-folder').remove();
              return;
            }
            // create a form to get the name of the new directory to add
            var form = '<li class="new-folder"><input type="text" placeholder="Enter the name of the Directory" /><a id="saveNewFolder">Save Folder</a></li>';
            $(this).parent().parent().append(form);
            break;
          case 'delete-folder':
            if(selectedEntity == undefined || selectedEntity.type == 'file') {
              alertMessage('warning', 'Please select a folder to delete');
              return;
            }
            $('button#delete-file').trigger('click');
            break;
          case 'move-folder':
            // @TODO: We need to work on this.
            break;
        }
      });

      // Saves new Folder
      $(document).delegate('#saveNewFolder', 'click', function() {
        if(currentFolder == undefined) {
          alertMessage('warning', 'Please select a parent folder to add the new folder into.');
          return;
        }
        var name = $('li.new-folder input'); // Name of the Directory to be created.
        var id = currentFolder.info.id; // Id of the Parent Directory.
        $.ajax({
          type: "POST",
          url: '/dam/assets/add-directory?_format=json&parent_id=' + parseInt(id) + '&name=' + name.val(),
          data: {
            parent_id: parseInt(id),
            name: name.val()
          },
          success: function(response) {
            if(response.status != undefined && response.status == 200) {
              alertMessage('success', response.message);
              name.val('');
              // Add the new data to the list.
              currentFolder.nodes.push(response.data);
              renderTree(response.data, currentFolder);
              renderList(response.data, currentFolder);
            } else {
              alertMessage('danger', response.message);
            }
          },
          error: function(error) {
            alertMessage('danger', 'There was an issue saving directory.');
          }
        });
      });

      // Initialize Page load display
      var defaultActiveTree = drupalSettings.dam.activeTree;
      currentFolder = defaultActiveTree;
      $('#assets-explorer > ul > li:first-child').css('color', '#FFFFFF');
      for (var i in defaultActiveTree.nodes) {
          renderTree(defaultActiveTree.nodes[i], drupalSettings.dam.folders);
          renderList(defaultActiveTree.nodes[i], drupalSettings.dam.folders);
          if((defaultActiveTree.nodes.length-1) == i) {
            $('.loader-wrapper').fadeOut();
          }
      }

      function getTree() {
        return [ settings.dam.folders ];
      }

    }
  }
})(jQuery);
