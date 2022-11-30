class ObjectModelCreateForm {
    constructor(modName) {
        var formContent = $('<div>');
        this.form = formContent;
        $.ajax({
            url: `/models/${modName}`,
            type: 'OPTIONS',
        }).fail(function(err) {
            return false;
        }).done(function(res) {
            var formBody = new ModelEditForm(res, "create", modName);
            formContent.append(formBody);
            console.log(formBody);
            this.form = formBody.form;
        });
    }
}


class PageInfoBox {
    constructor(opts) {
        var infoBox = $('<div>', {class: 'navHelpText'});
        var infoTitle = $('<h1>', {text: opts.title});
        var infoBody = $('<p>', {html: opts.body});
        infoBox.append(infoTitle, infoBody);
        this.box = infoBox;
    }
}

class ObjectList {
    constructor(opts) {
        var top = $('<div>');
        var tools = $('<div>', {class:'adminNewObjectToolbar'});
        if (typeof opts.filter != 'undefined' && opts.filter === true) {
            var filter = $('<input>', {
                type: 'text',
                class: 'objectListFilter',
                list_id: opts.list_id,
                placeholder: 'Filter ...'
            });
            tools.append(filter);
        }
        var list = $('<ul>', {
            class: `objectList ${opts.list_class}`,
            id: opts.list_id
        });
        top.append(tools, list);
        $.each(opts.items, (i, item) => {
            var ii = $('<li>', {
                class: 'objectItem ',
                item_id: item.id
            });
            list.append(ii);
            var ii_icon = $('<img>', {
                class: 'objectItemIcon'
            });
            if (typeof item.icon !== "undefined") {
                ii_icon.attr('src', item.icon);
            } else {
                ii_icon.attr('src', opts.item_icon);
            }
            var ii_label = $('<label>', {
                class: `objectItemLabel ${opts.item_label_class}`,
                text: item.label,
                item_id: item.id
            });
            if (typeof item.data != "undefined") {
                $.each(item.data, (tagName, tagData) => {
                    ii_label.attr(tagName, btoa(JSON.stringify(tagData)));
                });
            }
            ii.append(ii_icon, ii_label);
        });
        this.list = top;
    }
}

