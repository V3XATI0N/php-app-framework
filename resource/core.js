$(document).on('keyup', '.multiListFilter', function(e) {
    var list_class = $(this).attr('list_class');
    var txt = $(this).val().toLowerCase();
    var titles = $(`.sectionTrigger${list_class}`);
    var lists = $(`.sectionAction${list_class}`);
    var items = $(`.sectionAction .objectList .objectItem${list_class}`);
    $(`ul${list_class}`).show(0).removeClass('multiFilterVisible');
    if (e.key == "Escape" || txt == "") {
        $(this).val('');
        titles.show(0);
        lists.hide(0);
        items.show(0);
    } else {
        titles.hide(0);
        lists.show(0);
        var list_vis_ct = 0;
        $.each(lists.find('ul.objectList'), (i, list) => {
            var list_vis = false;
            $.each($(list).find('li'), (x, item) => {
                var label_text = $(item).find('.objectItemLabel').text().toLowerCase();
                if (label_text.includes(txt)) {
                    $(item).show(0);
                    list_vis = true;
                } else {
                    $(item).hide(0);
                }
            });
            if (list_vis == true) {
                if (list_vis_ct > 0) {
                    $(list).show(0).addClass('multiFilterVisible');
                }
                list_vis_ct++;
            } else {
                $(list).hide(0).removeClass('multiFilterVisible');
            }
        });
    }
});

$(document).on('keyup', '.objectListFilter', function(e) {
    var list_id = $(this).attr('list_id');
    var clear_btn = $(`.objectListFilterClear[list_id="${list_id}"]`);
    var txt = $(this).val().toLowerCase();
    if (e.key == "Escape") {
        $(this).val('');
        $(`#${list_id} li`).css('display', '');
        clear_btn.css('visibility', 'hidden');
        return true;
    }
    if (txt == "") {
        clear_btn.css('visibility', 'hidden');
    } else {
        clear_btn.css('visibility', 'visible');
    }
    if ($(this).hasClass('allowFormInputList')) {
        var target_list = `#${list_id} li`;
        var target_sub_list = '.objectItemLabel';
        var target_tag = '.objectItemTag';
    } else {
        var target_list = `#${list_id} li:not(.formInputListItem)`;
        var target_sub_list = '.objectItemLabel:not(.formInputListItem .objectItemLabel)';
        var target_tag = '.objectItemTag:not(.formInputListItem .objectItemLabel .objectItemTag)';
    }
    $.each($(target_list), function() {
        var thisItem = $(this);
        var thisText = $(this).find(target_sub_list).text().toLowerCase();
        thisText += $(this).find(target_tag).text().toLowerCase();
        if (thisText.includes(txt)) {
            if ((thisItem.hasClass('hideOnMobile') || thisItem.parent().hasClass('hideOnMobile')) && $(window).width() <= 600) {
                thisItem.css('display', 'block');
            } else {
                thisItem.css('display', '');
            }
        } else {
            if ((thisItem.hasClass('hideOnMobile') || thisItem.parent().hasClass('hideOnMobile')) && $(window).width() <= 600) {
                thisItem.css('display', '');
            } else {
                thisItem.css('display', 'none');
            }
        }
    });
});

$(document).on('click', '.modelEditSubmit:not(.disableDefaultAction)', function() {
    var thisButton = $(this);
    var pdata = {};
    var fdata = new FormData();
    fdata.append('__method__', 'PATCH');
    var model_name = $(this).attr('context');
    var item_id = $(this).attr('item_id');
    switch (model_name) {
        case "users":
        case "groups":
            var api_url = `/api/admin/${model_name}/${item_id}`;
            break;
        default:
            var api_url = `/models/${model_name}/${item_id}`;
    }
    $.each($(`.modEditInput[context="${model_name}"][item_id="${item_id}"]`), function() {
        var field_name = $(this).attr('field_name');
        switch ($(this).attr('type')) {
            case 'checkbox':
                //pdata[field_name] = $(this).prop('checked');
                fdata.append(field_name, $(this).prop('checked'));
                break;
            case 'file':
                fdata.append(field_name, $(this)[0].files[0]);
                break;
            default:
                //pdata[field_name] = $(this).val();
                if (typeof $(this).attr('field_type') !== 'undefined' && $(this).attr('field_type') == 'str' && $(this).attr('ckedit') !== undefined && $(this).attr('ckedit') == 'true') {
                    var field_val = CKEDITOR.instances[$(this).attr('id')].getData();
                    fdata.append(field_name, field_val);
                } else {
                    fdata.append(field_name, $(this).val());
                }
        }
    });
    $.each($(`.adminUserEditListItem[context="${model_name}"][item_id="${item_id}"]`), function() {
        var field_name = $(this).attr('field_name');
        if (typeof pdata[field_name] == "undefined") {
            pdata[field_name] = [];
        }
        if ($(this).prop('checked') === true) {
            pdata[field_name].push($(this).attr('option_id'));
        }
    });
    $.each(pdata, function(pkey, pval) {
        fdata.append(pkey, JSON.stringify(pval));
    });
    $.ajax({
        url: api_url,
        type: 'POST',
        contentType: false,
        processData: false,
        cache: false,
        data: fdata
    })
    .fail(function(err) {
        if (err.status == 200) {
            if (thisButton.attr('callback') != "") {
                eval(thisButton.attr('callback'));
            }
            thisButton.text('Saved !');
            return true;
        }
        alert(err.responseText);
        thisButton.text('ERROR :(');
        return false;
    }).done(function(res) {
        if (thisButton.attr('callback') != "") {
            eval(thisButton.attr('callback'));
        }
        thisButton.text('Saved !');
        return true;
    });
});

