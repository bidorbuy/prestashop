jQuery(document).ready(function () {
    // var x = jQuery('#loggingForm').html();
    // jQuery('#loggingForm').html("<fieldset>" + x + "</fieldset>");
    //
    // //------For PS 1.6+
    //  jQuery('#content.bootstrap #loggingForm').html("<div class='panel'><div class='panel-heading'></div>" + x + "</div>");
    jQuery('.panel-footer a').first().attr('target', '_blank').next().attr('target', '_blank');
    //  jQuery('.loggingFormButton').addClass('btn btn-default');
    // //-----------------
    jQuery('#fieldset_4').append(jQuery('#loggingForm'));

    jQuery('input[name="password"]').val(jQuery('#hiddenPass').val());

    jQuery('#tokenExportUrl, #tokenDownloadUrl, #resetaudit').after(
        "<button type=\"button\" class=\"button copy-button btn btn-default\">Copy</button>"
    );

    jQuery('.copy-button').click(function () {
        jQuery(this).prev().select();
    });

    //Is it bidorbuy module page? Add the crutch for disabled categories:
    if ($('#tokenExportUrl')) {
        var intervalID = setInterval(function () {

            if ((typeof disabledCats !== 'undefined') && $('#categories-treeview input').length > 1) {
                catsArray = disabledCats.split(',');
                $('#categories-treeview input').each(function (a, el) {
                    if ($.inArray(el.value, catsArray) !== -1) {
                        $(el).attr('disabled', 'disabled');
                    }
                });

                clearInterval(intervalID);
            }
        }, 1000);
    }

    //Set target="_blank" to Reset Audit button. It's known Prestashop issue: http://forge.prestashop.com/browse/PSCSX-3198
    $('#launch-reset-audit-button').attr('target', '_blank');

    InfoBlock = '<h4> <span>Basic Access Authentication</span></h4>' +
        '<span>(if necessary)</span><br>    <h4><span style="color: red">' +
        'Do not enter username or password of ecommerce platform, please read carefully about this kind of authentication! ' +
        '</span> </h4><br>';


    /*
     * Create launch buttons
     */
    ExportLink = jQuery('#tokenExportUrl').val();
    DownloadLink = jQuery('#tokenDownloadUrl').val();
    ResetAuditLink = jQuery('#resetaudit').val();

    jQuery('#tokenExportUrl').after(
        "<button type=\"button\" class=\"button copy-button btn btn-default\"  onclick=\"window.open('"+ExportLink+"')\">Launch</button>");

    jQuery('#tokenDownloadUrl').after(
        "<button type=\"button\" class=\"button copy-button btn btn-default\"  onclick=\"window.open('"+DownloadLink+"')\">Launch</button>");

    jQuery('#resetaudit').after(
        "<button type=\"button\" class=\"button copy-button btn btn-default\"  onclick=\"window.open('"+ResetAuditLink+"')\">Launch</button>");
    
    //For PrestaShop 1.5
    if($('#top_container').width()){
        $('#fieldset_3 label:eq(0)').before(InfoBlock);
        $('#fieldset_4 .margin-form:eq(1)').after( '<h4>Logs</h4>');
        $('.bob-version').wrap('<fieldset id="fieldset_ver"> <legend>Version</legend> </fieldset> ');
        $('#fieldset_ver').before('<br>');
        
    } else { // PrestaShop 1.6
        $('#fieldset_3 .form-group:first').prepend(InfoBlock);
        $('#fieldset_4 .form-group:eq(1)').append('<h4>Logs</h4>');
        $('.bob-version').wrap('<div class="panel"> <div class="panel-heading">Version</div> </div>');
    }
});
