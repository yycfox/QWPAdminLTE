function createICheckbox(p) {
    var t1, t2, t3, t4;
    if (p) {
        t1 = p.find('input[type="checkbox"].minimal, input[type="radio"].minimal');
        t2 = p.find('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red');
        t3 = p.find('input[type="checkbox"].flat-red, input[type="radio"].flat-red');
        t4 = p.find('input[type="checkbox"].square, input[type="radio"].square');
    } else {
        t1 = $('input[type="checkbox"].minimal, input[type="radio"].minimal');
        t2 = $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red');
        t3 = $('input[type="checkbox"].flat-red, input[type="radio"].flat-red');
        t4 = $('input[type="checkbox"].square, input[type="radio"].square');
    }
    t1.iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass: 'iradio_minimal-blue'
    });
    t2.iCheck({
        checkboxClass: 'icheckbox_minimal-red',
        radioClass: 'iradio_minimal-red'
    });
    t3.iCheck({
        checkboxClass: 'icheckbox_flat-green',
        radioClass: 'iradio_flat-green'
    });
    t4.iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue'
    });
}
function initAdminLTE() {
    qwp.ui.push(createICheckbox);
}
qwp.r(initAdminLTE);