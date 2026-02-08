<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #preview {
            display: none;
            max-width: 300px;
            margin-top: 20px;
            border: 2px solid #ccc;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-active {
            background-color: #10B981;
        }

        .status-inactive {
            background-color: #EF4444;
        }
    </style>
</head>

<body class="font-sans antialiased">
    @include('components.navbar')

    <div class="min-h-screen bg-gray-100">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold mb-6">Employee Dashboard</h2>

                        @if (session('error'))
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="mb-8">
                            <p class="text-gray-600 mb-4">
                                Welcome to the employee monitoring system. Click "Start Work" to begin screen capture.
                            </p>

                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Important Notice:</strong> By clicking "Start Work", you agree to
                                            share your screen for monitoring purposes. Screen capture will only occur
                                            while this browser tab is open and you have granted permission.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 mb-6">
                                <span class="status-indicator status-inactive" id="statusIndicator"></span>
                                <span class="text-gray-700" id="statusText">Not Active</span>
                            </div>

                            <div class="space-x-4">
                                <button id="startBtn"
                                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded transition duration-300">
                                    Start Work
                                </button>

                                <button id="stopBtn" disabled
                                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded opacity-50 cursor-not-allowed transition duration-300">
                                    Stop Work
                                </button>
                            </div>

                            <div id="previewContainer" class="mt-4">
                                <p class="text-sm text-gray-500">Screen Preview (Updates every 5 minutes):</p>
                                <img id="preview" alt="Screen preview">
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-lg mb-2">How it works:</h3>
                            <ol class="list-decimal pl-5 space-y-2 text-gray-600">
                                <li>Click "Start Work" button</li>
                                <li>Grant screen sharing permission when browser asks</li>
                                <li>System will capture screenshots every 5 minutes</li>
                                <li>Click "Stop Work" when you're done</li>
                                <li>Screen capture stops if you close this tab or revoke permission</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mediaStream = null;
        let captureInterval = null;
        let isActive = false;
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const preview = document.getElementById('preview');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function updateStatus() {
            if (isActive) {
                statusIndicator.className = 'status-indicator status-active';
                statusText.textContent = 'Active - Capturing screenshots every 5 minutes';
                startBtn.disabled = true;
                startBtn.classList.add('opacity-50', 'cursor-not-allowed');
                stopBtn.disabled = false;
                stopBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                statusIndicator.className = 'status-indicator status-inactive';
                statusText.textContent = 'Not Active';
                startBtn.disabled = false;
                startBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                stopBtn.disabled = true;
                stopBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        async function startScreenCapture() {
            try {
                console.log('Starting screen capture...');

                // Check if getDisplayMedia is available
                if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                    throw new Error('Screen sharing is not supported in this browser');
                }

                // Request screen capture permission with simpler options
                mediaStream = await navigator.mediaDevices.getDisplayMedia({
                    video: true, // Simplified options
                    audio: false,
                });

                console.log('Screen capture started successfully');

                // Create video element to capture frames
                const video = document.createElement('video');
                video.srcObject = mediaStream;

                // Wait for video to be ready
                video.onloadedmetadata = async () => {
                    await video.play();

                    isActive = true;
                    updateStatus();

                    // Show initial preview
                    captureAndUpload(video);
                    preview.style.display = 'block';

                    // 🔁 Random capture between 3–5 minutes
                    function scheduleNextCapture() {
                        const min = 3 * 60 * 1000; // 3 minutes
                        const max = 5 * 60 * 1000; // 5 minutes
                        const delay = Math.floor(Math.random() * (max - min + 1)) + min;

                        console.log(`Next capture in ${(delay / 60000).toFixed(2)} minutes`);

                        captureInterval = setTimeout(() => {
                            captureAndUpload(video);
                            scheduleNextCapture(); // schedule next random time
                        }, delay);
                    }

                    scheduleNextCapture(); // start random capturing

                    console.log('Random capture started');
                };


                // Handle when user stops sharing
                mediaStream.getVideoTracks()[0].onended = () => {
                    console.log('User stopped screen sharing');
                    stopScreenCapture();
                    alert('Screen sharing stopped by user.');
                };

            } catch (error) {
                console.error('Error starting screen capture:', error);

                // Show specific error messages
                if (error.name === 'NotAllowedError') {
                    alert('Permission denied. Please allow screen sharing when prompted.');
                } else if (error.name === 'NotFoundError') {
                    alert('No screen sharing source found.');
                } else if (error.name === 'NotReadableError') {
                    alert('Cannot access screen sharing source.');
                } else if (error.name === 'OverconstrainedError') {
                    alert('Screen sharing constraints cannot be satisfied.');
                } else if (error.name === 'TypeError') {
                    alert('Screen sharing is not supported in this browser.');
                } else {
                    alert('Failed to start screen capture: ' + error.message);
                }

                // Reset state
                isActive = false;
                updateStatus();
            }
        }

        function captureAndUpload(video) {
            try {
                // Check if video is ready
                if (video.videoWidth === 0 || video.videoHeight === 0) {
                    console.warn('Video not ready for capture');
                    return;
                }

                // Create canvas element
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                // Draw video frame to canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to base64 with lower quality for faster upload
                const imageData = canvas.toDataURL('image/jpeg', 0.7);

                // Update preview
                preview.src = imageData;

                // Upload to server
                uploadScreenshot(imageData);

                console.log('Screenshot captured and uploaded');

            } catch (error) {
                console.error('Error capturing screenshot:', error);
            }
        }

        function uploadScreenshot(imageData) {
            console.log('📤 Uploading screenshot...');

            // Convert base64 to blob
            const blob = dataURLtoBlob(imageData);

            // Create form data
            const formData = new FormData();
            formData.append('screenshot', blob, 'screenshot.jpg');

            console.log('Blob size:', blob.size, 'bytes');
            console.log('Blob type:', blob.type);

            // Upload
            fetch('/screenshot/store', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Upload response:', data);

                    if (data.success) {
                        console.log('✅ SUCCESS:', data.message);
                        if (data.debug) {
                            console.log('📁 Path:', data.debug.path);
                            console.log('📊 Size:', data.debug.file_size, 'bytes');
                            console.log('💾 Storage exists:', data.debug.storage_exists);
                            console.log('🌐 Public exists:', data.debug.public_exists);
                        }
                    } else {
                        console.error('❌ FAILED:', data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ NETWORK ERROR:', error);
                });
        }

        // Helper function to convert base64 to Blob (ensure this exists)
        function dataURLtoBlob(dataurl) {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], {
                type: mime
            });
        }

        function stopScreenCapture() {
            console.log('Stopping screen capture...');

            isActive = false;
            updateStatus();

            if (captureInterval) {
                clearInterval(captureInterval);
                captureInterval = null;
            }

            if (mediaStream) {
                mediaStream.getTracks().forEach(track => {
                    track.stop();
                });
                mediaStream = null;
            }

            preview.style.display = 'none';
            console.log('Screen capture stopped');
        }

        // Event listeners
        startBtn.addEventListener('click', startScreenCapture);
        stopBtn.addEventListener('click', stopScreenCapture);

        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            // Only log, don't stop capture
            if (document.hidden) {
                console.log('Page hidden, but screen capture continues...');
            } else {
                console.log('Page visible again');
            }
        });

        // Handle beforeunload to stop capture when leaving page
        window.addEventListener('beforeunload', () => {
            if (isActive) {
                stopScreenCapture();
            }
        });

        // Initialize
        updateStatus();

        // Test if screen capture is available
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            console.warn('Screen capture API not available');
            document.getElementById('startBtn').disabled = true;
            document.getElementById('startBtn').textContent = 'Screen Capture Not Supported';
            document.getElementById('startBtn').classList.add('opacity-50', 'cursor-not-allowed');

            const warningDiv = document.createElement('div');
            warningDiv.className = 'mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded';
            warningDiv.innerHTML = `
                <strong>Browser Compatibility Issue:</strong><br>
                Your browser does not support screen capture or you are not using HTTPS.<br>
                Please use:
                <ul class="list-disc ml-5 mt-2">
                    <li>Chrome 72+</li>
                    <li>Firefox 66+</li>
                    <li>Edge 79+</li>
                    <li>Safari 13+ (with limited support)</li>
                </ul>
                <strong>Note:</strong> HTTPS is required for screen capture in production.
            `;
            document.querySelector('.bg-white').appendChild(warningDiv);
        }
    </script>
</body>

</html>
