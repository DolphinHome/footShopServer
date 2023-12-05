jQuery(document).ready(function(){
    $('#express_company_id').change(function(){
        var company = $(this).find('option:selected').text();
        $('#express_company').val(company);
    })
});
 