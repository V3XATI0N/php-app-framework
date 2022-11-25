function submitSystemSetting(optName, optSource, optVal, optType) {
    if (optType == "int") {
        optVal = Number(optVal);
    }
    $.ajax({
        url: '/api/admin/settings/' + optName,
        type: 'PATCH',
        headers: {
            'content-type': 'application/json',
            'accept': 'application/json'
        },
        data: JSON.stringify({"state": optVal, "source": optSource})
    }).fail(function(err) {
        switch (optType) {
            case "int":
            case "str":
                var current = $('.adminOptSetInput[option='+$.escapeSelector(optName)+']').attr('current');
                $('.adminOptSetInput[option='+$.escapeSelector(optName)+']').val(atob(current));
                break;
            case "bool":
                var current = $('.adminOptSetCheckbox[option='+$.escapeSelector(optName)+']').attr('current');
                if (current == "true") {
                    $('.adminOptSetCheckbox[option='+$.escapeSelector(optName)+']').prop('checked', true);
                } else {
                    $('.adminOptSetCheckbox[option='+$.escapeSelector(optName)+']').prop('checked', false);
                }
                break;
        }
        console.log(err);
    }).done(function(res) {
        if (typeof(res.changes) != "undefined" && Object.keys(res.changes).length >= 1) {
            $.each(res.changes, function(itemName, itemSet) {
                if (itemName != optName) {
                    $('.adminOptSetCheckbox[option="'+itemName+'"]').prop('checked', itemSet.value);
                }
                if (itemSet.script != "") {
                    eval(itemSet.script);
                }
            });
        }
    });
}

function loadPluginAdminPageModule(pluginName, moduleName) {
    var domTemp = $('<div>', {
        css: {
            'display': 'none'
        },
        class: 'loadPluginAdminPageModule',
        plugin_name: pluginName,
        module_name: moduleName
    });
    $('body').append(domTemp);
    domTemp.click();
    console.log(domTemp);
    setTimeout(function() {
        domTemp.remove();
    }, 500);
}

