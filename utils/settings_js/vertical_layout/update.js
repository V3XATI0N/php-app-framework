$.ajax({
    url: '/api/admin/settings/core/vertical_layout',
}).done(function(res) {
    if (res.value === true) {
        $('#header').addClass('vertical_layout');
        $('#quicklinks').addClass('vertical_layout');
        $('#core_template_base').addClass('vertical_layout');
    } else {
        $('#header').removeClass('vertical_layout');
        $('#quicklinks').removeClass('vertical_layout');
        $('#core_template_base').removeClass('vertical_layout');
    }
});