<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy Architecture ULTRA HD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root {
            --box-border: #000000;
            --box-bg: #ffffff;
            --text-color: #000000;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            background-color: #f0f0f0; /* 背景灰一点，突出中间的图 */
            margin: 0; 
            padding: 40px;
            display: flex; 
            flex-direction: column; 
            align-items: center;
            min-height: 100vh;
        }

        /* --- 顶部按钮 --- */
        .controls-bar {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-download {
            background: #ff4757; /* 红色按钮醒目一点 */
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s;
        }
        .btn-download:hover { transform: scale(1.05); }

        /* --- 图表容器 --- */
        #hub-container {
            position: relative;
            width: 1600px; /* 基础宽度 */
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            grid-template-rows: auto auto auto;
            gap: 60px;
            padding: 50px;
            background-color: #ffffff;
            margin-top: 60px; /* 让出顶部空间 */
            /* 确保截图时背景是白的 */
        }

        h1 {
            grid-column: 1 / -1;
            text-align: center;
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        /* SVG 线条 */
        #svg-layer {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 0;
        }
        .connection-line { stroke: #000; stroke-width: 3px; fill: none; }

        /* 方框 */
        .box {
            background: white;
            border: 4px solid black; /* 边框更粗 */
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            z-index: 2; 
            box-shadow: 15px 15px 0px rgba(0,0,0,0.15); 
            height: 100%; 
        }

        .box-title {
            font-weight: 900;
            font-size: 2rem; /* 字体加大 */
            margin-bottom: 25px;
            border-bottom: 3px solid #eee;
            width: 100%;
            padding-bottom: 15px;
            text-transform: uppercase;
        }

        .tech-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            width: 100%;
            margin-bottom: 20px;
            flex-grow: 1; 
            align-content: center;
        }

        .tech-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100px;
        }

        .tech-item i {
            font-size: 3.5rem; /* 图标加大 */
            margin-bottom: 10px;
            color: #333; /* 默认深色，下面会有特定色 */
        }
        
        .tech-item span {
            font-size: 1.2rem;
            font-weight: 800; /* 字体加粗 */
            color: #000;
            line-height: 1.2;
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            border-top: 2px dashed #ccc;
            padding-top: 15px;
            width: 100%;
        }
        
        .feature-item { 
            color: #333; 
            font-weight: 800;
            font-size: 1rem;
            padding: 5px 12px;
            background: #eee;
            border-radius: 6px;
        }

        /* 布局位置 */
        #box-client   { grid-column: 1; grid-row: 2; }
        #box-security { grid-column: 3; grid-row: 2; }
        #box-users    { grid-column: 1; grid-row: 3; align-self: center;}
        
        #box-core { 
            grid-column: 2; 
            grid-row: 3; 
            border-width: 6px;
            transform: scale(1.05); 
            padding: 40px;
            z-index: 3;
        }
        
        #box-quantum  { grid-column: 3; grid-row: 3; align-self: center;}
        #box-infra    { grid-column: 2; grid-row: 4; }

    </style>