function loadAdminPageModule(target) {
    var waitBlock = $('<div>', {class: 'loading_place'});
    var navItem = $('admin navItem[target_item="'+target+'"]');
    navItem.parent().animate({
        scrollTop: navItem.offset() - 20,
        scrollLeft: navItem.offset() - 20
    });
    var targetSplit = target.split('/');
    if (targetSplit[0] == "plugin") {
        return false;
    }
    $('#adminContent').html(waitBlock);
    $.ajax({
        url: '/api/admin/' + target,
        type: 'GET',
        headers: {
            accept: 'application/json'
        }
    }).fail(function(err) {
        // console.log(err);
        $('#adminContent').text(err.responseText + " " + err.statusText + " (" + err.status + ")");
    }).done(function(res) {
        waitBlock.remove();
        switch (target) {
            case "users":
            case "groups":
                var contentCatch = $('<div>', {
                    class: 'adminContentCatch',
                    id: 'adminLoadMainList'
                });
                if (target == "users") {
                    var tagName = 'fullname';
                    var tagValues = [
                        'name',
                        'email'
                    ];
                    var listTitle = pageHeader('User Accounts');
                } else {
                    var tagName = 'name';
                    var tagValues = [
                        'url'
                    ];
                    var listTitle = pageHeader('User Groups');
                }
                contentCatch.append(listTitle);
                $('#adminContent').html(contentCatch);
                var objectList = new ObjectModelList({
                    output_div: '#adminLoadMainList',
                    model_name: target,
                    edit_items: false,
                    actions: true,
                    icon_source: '/resource/' + target + '.png',
                    filter: true,
                    labelValue: tagName,
                    tagValues: tagValues,
                    buttons: [
                        {
                            "id": "newUserGroup_" + target,
                            "class": "adminNewUserGroupButton",
                            "context": target,
                            "text": "New",
                            "icon": "/resource/white_plus.png"
                        },
                        {
                            "id": "userGroupExport",
                            "class": "adminExportUserGroupData",
                            "context": target,
                            "text": "Export",
                            "icon": "/resource/export_white.png"
                        },
                        {
                            "id": "userGroupImport",
                            "class": "adminImportUserGroupData",
                            "context": target,
                            "text": "Import",
                            "icon": "/resource/import_white.png"
                        }
                    ]
                });
                contentCatch.append(objectList.list);
                break;
            case "models":
                var listCatch = $('<div>', {
                    class: 'adminContentCatch'
                });
                $('#adminContent').html(listCatch);
                var objList = $('<ul>', {
                    class: 'objectList',
                });
                listCatch.append(pageHeader('Object Models'), objList);
                $.each(res, function(objName, objConf) {
                    if (objConf.hide_on_admin_page === true) { return; }
                    var objItem = $('<li>', {
                        class: 'objectItem adminOptItem',
                        option: objName
                    });
                    var objIcon = $('<img>', {
                        class: 'objectItemIcon',
                        src: '/resource/object.png'
                    });
                    var objLabel = $('<label>', {
                        class: 'objectItemLabel optItemLabel actionTrigger',
                        action_class: '.adminOptAction',
                        item_id: objName,
                        text: objConf.humanname
                    });
                    var objAction = $('<div>', {
                        class: 'objectAction adminOptAction',
                        item_id: objName,
                    });
                    if (typeof objConf.admin_manage == "undefined" || objConf.admin_manage === false) {
                        var objItemModListOpts = {
                            model_name: objName,
                            edit_items: false,
                            delete_items: false,
                            icon: false,
                            filter: true,
                            filter_fullwidth: true
                        }
                    } else {
                        var objItemModListOpts = {
                            model_name: objName,
                            edit_items: true,
                            delete_items: true,
                            icon: false,
                            filter: true,
                            buttons: [
                                {
                                    text: "Add...",
                                    class: "core_adminCreateModelObject",
                                    id: "core_createModelObject_" + objName,
                                    context: objName,
                                    model_name: objConf.humanname,
                                    icon: "/resource/white_plus.png"
                                }
                            ]
                        }
                    }
                    var objItemModList = new ObjectModelList(objItemModListOpts);
                    objAction.append(objItemModList.list);
                    objItem.append(objIcon, objLabel, objAction);
                    objList.append(objItem);
                });
                break;
            case "settings":
                var listCatch = $('<div>', {
                    class: 'adminContentCatch'
                });
                var pageTitle = pageHeader('System Settings');
                listCatch.append(pageTitle);
                $('#adminContent').html(listCatch);
                var masterToolbox = $('<div>', {
                    class: 'adminNewObjectToolbar'
                });
                var masterFilter = $('<input>', {
                    type: 'text',
                    class: 'multiListFilter',
                    list_class: '.admin_settings_multilist',
                    placeholder: 'Find Settings...'
                });
                masterToolbox.append(masterFilter);
                listCatch.append(masterToolbox)
                $.each(res.groups, function(groupName, groupConf) {
                    if (typeof groupConf.dark_release != "undefined" && groupConf.dark_release === true) {
                        return;
                    }
                    var groupTitle = $('<h3>', {
                        class: 'multiListTitle sectionTrigger admin_settings_multilist',
                        section_class: '.adminOptSection',
                        item_id: groupName,
                        text: groupConf.name
                    });
                    var groupSection = $('<div>', {
                        class: 'adminOptSection sectionAction admin_settings_multilist',
                        item_id: groupName
                    });
                    var groupList = $('<ul>', {
                        class: 'objectList admin_settings_multilist',
                        id: 'admin_option_list_' + groupName,
                        group: groupName
                    });
                    listCatch.append(groupTitle, groupSection.append(groupList));
                });
                $.each(res.core, function(optName, optConf) {
                    if (optConf.hide === true) { return; }
                    var optItem = $('<li>', {
                        class: 'objectItem adminOptItem admin_settings_multilist',
                        option: optName
                    });
                    var optLabel = $('<label>', {
                        class: 'objectItemLabel optItemLabel actionTrigger',
                        action_class: '.adminOptAction',
                        item_id: optName,
                        text: optConf.name
                    });
                    var optLabelTag = $('<div>', {
                        class: 'objectItemTag',
                        text: optConf.description
                    });
                    var optIcon = $('<img>', {
                        class: 'objectItemIcon',
                        src: '/resource/' + optConf.type + '_edit.png'
                    });
                    optItem.append(optIcon, optLabel.append(optLabelTag));
                    $('.objectList[group='+optConf.group+']').append(optItem);
                    var optAction = $('<div>', {
                        class: 'objectAction adminOptAction',
                        item_id: optName,
                    });
                    var optSetLabel = $('<label>', {
                        class: 'adminOptSetLabel ' + optConf.type,
                        text: ""
                    });
                    switch (optConf.type) {
                        case "str":
                        case "int":
                        case "color":
                        case "password":
                            var optSetInput = $('<input>', {
                                type: 'text',
                                placeholder: optConf.name,
                                class: 'adminOptSetInput',
                                option: optName,
                                value: optConf.value,
                                source: optConf.source,
                                current: btoa(optConf.value),
                                opt_type: optConf.type
                            });
                            var optSetSubmit = $('<button>', {
                                class: 'adminOptSetSubmit',
                                option: optName,
                                source: optConf.source,
                                text: "save",
                                opt_type: optConf.type
                            });
                            optSetLabel.append(optSetInput, optSetSubmit);
                            if (optConf.type == "int") {
                                optSetInput.attr('type', 'number');
                            }
                            if (optConf.type == "color") {
                                optSetInput.attr('type', 'color');
                                optSetInput.css('opacity', 0);
                                optSetLabel.css('background-color', optConf.value);
                            }
                            if (optConf.type == "password") {
                                optSetInput.attr('type', 'password');
                                var passwordView = $('<input>', {
                                    class: "adminOptSetPasswordView",
                                    type: "text",
                                    value: optConf.value,
                                    option: optName,
                                    source: optConf.source
                                });
                                var viewToggle = $('<button>', {
                                    class: 'adminOptSetPasswordViewToggle',
                                    text: "show",
                                    option: optName,
                                    source: optConf.source
                                })
                                optSetLabel.append(viewToggle, passwordView);
                            }
                            break;
                        case "bool":
                            optSetLabel.text(optName);
                            var optSetInput = $('<input>', {
                                type: 'checkbox',
                                class: 'adminOptSetCheckbox',
                                option: optName,
                                checked: optConf.value,
                                source: optConf.source,
                                current: optConf.value,
                                opt_type: optConf.type
                            });
                            optSetLabel.prepend(optSetInput);
                            break;
                    }
                    optAction.append(optSetLabel);
                    optItem.append(optAction);
                    var optView = $('<pre>', {
                        text: 'option name: ' + optName + '\ndata type: ' + optConf.type + '\ncontrolled by: ' + optConf.source + "\nrequires: " + JSON.stringify(optConf.require)
                    });
                    if (optConf.type != "password") {
                        var infoTxt = optView.text();
                        optView.text(infoTxt += "\ndefault: " + optConf.default);
                    }
                    optAction.append(optView);
                });
                break;
            case "update":
                // console.log(res);
                var uCatch = $('<div>', { class: 'adminContentCatch' });
                $('#adminContent').html(uCatch.append(pageHeader('System Updates')));
                var verDataList = $('<ul>', {
                    class: 'objectList formInputList'
                });
                var verLine = $('<li>', {
                    class: 'objectItem formInputListItem'
                });
                var verLabel = $('<label>', {
                    class: 'objectItemLabel standardWidth',
                    text: "Current Version:"
                });
                var verInput = $('<input>', {
                    type: 'text',
                    value: res.version,
                    disabled: 'disabled'
                });
                var changeLine = $('<li>', {
                    class: 'objectItem formInputListItem'
                });
                var changeLabel = $('<label>', {
                    class: 'objectItemLabel standardWidth',
                    text: 'Change Log:'
                });
                var changeContent = $('<div>', {
                    class: 'formInputListContainer'
                });
                verDataList.append(
                    verLine.append(
                        verLabel,
                        verInput
                    ),
                    changeLine.append(
                        changeLabel,
                        changeContent
                    )
                );
                uCatch.append(verDataList);
                $.each(res.changelog, function(verName, verItems) {
                    var verTitle = $('<h3>', {
                        class: 'multiListTitle sectionTrigger',
                        section_class: '.changelog_version',
                        item_id: verName,
                        text: verName
                    });
                    var verItemDiv = $('<div>', {
                        class: 'changelog_version sectionAction',
                        item_id: verName
                    });
                    var verItemList = $('<ul>', {
                        class: 'objectList',
                        group: verName
                    });
                    verItemDiv.append(verItemList);
                    $.each(verItems, function(i, item) {
                        var verListItem = $('<li>', {
                            class: 'objectItem',
                            text: item
                        });
                        verItemList.append(verListItem);
                    });
                    changeContent.append(verTitle, verItemDiv);
                });
                break;
            case "audit_log":
                var auditCatch = $('<div>', {class: 'adminContentCatch', context: 'audit_log', list_start: '1', list_end: '25'});
                $('#adminContent').html(auditCatch);
                var logTitle = pageHeader("Audit Log");
                var logTools = $('<div>', {
                    class: 'adminNewObjectToolbar'
                });
                var logFilter = $('<input>', {
                    class: 'objectListFilter fullwidth',
                    list_id: 'admin_auditLogList',
                    type: 'text',
                    placeholder: 'Filter'
                });
                logTools.append(logFilter);
                var logList = $('<ul>', {
                    class: 'objectList',
                    id: 'admin_auditLogList'
                });
                auditCatch.append(logTitle, logTools, logList);
                $.each(res, function(i, event) {
                    var eItem = $('<li>', {
                        class: 'objectItem auditLogEventItem',
                        item_id: event.log_id
                    });
                    $.each(event, function(evTag, evData) {
                        switch (evTag) {
                            case "method":
                            case "user":
                            case "path":
                            case "time":
                                var evTagLabel = $('<label>', {
                                    class: 'objectItemLabel actionTrigger auditLogEventLabel',
                                    text: evData,
                                    action_class: '.auditLogEventAction',
                                    item_id: event.id
                                });
                                eItem.append(evTagLabel);
                                break;
                            case "addr":
                                $.each(evData, function(i, addr) {
                                    var evAddrLabel = $('<label>', {
                                        class: 'objectItemLabel actionTrigger auditLogEventLabel',
                                        text: addr,
                                        action_class: '.auditLogEventAction',
                                        item_id: event.id
                                    });
                                    eItem.append(evAddrLabel);
                                });
                                break;
                        }
                    });
                    var evAction = $('<div>', {
                        class: 'objectAction auditLogEventAction',
                        item_id: event.id
                    });
                    eItem.append(evAction);
                    logList.append(eItem);
                });
                /*
                $('.adminContentCatch[context="audit_log"]').on('scroll', function() {
                    var box = $('.adminContentCatch[context="audit_log"]');
                    var boxStart = Number(box.attr('list_start'));
                    var boxEnd = Number(box.attr('list_end'));
                    if (box.scrollTop() + box.innerHeight() >= box[0].scrollHeight) {
                        console.log(box, boxStart, boxEnd);
                        box.attr('list_end', boxEnd + 25);
                        box.attr('list_start', boxEnd);
                        appendLogPage(boxStart);
                    }
                });
                */
                break;
            case "plugins":
                var contentCatch = $('<div>', {
                    class: 'adminContentCatch'
                });
                var pageTitle = pageHeader('Plugins');
                var pluginTools = $('<div>', {
                    class: 'adminNewObjectToolbar'
                });
                var uploadPlugin = $('<button>', {
                    id: 'adminUploadPlugin',
                    text: 'Install...'
                });
                var pluginFilter = $('<input>', {
                    type: 'text',
                    placeholder: 'Filter',
                    class: 'objectListFilter',
                    list_id: 'adminPluginList'
                });
                var pluginList = $('<ul>', {
                    class: 'objectList',
                    id: 'adminPluginList'
                });
                contentCatch.append(pageTitle, pluginTools.append(pluginFilter, uploadPlugin), pluginList);
                $('#adminContent').html(contentCatch);
                $.each(res, function(pluginName, pluginConf) {
                    var pluginItem = $('<li>', {
                        class: 'objectItem adminPluginItem',
                        plugin: pluginName
                    });
                    var pluginLabel = $('<label>', {
                        class: 'objectItemLabel adminPluginItem actionTrigger',
                        action_class: '.adminPluginAction',
                        item_id: pluginName,
                        text: pluginConf.name
                    });
                    var pluginTools = $('<div>', {
                        class: 'objectItemTools'
                    });
                    var pluginLabelTag = $('<div>', {
                        class: 'objectItemTag',
                        text: pluginConf.description
                    });
                    var pluginIcon = $('<img>', {
                        class: 'objectItemIcon',
                    });
                    if (pluginConf.logo_image) {
                        pluginIcon.attr('src', pluginConf.logo_image);
                    } else {
                        pluginIcon.attr('src', '/resource/generic_plugin.png');
                        pluginIcon.css({
                            'background': 'black',
                            'border-radius': '200px',
                            'padding': '4px',
                            'box-sizing': 'border-box'
                        });
                    }
                    pluginItem.prepend(pluginIcon);
                    pluginLabel.append($('<br>'), pluginLabelTag);
                    var pluginAction = $('<div>', {
                        class: 'adminPluginAction objectAction',
                        item_id: pluginName
                    });
                    if (typeof pluginConf.info != "undefined") {
                        var pluginInfoBox = $('<div>', {
                            class: 'pluginInfoBox',
                            html: pluginConf.info
                        });
                        pluginAction.append(pluginInfoBox);
                    }
                    var pluginEnableLabel = $('<label>', {
                        class: 'objectActionLabel alignLeft',
                        text: 'Enabled'
                    });
                    var pluginEnableCheck = $('<input>', {
                        class: 'objectActionInput adminPluginEnableCheck',
                        type: 'checkbox',
                        plugin: pluginName,
                        checked: pluginConf.enabled
                    });
                    pluginEnableLabel.prepend(pluginEnableCheck);
                    var pluginInfoGrid = $('<div>', {
                        class: 'gridTop'
                    });
                    var pluginControls = $('<div>', {
                        class: 'whole'
                    });
                    var pluginIdCol = $('<div>', {
                        class: 'third'
                    });
                    if (pluginConf.version_compare < 0) {
                        pluginIdCol.append($('<label>',{class:'errorValue',text:`This plugin cannot be enabled because it requires Core version ${pluginConf.depends.core}`}));
                    }
                    pluginIdCol.append($('<div>', {class:'listLabelTitle',text:'Plugin Info'}));
                    if (typeof pluginConf['LOAD_ERR'] != "undefined") {
                        pluginTools.append($('<img>', {
                            src: '/resource/page_error_2.png'
                        }));
                        pluginLabel.css({'border':'1px solid rgba(255,65,65,0.7)','color':'darkred'});
                    }
                    var pluginStatCol = $('<div>', {
                        class: 'two-thirds'
                    });
                    var sc = 0;
                    $.each(pluginConf.settings_changes, function(key, val) {
                        sc++;
                        var kl = $('<label>', {
                            class: 'listLabelName',
                            text: `${key}: `
                        });
                        var kv = $('<label>', {
                            class: 'listLabelValue',
                            text: `${val}`
                        });
                        pluginStatCol.append(kl, kv, $('<br>'));
                    });
                    if (sc > 0) {
                        pluginStatCol.prepend($('<div>', {class: 'listLabelTitle', text: "Settings Changes"}));
                    }
                    $.each(['version', 'maintainer', 'depends'], function(i, key) {
                        var val = pluginConf[key];
                        var kl = $('<label>', {
                            class: 'listLabelName',
                            text: `${key}: `
                        });
                        var kv = $('<label>', {
                            class: 'listLabelValue',
                        });
                        if (key == 'depends') {
                            kv.text(JSON.stringify(val));
                        } else {
                            kv.text(val);
                        }
                        pluginIdCol.append(kl, kv, $('<br>'));
                    });
                    pluginInfoGrid.append(pluginControls, pluginIdCol, pluginStatCol);
                    pluginControls.append(pluginEnableLabel);
                    pluginList.append(pluginItem.append(pluginLabel, pluginTools, pluginAction.append(pluginInfoGrid)));
                });
                break;
        }
    });
}