class FormInputList {
    constructor(options) {
        if (typeof options == "object") {
            this.form = this.generateForm(options);
        } else {
            this.form = $($(`.formInputList[form_name="${options}"]`));
        }
    }
    generateForm(options) {
        var ckeditors = [];
        var listBody = $('<ul>', {
            class: 'objectList formInputList',
            form_name: options.form_name
        });
        $.each(options.fields, function(i, field) {
            var iLine = $('<li>', {
                class: 'objectItem formInputListItem',
                field_name: field.name
            });
            var iLabel = $('<label>', {
                class: 'objectItemLabel standardWidth',
                field_name: field.name,
                text: field.display,
                for: `${options.form_name}_${field.name}`
            });
            switch(field.type) {
                case "bool":
                    var iInput = $('<input>', {
                        class: 'formInputListField',
                        form_name: options.form_name,
                        type: 'checkbox',
                        field_name: field.name,
                        id: `${options.form_name}_${field.name}`,
                    });
                    if (typeof field.default != "undefined" && (field.default === true || field.default === false)) {
                        iInput.prop('checked', field.default);
                    }
                    break;
                case "multi":
                case "option":
                    var iInput = $('<div>', {class: 'formInputListContainer'});
                    var iInputList = $('<ul>', {class: 'objectList formInputList', id: `${options.form_name}_${field.name}_list`});
                    var iListTools = $('<div>', { class: 'adminNewObjectToolbar' });
                    var iListFilter = $('<input>', {
                        type: 'text',
                        class: 'objectListFilter allowFormInputList',
                        placeholder: 'Filter Items',
                        list_id: `${options.form_name}_${field.name}_list`
                    });
                    iInput.append(iListTools.append(iListFilter), iInputList);
                    if (typeof field.option_src != "undefined") {
                        iListTools.hide(0);
                        var iListWait = $('<div>', { class: 'loading_place no-margin' });
                        iInput.append(iListWait)
                        $.ajax({
                            url: field.option_src,
                            type: 'GET'
                        }).fail(function(zerr) {
                            iListWait.remove();
                        }).done(function(zres) {
                            iListWait.remove();
                            iListTools.show(0);
                            $.each(zres, function(optName, optVal) {
                                var iInputItem = $('<li>', {
                                    class: 'objectItem formInputListItem'
                                })
                                var iInputLabel = $('<label>', {
                                    class: 'objectItemLabel',
                                    text: optName,
                                    field_name: field.name
                                });
                                var optInput = $('<input>', {
                                    type: 'radio',
                                    _name: `${options.form_name}_${field.name}`,
                                    get name() {
                                        return this._name;
                                    },
                                    set name(value) {
                                        this._name = value;
                                    },
                                    value: optVal,
                                    class: 'formInputListField',
                                    form_name: options.form_name,
                                    field_name: field.name,
                                    id: `${options.form_name}_${field.name}`
                                });
                                iInputItem.append(iInputLabel.prepend(optInput));
                                iInputList.append(iInputItem)
                            });
                        });
                    } else {
                        $.each(field.options, function(optName, optVal) {
                            var iInputItem = $('<li>', {
                                class: 'objectItem formInputListItem'
                            })
                            var iInputLabel = $('<label>', {
                                class: 'objectItemLabel',
                                text: optName,
                                field_name: field.name
                            });
                            var optInput = $('<input>', {
                                type: 'radio',
                                name: `${options.form_name}_${field.name}`,
                                value: optVal,
                                class: 'formInputListField',
                                form_name: options.form_name,
                                field_name: field.name,
                                id: `${options.form_name}_${field.name}`
                            });
                            iInputItem.append(iInputLabel.prepend(optInput));
                            iInputList.append(iInputItem)
                        });
                    }
                    break;
                case "select":
                    var iInput = $('<select>', {
                        class: 'formInputListField',
                        form_name: options.form_name,
                        field_name: field.name,
                        id: `${options.form_name}_${field.name}`
                    });
                    var iInputLabel = $('<option>', {
                        disabled: 'disabled',
                        selected: 'selected',
                        value: null,
                        text: field.display
                    });
                    iInput.append(iInputLabel);
                    $.each(field.options, function(optName, optVal) {
                        var optInput = $('<option>', {
                            value: optVal,
                            text: optName,
                            field_name: field.name,
                            form_name: options.form_name
                        });
                        iInput.append(optInput);
                    });
                    break;
                case "password":
                    var iInput = $('<input>', {
                        class: 'formInputListField',
                        type: "password",
                        placeholder: field.display,
                        field_name: field.name,
                        form_name: options.form_name,
                        id: `${options.form_name}_${field.name}`,
                    });
                    break;
                case "textarea":
                    var iInput = $('<textarea>', {
                        class: 'formInputListField',
                        type: "text",
                        placeholder: field.display,
                        field_name: field.name,
                        form_name: options.form_name,
                        id: `${options.form_name}_${field.name}`,
                    });
                    if (typeof field.placeholder != "undefined") {
                        iInput.attr('placeholder', field.placeholder);
                    }
                    if (typeof field.default != "undefined") {
                        iInput.val(field.default);
                    }
                    if (field.ckedit === true && typeof CKEDITOR == "object") {
                        ckeditors.push(`${options.form_name}_${field.name}`);
                    }
                    break;
                case "str":
                default:
                    var iInput = $('<input>', {
                        class: 'formInputListField',
                        type: "text",
                        placeholder: field.display,
                        field_name: field.name,
                        form_name: options.form_name,
                        id: `${options.form_name}_${field.name}`,
                    });
                    if (typeof field.placeholder != "undefined") {
                        iInput.attr('placeholder', field.placeholder);
                    }
                    if (typeof field.default != "undefined") {
                        iInput.val(field.default);
                    }
                    break;
            }
            iLine.append(iLabel, iInput);
            if (typeof field.buttons != "undefined") {
                $.each(field.buttons, function(i, btn) {
                    var bx = $('<button>', {
                        text: btn.label,
                        class: btn.class,
                        field_name: field.name,
                        form_name: options.form_name,
                        id: btn.id
                    });
                    iLine.append(bx);
                });
            }
            listBody.append(iLine);
        });
        if (typeof options.buttons != "undefined") {
            var btnLine = $('<li>', {
                class: 'objectItem formInputListItem'
            });
            var btnBox = $('<div>', {
                class: 'adminNewObjectToolbar align-right no-flex-grow'
            });
            listBody.append(btnLine.append(btnBox));
            $.each(options.buttons, function(i, btn) {
                var btnItem = $('<button>', {
                    form_name: options.form_name,
                    id: btn.id,
                    text: btn.label
                });
                btnBox.append(btnItem);
            })
        }
        this.ckeditors = ckeditors;
        if (ckeditors.length > 0) {
            var fil = this;
            if (typeof options.ckeditor_opts == "undefined") {
                options.ckeditor_opts = null;
            }
            setTimeout(function() {
                fil.enableCKEditors(ckeditors, options.ckeditor_opts);
            }, 500);
        }
        return listBody;
    }
    enableCKEditors(ckeditors, cke_opts = null) {
        if (cke_opts === null) {
            cke_opts = {
                height: '300px',
                toolbarGroups: [
                    { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
                    { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                    { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
                    { name: 'forms', groups: [ 'forms' ] },
                    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                    { name: 'links', groups: [ 'links' ] },
                    { name: 'insert', groups: [ 'insert' ] },
                    { name: 'styles', groups: [ 'styles' ] },
                    { name: 'colors', groups: [ 'colors' ] },
                    { name: 'tools', groups: [ 'tools' ] },
                    { name: 'others', groups: [ 'others' ] },
                    { name: 'about', groups: [ 'about' ] }
                ],
                removeButtons: 'Templates,Save,NewPage,ExportPdf,Preview,Print,PasteText,PasteFromWord,Replace,Find,SelectAll,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Subscript,Strike,Superscript,CopyFormatting,CreateDiv,JustifyBlock,BidiRtl,BidiLtr,Language,Anchor,Smiley,SpecialChar,PageBreak,Iframe,ShowBlocks,Flash,Styles,Format,Blockquote,Outdent,Indent,RemoveFormat,Source,Cut,Copy,Paste,Scayt,NumberedList,BulletedList,JustifyLeft,JustifyCenter,JustifyRight,Image,Table,HorizontalRule,Maximize,About'
            }
        }
        $.each(ckeditors, function(i, ckid) {
            $(`#${ckid}`).attr('ckedit', 'true');
            CKEDITOR.replace(ckid, cke_opts);
        });
    }
    getFormInputData(returnJSON = false) {
        var data = {};
        let thisFormName = this.form[0].getAttribute('form_name');
        console.log(thisFormName);
        $.each($(`.formInputListField[form_name="${thisFormName}"]`), function() {
            var field = $(this).attr('field_name');
            if (typeof data[field] == "undefined") {
                data[field] = [];
            }
            switch ($(this).attr('type')) {
                case 'checkbox':
                    $.each($(`.formInputListField[form_name="${thisFormName}"][field_name="${field}"]`), function() {
                        if ($(this).prop('checked') === true) {
                            data[field].push($(this).val());
                        }
                    });
                    break;
                case 'radio':
                    $.each($(`.formInputListField[form_name="${thisFormName}"][field_name="${field}"]`), function() {
                        if ($(this).prop('checked') === true) {
                            data[field] = $(this).val();
                        }
                    });
                    break;
                default:
                    if ($(this).attr('ckedit') == "true") {
                        data[field] = CKEDITOR.instances[$(this).attr('id')].getData();
                    } else {
                        data[field] = $(this).val();
                    }
            }
            console.log(field, data[field]);
        });
        if (returnJSON === true) {
            return JSON.stringify(data);
        } else {
            return data;
        }
    }
}

class ObjectModel {
    constructor(model = null) {
        let objMod = this;
        return new Promise(function(resolve, reject){
            $.ajax({
                url: '/models',
                type: 'GET'
            }).fail(err => {
                reject(err);
            }).done(mods => {
                objMod.all_models = mods;
                if (model !== null) {
                    if (typeof mods[model] == "undefined") {
                        throw new Error(`no such model ${model}`);
                    }
                    objMod.modelName = model;
                    objMod.schema = mods[model];
                }
                resolve(objMod);
            });
        });
    }
    get_items(item = null, get_opts = null) {
        let objMod = this;
        let modelName = objMod.modelName;
        if (typeof get_opts.fields != "undefined") {
            console.log(get_opts.fields);
            var fstring = `?fields=${get_opts.fields}`;
        } else if (typeof get_opts.details != "undefined" && get_opts.details === true) {
            var fstring = "?details=true"
        } else {
            var fstring = "?";
        }
        if (typeof get_opts.override_owner != "undefined" && get_opts.override_owner === true) {
            fstring += "&override_owner=1";
        }
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: `/models/${modelName}${fstring}`,
                type: 'GET'
            }).fail(err => {
                reject(err);
            }).done(items => {
                if (item === null) {
                    resolve(items);
                } else {
                    $.each(items, function(i, ii) {
                        if (ii.id == item) {
                            resolve(ii);
                        }
                    });
                    resolve(false);
                }
            });
        });
    }
    add_item(data, owner = null) {
        let objMod = this;
        let modelName = objMod.modelName;
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: `/models/${modelName}`,
                type: 'POST',
                headers: {
                    'content-type': 'application/json'
                },
                data: JSON.stringify(data)
            }).fail(err => {
                reject(err);
            }).done(res => {
                resolve(res);
            });
        });
    }
    del_item(item) {
        let objMod = this;
        let modelName = objMod.modelName;
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: `/models/${modelName}/${item}`,
                type: 'DELETE'
            }).fail(err => {
                reject(err);
            }).done(res => {
                resolve(res);
            });
        });
    }
    update_item(item, data) {
        let objMod = this;
        let modelName = objMod.modelName;
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: `/models/${modelName}/${item}`,
                type: 'PATCH',
                headers: {
                    'content-type': 'application/json'
                },
                data: JSON.stringify(data)
            }).fail(err => {
                reject(err);
            }).done(res => {
                resolve(res);
            });
        });
    }
}

