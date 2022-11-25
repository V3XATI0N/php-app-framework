function loadUserSettingsModule(moduleName, plugin = null) {
    if (plugin === null) {
        $.ajax({
            url: '/api/account/' + moduleName,
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).fail(function(err) {
            console.log(err);
        }).done(function(res) {
            switch(moduleName) {
                case "theme":
                    $.ajax({
                        url: '/api/account/theme',
                        type: 'OPTIONS',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }).fail(function(x_err) {
                        console.log(x_err);
                    }).done(function(x_res) {
                        var optList = $('<ul>', {
                            class: 'objectList'
                        });
                        $('#userSettingsContent').append(optList);
                        x_res['local_dark_theme'] = {
                            "default": false,
                            "description": "Follow your system's light/dark theme preference",
                            "group": "presentation",
                            "source": "core",
                            "type": "bool",
                            "name": "Use Browser Theme"
                        };
                        console.log(x_res);
                        $.each(x_res, function(optName, optConf) {
                            var optItem = $('<li>', {
                                class: 'objectItem userSettingsItem',
                                field: optName
                            });
                            var optIcon = $('<img>', {
                                class: 'objectItemIcon',
                                src: `/resource/${optConf.type}_edit.png`
                            });
                            var optLabel = $('<label>', {
                                class: 'objectItemLabel standardWidth',
                                text: optConf.name,
                                for: 'user_option_input_' + optName,
                                opt_type: optConf.type
                            });
                            var optInput = $('<input>', {
                                class: 'userSettingsInput',
                                name: 'user_option_input_' + optName,
                                id: 'user_option_input_' + optName,
                                value: res[optName],
                                opt_name: optName,
                                opt_type: optConf.type
                            });
                            optItem.append(optIcon, optLabel, optInput);
                            optList.append(optItem);
                            switch (optConf.type) {
                                case "str":
                                    optInput.attr('placeholder', optConf.name);
                                    optInput.attr('type', 'text');
                                    break;
                                case "color":
                                    optInput.attr('type', 'color');
                                    optInput.css({"background": "black"});
                                    var label2 = $('<label>', {
                                        class: 'userSettingsColorLabel',
                                        for: 'user_option_input_' + optName,
                                        css: {
                                            "background": res[optName]
                                        }
                                    });
                                    optItem.append(label2);
                                    break;
                                case "bool":
                                default:
                                    if (res[optName] === true) {
                                        optInput.prop('checked', true);
                                    }
                                    optInput.attr('type', 'checkbox');
                            }
                        });
                    });
                    break;
                case "settings":
                default:
                    $.ajax({
                        url: '/api/admin/users',
                        type: 'OPTIONS',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }).fail(function(x_err) {
                        console.log(x_err);
                    }).done(function(x_res) {
                        // console.log(x_res);
                        var fieldList = $('<ul>', {
                            class: 'objectList',
                            id: 'userSettingsProfileForm'
                        });
                        $('#userSettingsContent').append(fieldList);
                        var userItem = $('<li>', {
                            class: 'objectItem userSettingsItem',
                            field: 'username'
                        });
                        var userIcon = $('<img>', {
                            class: 'objectItemIcon',
                            src: '/resource/users.png'
                        });
                        var userLabel = $('<label>', {
                            class: 'objectItemLabel standardWidth',
                            text: 'Username',
                            for: 'user_settings_input_username_' + res.id,
                        });
                        var userInput = $('<input>', {
                            id: 'user_settings_input_username_' + res.id,
                            name: 'user_settings_input_username_' + res.id,
                            field_type: 'text',
                            field_name: 'Username',
                            class: 'userSettingsInput',
                            placeholder: 'Username',
                            type: 'text',
                            user: res.id,
                            field: 'username',
                            value: res.username,
                            disabled: 'disabled'
                        });
                        userItem.append(userIcon, userLabel, userInput);
                        fieldList.append(userItem);
                        $.each(x_res.fields, function(i, field) {
                            if (field.user_edit === false) { return; }
                            if (field.hash === true) {
                                field.type = "password";
                            }
                            var fieldItem = $('<li>', {
                                class: 'objectItem userSettingsItem',
                                field: field.name,
                                required: field.required,
                                field_type: field.type
                            });
                            var fieldIcon = $('<img>', {
                                class: 'objectItemIcon',
                                src: `/resource/${field.type}_edit.png`
                            });
                            var fieldLabel = $('<label>', {
                                class: 'objectItemLabel standardWidth',
                                text: field.display,
                                for: `user_settings_input_${field.name}_${res.id}`,
                            });
                            var fieldInput = $('<input>', {
                                id: `user_settings_input_${field.name}_${res.id}`,
                                name: `user_settings_input_${field.name}_${res.id}`,
                                field_type: field.type,
                                field_name: field.display,
                                class: 'userSettingsInput',
                                placeholder: field.display,
                                type: 'text',
                                user: res.id,
                                field: field.name,
                                value: res.userdata[field.name]
                            });
                            fieldItem.append(fieldIcon, fieldLabel, fieldInput);
                            fieldList.append(fieldItem);
                            if (field.type == "password" && (typeof res.userdata.isPluginUser == "undefined" || res.userdata.isPluginUser === false)) {
                                var fieldItemVerify = $('<li>', {
                                    class: 'objectItem userSettingsItem',
                                    field: `${field.name}_verify`,
                                    required: "required",
                                    field_type: field.type
                                });
                                var fieldItemVerifyIcon = $('<img>', {
                                    class: 'objectItemIcon',
                                    src: `/resource/${field.type}_edit.png`
                                });
                                var fieldLabelVerify = $('<label>', {
                                    class: 'objectItemLabel standardWidth',
                                    text: "Verify",
                                    for: `user_settings_verify_${field.name}_${res.id}`,
                                });
                                var fieldInputVerify = $('<input>', {
                                    field_name: field.humanname,
                                    field_type: field.type,
                                    class: 'userSettingsInputVerify',
                                    id: `user_settings_verify_${field.name}_${res.id}`,
                                    name: `user_settings_verify_${field.name}_${res.id}`,
                                    user: res.id,
                                    field: field.name,
                                    placeholder: 'Verify ' + field.humanname,
                                    type: 'password'
                                });
                                fieldInput.attr('type', 'password');
                                fieldItemVerify.append(fieldItemVerifyIcon, fieldLabelVerify, fieldInputVerify);
                                fieldList.append(fieldItemVerify);
                            }
                            if (typeof res.userdata.isPluginUser != "undefined" && res.userdata.isPluginUser === true) {
                                fieldInput.attr('disabled', 'disabled');
                                fieldList.hide(0);
                            }
                        });
                    });
            }
            if (typeof res.userdata == "undefined" || typeof res.userdata.isPluginUser == "undefined" || res.userdata.isPluginUser === false || moduleName != "settings") {
                var settingsSave = $('<button>', {
                    id: 'userSettingsSubmit',
                    module: moduleName,
                    text: 'Save Settings',
                    user: res.id
                });
                $('#userSettingsSubmitTools').append(settingsSave);
            } else if (moduleName == "settings") {
                var pageHelp = new PageInfoBox({
                    title: 'Your account is controlled by a plugin.',
                    body: `Because your account is defined by the ${res.userdata.auth_source} plugin, you cannot edit your profile details here.`
                });
                $('#userSettingsContent').prepend(pageHelp.box);
            }
        });
    } else {
        $.ajax({
            url: `/account/plugin/${plugin}/${moduleName}`,
            type: 'OPTIONS',
        }).done((res) => {
            // don't ask me why browsers execute javascript just because they receive it from a server but whatevs
        }).fail((err) => {
            console.log(err);
        });
    }
}

$(document).on('change', '#user_option_input_theme_color', function() {
    var color = $(this).val();
    $('.userSettingsColorLabel[for="user_option_input_theme_color"]').css({"background": color});
});

$(document).on('click', '#userSettingsSubmit', function() {
    var moduleName = $(this).attr('module');
    switch (moduleName) {
        case "theme":
            var pdata = {};
            $.each($('.userSettingsInput'), function() {
                var optName = $(this).attr('opt_name');
                switch ($(this).attr('opt_type')) {
                    case 'bool':
                        pdata[optName] = $(this).prop('checked');
                        break;
                    default:
                        pdata[optName] = $(this).val();
                }
            });
            console.log(pdata);
            $.ajax({
                url: '/api/account/theme',
                type: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                data: JSON.stringify(pdata)
            }).fail(function(err) {
                alert(err.responseText);
                return false;
            }).done(function(res) {
                location.reload();
            });
            break;
        case "settings":
            var pdata = {};
            $.each($('.userSettingsInput'), function() {
                var type = $(this).attr('field_type');
                var field = $(this).attr('field');
                var value = $(this).val();
                if (type == "password") {
                    var verify = $('.userSettingsInputVerify[field="'+field+'"]').val();
                    var field_name = $(this).attr('field_name');
                    if (verify != value) {
                        console.log(`${verify} (verify) is not ${value} (value), apparently.`);
                        alert(`Please verify the ${field_name} accurately.`);
                        return false;
                    }
                }
                pdata[field] = value;
            });
            $.ajax({
                url: '/api/account/settings',
                type: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                data: JSON.stringify(pdata)
            }).fail(function(err) {
                alert(err.responseText);
                return false;
            }).done(function(res) {
                console.log(res);
                location.reload();
                return true;
            });
            break;
        default:
            return false;
    }
});