function appendLogPage(start, block = 25) {
    var start = start - 1;
    var end = start + block + 1;
    var listWait = $('<div>', {
        class: 'loading_place',
        css: {
            'position': 'absolute',
            'bottom': '0',
            'left': '0',
            'width': '100%'
        }
    });
    var logList = $('#admin_auditLogList');
    $('.adminContentCatch[context="audit_log"]').css({
        opacity: '0.5',
        overflow: 'hidden'
    });
    logList.append(listWait);
    $.ajax({
        url: '/api/admin/audit_log',
        type: 'GET'
    }).fail(function(err) {
        return false;
    }).done(function(res) {
        console.log(res);
        $.each(res, function(i, event) {
            var eItem = $('<li>', {
                class: 'objectItem auditLogEventItem',
                item_id: event.log_id
            });
            $.each(event, function(evTag, evData) {
                switch (evTag) {
                    case "method":
                    case "user":
                    case "path":
                    case "time":
                        var evTagLabel = $('<label>', {
                            class: 'objectItemLabel actionTrigger auditLogEventLabel',
                            text: evData,
                            action_class: '.auditLogEventAction',
                            item_id: event.id
                        });
                        eItem.append(evTagLabel);
                        break;
                    case "addr":
                        $.each(evData, function(i, addr) {
                            var evAddrLabel = $('<label>', {
                                class: 'objectItemLabel actionTrigger auditLogEventLabel',
                                text: addr,
                                action_class: '.auditLogEventAction',
                                item_id: event.id
                            });
                            eItem.append(evAddrLabel);
                        });
                        break;
                }
            });
            var evAction = $('<div>', {
                class: 'objectAction auditLogEventAction',
                item_id: event.id
            });
            eItem.append(evAction);
            logList.append(eItem);
        });
        $('.adminContentCatch[context="audit_log"]').css({
            opacity: '',
            overflow: ''
        });
        listWait.remove();
    });
}

