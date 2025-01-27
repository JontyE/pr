jQuery(document).ready(function($) {
    function startDatabaseProgress(totalRows) {
        let stage = "processing"; // Track the current stage
        let interval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_import_progress',
                    security: pr_nonce
                },
                success: function(response) {
                    console.log("🔍 Progress Response:", response);

                    if (response.success) {
                        let processedRows = parseInt(response.data.processed_rows) || 0;
                        let total = parseInt(response.data.total_rows) || 1; // Prevent division by zero
                        let percentComplete = Math.round((processedRows / total) * 100);

                        if (stage === "processing") {
                            $("#upload-status").text(`Processing CSV... ${processedRows}/${total}`);
                        } else if (stage === "inserting") {
                            $("#upload-status").text(`Adding Data... ${processedRows}/${total}`);
                        }

                        // ✅ Update progress bar width dynamically
                        $("#progress-bar").css({
                            width: `${percentComplete}%`,
                            background: "green"
                        });

                        // ✅ Move to the next stage after CSV processing is done
                        if (processedRows >= total && stage === "processing") {
                            stage = "inserting";
                            processedRows = 0; // Reset for database insertion tracking
                            setTimeout(() => {
                                $("#upload-status").text("Adding Data...");
                            }, 1000);
                        }

                        // ✅ If database insertion is complete, close popup
                        if (processedRows >= total && stage === "inserting") {
                            clearInterval(interval);
                            $("#upload-status").text("Upload Complete ✅");

                            setTimeout(() => {
                                $("#progress-bar").css("width", "100%");
                                setTimeout(() => {
                                    $("#upload-popup").fadeOut();
                                    $("#progress-bar").css("width", "0%"); // Reset bar
                                }, 2000);
                            }, 1000);
                        }
                    } else {
                        console.error("❌ Progress Error:", response.message);
                        $("#upload-status").text("Error fetching progress.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("❌ Progress Check AJAX Error:", xhr.responseText || status, error);
                    clearInterval(interval);
                    $("#upload-status").text("Error checking progress.");
                }
            });
        }, 1000); // Poll every second
    }

    $("#upload_csv").on("click", function() {
        let fileInput = $("#csv_file")[0].files[0];
        if (!fileInput) {
            alert("Please select a file first.");
            return;
        }

        let formData = new FormData();
        formData.append("file", fileInput);
        formData.append("action", "import_csv");
        formData.append("security", pr_nonce);

        // ✅ Ensure Bootstrap Modal Shows Correctly
        $("#upload-popup").fadeIn();
        $("#progress-bar").css({ width: "0%", background: "#28a745" });
        $("#upload-status").text("Uploading file...");

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log("✅ File Upload Success:", response);
                if (response.success) {
                    startDatabaseProgress(parseInt(response.data.total_rows) || 1);
                } else {
                    $("#upload-status").text(response.message || "Upload failed.");
                    console.error("❌ Upload Error:", response.message);
                    $("#progress-bar").css({ width: "100%", background: "red" });
                    setTimeout(() => $("#upload-popup").fadeOut(), 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error("❌ AJAX Error:", xhr.responseText || status, error);
                $("#upload-status").text("Upload failed: " + (xhr.responseText || error));
                $("#progress-bar").css({ width: "100%", background: "red" });
                setTimeout(() => $("#upload-popup").fadeOut(), 3000);
            }
        });
    });
});
