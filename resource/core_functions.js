function compareVersion(v1, v2) {
    if (typeof v1 !== 'string') return false;
    if (typeof v2 !== 'string') return false;
    v1 = v1.split('.');
    v2 = v2.split('.');
    const k = Math.min(v1.length, v2.length);
    for (let i = 0; i < k; ++ i) {
        v1[i] = parseInt(v1[i], 10);
        v2[i] = parseInt(v2[i], 10);
        if (v1[i] > v2[i]) return 1;
        if (v1[i] < v2[i]) return -1;        
    }
    return v1.length == v2.length ? 0: (v1.length < v2.length ? -1 : 1);
}

function nl2br(str, is_xhtml = false) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (`${str}`).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, `$1${breakTag}$2`);
}

function toggleAction(actionClass, actionItem) {
    var elem = $(`.actionTrigger[action_class="${$.escapeSelector(actionClass)}"][item_id="${$.escapeSelector(actionItem)}"]`);
    var parentItem = elem.parent();
    $.each($(`.actionTrigger[action_class="${$.escapeSelector(actionClass)}"]`), function() {
        $(this).parent().css('width', '');
    });
    var targetDiv = $(`${actionClass}[item_id=${$.escapeSelector(actionItem)}]`);
    var targetVisible = targetDiv.is(':visible');
    var otherItems = $(`.actionTrigger[action_class="${actionClass}"]:not([item_id="${$.escapeSelector(actionItem)}"])`);
    $(actionClass).slideUp('fast').removeClass('actionClassVisible');
    otherItems.parent().children('.objectItemTools').removeClass('actionClassVisible');
    if (targetVisible === false) {
        targetDiv.slideDown('fast');
        targetDiv.addClass('actionClassVisible');
        targetDiv.parent().children('.objectItemTools').addClass('actionClassVisible');
        parentItem.css('width', '100%');
    } else {
        targetDiv.removeClass('actionClassVisible');
        targetDiv.parent().children('.objectItemTools').removeClass('actionClassVisible');
        parentItem.css('width', '');
    }
}

function savePdfFromDiv(divid, options = null) {
    var html = $(divid).html();
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/tools/html2pdf');
    xhr.responseType = 'arraybuffer';
    xhr.send(JSON.stringify({
        html: html,
        options: options
    }));
    xhr.onload = function() {
        if (xhr.status != 200) {
            console.log(xhr.status, xhr.statusText);
            return false;
        }
        console.log(xhr.response);
        //var json = JSON.parse(xhr.response);
        //var pdfd = atob(json.pdfstream);
        //console.log(pdfd);
        var blob = new Blob([xhr.response], { type: 'application/pdf' });
        var link = document.createElement('a');
        var data = window.URL.createObjectURL(blob);
        link.href = data;
        link.download = 'export.pdf';
        document.body.appendChild(link);
        link.click();
        setTimeout(function() {
            window.URL.revokeObjectURL(data);
            document.body.removeChild(link);
        }, 1000);
    }
}

function simpleJsonToCsv(json, addHeader = true) {
    var csvText = "";
    var headerArr = [];
    var cCount = 0;
    $.each(json, function(i, jdata) {
        var lineArr = [];
        $.each(jdata, function(jkey, jval) {
            if (cCount == 0) {
                headerArr.push(jkey);
            }
            if (jval == "" || jval == " ") {
                jval = "nodata";
            }
            lineArr.push(jval);
        });
        cCount++;
        csvText += lineArr.join(',') + "\n";
    });
    headerText = headerArr.join(',');
    if (addHeader === true) {
        csvText = headerText + "\n" + csvText;
    }
    return csvText;
}

function saveJsonToFile(json, fileName = "export") {
    var file = new Blob([JSON.stringify(json, null, 2)], {type: "application/json"});
    var a = document.createElement("a");
    a.href = URL.createObjectURL(file);
    a.download = `${fileName}.json`;
    a.click();
}

function saveTextToFile(text, fileName = "export.txt", mime = "text") {
    var file = new Blob([text], { type: mime });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(file);
    a.download = fileName;
    a.click();
}

function invBool(bool) {
    if (bool === false) {
        return true;
    } else {
        return false;
    }
}

function reloadCss(append = "") {
    var themeString = `?darktheme=default${append}`;
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        themeString = `?darktheme=true${append}`;
    }
    $('#core_stylesheet').attr('href', `/resource/core.css${themeString}&reload=${new Date().getTime()}`);
}

function greyBright(hex) {
    var bv = isLightColor(hex, true);
    return adjustBrightness(rgb2hex(bv, bv, bv), 96);
}