function adminNewObjectForm(context) {
    switch (context) {
        case "users":
        case "groups":
            $.ajax({
                url: '/api/admin/' + context,
                type: 'OPTIONS'
            }).fail(function(opt_err) {
                return false;
            }).done(function(opt_res) {
                console.log(opt_res);
                var dialog = uxBlock(context, 'new');
                var dialogBase = $('<div>', {
                    class: 'adminNewObjectDialog'
                });
                if (context == "users") {
                    dialog.append(pageHeader('New User Account'));
                } else {
                    dialog.append(pageHeader('New User Group'));
                }
                dialog.append(dialogBase);
                var fieldList = $('<ul>', {
                    class: 'objectList'
                });
                dialogBase.append(fieldList);
                var newObjectNameItem = $('<li>', {
                    class: 'objectItem formInputListItem'
                });
                var newObjectNameLabel = $('<label>', {
                    class: 'objectItemLabel standardWidth',
                    text: "Name",
                    for: 'newObjectNameInput'
                });
                var newObjectNameInput = $('<input>', {
                    id: 'newObjectNameInput',
                    class: 'adminUserEditInput',
                    type: 'text',
                    context: context,
                    item_id: '__new__',
                    field_name: '__name__',
                    field_type: 'str',
                    placeholder: 'Name'
                });
                fieldList.append(newObjectNameItem.append(newObjectNameLabel, newObjectNameInput));
                $.each(opt_res.fields, function(i, fieldConf) {
                    if (fieldConf.auto === true) { return; }
                    var fieldItem = $('<li>', {
                        class: 'objectItem formInputListItem'
                    });
                    var fieldLabel = $('<label>', {
                        class: 'objectItemLabel standardWidth',
                        text: fieldConf.display,
                        for: 'newObject_' + fieldConf.name + '_new'
                    });
                    fieldItem.append(fieldLabel);
                    switch (fieldConf.type) {
                        case 'multi':
                            var fieldSelectDiv = $('<div>', {
                                class: 'formInputListContainer'
                            });
                            var fieldSelectTools = $('<div>', {
                                class: 'adminNewObjectToolbar'
                            })
                            var fieldSelectFilter = $('<input>', {
                                type: 'text',
                                class: 'objectListFilter fullwidth allowFormInputList',
                                list_id: 'multiCheck_' + fieldConf.name + '_new',
                                placeholder: 'Filter'
                            });
                            fieldSelectTools.append(fieldSelectFilter);
                            var fieldSelectList = $('<ul>', {
                                class: 'objectList formInputList',
                                id: 'multiCheck_' + fieldConf.name + '_new'
                            });
                            fieldSelectDiv.append(fieldSelectTools, fieldSelectList);
                            fieldItem.append(fieldSelectDiv);
                            $.ajax({
                                url: '/' + fieldConf.source.replace(/\./g, '/'),
                                type: 'GET'
                            }).fail(function(src_err) {
                                console.log(src_err);
                                return false;
                            }).done(function(src_res) {
                                // console.log(src_res);
                                $.each(src_res, function(objName, objConf) {
                                    if (fieldConf.name_field == "__key__") {
                                        var objDisplayName = objName;
                                    } else {
                                        var objDisplayName = objConf[fieldConf.name_field];
                                    }
                                    var objItem = $('<li>', {
                                        class: 'objectItem'
                                    });
                                    var objSelect = $('<input>', {
                                        type: 'checkbox',
                                        class: 'adminUserEditListItem',
                                        id: 'multiSelect_' + objConf[fieldConf.id_field] + '_' + fieldConf.name + '_new',
                                        context: context,
                                        item_id: "__new__",
                                        option_id: objConf[fieldConf.id_field],
                                        field_name: fieldConf.name
                                    });
                                    var objLabel = $('<label>', {
                                        class: 'objectItemLabel',
                                        text: objDisplayName,
                                        for: 'multiSelect_' + objConf[fieldConf.id_field] + '_' + fieldConf.name + '_new'
                                    });
                                    objItem.append(objSelect, objLabel);
                                    fieldSelectList.append(objItem);
                                });
                            });
                            break;
                        case 'option':
                            var fieldSelect = $('<select>', {
                                class: 'adminUserEditInput',
                                context: context,
                                item_id: '__new__',
                                field_name: fieldConf.name,
                                field_type: fieldConf.type,
                            });
                            fieldItem.append(fieldSelect);
                            $.ajax({
                                url: '/' + fieldConf.source.replace(/\./g, '/'),
                                type: 'GET'
                            }).fail(function(src_err) {
                                return false;
                            }).done(function(src_res) {
                                // console.log(src_res);
                                optList = {};
                                if (fieldConf.name == "rank") {
                                    $.each(src_res.value, function(rankName, rankValue) {
                                        if (rankValue > 0) {
                                            optList[rankName] = rankName;
                                        }
                                    });
                                } else {
                                    $.each(src_res, function(objName, objConf) {
                                        optList[objName] = objConf[fieldConf.id_field];
                                    });
                                }
                                $.each(optList, function(optName, optVal) {
                                    var fieldOpt = $('<option>', {
                                        value: optVal,
                                        text: optName
                                    });
                                    fieldSelect.append(fieldOpt);
                                });
                            });
                            break;
                        case "bool":
                            var fieldInput = $('<input>', {
                                type: 'checkbox',
                                class: 'adminUserEditInput',
                                context: context,
                                item_id: '__new__',
                                field_name: fieldConf.name,
                                field_type: fieldConf.type,
                                id: 'newObject_' + fieldConf.name + '_new'
                            });
                            fieldItem.append(fieldInput);
                            break;
                        default:
                            var fieldInput = $('<input>', {
                                class: 'adminUserEditInput',
                                type: 'text',
                                context: context,
                                item_id: '__new__',
                                field_name: fieldConf.name,
                                field_type: fieldConf.type,
                                placeholder: fieldConf.display,
                                id: 'newObject_' + fieldConf.name + '_new'
                            });
                            fieldItem.append(fieldInput);
                    }
                    fieldList.append(fieldItem);
                });
            });
            break;
        default:
            console.log('no.');
    }
}

