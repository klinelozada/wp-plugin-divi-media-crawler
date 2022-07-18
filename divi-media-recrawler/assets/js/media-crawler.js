jQuery(document).ready(function($) {
    
    // Search External URL
    $( "#search-external" ).on( "click", function() {
    
        var external_url = $('#external-url').val();
        var data = {
            'action': 'dmc_action',
            'external_url': external_url
        };

        $.post(ajax_object.ajax_url, data, function(response) {
            $('#searchResult').empty();
            $('#searchResult').append(response).hide().fadeIn(1000);
        });

    });
    
    $( "body" ).on( "click", ".test",function() {
        console.log('xda');
    });

    // Search Bulk Reupload
    $( "body" ).on( "click", "#bulk-reupload", function() {

        var external_url = $('#external-url').val();
        var bulk_array = $('input:text#bulk-data').data('value');

        // console.log(bulk_array); 

        $.ajax({
            type: "POST",
            url: ajax_object.ajax_url,
            data : {
                action: 'dmc_bulk_upload_media',
                external_url : external_url,
                bulk_data: bulk_array
            },
            success: function(response) {
                // console.log(response.type);
                $('#bulkProcessResult').empty();
                $('#bulkProcessResult').append(response).hide().fadeIn(1000);
            }
        });

    });

    // Search Single Reupload
    $( "body" ).on( "click", "#single-reupload", function() {

        var external_url = $('#external-url').val();
        // var single_data = $('input:text#single-data').data('value');
        var single_data = $(this).data('value');
        var root_click = $(this);

        console.log(single_data);

        $.ajax({
            type: "POST",
            url: ajax_object.ajax_url,
            data : {
                action: 'dmc_single_upload_media',
                external_url : external_url,
                single_data: single_data
            },
            success: function(response) {

                console.log(response);

                // $('#singleProcessResult').empty();
                // $('#singleProcessResult').append(response).hide().fadeIn(1000);

                if(response == 10) {

                    // Show message to let the user know that the file has been uploaded
                    $(root_click).parent().parent().after('<tr><td colspan="4">File Reuploaded Successfully!</td></tr>');

                    // Remove the action button and replace with a check to avoid multiple actions
                    $(root_click).replaceWith('<i class="far fa-check dmc-result"></i>');

                } else {

                    // Show message to let the user know that the file has been uploaded
                    $(root_click).parent().parent().after('<tr><td colspan="4">File Reupload Failed!</td></tr>');

                }

            }
        });

    });
    
});