function adjustBrightness(color, steps = 0) {
    var steps = Number(steps);
    var hex = color.replace(/#/g, '');
    if (hex.length == 3) {
        hex = hex.substr(0, 1) + hex.substr(0, 1) + hex.substr(1, 1) + hex.substr(1, 1) + hex.substr(2, 1) + hex.substr(2, 1);
    }
    var parts = [
        Number(parseInt(hex.substr(0,2), 16) + steps),
        Number(parseInt(hex.substr(2,2), 16) + steps),
        Number(parseInt(hex.substr(4,2), 16) + steps)
    ];
    var hexReturn = [];
    $.each(parts, function(i, part) {
        if (part < 0) {
            part = '00';
        }
        if (part > 255) {
            part = '255';
        }
        var ins = part.toString(16).padStart(2, '0');
        hexReturn.push(ins);
    });
    var returnVal = `#${hexReturn.join('').replace(/-/g, '')}`;
    return returnVal;
}

function textOnBgColor(color, darkColor = "white", lightColor = "black") {
    if (isLightColor(color) === true) {
        return lightColor;
    } else {
        return darkColor;
    }
}
function isLightColor(color, returnBrightness = false) {
    if (!Array.isArray(color)) {
        if (color.match(/^\#.*$/)) {
            color1 = hex2rgb(color);
            color = [color1.r, color1.g, color1.b];
        } else {
            color = color.split(',');
        }
    }
    var r = color[0];
    var g = color[1];
    var b = color[2];
    var rb = r * 299;
    var gb = g * 587;
    var bb = b * 114;
    var bv = (rb + gb + bb) / 1000;
    if (returnBrightness === true) {
        return Math.round(bv);
    }
    if (bv > 200) {
        return true;
    } else {
        return false;
    }
}
function componentToHex(c) {
    var hex = c.toString(16);
    return hex.length == 1 ? `0${hex}` : hex;
}
function rgb2hex(r, g, b) {
    return `#${componentToHex(r)}${componentToHex(g)}${componentToHex(b)}`;
}
function hex2rgb(hex) {
    if (hex.length == 4) {
        hex = hex.substr(1, 1) + hex.substr(1, 1) + hex.substr(2, 1) + hex.substr(2, 1) + hex.substr(3, 1) + hex.substr(3, 1);
    }
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function revBool(bool, string = false) {
    if (typeof(bool) != "boolean") { return false; }
    if (bool === true) {
        var res = false;
    } else {
        var res = true;
    }
    if (string === true) {
        if (res === true) {
            return "on";
        } else {
            return "off";
        }
    } else {
        return res;
    }
}

function doNothing() {
    console.log('no.');
}
function uxBlock(context = "", action = null) {
    var block = $('<div>', {
        class: 'uxBlock'
    });
    var dialogBase = $('<div>', {
        class: 'uxBlockDialog',
        css: {
            display: 'none'
        }
    });
    var dialog = $('<div>', {
        class: 'uxBlockDialogBase'
    });
    var dialogControls = $('<div>', {
        class: 'dialogToolbar',
        context: context
    });
    var dialogCancel = $('<button>', {
        class: 'uxBlockDialogCancel',
        text: 'Cancel'
    });
    var dialogSubmit = $('<button>', {
        class: 'uxBlockDialogSubmit',
        text: 'Submit',
        context: context,
        action: action
    });
    dialogControls.append(dialogCancel, dialogSubmit);
    $('body').append(block.append(dialogBase.append(dialog, dialogControls)));
    //dialogBase.slideDown('fast');
    dialogBase.fadeIn(300);
    return dialog;
}
function pageHeader(title) {
    return $('<div>', {
        class: 'pageHeader',
        text: title
    });
}
function pageSubHeader(title) {
    return $('<div>', {
        clasS: 'pageSubHeader',
        text: title
    });
}
function loadObjectFormData(context, item_id = "__new__") {
    var rdata = {};
    var error = false;
    var err_string = "";
    $.each($(`.modEditInput[context="${context}"][item_id="${item_id}"]`), function() {
        var field_name = $(this).attr('field_name');
        switch ($(this).attr('type')) {
            case 'checkbox':
            case 'radio':
                rdata[field_name] = $(this).prop('checked');
                break;
            case 'text':
            case 'password':
            default:
                if ($(this).attr('type') == "password") {
                    var verify_val = $(`.modEdit_verify[item_id="${item_id}"][context="${context}"]`).val();
                    if (verify_val != $(this).val()) {
                        err_string = `${$(this).attr('placeholder')} values do not match (${verify_val} / ${$(this).val()})`;
                        error = true;
                    }
                }
                if ($(this).attr('required') == "required") {
                    if ($(this).val() == "" || typeof $(this).val() == "undefined") {
                        err_string = `${$(this).attr('placeholder')} value is required.`;
                        error = true;
                    }
                }
                rdata[field_name] = $(this).val();
        }
    });
    if (error === true) {
        return {
            "success": false,
            "error": err_string
        };
    }
    return rdata;
}

function deleteModelItem(model, id) {
    return $.ajax({
        url: `/models/${model}/${id}`,
        type: 'DELETE'
    }).fail(function(err) {
        throw new Error(err);
    });
}

function patchModelItem(model, data) {
    return $.ajax({
        url: `/models/${model}`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data)
    });
}

function createModelItem(model, data) {
    return $.ajax({
        url: `/models/${model}`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data)
    });
}

function createModelObjectFromForm(model, callback = null) {
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
}

function createModelObjectFromFormOld(modName, callback = null) {
    var pdata = {};
    $.each($(`.modEditInput[context="${modName}"][item_id="__new__"]`), function() {
        var field_name = $(this).attr('field_name');
        switch ($(this).attr('type')) {
            case "checkbox":
                pdata[field_name] = $(this).prop('checked');
                break;
            default:
                pdata[field_name] = $(this).val();
        }
    });
    console.log(pdata);
    $.ajax({
        url: `/models/${modName}`,
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(pdata)
    }).fail(function(err) {
        console.log(err);
        return false;
    }).done(function(res) {
        $('.uxBlock').remove();
        if (callback !== null) {
            eval(callback);
        }
        return true;
    });
}

function objectItemCommand(context, item_id, command, callback = null) {
    switch (context) {
        case "users":
        case "groups":
            var api_url = `/api/admin/${context}/${item_id}`;
            break;
        default:
            var api_url = `/models/${context}/${item_id}`;
    }
    $.ajax({
        url: api_url,
        type: command
    }).fail(function(err) {
        console.log('thing fail', err);
        return false;
    }).done(function(res) {
        console.log('thing ok', res, callback);
        if (callback !== null) {
            eval(callback);
        }
    });
}
function processListItem(options, res, opt_res, modName, modItem, listBody) {
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
    var listItem = $('<li>', {
        class: 'objectItem firstItem',
        item_id: modItem.id
    });
    if (options.hide_items_on_mobile === true) {
        listItem.addClass('hideOnMobile');
    }
    var listLabel = $('<label>', {
        class: 'objectItemLabel',
        item_id: modItem.id
    });
    if (typeof options.labelValue != "undefined") {
        listLabel.text(modItem[options.labelValue]);
    } else {
        var label_field = "name";
        $.each(opt_res, function(x, f) {
            if (f.label === true) {
                label_field = f.name;
            }
        });
        listLabel.text(modItem[label_field]);
    }
    if (typeof options.tagValues != "undefined") {
        var listTag = $('<div>', {
            class: 'objectItemTag'
        });
        $.each(options.tagValues, function(i, tagVal) {
            var xText = listTag.text();
            xText += " - " + modItem[tagVal];
            listTag.text(xText);
        });
        listLabel.append(listTag);
    }
    listItem.append(listLabel);
    listBody.append(listItem);
    if (typeof options.label_attrs != "undefined") {
        $.each(options.label_attrs, function(attrName, attrVal) {
            listLabel.attr(attrName, attrVal);
        });
    }
    if (options.icon !== false) {
        if (typeof options.icon_source != "undefined" && options.icon_source.length > 0) {
            switch (options.icon_source) {
                case 'model_file':
                    var icon_uri = '/models/' + options.model_name + '/' + modItem.id + '/' + options.icon_field;
                    break;
                default:
                    if (typeof options.icon_field != 'undefined' && modItem[options.icon_field]) {
                        var icon_uri = modItem[options.icon_field];
                    } else {
                        var icon_uri = options.icon_source
                    }
            }
        } else {
            var icon_uri = options.icon;
        }
        var listIcon = $('<img>', {
            class: 'objectItemIcon',
            src: icon_uri
        });
        if (typeof options.icon_bg != 'undefined') {
            listIcon.css('background', options.icon_bg);
        }
        listItem.prepend(listIcon);
    }
    var listItemTools = $('<div>', {
        class: 'objectItemTools'
    });
    if (options.delete_items !== false) {
        var listItemDelete = $('<img>', {
            class: 'objectItemToolsCommand',
            command: 'DELETE',
            context: modName,
            item_id: modItem.id,
            src: '/resource/delete_bin.png'
        });
        listItemTools.append(listItemDelete);
    }
    listItem.append(listItemTools);
    if (options.edit_items === true || options.actions === true) {
        listLabel.addClass('actionTrigger');
        listLabel.attr('action_class', `.${modName}Action`);
        var listAction = $('<div>', {
            class: `objectAction ${modName}Action`,
            item_id: modItem.id
        });
        listItem.append(listAction);
        if (options.edit_items === true) {
            var editForm = new ModelEditForm(opt_res, modItem, modName, options.edit_callback);
            listAction.append(editForm.form);
        }
    }
}

function getFormInputListData(form_name) {
    let data = {};
    $.each($(`.formInputListField[form_name="${form_name}"]`), function() {
        var field = $(this).attr('field_name');
        switch ($(this).attr('type')) {
            case 'checkbox':
                if (typeof data[field] == "undefined") {
                    if (typeof data[field] == "undefined") {
                        data[field] = [];
                        $.each($(`.formInputListField[form_name="${form_name}"][field_name="${field}"]`), function() {
                            if ($(this).prop('checked') === true) { data[field].push($(this).val()); }
                        });
                    }    
                }
                break;
            case 'radio':
                if (typeof data[field] == "undefined") {
                    $.each($(`.formInputListField[form_name="${form_name}"][field_name="${field}"]`), function() {
                        if ($(this).prop('checked') === true) { data[field] = $(this).val(); }
                    });
                }
                break;
            default:
                if ($(this).attr('ckedit') == 'true') {
                    data[field] = CKEDITOR.instances[$(this).attr('id')].getData();
                } else {
                    data[field] = $(this).val();
                }
        }
    });
    return data;
}
