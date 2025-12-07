<?php
require_once 'session.php';
require_once 'check_profile_status.php';

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
    
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Quantum Blockchain</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        :root {
            --deep-blue: #1e40af;
            --neon-purple: #d946ef; /* Fuchsia-500 roughly */
            --glass-border: rgba(226, 232, 240, 0.8);
        }
        
        body {
            background-color: #f8fafc; /* Slate-50 */
            color: #1e293b; /* Slate-800 */
        }

        .dashboard-content-card {
            background: white;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        /* Metric Card Styling (Shared) */
        .metric-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-color: var(--neon-purple);
        }

        .progress-bar-bg {
            background-color: #e2e8f0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--deep-blue), var(--neon-purple));
            width: 0%;
            transition: width 1.5s ease-out;
        }

        /* Blockchain Timeline Container */
        .blockchain-container {
            position: relative;
            min-height: 500px;
            background: linear-gradient(180deg, #f8fafc 0%, #eff6ff 100%);
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            overflow: hidden; /* Keep content inside */
            padding: 2rem;
        }

        /* Vertical Line */
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 2rem;
            bottom: 2rem;
            width: 2px;
            background: #cbd5e1;
            transform: translateX(-50%);
            z-index: 0;
        }

        .timeline-pulse {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 20%;
            background: linear-gradient(to bottom, transparent, var(--neon-purple), transparent);
            opacity: 0.5;
            animation: pulse-down 3s linear infinite;
        }

        @keyframes pulse-down {
            0% { top: -20%; }
            100% { top: 120%; }
        }

        /* Timeline Blocks */
        .block-node {
            position: relative;
            z-index: 10;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            width: 45%; /* Fit on one side */
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: default;
        }
        
        .block-node:hover {
            border-color: var(--neon-purple);
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(30, 64, 175, 0.15);
        }

        .block-node::before {
            content: '';
            position: absolute;
            top: 50%;
            width: 1rem;
            height: 2px;
            background: #cbd5e1;
            transform: translateY(-50%);
        }

        /* Left aligned blocks */
        .block-left {
            margin-left: 0;
            margin-right: auto;
        }
        .block-left::before {
            right: -1rem; /* Connect to center */
            width: calc(50vw - 100% - 2rem); /* Fallback width logic managed by flex alignment instead */
        }
        
        /* Right aligned blocks */
        .block-right {
            margin-left: auto;
            margin-right: 0;
        }

        /* Center Connector Dot */
        .connector-dot {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 12px;
            height: 12px;
            background: white;
            border: 2px solid var(--deep-blue);
            border-radius: 50%;
            z-index: 20;
            box-shadow: 0 0 0 4px rgba(248, 250, 252, 1); /* fake padding */
        }

        .block-hash {
            font-family: 'Courier New', monospace;
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.5rem;
            display: block;
        }

        /* Icon Badge */
        .icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 1rem;
        }

        .badge-blue { background: #eff6ff; color: #1d4ed8; }
        .badge-purple { background: #fdf4ff; color: #a855f7; }
        .badge-green { background: #f0fdf4; color: #16a34a; }
        .badge-orange { background: #fff7ed; color: #ea580c; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container mt-24 md:mt-28">
        <div class="dashboard-content-card">
            
            <!-- Header Section -->
            <div class="flex justify-between items-end mb-8 pb-4 border-b border-gray-100">
                <div>
                    <h1 class="text-3xl font-bold text-blue-900">Quantum <span class="text-neon-purple font-light">Blockchain</span></h1>
                    <p class="text-gray-500 mt-1">Immutable Academic Activity Ledger</p>
                </div>
                <div class="text-right hidden sm:block">
                    <div class="text-sm text-gray-400">Ledger Status</div>
                    <div class="text-green-600 font-semibold flex items-center justify-end gap-2">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Live & Secured
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Panel: Blockchain Activity Timeline (Takes up 2 cols) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-200 p-1 shadow-sm h-full">
                        <div class="flex justify-between items-center px-4 py-3 border-b border-gray-50">
                            <h3 class="font-semibold text-gray-700">Activity Block Explorer</h3>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-50 text-blue-600 rounded-md border border-blue-100">Latest Blocks</span>
                                <i class="fas fa-link text-gray-300"></i>
                            </div>
                        </div>
                        
                        <div class="blockchain-container">
                            <!-- The Vertical Line -->
                            <div class="timeline-line">
                                <div class="timeline-pulse"></div>
                            </div>

                            <!-- Block 1 (Latest) - Right Aligned -->
                            <div class="relative w-full h-32">
                                <div class="connector-dot top-10"></div>
                                <div class="block-node block-right absolute right-0 top-0 w-[45%]">
                                    <div class="flex items-start">
                                        <div class="icon-badge badge-blue">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Study Session Logged</h4>
                                            <p class="text-xs text-gray-500 mt-1">Focus Mode: 45 mins (Calculus)</p>
                                            <span class="block-hash">HASH: 0x8f...a2b1 [Quantum Verified]</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 text-[10px] text-gray-400 font-mono">10:42 AM</div>
                                </div>
                            </div>

                            <!-- Block 2 - Left Aligned -->
                            <div class="relative w-full h-32">
                                <div class="connector-dot top-10"></div>
                                <div class="block-node block-left absolute left-0 top-0 w-[45%]">
                                    <div class="flex items-start">
                                        <div class="icon-badge badge-purple">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Quiz Completed</h4>
                                            <p class="text-xs text-gray-500 mt-1">Physics Unit 3: 85% Score</p>
                                            <span class="block-hash">HASH: 0x3c...d9e4 [Immutable]</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 text-[10px] text-gray-400 font-mono">09:15 AM</div>
                                </div>
                            </div>

                            <!-- Block 3 - Right Aligned -->
                            <div class="relative w-full h-32">
                                <div class="connector-dot top-10"></div>
                                <div class="block-node block-right absolute right-0 top-0 w-[45%]">
                                    <div class="flex items-start">
                                        <div class="icon-badge badge-green">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Attendance Verified</h4>
                                            <p class="text-xs text-gray-500 mt-1">Weekly Group Check-in</p>
                                            <span class="block-hash">HASH: 0x1a...f7b2 [Signed]</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 text-[10px] text-gray-400 font-mono">Yesterday</div>
                                </div>
                            </div>

                            <!-- Block 4 - Left Aligned -->
                            <div class="relative w-full h-32">
                                <div class="connector-dot top-10"></div>
                                <div class="block-node block-left absolute left-0 top-0 w-[45%]">
                                    <div class="flex items-start">
                                        <div class="icon-badge badge-orange">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Resource Accessed</h4>
                                            <p class="text-xs text-gray-500 mt-1">Downloaded "Organic Chem Notes"</p>
                                            <span class="block-hash">HASH: 0x9b...c4d1 [Access Log]</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 text-[10px] text-gray-400 font-mono">Yesterday</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Right Panel: Information Cards -->
                <div class="space-y-6">
                    
                    <!-- Card 1: Blocks Verified -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Blocks Verified</div>
                            <i class="fas fa-cubes text-blue-800 opacity-50"></i>
                        </div>
                        <div class="text-3xl font-bold text-blue-900 counter" data-target="3284">0</div>
                        <div class="text-xs text-green-600 mt-1 flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            All verifications stable
                        </div>
                    </div>

                    <!-- Card 2: Encryption Strength -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Quantum Encryption</div>
                            <i class="fas fa-shield-alt text-purple-500 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="99.9">0</div>
                            <span class="text-lg text-gray-400 font-medium">%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: 0%;" data-width="99.9%"></div>
                        </div>
                    </div>

                    <!-- Card 3: Ledger Integrity -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Ledger Integrity Score</div>
                            <i class="fas fa-file-signature text-blue-800 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="97">0</div>
                            <span class="text-lg text-gray-400 font-medium">/ 100</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Top-tier integrity
                        </div>
                        <div class="progress-bar-bg mt-2">
                            <div class="progress-bar-fill" style="width: 0%;" data-width="97%"></div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button class="w-full bg-blue-900 hover:bg-blue-800 text-white font-medium py-3 px-4 rounded-xl transition duration-200 shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2 group">
                        <span>Sync Ledger</span>
                        <i class="fas fa-link group-hover:rotate-90 transition-transform duration-500"></i>
                    </button>

                </div>
            </div>

            <a href="dashboard.php" class="back-link block text-center mt-8 text-gray-400 hover:text-blue-800 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Counter Animation
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseFloat(counter.getAttribute('data-target'));
                const duration = 1500;
                const increment = target / (duration / 16);
                
                let current = 0;
                const updateCount = () => {
                    current += increment;
                    if (current < target) {
                        // Handle decimal for 99.9
                        if (target % 1 !== 0) {
                             counter.innerText = current.toFixed(1);
                        } else {
                             counter.innerText = Math.ceil(current);
                        }
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });

            // Progress Bar Animation
            const progressBars = document.querySelectorAll('.progress-bar-fill');
            setTimeout(() => {
                progressBars.forEach(bar => {
                    const width = bar.getAttribute('data-width');
                    bar.style.width = width;
                });
            }, 300);
        });
    </script>
</body>
</html>