jQuery(document).ready(function($) {
    $('#upload_csv').on('click', function() {
        let formData = new FormData();
        formData.append('file', $('#csv_file')[0].files[0]);
        formData.append('action', 'import_csv');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#progressBar').css('width', '0%').text('Uploading...');
            },
            success: function(response) {
                $('#progressBar').css('width', '100%').text('Completed');
                alert(response);
            }
        });
    });
});
