$.ajax({
    url: '/api/admin/settings/core/theme_color',
}).done(function(res) {
    reloadCss('&theme_preview=true');
});