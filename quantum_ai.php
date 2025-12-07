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
    <title>Study Buddy - Quantum AI</title>

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
            background-color: #f8fafc; /* Slate-50 - Clean light background */
            color: #1e293b; /* Slate-800 */
        }

        .dashboard-content-card {
            background: white;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        /* Quantum Accent Styles */
        .text-neon-purple {
            color: var(--neon-purple);
        }
        
        .border-neon-purple {
            border-color: var(--neon-purple);
        }

        .quantum-accent-line {
            height: 3px;
            background: linear-gradient(90deg, var(--deep-blue) 0%, var(--neon-purple) 100%);
            border-radius: 2px;
        }

        /* Holograph Container */
        .holograph-container {
            position: relative;
            height: 400px;
            background: linear-gradient(180deg, #f8fafc 0%, #eff6ff 100%); /* Very subtle gradient */
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .holograph-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                linear-gradient(rgba(30, 64, 175, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(30, 64, 175, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            transform: perspective(500px) rotateX(60deg) translateY(-100px) translateZ(-200px);
            animation: grid-move 20s linear infinite;
        }

        @keyframes grid-move {
            0% { background-position: 0 0; }
            100% { background-position: 0 40px; }
        }

        /* SVG Graph Animations */
        .graph-path {
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            filter: drop-shadow(0 0 4px rgba(217, 70, 239, 0.3));
        }

        .ground-state-path {
            stroke: var(--neon-purple);
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw-path 3s ease-out forwards, glow-pulse 3s infinite alternate;
        }

        .stress-peak {
            fill: rgba(239, 68, 68, 0.1); /* Red-ish fill for stress */
            stroke: #ef4444; /* Red stroke */
        }

        .flow-valley {
            fill: rgba(30, 64, 175, 0.05); /* Blue fill for flow */
            stroke: var(--deep-blue);
        }

        @keyframes draw-path {
            to { stroke-dashoffset: 0; }
        }

        @keyframes glow-pulse {
            from { filter: drop-shadow(0 0 2px rgba(217, 70, 239, 0.5)); }
            to { filter: drop-shadow(0 0 8px rgba(217, 70, 239, 0.8)); }
        }

        /* Metric Card Styling */
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
            width: 0%; /* Animate to value */
            transition: width 1.5s ease-out;
        }

        .floating-badge {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container mt-24 md:mt-28">
        <div class="dashboard-content-card">
            
            <!-- Header Section -->
            <div class="flex justify-between items-end mb-8 pb-4 border-b border-gray-100">
                <div>
                    <h1 class="text-3xl font-bold text-blue-900">Quantum AI <span class="text-neon-purple font-light">Optimizer</span></h1>
                    <p class="text-gray-500 mt-1">Personalized Knowledge Energy Landscape</p>
                </div>
                <div class="text-right hidden sm:block">
                    <div class="text-sm text-gray-400">System Status</div>
                    <div class="text-green-600 font-semibold flex items-center justify-end gap-2">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Coherent
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Center Content: Holographic Graph (Takes up 2 cols) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-200 p-1 shadow-sm h-full">
                        <div class="flex justify-between items-center px-4 py-3 border-b border-gray-50">
                            <h3 class="font-semibold text-gray-700">Energy Landscape Visualization</h3>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 text-xs font-medium bg-red-50 text-red-600 rounded-md border border-red-100">Peaks: Stress</span>
                                <span class="px-2 py-1 text-xs font-medium bg-blue-50 text-blue-600 rounded-md border border-blue-100">Valleys: Flow</span>
                            </div>
                        </div>
                        
                        <div class="holograph-container relative w-full" id="holograph">
                            <!-- Background Grid Animation -->
                            <div class="holograph-grid"></div>

                            <!-- SVG Visualization -->
                            <svg viewBox="0 0 800 400" class="absolute w-full h-full z-10" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="gradStress" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#ef4444;stop-opacity:0.1" />
                                        <stop offset="100%" style="stop-color:#ef4444;stop-opacity:0" />
                                    </linearGradient>
                                    <linearGradient id="gradFlow" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#1e40af;stop-opacity:0.1" />
                                        <stop offset="100%" style="stop-color:#1e40af;stop-opacity:0" />
                                    </linearGradient>
                                </defs>

                                <!-- Stress Peak (Left) -->
                                <path d="M0,300 Q200,50 400,250 T800,300" class="graph-path stress-peak" stroke="#ef4444" fill="url(#gradStress)"/>
                                
                                <!-- Flow Valley (Background) -->
                                <path d="M0,350 Q300,380 500,200 T800,350" class="graph-path" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="5,5" />

                                <!-- Ground State Path (The "Solution" Line) -->
                                <!-- A smooth path navigating the "valley" -->
                                <path d="M50,320 C150,320 200,280 350,330 S600,350 750,280" class="graph-path ground-state-path" fill="none" />

                                <!-- Interactive Points (Holographic Nodes) -->
                                <circle cx="350" cy="330" r="6" fill="#d946ef" class="floating-badge">
                                    <title>Optimal Learning State</title>
                                </circle>
                                <text x="365" y="335" font-family="Inter" font-size="12" fill="#d946ef" font-weight="bold">Current State</text>
                                
                                <circle cx="200" cy="150" r="4" fill="#ef4444" opacity="0.6">
                                    <title>High Stress Potential</title>
                                </circle>
                                <text x="180" y="140" font-family="Inter" font-size="10" fill="#ef4444">Calc Exam</text>
                            </svg>
                            
                            <!-- Overlay Label -->
                            <div class="absolute bottom-4 left-4 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg px-3 py-2 text-xs shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-800 to-purple-500"></div>
                                    <span class="text-gray-600 font-medium">Ground State Path Detected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Data Panel -->
                <div class="space-y-6">
                    
                    <!-- Metric 1 -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Qubits Active</div>
                            <i class="fas fa-microchip text-blue-800 opacity-50"></i>
                        </div>
                        <div class="text-3xl font-bold text-blue-900 counter" data-target="512">0</div>
                        <div class="text-xs text-green-600 mt-1 flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i> Stable coherence
                        </div>
                    </div>

                    <!-- Metric 2 -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Annealing Progress</div>
                            <i class="fas fa-fire-alt text-purple-500 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="99">0</div>
                            <span class="text-lg text-gray-400 font-medium">%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: 0%;" data-width="99%"></div>
                        </div>
                    </div>

                    <!-- Metric 3 -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Optimization Score</div>
                            <i class="fas fa-chart-line text-blue-800 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="98">0</div>
                            <span class="text-lg text-gray-400 font-medium">/ 100</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Top 1% of study plans
                        </div>
                        <div class="progress-bar-bg mt-2">
                            <div class="progress-bar-fill" style="width: 0%;" data-width="98%"></div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button class="w-full bg-blue-900 hover:bg-blue-800 text-white font-medium py-3 px-4 rounded-xl transition duration-200 shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2 group">
                        <span>Recalculate Path</span>
                        <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i>
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
                const target = +counter.getAttribute('data-target');
                const duration = 1500;
                const increment = target / (duration / 16);
                
                let current = 0;
                const updateCount = () => {
                    current += increment;
                    if (current < target) {
                        counter.innerText = Math.ceil(current);
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