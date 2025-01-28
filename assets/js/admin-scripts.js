jQuery(document).ready(function ($) {
    function startProgressTracking() {
        let interval = setInterval(function () {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_import_progress',
                    security: pr_nonce
                },
                success: function (response) {
                    if (response.success) {
                        let stage = response.data.stage || "processing";
                        let currentProcessed = parseInt(response.data.processed_rows) || 0;
                        let total = parseInt(response.data.total_rows) || 1;
                        let percentComplete = Math.round((currentProcessed / total) * 100);

                        if (stage === "processing") {
                            $("#upload-status").text(`Processing CSV... ${currentProcessed}/${total}`);
                            $("#progress-bar").css({ width: `${percentComplete}%`, background: "red" });

                            if (percentComplete >= 100) {
                                clearInterval(interval);
                                setTimeout(() => startProgressTracking(), 100); // Switch to "inserting"
                            }
                        } else if (stage === "inserting") {
                            $("#upload-status").text(`Inserting Data... ${currentProcessed}/${total}`);
                            $("#progress-bar").css({ width: `${percentComplete}%`, background: "green" });
    
                        } else if (stage === "completed") {
                            clearInterval(interval);
                            $("#upload-status").text("Upload Complete ✅");
    
                            setTimeout(() => {
                                $("#upload-popup").fadeOut();
                                $("#progress-bar").css("width", "0%");
                            }, 3000); // ✅ Hide popup after 3 seconds
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error("❌ AJAX Error:", xhr.responseText || status, error);
                    clearInterval(interval);
                    $("#upload-status").text("Error occurred while tracking progress.");
                    $("#progress-bar").css({ width: "100%", background: "red" });
                }
            });
        }, 500);
    }

    $("#upload_csv").on("click", function () {
        let fileInput = $("#csv_file")[0].files[0];
        if (!fileInput) {
            alert("Please select a file first.");
            return;
        }

        let formData = new FormData();
        formData.append("file", fileInput);
        formData.append("action", "import_csv");
        formData.append("security", pr_nonce);

        $("#upload-popup").fadeIn();
        $("#progress-bar").css({ width: "0%", background: "#28a745" });
        $("#upload-status").text("Uploading file...");

        startProgressTracking(); // Start tracking before upload completes

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $("#upload-status").text("Processing CSV...");
                } else {
                    $("#upload-status").text(response.message || "Upload failed.");
                }
            }
        });
    });

// Add Code Start -- Upload Quotes CSV
// Add Code Start -- Upload Quotes CSV
$("#quotes-upload-form").on("submit", function(e) {
    e.preventDefault();

    let fileInput = $("#quotes_csv")[0].files[0];
    if (!fileInput) {
        alert("Please select a file.");
        return;
    }

    let formData = new FormData();  // ✅ Ensure formData is defined
    formData.append("quotes_csv", fileInput);
    formData.append("action", "upload_quotes_csv");
    formData.append("security", pr_nonce);

    $("#quotes-progress-bar").show();

    $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function() {
            $("#upload-popup").fadeIn();
            $("#progress-bar").css("width", "0%");
        },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    var percent = (e.loaded / e.total) * 100;
                    $("#progress-bar").css("width", percent + "%");
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            console.log("✅ Success Response:", response);
            
            // ✅ Prevent alert if response is silent
            if (response.success && (!response.data.silent || response.data.silent !== true)) {
                alert(response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.log("❌ AJAX Error: ", error);
            alert("AJAX Error: " + error);
            $("#upload-popup").fadeOut();
        },
        complete: function() {
            $("#progress-bar").css("width", "100%");
            setTimeout(function() {
                $("#upload-popup").fadeOut();
            }, 1000);
        }
    });
});


// Add Code Start -- Upload Line Items CSV

$("#line-items-upload-form").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    let fileInput = $("#line_items_csv")[0].files[0];
    if (!fileInput) {
        alert("Please select a file first.");
        return;
    }

    let formData = new FormData();
    formData.append("line_items_csv", fileInput);
    formData.append("action", "upload_line_items_csv");
    formData.append("security", pr_nonce);

    // Show progress bar & disable button
    $("#line-items-progress-bar").show();
    let progressBar = $("#line-items-progress-bar .progress-bar");
    let uploadButton = $("#upload-line-items-btn");

    uploadButton.prop("disabled", true).text("Uploading...");
    progressBar.css("width", "0%").text("0%"); // Reset progress to 0%

    $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        xhr: function () {
            let xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function (e) {
                if (e.lengthComputable) {
                    let percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.css("width", percent + "%").text(percent + "%");
                }
            }, false);
            return xhr;
        },
        success: function (response) {
            console.log("✅ Response from server:", response);

            if (response.success) {
                uploadButton.text("Upload Completed").removeClass("btn-success").addClass("btn-secondary");
                progressBar.css("width", "100%").text("100%");
            } else {
                alert(response.message || "Upload failed.");
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ AJAX Error:", xhr.responseText || status, error);
            alert("Error occurred: " + (xhr.responseText || error));
        },
        complete: function () {
            // Reset progress bar & re-enable the button after 2 seconds
            setTimeout(function () {
                uploadButton.prop("disabled", false).text("Upload Line Items").removeClass("btn-secondary").addClass("btn-success");
                progressBar.css("width", "0%").text(""); // Reset progress bar to 0%
                $("#line-items-progress-bar").fadeOut(); // Hide progress bar
            }, 2000); 
        }
    });
});





});