class ObjectModelList {
    constructor(options) {
        if (typeof options.output_div != "undefined") {
            var waitBlock = $('<div>', {class: 'loading_place'});
            $(options.output_div).append(waitBlock);
        }
        var modName = options.model_name
        if (typeof options.list_id != "undefined") {
            var listId = options.list_id;
        } else {
            var listId = modName
        }
        var listContainer = $('<div>');
        if (typeof options.buttons != "undefined" || options.filter === true) {
            var listTools = $('<div>', {
                class: 'adminNewObjectToolbar'
            });
            listContainer.append(listTools);
            if (options.filter === true) {
                var listFilter = $('<input>', {
                    type: 'text',
                    placeholder: 'Filter',
                    class: 'objectListFilter',
                    list_id: listId
                });
                if (options.filter_fullwidth === true) {
                    listFilter.addClass('fullwidth');
                }
                listTools.append(listFilter);
            }
            if (typeof options.buttons != "undefined") {
                $.each(options.buttons, function(i, buttonConf) {
                    var button = $('<button>', {
                        text: buttonConf.text,
                        id: buttonConf.id,
                        context: buttonConf.context,
                        class: buttonConf.class
                    });
                    if (buttonConf.icon.length > 0) {
                        var buttonIcon = $('<img>', {
                            class: 'buttonIcon',
                            src: buttonConf.icon
                        });
                        button.prepend(buttonIcon);
                    }
                    $.each(buttonConf, (bKey, bVal) => {
                        switch (bKey) {
                            case "text":
                            case "id":
                            case "context":
                            case "class":
                            case "icon":
                                break;
                            default:
                                button.attr(bKey, bVal);
                        }
                    })
                    listTools.append(button);
                });
            }
        }
        var listBody = $('<ul>', {
            class: 'objectList',
            id: listId
        });
        if (typeof options.flex_grid != "undefined") {
            listBody.addClass('flexGrid');
            listBody.addClass(options.flex_grid);
        }
        listContainer.append(listBody);
        switch (modName) {
            case 'users':
            case 'groups':
                var opt_url = `/api/admin/${modName}`;
                var get_url = `/api/admin/${modName}`;
                break;
            default:
                var opt_url = `/models/${modName}`;
                var get_url = `/models/${modName}?details=true`;
        }
        $.ajax({
            url: opt_url,
            type: 'OPTIONS',
            dataType: 'json'
        }).done(function(opt_res) {
            if (modName == "users" || modName == "groups") { opt_res = opt_res.fields; }
            $.ajax({
                url: get_url,
                type: 'GET',
                dataType: 'json'
            }).fail(function(err) {
                console.log(err);
                return false
            }).done(function(res) {
                if (modName == "users" || modName == "groups") {
                    var res_list = [];
                    $.each(res, function(name, conf) {
                        var res_item = {};
                        res_item['name'] = name;
                        $.each(conf, function(key, val) {
                            res_item[key] = val;
                        });
                        res_list.push(res_item);
                    });
                    res = res_list;
                }
                $.each(res, function(i, modItem) {
                    setTimeout(function() {
                        processListItem(options, res, opt_res, modName, modItem, listBody);
                    }, 0.1);
                });
                if (typeof waitBlock != "undefined") { waitBlock.remove(); }
                return true;
            });
        });
        this.list = listContainer;
    }
    enablednd() {
        this.list.children('ul').dragndrop();
        $.each(this.list.children('ul').children('li.firstItem'), function() {
            var tools = $(this).children('.objectItemTools');
            var drag = $('<img>', {
                class: 'objectItemToolsDrag',
                src: '/resource/updown_black.png'
            });
            tools.prepend(drag);
        });
    }
    disablednd() {
        this.list.find('ul').dragndrop('disable');
    }
}