$(document).on('click', '.core_adminCreateModelObject', function() {
    var model = $(this).attr('context');
    var model_name = $(this).attr('model_name');
    console.log(model);
    var newItemBlock = uxBlock('core_adminCreateModelObjectForm');
    $.ajax({
        url: '/models/' + model,
        type: 'OPTIONS'
    }).done((optres) => {
        var newItemForm = new ModelEditForm(optres, 'create', model);
        newItemBlock.html(newItemForm.form);
        $('.uxBlockDialogSubmit[context="core_adminCreateModelObjectForm"]')
        .attr({model_id: model})
        var formTitle = pageHeader('New Item: ' + model_name);
        newItemBlock.prepend(formTitle);
    })
});

$(document).on('click', '.uxBlockDialogSubmit[context="core_adminCreateModelObjectForm"]', function() {
    var model = $(this).attr('model_id');
    var data = {};
    var fdata = new FormData();
    let form_err = false;
    $.each($('.modEditInput[context="'+model+'"][item_id="__new__"]'), function() {
        var field_name = $(this).attr('field_name');
        switch ($(this).attr('type')) {
            case "checkbox":
                var field_val = $(this).prop('checked');
                break;
            case "file":
                var field_val = $(this)[0].files[0];
                break;
            default:
                if (typeof $(this).attr('field_type') !== 'undefined' && $(this).attr('field_type') == 'str' && $(this).attr('ckedit') !== undefined && $(this).attr('ckedit') == 'true') {
                    var field_val = CKEDITOR.instances[$(this).attr('id')].getData();
                } else {
                    var field_val = $(this).val();
                }
        }
        if ($(this).attr('required') == "required") {
            if (typeof field_val == "undefined" || field_val == "") {
                alert(field_name + ' is required.');
                form_err = true;
                return false;
            }
        }
        fdata.append(field_name, field_val);
    });
    $.each($('.adminUserEditListItem[context="'+model+'"][item_id="__new__"'), function() {
        var field_name = $(this).attr('field_name');
        if (typeof data[field_name] == "undefined") {
            data[field_name] = [];
        }
        if ($(this).prop('checked') === true) {
            data[field_name].push($(this).attr('option_id'));
        }
    });
    $.each(data, function(dataI, dataV) {
        fdata.append(dataI, JSON.stringify(dataV));
    });
    if (form_err === false) {
        $.ajax({
            cache: false,
            data: fdata,
            url: `/models/${model}`,
            type: 'POST',
            processData: false,
            contentType: false
        }).done(function(res) {
            console.log(res);
            $('.uxBlock').remove();
            location.reload();
        })
        .fail(function(err) {
            console.log('fail', err);
            alert(err.responseText);
        });
    }
});

