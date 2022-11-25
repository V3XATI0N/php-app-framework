$.ajax({
    url: '/api/admin/settings/core/app_name',
}).done(function(res) {
    $(document).prop('title', res.value);
    if ($('#headertitle').length) {
        $('#headertitle').text(res.value);
    }
});