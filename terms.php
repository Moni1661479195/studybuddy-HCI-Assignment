<?php
// Start Session
require_once 'session.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Study Buddy</title>
    
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/modern_auth.css?v=1.0.3"> 
    <link rel="stylesheet" href="assets/css/modal.css?v=1.0.3">
    <link rel="stylesheet" href="assets/css/footer.css?v=1.0.1">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffffff; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0; 
        }
        

        footer svg { width: 1.75rem !important; height: 1.75rem !important; max-width: 100%; }
        nav svg { width: 2.25rem !important; height: 2.25rem !important; }

        .content-container {
            flex: 1; 
            display: flex;
            justify-content: center;
            padding: 2rem;
            margin-top: 80px; 
            position: relative;
            z-index: 1; 
        }
        
        
        nav { z-index: 50 !important; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="content-container">
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 w-full max-w-4xl text-left overflow-hidden">
            
            <div class="text-center mb-10 border-b pb-6 border-gray-200">
                <h1 class="text-4xl font-extrabold text-gray-800 mb-4">Terms and Conditions</h1>
                <p class="text-gray-500">Last updated: November 2025</p>
            </div>

            <div class="space-y-6 text-gray-600 leading-relaxed">
                
                <p class="text-lg">
                    Welcome to <span class="font-bold text-blue-600">Study Buddy!</span> These terms and conditions outline the rules and regulations for the use of Study Buddy's Website.
                    By accessing this website we assume you accept these terms and conditions. <span class="font-semibold text-red-500">Do not continue to use Study Buddy if you do not agree to take all of the terms and conditions stated on this page.</span>
                </p>

                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3 mb-4">
                        <i class="fas fa-copyright text-blue-500"></i> 1. Intellectual Property Rights
                    </h2>
                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                        <p class="mb-2">Other than the content you own, under these Terms, Study Buddy and/or its licensors own all the intellectual property rights and materials contained in this Website.</p>
                        <p class="font-medium text-blue-800">You are granted limited license only for purposes of viewing the material contained on this Website.</p>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3 mb-4">
                        <i class="fas fa-ban text-red-500"></i> 2. Restrictions
                    </h2>
                    <p class="mb-4">You are specifically restricted from all of the following:</p>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Publishing any Website material in any other media</span>
                        </li>
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Selling, sublicensing and/or otherwise commercializing material</span>
                        </li>
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Publicly performing and/or showing any Website material</span>
                        </li>
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Using this Website in any way that is damaging</span>
                        </li>
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Engaging in data mining, harvesting, or extracting</span>
                        </li>
                        <li class="flex items-start gap-3 bg-red-50 p-3 rounded-md hover:bg-red-100 transition">
                            <i class="fas fa-times-circle text-red-500 mt-1"></i>
                            <span class="text-sm">Using this Website for advertising or marketing</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-8">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3 mb-4">
                        <i class="fas fa-user-pen text-green-500"></i> 3. Your Content
                    </h2>
                    <p>In these Website Standard Terms and Conditions, "Your Content" shall mean any audio, video text, images or other material you choose to display on this Website.</p>
                </div>

                <div class="mt-10 bg-gray-50 border border-gray-200 rounded-xl p-6 shadow-inner">
                    <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-triangle text-orange-500"></i> Legal Disclaimers
                    </h2>
                    
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800 mb-2">4. No warranties</h3>
                        <p class="text-sm italic text-gray-500">This Website is provided "as is," with all faults, and Study Buddy express no representations or warranties, of any kind related to this Website.</p>
                    </div>

                    <div>
                        <h3 class="font-bold text-gray-800 mb-2">5. Limitation of liability</h3>
                        <p class="text-sm italic text-gray-500">In no event shall Study Buddy, nor any of its officers, directors and employees, be held liable for anything arising out of or in any way connected with your use of this Website whether such liability is under contract.</p>
                    </div>
                </div>

                <div class="mt-8 border-t pt-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-2">6. Governing Law & Jurisdiction</h2>
                    <p class="text-sm text-gray-500">These Terms will be governed by and interpreted in accordance with the laws of the State, and you submit to the non-exclusive jurisdiction of the state and federal courts.</p>
                </div>

            </div>
            
            <div class="mt-12 flex justify-center">
                <a href="index.php" class="bg-blue-600 text-white px-8 py-3 rounded-full font-semibold hover:bg-blue-700 transition transform hover:scale-105 shadow-lg">
                    I Understand & Go Home
                </a>
            </div>

        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>