$(document).on('click', '.core_account_item_command', function() {
    var context = $(this).attr('context');
    var item_id = $(this).attr('item_id');
    var command = $(this).attr('command');
    $.ajax({
        url: '/account/plugin/core_account/' + context + '/' + item_id,
        type: command
    }).done(res => {
        console.log(res);
        switch (context) {
            case "core_account_api_keys":
                switch (command) {
                    case "DELETE":
                        $(this).parent().parent().remove();
                        break;
                }
                break;
        }
    }).fail(err => {
        throw new Error(err);
    });
});
$(document).on('click', '.uxBlockDialogSubmit[context="core_account_api_keys_create"]', function() {
    var keyLabel = $('.formInputListField[field_name="key_label"][form_name="core_account_api_keys_create"]').val();
    if (keyLabel != "" && keyLabel != " ") {
        $.ajax({
            url: '/account/plugin/core_account/core_account_api_keys',
            type: 'POST',
            data: {
                label: keyLabel
            }
        }).done(res => {
            console.log(res);
            $('.uxBlock').remove();
            var keyLine = $('<li>', {
                class: 'objectItem',
            });
            var keyItemLabel = $('<div>', {
                class: 'objectItemLabel',
                html: keyLabel + ' <i>Copy and save the key value below. This key will not be displayed again.</i>'
            });
            var keyItemData = $('<div>', {
                class: 'core_account_data_pre',
                text: res.key
            });
            keyLine.append(keyItemLabel, keyItemData);
            var result = $('#core_account_api_keys_info');
            result.html('');
            var resultHeader = pageSubHeader("A new API key has been provisioned: " + keyLabel);
            var resultInfo = $('<div>', { class: "core_account_info_text", text: "Copy and save the key value below. This key will not be displayed again." });
            var resultData = $('<div>', { class: "core_account_data_pre", text: res.key });
            result.append(resultHeader, resultInfo, resultData);
            result.slideDown('fast');
        });
    }
});