class ModelEditForm {
    constructor(schema, item, context = "modelEdit", callback = null) {
        var returnDiv = $('<div>');
        if (item == "create") {
            var create_form = true;
            item = {
                "id": "__new__"
            };
        }
        var modEditList = $('<ul>', {
            class: 'objectList',
        });
        var ckeditList = [];
        returnDiv.append(modEditList);
        $.each(schema, function(i, field) {
            if (field.auto === true) { return; }
            var eItem = $('<li>', {
                class: 'objectItem formInputListItem',
                item_id: field.name
            });
            if (typeof field.onlyif != "undefined") {
                eItem.attr('onlyif', btoa(JSON.stringify(field.onlyif)));
            }
            var eLabel = $('<label>', {
                class: 'objectItemLabel standardWidth',
                for: `modEdit_${field.name}_${item.id}`,
                text: field.display
            });
            switch (field.type) {
                case "file":
                    var eInput = $('<input>', {
                        context: context,
                        class: 'modEditInput',
                        type: 'file',
                        id: `modEdit_${field.name}_${item.id}`,
                        item_id: item.id,
                        required: field.required,
                        field_name: field.name,
                    });
                    eItem.append(eLabel, eInput);
                    if (field.file_type === "image") {
                        eInput.css('flex-grow', '1');
                        var ePreviewDiv = $('<div>', {
                            class: 'modEditInputPreview',
                            type: field.type,
                            item_id: item.id,
                            field_name: field.name
                        });
                        var ePreviewImg = $('<img>', {
                            class: 'modEditImagePreview',
                            type: field.type,
                            item_id: item.id,
                            field_name: field.name
                        });
                        if (item.id != "__new__" && typeof item[field.name] !== "undefined" && item[field.name].length > 0) {
                            ePreviewImg.attr('src', '/models/' + context + '/' + item.id + '/' + field.name);
                            //ePreviewImg.attr('src', item[field.name].split(':')[0]);
                        }
                        eItem.append(ePreviewDiv.append(ePreviewImg));
                    }
                    modEditList.append(eItem);
                    break;
                case "bool":
                    var eInput = $('<input>', {
                        context: context,
                        class: 'modEditInput',
                        type: 'checkbox',
                        id: `modEdit_${field.name}_${item.id}`,
                        field_name: field.name,
                        item_id: item.id,
                        required: field.required
                    });
                    if (item[field.name] === true) {
                        eInput.prop('checked', true);
                    }
                    if (typeof field.default != "undefined") {
                        eInput.prop('checked', field.default);
                    }
                    eItem.append(eLabel, eInput);
                    modEditList.append(eItem);
                    break;
                case "multi":
                    var eInputCont = $('<div>', {
                        class: 'formInputListContainer'
                    });
                    var eInputTools = $('<div>', {
                        class: 'adminNewObjectToolbar'
                    });
                    var eInputFilter = $('<input>', {
                        class: 'objectListFilter allowFormInputList fullwidth',
                        list_id: `${context}_${item.id}_${field.name}`,
                        type: 'text',
                        placeholder: 'Filter'
                    });
                    eInputTools.append(eInputFilter);
                    var eInputList = $('<ul>', {
                        class: 'objectList formInputList',
                        id: `${context}_${item.id}_${field.name}`
                    });
                    eInputCont.append(eInputTools, eInputList);
                    if (typeof field.option_src !== 'undefined') {
                        var objectOptions = false;
                        var optionUri = field.option_src;
                        if (typeof field.object_options !== 'undefined' && field.object_options === true) {
                            objectOptions = true;
                            optionUri = field.option_src + '?fields=name,id';
                        }
                        $.ajax({
                            url: optionUri,
                            type: 'GET'
                        }).done(function(zres) {
                            $.each(zres, function(optName, optVal) {
                                var optId = optVal.id;
                                var optDisplayName = optVal.name;
                                if (objectOptions === true) {
                                    var optItem = $('<li>', {
                                       class: 'objectItem formInputListItem'
                                    });
                                    var optSelect = $('<input>', {
                                        type: 'checkbox',
                                        class: 'adminUserEditListItem',
                                        id: `multiSelect_${optId}_${field.name}_${item.id}`,
                                        context: context,
                                        item_id: item.id,
                                        option_id: optId,
                                        field_name: field.name
                                    });
                                    var optLabel = $('<label>', {
                                        class: 'objectItemLabel',
                                        text: optDisplayName,
                                        for: `multiSelect_${optId}_${field.name}_${item.id}`
                                    });
                                    console.log(item);
                                    if (typeof item[field.name] !== 'undefined' && item[field.name] !== null) {
                                        if (item[field.name].includes(optId)) {
                                            optSelect.prop('checked', true);
                                        }
                                    }
                                    optItem.append(optSelect, optLabel);
                                    eInputList.append(optItem);
                                } else {
                                    console.log(optName, optVal);
                                }
                            });
                        });
                    } else {
                        $.each(field.options, function(optName, optVal) {
                            if (typeof field.name_field == "undefined" || field.name_field == "__key__") {
                                var optDisplayName = optName;
                            } else {
                                var optDisplayName = optVal[field.name_field];
                            }
                            if (typeof field.id_field == "undefined" || field.id_field == "__val__") {
                                var optId = optVal;
                            } else {
                                var optId = optVal[field.id_field];
                            }
                            var optItem = $('<li>', {
                                class: 'objectItem formInputListItem'
                            });
                            var optSelect = $('<input>', {
                                type: 'checkbox',
                                class: 'adminUserEditListItem',
                                id: `multiSelect_${optId}_${field.name}_${item.id}`,
                                context: context,
                                item_id: item.id,
                                option_id: optId,
                                field_name: field.name
                            });
                            var optLabel = $('<label>', {
                                class: 'objectItemLabel',
                                text: optDisplayName,
                                for: `multiSelect_${optId}_${field.name}_${item.id}`,
                            });
                            if (field.name == "users") {
                                optLabel.text(optVal.fullname);
                                var optTag = $('<div>', {
                                    class: 'objectItemTag',
                                    text: optVal.email
                                });
                                optLabel.append(optTag);
                            }
                            if (optVal[field.match_field] == item.id || (typeof item[field.name] != "undefined" && item[field.name] !== null && item[field.name].includes(optId))) {
                                optSelect.prop('checked', true);
                            }
                            optItem.append(optSelect, optLabel);
                            eInputList.append(optItem);
                        });
                    }

                    eItem.append(eLabel, eInputCont);
                    modEditList.append(eItem);
                    break;
                case "select":
                case "option":
                    var eInput = $('<select>', {
                        context: context,
                        class: 'modEditInput',
                        id: `modEdit_${field.name}_${item.id}`,
                        field_name: field.name,
                        item_id: item.id,
                        required: field.required
                    });
                    if (create_form === true) {
                        var eLabelOpt = $('<option>', {
                            value: null,
                            text: `${field.display}...`,
                            disabled: 'disabled',
                            selected: 'selected'
                        });
                        eInput.append(eLabelOpt);
                    }

                    if (typeof field.option_src != "undefined") {
                        $.ajax({
                            url: field.option_src,
                            type: 'GET',
                            headers: {
                                'accept': 'application/json'
                            }
                        }).done(opts => {
                            $.each(opts, function(optName, optVal) {
                                var optItem = $('<option>', {value: optVal, text: optName});
                                if (optVal == item[field.name]) {
                                    optItem.attr('selected', 'selected');
                                }
                                eInput.append(optItem);
                            });
                        });
                    } else if (typeof field.model_src != "undefined") {
                        $.ajax({
                            url: '/models/' + field.model_src + '?fields=name,id',
                            type: 'GET',
                            headers: {
                                'accept': 'application/json'
                            }
                        }).done(opts => {
                            $.each(opts, function(i, optData) {
                                var optItem = $('<option>', {value: optData.id, text: optData.name});
                                if (optData.id == item[field.name]) {
                                    optItem.attr('selected', 'selected');
                                }
                                eInput.append(optItem);
                            });
                        });
                    } else {
                        $.each(field.options, function(optName, optVal) {
                            var optItem = $('<option>', {
                                value: optVal,
                                text: optName
                            });
                            if (field.name == "rank") {
                                optItem.val(optName);
                            }
                            if (typeof field.id_field != "undefined") {
                                optItem.val(optVal[field.id_field]);
                                var valChk = optVal[field.id_field];
                            } else {
                                var valChk = optVal;
                            }
                            if (optName == item[field.name] || valChk == item[field.name]) {
                                optItem.attr('selected', 'selected');
                            }
                            eInput.append(optItem);
                            if (optVal == item[field.name]) {
                                optItem.attr('selected', 'selected');
                            }
                        });
                    }

                    eItem.append(eLabel, eInput);
                    modEditList.append(eItem);
                    break;
                case "str":
                default:
                    if (field.type == "datetime-local") {
                        var ftype = "datetime-local";
                    } else {
                        var ftype = "text";
                    }
                    if (field.textarea === true) {
                        var inputId = `${context}_cke_${field.name}_${item.id}`;
                        var eInput = $('<textarea>', {
                            name: inputId,
                            id: inputId,
                            context: context,
                            class: 'modEditInput',
                            field_name: field.name,
                            item_id: item.id,
                            required: field.required,
                            field_type: field.type
                        });
                        if (typeof field.ckedit !== 'undefined' && field.ckedit === true) {
                            eInput.attr('ckedit', 'true');
                            if (typeof field.ckeditor_opts !== 'undefined') {
//                                var cke = btoa(field.ckeditor_opts);
                                var cke = btoa(JSON.stringify(field.ckeditor_opts));
                                eInput.attr('cke_opts', cke);
                            }
                            ckeditList.push(inputId);
                        }
                        eInput.text(item[field.name]);
                        eItem.append(eLabel, eInput);
                        modEditList.append(eItem);
//                        if (field.ckedit === true) {
//                            ckeditList.push(inputId);
//                        }
                    } else {
                        var eInput = $('<input>', {
                            context: context,
                            class: 'modEditInput',
                            type: ftype,
                            placeholder: field.display,
                            id: `modEdit_${field.name}_${item.id}`,
                            value: item[field.name],
                            field_name: field.name,
                            item_id: item.id,
                            required: field.required
                        });
                        eItem.append(eLabel, eInput);
                        modEditList.append(eItem);
                    }
                    if (field.type == "password") {
                        eInput.attr('type', 'password');
                        eInput.val('');
                        var eItem_verify = $('<li>', {
                            class: 'objectItem formInputListItem',
                            field_name: field.name
                        });
                        var eLabel_verify = $('<label>', {
                            class: 'objectItemLabel standardWidth',
                            text: `Verify ${field.display}`,
                            for: `modEdit_${field.name}_${item.id}_verify`
                        });
                        var eInput_verify = $('<input>', {
                            class: 'modEdit_verify',
                            item_id: item.id,
                            context: context,
                            type: 'password',
                            placeholder: `Verify ${field.display}`,
                            id: `modEdit_${field.name}_${item.id}_verify`
                        });
                        eItem_verify.append(eLabel_verify, eInput_verify);
                        modEditList.append(eItem_verify);
                    } else {
                        if (typeof field.autocapitalize != "undefined") {
                            eInput.attr('autocapitalize', field.autocapitalize);
                        }
                        if (typeof field.default != 'undefined') {
                            eInput.val(field.default);
                        }
                    }
            }
            if (typeof field.onlyif != "undefined") {
                eItem.hide(0);
            }
        });
        if (create_form !== true) {
            var modTools = $('<div>', {
                class: 'adminNewObjectToolbar align-right no-flex-grow'
            });
            var modSubmit = $('<button>', {
                context: context,
                class: 'modelEditSubmit',
                item_id: item.id,
                text: 'Submit',
                callback: callback
            });
            modTools.append(modSubmit);
            returnDiv.append(modTools);
        }
        if (ckeditList.length > 0) {
            setTimeout(function() {
                $.each(ckeditList, (i, ii) => {
                    var ckeobj = $('#' + ii);
                    if (typeof ckeobj.attr('cke_opts') !== 'undefined') {
                        var ckeo = atob(ckeobj.attr('cke_opts'));
                        console.log(ckeo);
                        CKEDITOR.replace(ii, JSON.parse(ckeo));
                    } else {
                        CKEDITOR.replace(ii);
                    }
                });
            }, 100);
        }
        this.form = returnDiv;
    }
    disableDefaultAction() {
        this.form.find('.modelEditSubmit').addClass('disableDefaultAction');
    }
}

$(document).on('change', '.modEditInput', function() {
    var field_name = $(this).attr('field_name');
    switch ($(this).attr('type')) {
        case 'checkbox':
        case 'radio':
            var field_val = $(this).prop('checked');
            break;
        default:
            var field_val = $(this).val();
    }
    // console.log(field_name);
    $.each($('.modEditInput'), function() {
        var show_field = false;
        var field_item = $(this).parent();
        if (typeof field_item.attr('onlyif') != "undefined") {
            var onlyif = JSON.parse(atob(field_item.attr('onlyif')));
            $.each(onlyif, (i, v) => {
                show_field = false
                if (i == field_name) {
                    if (typeof v == "object") {
                        $.each(v, (ii, vv) => {
                            if (vv == field_val) {
                                show_field = true;
                            }
                        });
                    } else if (v == field_val) {
                        show_field = true;
                    }
                }
            });
            if (show_field === true) {
                field_item.show(0);
            } else {
                field_item.hide(0);
            }
        }
    });
});