$(document).on('click', '#adminUploadPlugin', function() {
    var upBlock = uxBlock('admin_upload_plugin');
    var upFormTitle = pageHeader('Upload Plugin Archive');
    var upForm = $('<ul>', {
        class: 'objectList formInputList'
    });
    var upFileLine = $('<li>', {
        class: 'objectItem formInputListItem'
    });
    var upFileLabel = $('<label>', {
        class: 'objectItemLabel standardWidth',
        for: 'adminUploadPluginFile',
        text: 'File'
    });
    var upFile = $('<input>', {
        type: 'file',
        id: 'adminUploadPluginFile'
    });
    upFileLine.append(upFileLabel, upFile);
    upForm.append(upFileLine);
    upBlock.append(upFormTitle, upForm);
});

$(document).on('click', '.adminNewUserGroupButton', function() {
    adminNewObjectForm($(this).attr('context'));
});

$(document).on('click', '.adminSubmitUserGroupState', function() {
    var context = $(this).attr('context');
    var item_id = $(this).attr('item_id');
    var pdata = {};
    $.each($('.adminUserEditInput[context="'+context+'"][item_id="'+item_id+'"]'), function() {
        var field_name = $(this).attr('field_name');
        var field_type = $(this).attr('field_type');
        var field_val = $(this).val();
        if (field_val != "") {
            pdata[field_name] = field_val;
        }
    });
    $.each($('.adminUserEditListItem[context="'+context+'"][item_id="'+item_id+'"]'), function() {
        var option_id = $(this).attr('option_id');
        var field_name = $(this).attr('field_name');
        if (typeof pdata[field_name] == "undefined") {
            pdata[field_name] = [];
        }
        if ($(this).prop('checked') === true) {
            pdata[field_name].push(option_id);
        }
    });
    console.log(context, item_id, pdata);
    $.ajax({
        url: '/api/admin/' + context + '/' + item_id,
        type: 'PATCH',
        headers: {
            'content-type': 'application/json',
            'accept': 'application/json'
        },
        data: JSON.stringify(pdata)
    }).fail(function(err) {
        console.log(err);
        return false;
    }).done(function(res) {
        console.log(res);
        toggleAction('.adminUserItemAction', $.escapeSelector(item_id));
        return true;
    });
});

