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
    <title>Study Buddy - Quantum IoT</title>

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

        /* Holograph/Grid Container */
        .iot-grid-container {
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

        .iot-grid-bg {
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                radial-gradient(rgba(30, 64, 175, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: grid-pan 60s linear infinite;
        }

        @keyframes grid-pan {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50px, -50px); }
        }

        /* SVG Animations for IoT */
        .connection-line {
            stroke: #cbd5e1;
            stroke-width: 1.5;
            stroke-dasharray: 10;
            animation: dash-flow 2s linear infinite;
            opacity: 0.6;
        }
        
        .quantum-link {
            stroke: var(--neon-purple);
            stroke-width: 2;
            stroke-dasharray: 4;
            animation: pulse-link 1.5s infinite alternate;
        }

        .device-node {
            fill: white;
            stroke: var(--deep-blue);
            stroke-width: 2;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));
            transition: all 0.3s ease;
        }

        .device-node:hover {
            fill: #eff6ff;
            stroke-width: 3;
            cursor: pointer;
        }

        .router-node {
            fill: var(--neon-purple);
            stroke: white;
            stroke-width: 3;
            filter: drop-shadow(0 0 8px rgba(217, 70, 239, 0.6));
            animation: pulse-node 2s infinite;
        }

        @keyframes dash-flow {
            to { stroke-dashoffset: -20; }
        }

        @keyframes pulse-link {
            from { stroke-opacity: 0.4; }
            to { stroke-opacity: 1; }
        }

        @keyframes pulse-node {
            0% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(217, 70, 239, 0.6)); }
            50% { transform: scale(1.1); filter: drop-shadow(0 0 12px rgba(217, 70, 239, 0.8)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(217, 70, 239, 0.6)); }
        }

        /* Metric Card Styling (Consistent with Quantum AI) */
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container mt-24 md:mt-28">
        <div class="dashboard-content-card">
            
            <!-- Header Section -->
            <div class="flex justify-between items-end mb-8 pb-4 border-b border-gray-100">
                <div>
                    <h1 class="text-3xl font-bold text-blue-900">Quantum IoT <span class="text-neon-purple font-light">Network</span></h1>
                    <p class="text-gray-500 mt-1">Decentralized Student Device Grid</p>
                </div>
                <div class="text-right hidden sm:block">
                    <div class="text-sm text-gray-400">Network Status</div>
                    <div class="text-green-600 font-semibold flex items-center justify-end gap-2">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Synchronized
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Panel: Quantum Device Grid Visualization (Takes up 2 cols) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-200 p-1 shadow-sm h-full">
                        <div class="flex justify-between items-center px-4 py-3 border-b border-gray-50">
                            <h3 class="font-semibold text-gray-700">Quantum Device Grid</h3>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 text-xs font-medium bg-purple-50 text-purple-600 rounded-md border border-purple-100">Quantum Router</span>
                                <span class="px-2 py-1 text-xs font-medium bg-blue-50 text-blue-600 rounded-md border border-blue-100">Active Node</span>
                            </div>
                        </div>
                        
                        <div class="iot-grid-container relative w-full" id="iot-grid">
                            <!-- Background Grid Animation -->
                            <div class="iot-grid-bg"></div>

                            <!-- SVG Visualization -->
                            <svg viewBox="0 0 800 400" class="absolute w-full h-full z-10" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <!-- Gradients for nodes -->
                                    <radialGradient id="nodeGlow" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                                        <stop offset="0%" style="stop-color:#fff;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#eff6ff;stop-opacity:1" />
                                    </radialGradient>
                                </defs>

                                <!-- Connections (Standard) -->
                                <line x1="150" y1="100" x2="350" y2="200" class="connection-line" />
                                <line x1="150" y1="300" x2="350" y2="200" class="connection-line" />
                                <line x1="650" y1="150" x2="350" y2="200" class="connection-line" />
                                <line x1="550" y1="320" x2="350" y2="200" class="connection-line" />
                                <line x1="150" y1="100" x2="150" y2="300" class="connection-line" />

                                <!-- Connections (Quantum Entanglement) -->
                                <line x1="350" y1="200" x2="650" y2="150" class="quantum-link" />
                                <line x1="550" y1="320" x2="650" y2="150" class="quantum-link" />

                                <!-- Central Quantum Router Node -->
                                <g transform="translate(350, 200)">
                                    <circle r="25" class="router-node" />
                                    <!-- Icon placeholder (simple shape for simplicity in SVG) -->
                                    <rect x="-8" y="-8" width="16" height="16" rx="2" fill="white" />
                                    <circle r="30" fill="none" stroke="#d946ef" stroke-width="1" opacity="0.5">
                                        <animate attributeName="r" from="25" to="40" dur="2s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" from="0.5" to="0" dur="2s" repeatCount="indefinite" />
                                    </circle>
                                </g>

                                <!-- Device Nodes -->
                                <!-- Laptop Node (Top Left) -->
                                <g transform="translate(150, 100)" class="device-group">
                                    <circle r="18" class="device-node" />
                                    <!-- Simple Laptop Icon Shape -->
                                    <rect x="-8" y="-5" width="16" height="10" rx="1" fill="#1e40af" />
                                    <rect x="-10" y="5" width="20" height="2" rx="1" fill="#1e40af" />
                                    <text x="0" y="35" text-anchor="middle" font-family="Inter" font-size="10" fill="#64748b">Laptop-04</text>
                                </g>

                                <!-- Tablet Node (Bottom Left) -->
                                <g transform="translate(150, 300)" class="device-group">
                                    <circle r="18" class="device-node" />
                                    <!-- Simple Tablet Icon Shape -->
                                    <rect x="-6" y="-8" width="12" height="16" rx="1" fill="#1e40af" />
                                    <text x="0" y="35" text-anchor="middle" font-family="Inter" font-size="10" fill="#64748b">Tablet-A2</text>
                                </g>

                                <!-- Wearable Node (Top Right) -->
                                <g transform="translate(650, 150)" class="device-group">
                                    <circle r="18" class="device-node" />
                                    <!-- Simple Watch Icon Shape -->
                                    <circle r="6" fill="none" stroke="#1e40af" stroke-width="2" />
                                    <rect x="-2" y="-8" width="4" height="2" fill="#1e40af" />
                                    <rect x="-2" y="6" width="4" height="2" fill="#1e40af" />
                                    <text x="0" y="35" text-anchor="middle" font-family="Inter" font-size="10" fill="#64748b">Band-X</text>
                                </g>

                                <!-- Mobile Node (Bottom Right) -->
                                <g transform="translate(550, 320)" class="device-group">
                                    <circle r="18" class="device-node" />
                                    <!-- Simple Mobile Icon Shape -->
                                    <rect x="-5" y="-8" width="10" height="16" rx="1" fill="#1e40af" />
                                    <text x="0" y="35" text-anchor="middle" font-family="Inter" font-size="10" fill="#64748b">Phone-S9</text>
                                </g>

                                <!-- Small Satellite Nodes (Decorations) -->
                                <circle cx="250" cy="150" r="4" fill="#cbd5e1" />
                                <circle cx="450" cy="280" r="4" fill="#cbd5e1" />
                            </svg>
                            
                            <!-- Overlay Label -->
                            <div class="absolute bottom-4 left-4 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg px-3 py-2 text-xs shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-purple-500 animate-pulse"></div>
                                    <span class="text-gray-600 font-medium">Quantum Entanglement Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Information Cards -->
                <div class="space-y-6">
                    
                    <!-- Card 1: Devices Active -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Devices Active</div>
                            <i class="fas fa-network-wired text-blue-800 opacity-50"></i>
                        </div>
                        <div class="text-3xl font-bold text-blue-900 counter" data-target="128">0</div>
                        <div class="text-xs text-green-600 mt-1 flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            Quantum-linked, stable
                        </div>
                    </div>

                    <!-- Card 2: Quantum Synchronization -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Quantum Synchronization</div>
                            <i class="fas fa-sync text-purple-500 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="93">0</div>
                            <span class="text-lg text-gray-400 font-medium">%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: 0%; background: linear-gradient(90deg, #d946ef, #8b5cf6);" data-width="93%"></div>
                        </div>
                    </div>

                    <!-- Card 3: Network Efficiency Score -->
                    <div class="metric-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-gray-500 text-sm font-medium">Network Efficiency Score</div>
                            <i class="fas fa-chart-bar text-blue-800 opacity-50"></i>
                        </div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-3xl font-bold text-blue-900 counter" data-target="96">0</div>
                            <span class="text-lg text-gray-400 font-medium">/ 100</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Top 1% of networks
                        </div>
                        <div class="progress-bar-bg mt-2">
                            <div class="progress-bar-fill" style="width: 0%;" data-width="96%"></div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button class="w-full bg-blue-900 hover:bg-blue-800 text-white font-medium py-3 px-4 rounded-xl transition duration-200 shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2 group">
                        <span>Recalibrate Network</span>
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