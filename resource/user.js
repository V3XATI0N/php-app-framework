function checkLoginStatus(timeoutCheck) {
    $.ajax({
        url: '/api/account/timeout?' + new Date().getTime(),
        type: 'GET'
    }).fail(function(err) {
        clearInterval(timeoutCheck);
        console.log('idle timeout');
        window.location = "/";
    }).done(function(res) {
        if (res.status == "expired" || res.status == "none") {
            console.log('idle timeout redirect');
            clearInterval(timeoutCheck);
            window.location = "/";
        } else {
            if (typeof res.actions != "undefined" && res.actions !== false) {
                $.each(res.actions, function(key, act) {
                    switch(key) {
                        case "logout":
                            if (act === true) {
                                window.location = 'logout';
                                throw new Error('logout forced');
                            }
                            break;
                        case "notifications":
                            $.each(act, function(i, notify) {
                                displayCoreNotification(notify);
                            });
                            break;
                    }
                });
            }
        }
    });
}

class notifyItem {
    constructor(options) {
        if (typeof options.icon === "undefined") {
            options.icon = "/resource/notify_default.png";
        }
        var ndiv = $('<div>', {
            class: 'core_notifyItemBox'
        });
        var ntitle = $('<div>', {
            class: 'core_notifyItemTitle',
            text: options.title
        });
        var ntext = $('<div>', {
            class: 'core_notifyItemText',
            text: options.text
        });
        var nicon = $('<img>', {
            class: 'core_notifyItemIcon',
            src: options.icon
        });
        var nclose = $('<img>', {
            class: 'core_notifyItemClose',
            src: '/resource/thin_x.png'
        });
        ndiv.append(nicon, ntitle, ntext, nclose);
        this.box = ndiv;
    }
}

function displayCoreNotification(notify) {
    if (document.getElementById('core_notifyTop') === null) {
        var notifyTop = $('<div>', {
            id: "core_notifyTop"
        });
    }
    var notifyBox = new notifyItem({
        title: notify.title,
        text: notify.text,
        icon: notify.icon
    }).box;
    notifyTop.append(notifyBox);
    $('body').append(notifyTop);
}

$(document).ready(function() {
    timeoutCheck = setInterval(
        function() {
            checkLoginStatus(timeoutCheck);
        }, 15000
    );
});