$(document).on('click', '.adminPluginEnableCheck', function() {
    var thisCheck = $(this);
    if ($(this).prop('checked') === true) {
        var method = "UNLOCK";
        var failback = false;
    } else {
        var method = "LOCK";
        var failback = true;
    }
    var plugin = $(this).attr('plugin');
    $.ajax({
        url: '/api/admin/plugins/' + plugin,
        type: method,
        headers: {
            'accept': 'application/json'
        }
    }).fail(function(err) {
        console.log(err);
        thisCheck.prop('checked', failback);
    }).done(function(res) {
        console.log(res);
        if (typeof res.script != "undefined" && res.script != null) {
            eval(res.script);
        }
        location.reload();
    });
});

$(document).on('click', '.adminOptSetPasswordViewToggle', function() {
    var opt = $(this).attr('option');
    var pwfield = $('input[type="password"][option="'+$.escapeSelector(opt)+'"]');
    var viewfield = $('input[type="text"][option="'+$.escapeSelector(opt)+'"]');
    if (pwfield.is(':visible')) {
        pwfield.css('display', 'none');
        viewfield.css('display', 'inline-block');
        $(this).text('hide');
    } else {
        pwfield.css('display', 'inline-block');
        viewfield.css('display', 'none');
        $(this).text('show');
    }
});

