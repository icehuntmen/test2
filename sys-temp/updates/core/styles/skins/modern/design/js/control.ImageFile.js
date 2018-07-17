/**
 * контрол для выбора изобрпажения
 * Created by bender on 21.01.16.
 */

var ControlImageFile = function (options) {
    var wrapper = options.container || null,
        id = 'imageField_',
        file = '',
        folder = '',
        file_hash = '',
        folder_hash = '',
        name = '',
        fm = '',
        cwd = '.',
        lang = options.lang || 'ru',
        selectId = 'imageControlSelect_',
        selectObj = null,
        container = null;

    function init() {
        id = id + wrapper.attr('id');
        selectId = selectId + wrapper.attr('id');
        container = $('#' + id,wrapper);
        file = wrapper.attr('umi:file');
        //folder = wrapper.attr('umi:folder');
        file_hash = wrapper.attr('umi:file-hash');
        folder_hash = wrapper.attr('umi:folder-hash');
        name = wrapper.attr('umi:input-name');
        fm = wrapper.attr('umi:filemanager');


        container.html('');

		var value = (file.indexOf('./')>=0 ? '':'.')+file;
		value = (value == '.') ? '' : value;

        container.append($([
            '<div class="thumbnail">'+( file !== '' ? '<img src="'+file+'"/>' : getLabel('js-image-field-empty') ),
            (file == '' ? '':'<div class="close">&times;</div>'),
            '</div>',
            '<input type="hidden" name="'+name+'" id="'+selectId+'" value="'+ value +'" />'

        ].join('')));

        selectObj = $('#'+selectId,wrapper);
        //window[selectId] = selectObj[0];

        container.find('.thumbnail').on('click',function(){
            if (fm == 'elfinder'){
                showelfinderFileBrowser(selectObj,cwd,true,false,folder_hash,file_hash,lang)
            }
        });

        if(file !== ''){
            container.find('.close').on('click',function(e){
                closeClickhandler();
                e.stopPropagation();
            });
        }


        selectObj.on('change',function(){
            fileChangehandler(this);
        });



    }


    function fileChangehandler(obj){
        var el = $('.thumbnail',container),
            val = obj.value;
        el.html('');
        file = val;
        obj.value = '.'+val;
        el.append($('<img src="'+val+'"/>'));
        var close = $('<div class="close">&times;</div>');
        close.on('click',function(e){
            closeClickhandler();
            e.stopPropagation();
        });
        el.append(close);
        cwd = '.'+val.substr(0, val.lastIndexOf("/"));
    }

    function closeClickhandler(){
        selectObj.val('');
        container.find('.thumbnail').html('файл не выбран');
        file = '';
        cwd = '.';
    }

    function showelfinderFileBrowser(select, folder, imageOnly, videoOnly, folder_hash, file_hash, lang) {
        var qs    = 'id='+select.attr('id');
        var index = 0;
        var file  = cwd.replace(/^\.\//, "/") + ((index = select.val().lastIndexOf('/')) != -1 ? select.val().substr(index) : select.val() );
        qs = qs + '&file=' + file;
        if(folder) {
            qs = qs + '&folder=' + folder;
        }
        if(imageOnly) {
            qs = qs + '&image=1';
        }
        if(videoOnly) {
            qs = qs + '&video=1';
        }
        if(typeof(folder_hash) != 'undefined') {
            qs = qs + '&folder_hash=' + folder_hash;
        }
        if(typeof(file_hash) != 'undefined') {
            qs = qs + '&file_hash=' + file_hash;
        }
        if(lang) {
            qs = qs + '&lang=' + lang;
        }
        $.openPopupLayer({
            name   : "Filemanager",
            title  : getLabel('js-file-manager'),
            width  : 660,
            height : 530,
            url    : "/styles/common/other/elfinder/umifilebrowser.html?"+qs
        });

        var filemanager = jQuery('div#popupLayer_Filemanager div.popupBody');
        if (!filemanager.length) {
            filemanager = jQuery(window.parent.document.getElementById('popupLayer_Filemanager')).find('div.popupBody');
        }

        var footer = '<div id="watermark_wrapper"><label for="add_watermark">';
		footer += getLabel('js-water-mark');
		footer += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
		footer += '<label for="remember_last_folder">';
		footer += getLabel('js-remember-last-dir');
		footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
        if (getCookie('remember_last_folder', true) > 0) {
			footer += 'checked="checked"';
        }
		footer +='/></div>';

        filemanager.append(footer);
    }

    init();
};
