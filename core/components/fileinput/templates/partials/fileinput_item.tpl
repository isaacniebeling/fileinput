<li id="{%=o.id%}" class="contentblocks-field-fileinput_file">
    <input type="hidden" class="url" value="{%=o.url%}">
    <input type="hidden" class="size" value="{%=o.size%}">
    <input type="hidden" class="extension" value="{%=o.extension%}">
    <div class="contentblocks-field-fileinput_file-view">
        <span class="file-url">{%=o.url%}</span>
    </div>
    <input type="text" class="title" value="{%=o.title%}" placeholder="{%=_('contentblocks.title')%}">
    <div class="contentblocks-field-fileinput_file-uploading">
        <div class="upload-progress">
            <div class="bar"></div>
        </div>
    </div>

    <div class="contentblocks-gallery-fileinput_file-actions">
        <a href="javascript:void(0);" class="contentblocks-field-button contentblocks-fileinput_file-delete">&times; {%=_('contentblocks.delete')%}</a>
    </div>
</li>
