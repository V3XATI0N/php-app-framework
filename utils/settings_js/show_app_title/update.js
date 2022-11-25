$.ajax({
    url: '/api/admin/settings/core/show_app_title',
}).done(function(res) {
    if (res.value === true) {
        $.ajax({
            url: '/api/admin/settings/core/app_name',
        }).done(function(resx) {
            $('#logo a').append($('<div>', {
                id: 'headertitle',
                text: resx.value
            }));
        });
    } else {
        $('#headertitle').remove();
    }
});