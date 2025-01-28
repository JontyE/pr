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
});
