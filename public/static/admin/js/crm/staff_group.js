$(function($) {
    $("#deptid").change( function() {
        var deptid = $(this).val();
        $.get("/admin.php/crm/staff/getStaff?deptid=" + deptid, function (res) {
            $('#leader').html('');
            var first = new Option('无', 0, true, true);
            $('#leader').append(first);
            $.each(res, function (i) {
                var option = new Option(res[i], i, true, false);
                $('#leader').append(option);
            });
            $('#pid').trigger("change");
        });
    });
});