</head>
<body>

    <div class="controls-bar">
        <button class="btn-download" onclick="downloadImage()">
            <i class="fa-solid fa-download"></i> Download ULTRA HD (4K)
        </button>
    </div>

    <div id="hub-container">
        
        <h1>StudyBuddy V2: System Architecture</h1>

        <svg id="svg-layer">
            <line x1="16.6%" y1="36%" x2="50%" y2="58%" class="connection-line" /> <line x1="83.3%" y1="36%" x2="50%" y2="58%" class="connection-line" /> <line x1="16.6%" y1="58%" x2="50%" y2="58%" class="connection-line" /> <line x1="83.3%" y1="58%" x2="50%" y2="58%" class="connection-line" /> <line x1="50%" y1="83%" x2="50%" y2="58%" class="connection-line" />   </svg>

        <div class="box" id="box-client">
            <div class="box-title">Web App Interface</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-brands fa-react" style="color:#61DAFB;"></i><span>React.js</span></div>
                <div class="tech-item"><i class="fa-brands fa-js" style="color:#F7DF1E;"></i><span>Next.js</span></div>
                <div class="tech-item"><i class="fa-brands fa-html5" style="color:#E34F26;"></i><span>HTML5</span></div>
                <div class="tech-item"><i class="fa-brands fa-css3-alt" style="color:#264de4;"></i><span>CSS3</span></div>
            </div>
            <div class="features">
                <div class="feature-item">Dashboard UI</div>
                <div class="feature-item">HttpOnly Cookies</div>
                <div class="feature-item">Browser Notifs</div>
            </div>
        </div>

        <div class="box" id="box-security">
            <div class="box-title">Security Framework</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-brands fa-google" style="color:#DB4437;"></i><span>OAuth 2.0</span></div>
                <div class="tech-item"><i class="fa-solid fa-key" style="color:#F4B400;"></i><span>OTP</span></div>
                <div class="tech-item"><i class="fa-brands fa-php" style="color:#777BB4;"></i><span>Sessions</span></div>
                <div class="tech-item"><i class="fa-solid fa-lock" style="color:#333;"></i><span>SSL/TLS</span></div>
            </div>
            <div class="features">
                <div class="feature-item">Google Login</div>
                <div class="feature-item">Email Verify</div>
                <div class="feature-item">Session Mgmt</div>
            </div>
        </div>

        <div class="box" id="box-core">
            <div class="box-title">StudyBuddy Core<br>(LAMP Hub)</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-brands fa-php" style="color:#777BB4;"></i><span>PHP 8</span></div>
                <div class="tech-item"><i class="fa-solid fa-server" style="color:#d33333;"></i><span>Apache</span></div>
                <div class="tech-item"><i class="fa-solid fa-network-wired" style="color:#333;"></i><span>REST API</span></div>
            </div>
            <p style="font-weight: 800; color:#555; margin-top:0; font-size:1.3rem;">Central Orchestration</p>
        </div>

        <div class="box" id="box-users">
            <div class="box-title">Users & Inputs</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-brands fa-bluetooth-b" style="color:#0082FC;"></i><span>Web BLE</span></div>
                <div class="tech-item"><i class="fa-solid fa-microphone" style="color:#333;"></i><span>Audio API</span></div>
                <div class="tech-item"><i class="fa-solid fa-file-pdf" style="color:#e11d48;"></i><span>File API</span></div>
            </div>
            <div class="features">
                <div class="feature-item">Student Onboarding</div>
                <div class="feature-item">HRV Stream</div>
                <div class="feature-item">Voice Uploads</div>
            </div>
        </div>

        <div class="box" id="box-quantum">
            <div class="box-title">Quantum Engines</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-solid fa-brain" style="color:#4285F4;"></i><span>Gemini AI</span></div>
                <div class="tech-item"><i class="fa-brands fa-python" style="color:#3776AB;"></i><span>Python</span></div>
                <div class="tech-item"><i class="fa-solid fa-atom" style="color:#61dafb;"></i><span>Qiskit</span></div>
                <div class="tech-item"><i class="fa-solid fa-microchip" style="color:#9333ea;"></i><span>Ocean</span></div>
                <div class="tech-item"><i class="fa-brands fa-ethereum" style="color:#3C3C3D;"></i><span>Solidity</span></div>
            </div>
            <div class="features">
                <div class="feature-item">AI Tutor</div>
                <div class="feature-item">Bio-Signal</div>
                <div class="feature-item">Governance</div>
            </div>
        </div>

        <div class="box" id="box-infra">
            <div class="box-title">Backend & Infra</div>
            <div class="tech-container">
                <div class="tech-item"><i class="fa-solid fa-database" style="color:#00758F;"></i><span>MySQL</span></div>
                <div class="tech-item"><i class="fa-brands fa-envira" style="color:#47A248;"></i><span>MongoDB</span></div>
                <div class="tech-item"><i class="fa-brands fa-linux" style="color:#333;"></i><span>Ubuntu</span></div>
                <div class="tech-item"><i class="fa-solid fa-cloud" style="color:#FF9900;"></i><span>AWS S3</span></div>
                <div class="tech-item"><i class="fa-solid fa-cubes" style="color:#8247E5;"></i><span>Polygon</span></div>
            </div>
            <div class="features">
                <div class="feature-item">Persistence</div>
                <div class="feature-item">Quantum Access</div>
                <div class="feature-item">Blockchain</div>
            </div>
        </div>
    </div>

    <script>
        function downloadImage() {
            const element = document.getElementById('hub-container');
            const btn = document.querySelector('.btn-download');
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating 4K...';
            
            html2canvas(element, {
                scale: 4, // 强制4倍分辨率，这会非常大
                backgroundColor: "#ffffff",
                useCORS: true,
                logging: false
            }).then(canvas => {
                let link = document.createElement('a');
                link.download = 'StudyBuddy_Architecture_UltraHD.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Done!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-solid fa-download"></i> Download ULTRA HD (4K)';
                }, 2000);
            });
        }
    </script>
</body>
</html>