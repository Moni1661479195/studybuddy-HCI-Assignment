<?php
require_once 'session.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reported_user_id = isset($_GET['reported_user_id']) ? (int)$_GET['reported_user_id'] : 0;

if ($reported_user_id === 0) {
    // Redirect if no user is specified to be reported
    header("Location: study-groups.php?error=No user specified for reporting.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a User - Study Buddy</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/report.css">

    <style>
      
        body {
            background-color: #f9fafb; 
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="report-container mt-24 md:mt-28">
        <div class="report-card">
            <div class="report-card-header">
                <h1 class="report-title">Report a User</h1>
                <p class="report-subtitle">Your report is anonymous to the reported user.</p>
            </div>
            <div class="report-card-body">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message" id="serverErrorMessage">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="success-message" id="serverSuccessMessage">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form id="reportForm" action="submit_report.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="reported_user_id" value="<?php echo $reported_user_id; ?>">
                    
                    <div class="input-group">
                        <label class="input-label" for="reason">Reason for Reporting</label>
                        <div class="input-wrapper">
                            <textarea id="reason" name="reason" class="input-field" placeholder="Please describe the issue in detail..." required></textarea>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="screenshot">Attach Screenshots (Optional)</label>
                        <div class="input-wrapper">
                            <input type="file" id="screenshot" name="screenshot[]" class="input-field" multiple accept="image/*">
                        </div>
                        <div id="image-preview-container"></div>
                    </div>

                    <button type="submit" id="submit-button">Submit Report</button>
                </form>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <style>
        #image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        .preview-container {
            position: relative;
        }
        .preview-image {
            width: 150px; /* Increased size */
            height: 150px; /* Increased size */
            object-fit: cover;
            border-radius: 0.75rem; /* Larger radius */
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .preview-image:hover {
            transform: scale(1.05);
        }
        .delete-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 20px;
            height: 20px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            line-height: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
    </style>

    <script>
        const reasonTextarea = document.getElementById('reason');
        const maxWords = 10000;
        const screenshotInput = document.getElementById('screenshot');
        const previewContainer = document.getElementById('image-preview-container');
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const closeModal = document.querySelector('.close-modal');

        let uploadedFiles = [];

        reasonTextarea.addEventListener('input', () => {
            const text = reasonTextarea.value.trim();
            const words = text.split(/\s+/).filter(Boolean);
            let wordCountValue = words.length;

            if (wordCountValue > maxWords) {
                document.getElementById('submit-button').disabled = true;
            } else {
                document.getElementById('submit-button').disabled = false;
            }
        });

        screenshotInput.addEventListener('change', () => {
            const files = screenshotInput.files;
            for (const file of files) {
                if (file.type.startsWith('image/') && !uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    uploadedFiles.push(file);
                }
            }
            updatePreview();
            updateFileInput();
        });

        function updatePreview() {
            previewContainer.innerHTML = '';
            uploadedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const previewWrapper = document.createElement('div');
                    previewWrapper.classList.add('preview-container');

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-image');
                    img.addEventListener('click', () => openModal(e.target.result));

                    const deleteBtn = document.createElement('span');
                    deleteBtn.classList.add('delete-preview');
                    deleteBtn.innerHTML = '&times;';
                    deleteBtn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        deleteImage(index);
                    });

                    previewWrapper.appendChild(img);
                    previewWrapper.appendChild(deleteBtn);
                    previewContainer.appendChild(previewWrapper);
                };
                reader.readAsDataURL(file);
            });
        }

        function deleteImage(index) {
            uploadedFiles.splice(index, 1);
            updatePreview();
            updateFileInput();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            uploadedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            screenshotInput.files = dataTransfer.files;
        }

        function openModal(src) {
            modalImage.src = src;
            imageModal.style.display = 'block';
        }

        closeModal.addEventListener('click', () => {
            imageModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target == imageModal) {
                imageModal.style.display = 'none';
            }
        });
    </script>
</body>



</html>