$(document).on('click', '#sessionOptionToggle', function() {
    if ($(this).hasClass('expanded')) {
        $(this).removeClass('expanded');
        $($('#sessionLinks .navMenuLink:not(.sticky)')).css('width', '');
        $($('#sessionLinks .navMenuLink:not(.sticky) img')).css({'display': 'none'});
        $($('#sessionLinks .navMenuLink .navMenuLinkText')).css({'display': 'none'});
    } else {
        $(this).addClass('expanded');
        $($('#sessionLinks .navMenuLink:not(.sticky)')).css('width', '64px');
        $($('#sessionLinks .navMenuLink:not(.sticky) img')).css({'display': 'inline-block'});
        $($('#sessionLinks .navMenuLink .navMenuLinkText')).css({'display': 'block'});
    }
    return true;
});
$(document).on('click', '.uxBlockDialogCancel', function() {
    $('.uxBlock').remove();
});
$(document).on('click', '.uxBlock', function() {
    $(this).remove();
});
$(document).on('click', '.uxBlockDialog', function(e) {
    e.stopPropagation();
});
$(document).on('click', '.objectItemToolsCommand:not(.disableDefaultAction)', function() {
    var this_item = $(this).attr('item_id');
    var this_context = $(this).attr('context');
    var this_command = $(this).attr('command');
    var this_item_esc = $.escapeSelector(this_item);
    console.log(this_item, this_item_esc);
    var callback = null;
    if ($(this).attr('command') == "DELETE") {
        callback = '$("li[item_id=' + this_item_esc + ']").remove();';
    }
    objectItemCommand(this_context, this_item, this_command, callback);
});
$(document).on('click', '.uxBlockDialogSubmit[action="new"]', function() {
    var context = $(this).attr('context');
    if ($('#newObjectNameInput').val() == "") {
        alert('You need to specify a name.');
        return false;
    }
    pdata = {};
    $.each($(`.adminUserEditInput[context="${context}"][item_id="__new__"]`), function() {
        var field_name = $(this).attr('field_name');
        var field_val = $(this).val();
        pdata[field_name] = field_val;
    });
    $.each($(`.adminUserEditListItem[context="${context}"][item_id="__new__"]`), function() {
        var field_name = $(this).attr('field_name');
        if (typeof pdata[field_name] == "undefined") {
            pdata[field_name] = [];
        }
        if ($(this).prop('checked') === true) {
            pdata[field_name].push($(this).attr('option_id'));
        }
    });
    if (typeof pdata['__name__'] != "undefined") {
        pdata_hold = pdata;
        pdata = {}
        pdata[pdata_hold['__name__']] = pdata_hold;
    }
    console.log(pdata);
    // return false;
    $.ajax({
        url: `/api/admin/${context}`,
        type: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        data: JSON.stringify(pdata)
    }).fail(function(err) {
        console.log('group create failure', err);
        return false;
    }).done(function(res) {
        console.log('group create success', res);
        location.reload();
        return true;
    });
});
$(document).on('click', '.actionTrigger:not(.disableDefaultAction)', function() {
    toggleAction($(this).attr('action_class'), $(this).attr('item_id'));
});
$(document).on('click', '.sectionTrigger', function() {
    var sectionClass = $(this).attr('section_class');
    var itemId = $(this).attr('item_id');
    var showOpen = $(`${sectionClass}[item_id=${$.escapeSelector(itemId)}]`).is(':visible');
    $('.sectionTrigger').removeClass('activeSection');
    if (showOpen === false) {
        $(this).addClass('activeSection');
    } else {
        $(this).removeClass('activeSection');
    }
    toggleAction(sectionClass, itemId);
});
$(document).on('click', '.adminExportUserGroupData', function() {
    var context = $(this).attr('context');
    $.ajax({
        url: `/api/admin/${context}`,
        type: 'GET'
    }).done(function(ex_res) {
        saveJsonToFile(ex_res, context);
    });
});
$(document).on('click', '.objectListFilterClear', function() {
    $(this).parent().find('.objectListFilter').val('').keyup().focus();
});

$(document).arrive('.adminNewObjectToolbar .objectListFilter', function() {
    var filter = $(this);
    var list_id = $(this).attr('list_id');
    var clearFilterLabel = $('<div>', {
        class: 'objectListFilterClear',
        list_id: list_id
    });
    clearFilterLabel.append(
        $('<img>', {
            src: '/resource/clear_grey.png'
        })
    );
    filter.after(clearFilterLabel);
});
$(document).arrive('.uxBlock .uxBlockDialog .uxBlockDialogBase .pageHeader', function() {
    var uxCloseX = $('<img>', {
        class: 'uxCloseX',
        src: '/resource/thin_x.png'
    });
    $(this).append(uxCloseX);
});
$(document).on('click', '.uxCloseX', () => {
    $('.uxBlock').remove();
});
$(document).on('mouseover', '.objectItemToolsCommand[command=DELETE]', function() {
    $(this).attr('src', '/resource/red_x.png');
});
$(document).on('mouseout', '.objectItemToolsCommand[command=DELETE]', function() {
    $(this).attr('src', '/resource/delete_bin.png');
});
