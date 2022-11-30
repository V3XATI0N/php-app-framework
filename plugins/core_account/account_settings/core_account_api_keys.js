var pageLabel = pageSubHeader('Account API Keys');
var pageInfo = $('<div>', {
    class: 'core_account_info',
    id: 'core_account_api_keys_info',
    css: {
        display: 'none'
    }
});

var keyBar = $('<div>', {
    class: 'adminNewObjectToolbar align-center'
});
var keyButtonIcon = $('<img>', {
    class: 'buttonIcon',
    src: '/resource/white_plus.png'
});
var keyButtonNew = $('<button>', {
    class: 'core_account_api_key_button_new',
    text: 'Provision API Key'
});
keyButtonNew.prepend(keyButtonIcon);
keyBar.append(keyButtonNew);
var keyList = $('<ul>', {
    class: 'objectList formInputList',
    id: 'core_account_api_key_list'
});

$('#userSettingsContent').html('');
$('#userSettingsContent').append(pageLabel, pageInfo, keyList, keyBar);

$.ajax({
    url: '/account/plugin/core_account/core_account_api_keys',
    type: 'SETUP'
}).done(res => {
    var keyCount = res[0];
    console.log(' you have ' + keyCount + ' keys.');
    if (keyCount > 0) {
        $.each(res[1], function(i, kd) {
            var keyItem = $('<li>', {
                class: 'objectItem core_account_api_keys'
            });
            var keyLabel = $('<label>', {
                class: 'objectItemLabel',
                text: kd.label
            });
            var keyTools = $('<div>', {
                class: 'objectItemTools'
            });
            var keyDelete = $('<img>', {
                class: 'core_account_item_command',
                context: 'core_account_api_keys',
                command: 'DELETE',
                item_id: kd.id,
                src: '/resource/delete_bin.png'
            });
            keyTools.append(keyDelete);
            keyItem.append(keyLabel, keyTools);
            keyList.append(keyItem);
        });
    }
});

keyButtonNew.on('click', function() {
    var createKeyBlock = uxBlock('core_account_api_keys_create');
    var blockTitle = pageHeader('Provision new API Key');
    var createKeyForm = new FormInputList({
        form_name: 'core_account_api_keys_create',
        fields: [
            {
                name: 'key_label',
                display: 'Label',
                type: 'str'
            }
        ]
    });
    createKeyBlock.append(blockTitle, createKeyForm.form);
});
