/**
 * Контрол набора файолов
 * Created by bender on 17.02.16.
 */

var ControlMultiImage = function (options) {
    var wrapper = options.container || null,
        id = '',
        prefix = '',
        lang = options.lang || 'ru',
        cwd = '.',
        maxorder  = 0;

    function init(){
        id = wrapper.attr('id');
        prefix = wrapper.attr('data-prefix');
        var files = $('.mimage_wrapper div.multi_image',wrapper);
        if (files.length > 0){
            $.each(files,function(id,item){
                var el = $(item),
                    prop = {};

                prop.file = el.attr('umi:file');
                prop.alt = el.attr('umi:alt');
                prop.order = el.attr('umi:order');
                prop.id = el.attr('umi:id');
                prop.el = el;

                maxorder = prop.order > maxorder ? prop.order : maxorder;
                init_control(prop);
            })
        }

        init_control({
            file: '',
            alt: '',
            order: -1,
            id:'',
            el: $('.emptyfield',wrapper)
        });

        $(".mimage_wrapper").sortable({
            placeholder: "thumbnail-placeholder",
            helper:'clone',
            revert: true,
            update: function( event, ui ) {
                updateOrder();
            }
        });
    }

    function init_control(prop){
        var container = prop.el,
            selectId = 'miSelect_'+prop.id+'_'+id,
            setings = prop;
        container.html = '';
        container.append($([
            '<div class="thumbnail">'+( prop.file !== '' ? '<img src="'+prop.file+'"/>' : getLabel('js-image-field-empty') ),
            (prop.file == '' ? '':'<div class="close">&times;</div><div class="alt">ALT</div>'),
            '</div>',
            (prop.file == '' ? '<input type="hidden" name="" value="" id="'+selectId+'"/>' : ''),
            (prop.file != '' ? '<input type="hidden" name="'+prefix+'['+prop.id+'][src]" value=".'+prop.file+'" id="'+selectId+'"/>' : ''),
            (prop.file != '' ? '<input type="hidden" name="'+prefix+'['+prop.id+'][alt]" value="'+prop.alt+'" />' : ''),
            (prop.file != '' ? '<input type="hidden" name="'+prefix+'['+prop.id+'][order]" value="'+prop.order+'" />' : '')
        ].join('')));

        var selectObj = $('input[name$="[src]"]',container);
        if (prop.file == ''){
            selectObj = $('input',container);
        } else {
            var altInput = $('input[name$="[alt]"]',container);
            container.find('.close').on('click',function(e){
                closeClickhandler(setings);
                e.stopPropagation();
            });
            container.find('.alt').on('click',function(e){
                altClickhandler(altInput);
                e.stopPropagation();
            });
        }
        window[selectId] = selectObj[0];

        container.find('.thumbnail').on('click',function(){
            showelfinderFileBrowser(selectObj,cwd,true,false,'','',lang)
        });

        selectObj.on('change',function(e){
            fileChangehandler($(this), setings);
        });

    }

    function fileChangehandler(obj,prop){
        var el = $(prop.el),
            val = obj.val();
        el.html('');
        prop.file = val;
        if (prop.id == ''){
            prop.id = prop.file;
            prop.order = maxorder+1;
            maxorder = prop.order;
            $(prop.el)[0].className = 'multi_image';
            $(prop.el).attr('id','mifile_'+prop.id);
            addEmptyField();
        }
        init_control(prop);
        updateOrder();
    }

    function closeClickhandler(prop){
        $(prop.el).remove();
    }

    function altClickhandler(selobj){

        openDialog('', getLabel('js-image-field-alt-dialog-title'), {
            html: '<input type="text" class="default" value="'+$(selobj).val()+'" id="newAltForImage"/>',
            confirmText: 'OK',
            confirmOnEnterElement: '#newAltForImage',
            cancelButton: true,
            cancelText: getLabel('js-cancel'),
            confirmCallback: function (dialogName) {
                var val = $('#newAltForImage').val();
                selobj.val(val);
                closeDialog(dialogName);
            }
        });

    }

    function updateOrder(){
        var els = $('.mimage_wrapper div.multi_image');
        if (els.length > 0){
            maxorder = 0;
            for (var i= 0, cnt = els.length;i<cnt;i++){
                var id = $(els[i]).attr('id');
                maxorder++;
                $(els[i]).find('input[name$="[order]"]').val(maxorder);

            }
        }
    }

    function addEmptyField(){
        wrapper.find('.mimage_wrapper').append($([
            '<div class="emptyfield" ></div>'
        ].join('')));

        init_control({
            file: '',
            alt: '',
            order: -1,
            id:'',
            el: $('.emptyfield',wrapper)
        });

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
		footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"';
        if (getCookie('remember_last_folder', true) > 0) {
			footer += 'checked="checked"';
        }
		footer +='/></div>';

        filemanager.append(footer);
    }

    init();
};