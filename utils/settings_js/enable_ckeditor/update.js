$.ajax({
    url: '/api/admin/settings/core/enable_ckeditor'
}).done(function(res) {
    if (res.value === true) {
        $('head').prepend('<script id="script_enable_ckeditor" src="/resource/ckeditor/ckeditor.js"></script>');
    } else {
        $('#script_enable_ckeditor').remove();
    }
})