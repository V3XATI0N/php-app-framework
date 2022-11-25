$.ajax({
    url: '/api/admin/settings/core/show_nav_text'
}).done(function(res) {
    if (res.value === true) {
        $('#quicklinks .navMenuLink').removeClass('notext');
    } else {
        $('#quicklinks .navMenuLink').addClass('notext');
    }
})