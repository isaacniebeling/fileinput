(function ($, ContentBlocks) {
    ContentBlocks.fieldTypes.fileinput = function(dom, data) {
        var input = {
            fileCount: 0,
            fileBrowser: false,
            source: data.properties.source > 0 ? data.properties.source : ContentBlocksConfig['image.source'],
            directory: data.properties.directory
        };

        input.init = function () {
            this.initUpload();
            dom.find('.contentblocks-field-upload').on('click', function() {
                dom.find('.contentblocks-field-upload-field').click();
            });
            dom.find('.contentblocks-field-file-choose').on('click', $.proxy(function() {
                this.chooseFile();
            }, this));

            if ($.isArray(data.files)) {
                $.each(data.files, function(idx, file) {
                    input.fileCount++;
                    file.id = data.generated_id + '-file' + input.fileCount;
                    input.addFile(file);
                });
            }
            dom.find('.file-holder').sortable({
                connectWith: '.file-holder',
                forceHelperSize: true,
                forcePlaceholderSizeType: true,
                placeholder: 'contentblocks-file-placeholder',
                tolerance: 'pointer',
                cursor: 'move',
                update: function() {
                    ContentBlocks.fixColumnHeights();
                    MODx.fireResourceFormChange();
                }
            });
        };

        input.chooseFile = function() {
            var maxFiles = data.properties.max_files,
                numFiles = dom.find('.file-holder').find('li').length;

            if (numFiles >= maxFiles) {
                alert(_('fileinput.max_files.reached', {max: maxFiles}));
                return false;
            }
            if (!this.fileBrowser) this.fileBrowser = MODx.load({
                xtype: 'modx-browser',
                id: Ext.id(),
                multiple: true,
                listeners: {
                    select: function(fileData) {
                        input.chooseFileCallback(fileData);
                    }
                },
                allowedFileTypes: data.properties.file_types,
                hideFiles: true,
                title: _('fileinput.choose_file')
            });
            this.fileBrowser.setSource(input.source);

            this.fileBrowser.show();
        };

        input.chooseFileCallback = function(fileData) {
            var url = fileData.fullRelativeUrl;
            if (url.substr(0, 4) != 'http' && url.substr(0,1) != '/' ) {
                url = MODx.config.base_url + url;
            }
            input.fileCount++;
            var fileId = dom.attr('id') + '-file' + input.fileCount;
            var size = fileData.size;
            var extension = fileData.ext;

            this.addFile({
                url: url,
                title: fileData.filename,
                id: fileId,
                size: size,
                extension: extension
            });
        };

        input.addFile = function(values) {
            var holder = dom.find('.file-holder');

            holder.append(tmpl('contentblocks-field-fileinput_file', values));
            var inserted = $('#' + values.id);

            inserted.find('.contentblocks-fileinput_file-delete').on('click', function() {
                inserted.fadeOut(function() {
                    inserted.remove();
                    ContentBlocks.fixColumnHeights();
                    MODx.fireResourceFormChange();
                });
            });
        };

        input.initUpload = function() {
            var id = dom.attr('id'),
                maxFiles = data.properties.max_files;
            dom.find('#' + id + '-upload').fileupload({
                url: fileInputConnectorUrl + '?action=upload',
                dataType: 'json',
                dropZone: $('#' + id),
                progressInterval: 250,
                paramName: 'file',
                multiple: true,

                /**
                 * Add an item to the upload queue.
                 *
                 * @param e
                 * @param data
                 */
                add: function(e, data) {
                    // Check if we're not at the limit already
                    var numFiles = dom.find('.file-holder').find('li').length;
                    if (numFiles >= maxFiles) {
                        alert(_('contentblocks.max_files.reached', {max: maxFiles}));
                        return false;
                    }

                    input.fileCount++;
                    var fileId = id + '-file' + input.fileCount;
                    data.files[0].ext = data.files[0].name.split('.').pop();
                    // Add file to the page
                    input.addFile({
                        title: data.files[0].name,
                        url: '',
                        id: fileId,
                        size: data.files[0].size,
                        extension: data.files[0].ext
                    });
                    data.domId = '#' + fileId;

                    var file = $(data.domId);
                    file.addClass('uploading');

                    setTimeout(function() {
                        data.submit();
                    }, 1000);

                    MODx.fireResourceFormChange();
                },

                /**
                 * When the file has been uploaded add it to the collection.
                 *
                 */
                done: function(e, data) {
                    var dom = $(data.domId);
                    if (data.result.success) {
                        var record = data.result.object;
                        dom.find('.url').val(record.url);
                        dom.find('.file-url').text(record.url);
                        dom.find('.size').val(record.size);
                        dom.find('.extension').val(record.extension);
                        dom.removeClass('uploading');
                    }
                    else {
                        var message = _('contentblocks.upload_error', {file: data.files[0].filename, message:  data.result.message});
                        if (data.files[0].size > 1048576*1.5) {
                            message += _('contentblocks.upload_error.file_too_big');
                        }
                        alert(message);
                        dom.remove();
                    }

                    setTimeout(function() {
                        ContentBlocks.fixColumnHeights();
                    }, 150);
                },

                fail: function(e, data) {
                    var message = _('contentblocks.upload_error', {file: data.files[0].filename, message:  data.result.message});
                    if (data.files[0].size > 1048576*1.5) {
                        message += _('contentblocks.upload_error.file_too_big');
                    }
                    alert(message);

                    $(data.domId).remove();
                    ContentBlocks.fixColumnHeights();
                },

                /**
                 * Fetch the items we want to send along in the POST. In this case,
                 * this is overridden because normally it sends the entire form = the resource.
                 * All we really want is the resource ID, which we fetch from the URL.
                 * @returns {Array}
                 */
                formData: function() {
                    return [{
                        name: 'HTTP_MODAUTH',
                        value: MODx.siteId
                    },{
                        name: 'resource',
                        value: MODx.request.id || 0
                    },{
                        name: 'field',
                        value: data.id
                    }];
                },

                /**
                 * Update progress for queue items
                 */
                progress: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10) + '%';
                    $(data.domId).find('.upload-progress .bar').width(progress);
                }
            }).on('fileuploaddragover', function() {
                $(this).css('background', 'red');
            });
        };

        input.getData = function () {
            var files = [];
            dom.find('.file-holder li').each(function(idx, file) {
                var $file = $(file),
                    data = {
                    url: $file.find('.url').val(),
                    title: $file.find('.title').val(),
                    size: $file.find('.size').val(),
                    extension: $file.find('.extension').val()
                };
                files.push(data);
            });

            return {
                files: files
            };
        };

        return input;
    };
})(vcJquery, ContentBlocks);
