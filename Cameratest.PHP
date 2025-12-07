<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>True AI-AR Shapes</title>

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@latest/dist/coco-ssd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <style>
        body {
            margin: 0;
            overflow: hidden;
            font-family: Arial, sans-serif;
            background: #000;
        }
        #overlay {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            z-index: 20; color: white; text-align: center;
        }
        #overlay.hidden { display: none; }
        #startBtn {
            background: #4CAF50; border: none; color: white;
            padding: 20px 40px; font-size: 18px; border-radius: 8px;
            cursor: pointer; margin-top: 20px;
        }
        #status {
            position: absolute; top: 10px; left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white; padding: 15px; border-radius: 8px;
            z-index: 10; font-size: 16px;
        }
        
        /* The video feed from the camera (bottom layer) */
        #video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            object-fit: cover;
            z-index: 1;
        }
        
        /* The Three.js canvas (top layer) */
        #ar-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2; /* This canvas must be on top of the video */
        }
    </style>
</head>
<body>
    <div id="overlay">
        <h2>🤖 True AR App</h2>
        <p>This will load a large AI model to find objects and place 3D shapes on them.</p>
        <button id="startBtn">Start AR</button>
    </div>

    <div id="status" style="display: none;">Loading AI Model...</div>

    <video id="video" playsinline autoplay muted></video>
    
    <canvas id="ar-canvas"></canvas>

    <script>
        const startBtn = document.getElementById('startBtn');
        const overlay = document.getElementById('overlay');
        const video = document.getElementById('video');
        const canvas = document.getElementById('ar-canvas');
        const statusDiv = document.getElementById('status');

        let model = null;
        let scene, camera, renderer;
        let shapes = {}; // To store our 3D shapes

        startBtn.addEventListener('click', startExperience);

        async function startExperience() {
            overlay.classList.add('hidden');
            statusDiv.style.display = 'block';

            // 1. Load the AI Model
            try {
                model = await cocoSsd.load();
                statusDiv.textContent = 'AI Loaded. Starting Camera...';
            } catch (e) {
                console.error(e);
                statusDiv.textContent = 'Error loading AI model.';
                return;
            }

            // 2. Start the Camera
            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                video.srcObject = stream;
                await video.play(); // Wait for the video to start
                statusDiv.textContent = 'Camera On. Starting 3D Scene...';
                
                // 3. Setup the 3D Scene
                setupThreeJS();
                
                // 4. Start the detection loop
                statusDiv.textContent = 'AR is Running!';
                detectObjects();

            } catch (error) {
                alert('Camera access is required. Please grant permission and reload.');
            }
        }

        function setupThreeJS() {
            // Setup scene, camera, and renderer
            scene = new THREE.Scene();
            
            // This camera needs to match the video feed.
            // We use the video's dimensions to set the aspect ratio.
            const fov = 75;
            const aspect = video.videoWidth / video.videoHeight;
            camera = new THREE.PerspectiveCamera(fov, aspect, 0.1, 1000);
            
            // We position the camera at the origin (0,0,0)
            // It will look down the Z-axis.
            camera.position.z = 5;

            renderer = new THREE.WebGLRenderer({
                canvas: canvas,
                alpha: true // This makes the canvas background transparent
            });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);

            // Add some light so we can see the 3D objects
            const light = new THREE.DirectionalLight(0xffffff, 1.0);
            light.position.set(1, 1, 5);
            scene.add(light);
            scene.add(new THREE.AmbientLight(0xffffff, 0.5));
            
            window.addEventListener('resize', onWindowResize);
        }
        
        function onWindowResize() {
            // Update camera and renderer on window resize
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        async function detectObjects() {
            // 1. Get AI predictions
            const predictions = await model.detect(video);
            
            // --- This is the AR logic ---
            // 2. Clear old shapes that are no longer detected
            clearUnusedShapes(predictions);

            // 3. Update or create shapes for new detections
            for (let p of predictions) {
                let shape = getOrCreateShape(p.class, p.bbox.toString()); // Use bbox as a unique ID
                
                // Convert 2D pixel coordinates to 3D world coordinates
                const worldPos = convert2DTo3D(p.bbox[0], p.bbox[1], p.bbox[2], p.bbox[3]);
                
                shape.position.set(worldPos.x, worldPos.y, worldPos.z);

                // Make shape bigger based on bounding box size
                const scale = (p.bbox[2] + p.bbox[3]) / 1000; // Average width/height
                shape.scale.set(scale, scale, scale);
                
                shape.rotation.y += 0.01; // Make it spin
            }
            
            // 4. Render the 3D scene
            renderer.render(scene, camera);
            
            // 5. Loop
            requestAnimationFrame(detectObjects);
        }
        
        function getOrCreateShape(type, id) {
            // If we already have a shape for this ID, return it
            if (shapes[id]) {
                return shapes[id];
            }
            
            // Otherwise, create a new shape based on the object type
            let geometry, material;
            
            if (type === 'chair' || type === 'table') {
                // Show a BLUE CIRCLE for a chair or table
                geometry = new THREE.TorusGeometry(0.5, 0.1, 16, 100);
                material = new THREE.MeshStandardMaterial({ color: 0x3498db });
            } else if (type === 'person' || type === 'cell phone') {
                // Show a RED SQUARE for a person or phone
                geometry = new THREE.BoxGeometry(1, 1, 1);
                material = new THREE.MeshStandardMaterial({ color: 0xe74c3c });
            } else {
                // Default: A small WHITE SPHERE
                geometry = new THREE.SphereGeometry(0.2, 16, 16);
                material = new THREE.MeshStandardMaterial({ color: 0xffffff });
            }
            
            const shape = new THREE.Mesh(geometry, material);
            shapes[id] = shape; // Save it in our tracking object
            scene.add(shape); // Add it to the 3D world
            return shape;
        }

        function clearUnusedShapes(predictions) {
            const activeIds = predictions.map(p => p.bbox.toString());
            for (let id in shapes) {
                if (!activeIds.includes(id)) {
                    // This object is no longer detected
                    scene.remove(shapes[id]); // Remove from 3D world
                    shapes[id].geometry.dispose();
                    shapes[id].material.dispose();
                    delete shapes[id]; // Remove from our tracking object
                }
            }
        }

        // --- This is the most important function! ---
        // It converts 2D screen pixels into 3D world coordinates.
        function convert2DTo3D(x, y, width, height) {
            // 1. Find the center of the bounding box
            let centerX = x + width / 2;
            let centerY = y + height / 2;
            
            // 2. Convert pixel coordinates to "Normalized Device Coordinates" (NDC)
            // (where x and y range from -1 to +1)
            let ndcX = (centerX / window.innerWidth) * 2 - 1;
            let ndcY = -(centerY / window.innerHeight) * 2 + 1;

            // 3. Create a 3D vector and "unproject" it
            // This casts a ray from the camera's 2D viewport into the 3D world
            let vector = new THREE.Vector3(ndcX, ndcY, 0.5); // z=0.5 is halfway into the scene
            vector.unproject(camera);

            // 4. Adjust the Z position
            // We'll place the object at a fixed distance from the camera for simplicity
            let dir = vector.sub(camera.position).normalize();
            let distance = 3; // Place it 3 units in front of the camera
            let pos = camera.position.clone().add(dir.multiplyScalar(distance));
            
            return pos;
        }

    </script>
</body>
</html>