$(document).on('input', '.adminOptSetInput[type="password"]', function() {
    var opt = $(this).attr('option');
    var viewfield = $('input[type="text"][option="'+$.escapeSelector(opt)+'"]');
    viewfield.val(this.value);
});

$(document).on('input', '.adminOptSetPasswordView', function() {
    var opt = $(this).attr('option');
    var pwfield = $('input[type="password"][option="'+$.escapeSelector(opt)+'"]');
    pwfield.val(this.value);
});

$(document).on('click', '.adminOptSetSubmit', function() {
    var optName = $(this).attr('option');
    var optSource = $(this).attr('source');
    var optVal = $('.adminOptSetInput[option='+optName+']').val();
    var optType = $(this).attr('opt_type');
    submitSystemSetting(optName, optSource, optVal, optType);
});

$(document).on('click', '.adminOptSetCheckbox', function() {
    var optName = $(this).attr('option');
    var optSource = $(this).attr('source');
    var optVal = $(this).prop('checked');
    var optType = $(this).attr('opt_type');
    submitSystemSetting(optName, optSource, optVal, optType);
});

$(document).on('change', '.adminOptSetInput[opt_type="color"]', function() {
    $(this).parent().css('background-color', $(this).val());
});

$(document).on('click', '.adminImportUserGroupData', function() {
    var context = $(this).attr('context');
    var inputDialog = uxBlock('importUserGroupData');
    var inputText = $('<textarea>', {
        class: 'inputTextArea',
        id: 'importUserGroupData_input',
        context: context,
        placeholder: '{\n  "paste": {\n    "data": "here"\n  }\n}'
    });
    inputDialog.append(inputText);
    $('.uxBlockDialogSubmit[context="importUserGroupData"]').attr('target', context);
});
$(document).on('click', '.uxBlockDialogSubmit[context="importUserGroupData"]', function() {
    var context = $(this).attr('target');
    var data = $('#importUserGroupData_input[context='+context+']').val();
    data = JSON.parse(data);
    $.ajax({
        url: '/api/admin/' + context,
        type: 'IMPORT',
        contentType: 'application/json',
        data: JSON.stringify(data)
    }).fail(function(err) {
        console.log(err);
        return false;
    }).done(function(res) {
        $('.uxBlock').remove();
        loadAdminPageModule(context);
        return true;
    });
});
$(document).on('click', '.auditLogEventLabel', function() {
    var event = $(this).attr('item_id');
    $.ajax({
        url: '/api/admin/audit_log/' + event,
        type: 'GET'
    }).done(function(res) {
        var eventPre = $('<pre>', {
            text: JSON.stringify(res, null, 2)
        });
        $('.auditLogEventAction[item_id='+event+']').html(eventPre);
    });
});
$(document).on('click', '.objectItemLabel.actionTrigger[action_class=".usersAction"], .objectItemLabel.actionTrigger[action_class=".groupsAction"]', function() {
    var action_class = $(this).attr('action_class');
    if (action_class == ".usersAction") {
        var context = "users";
    } else {
        var context = "groups";
    }
    var item_id = $(this).attr('item_id');
    var action = $(action_class + '[item_id="'+item_id+'"]');
    var action_vis = action.hasClass('actionClassVisible');
    if (action_vis === true) {
        $.ajax({
            url: '/api/admin/' + context,
            type: 'OPTIONS'
        }).done(function(opt_res) {
            $.ajax({
                url: '/api/admin/' + context + '/' + item_id,
                type: 'GET'
            }).done(function(item_res) {
                var uname = Object.keys(item_res)[0];
                var uval = item_res[uname];
                uval['name'] = uname;
                var actForm = new ModelEditForm(opt_res.fields, uval, context);
                action.html(actForm.form);
                $('.modelEditSubmit[context='+context+'][item_id='+item_id+']').attr('callback', "loadAdminPageModule('"+context+"')");
            });
        });
    } else {
        action.html('');
    }
});
