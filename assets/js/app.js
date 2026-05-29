// Terminal scan webcam capture + face verification //

document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('canvas');
    const rfidInput = document.getElementById('rfid-input');
    const statusText = document.getElementById('status-text');
    const scanOverlay = document.getElementById('scan-overlay');

    const photoCompare = document.getElementById('photo-compare');
    const resultPhotoMaster = document.getElementById('result-photo-master');
    const resultPhotoLive = document.getElementById('result-photo-live');
    const resultPlaceholder = document.getElementById('result-placeholder');
    const resultNama = document.getElementById('result-nama');
    const resultClass = document.getElementById('result-class');

    const successAudio = new Audio('assets/audio/success.mp3');
    const errorAudio = new Audio('assets/audio/error.mp3');

    let isProcessing = false;
    const CHECKIN_START = '';

    // Semakin kecil semakin ketat. Diubah ke 0.45 agar lebih presisi membedakan wajah yang mirip //
    const FACE_MATCH_THRESHOLD = 0.45;

    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';

    let modelsPromise = null;

    async function ensureFaceModels() {
        if (typeof faceapi === 'undefined') {
            throw new Error('Library face-api belum termuat. Periksa koneksi internet (CDN) lalu muat ulang halaman.');
        }
        if (!modelsPromise) {
            modelsPromise = (async () => {
                await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            })();
        }
        await modelsPromise;
    }

    // Preload model di latar belakang agar scan pertama tidak lama
    ensureFaceModels().catch(console.error);

    async function startWebcam() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } }
            });
            video.srcObject = stream;
        } catch (err) {
            console.error('Error accessing webcam: ', err);
            statusText.innerText = 'Kamera tidak dapat diakses.';
            statusText.style.color = 'var(--error)';
        }
    }

    startWebcam();

    // Fitur Live Tracking Wajah (Kotak-kotak dari awal)
    video.addEventListener('play', () => {
        const liveOverlay = document.getElementById('live-overlay');
        if (!liveOverlay) return;

        // Samakan ukuran canvas dengan ukuran asli video
        const displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(liveOverlay, displaySize);

        // Jalankan deteksi berulang-ulang setiap 100ms
        setInterval(async () => {
            // Jangan gambar kalau lagi proses verifikasi biar ga berat
            if (isProcessing) {
                liveOverlay.getContext('2d').clearRect(0, 0, liveOverlay.width, liveOverlay.height);
                return;
            }

            try {
                // Deteksi wajah secara live
                const tinyOpts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 });
                const detections = await faceapi.detectAllFaces(video, tinyOpts);
                
                // Sesuaikan ukuran kotak deteksi dengan ukuran layar
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                // Hapus gambar lama, lalu gambar kotak baru
                liveOverlay.getContext('2d').clearRect(0, 0, liveOverlay.width, liveOverlay.height);
                resizedDetections.forEach(det => {
                    // Balikkan koordinat X karena video di-mirror
                    const mirroredBox = new faceapi.Rect(
                        displaySize.width - det.box.x - det.box.width,
                        det.box.y,
                        det.box.width,
                        det.box.height
                    );
                    
                    const drawBox = new faceapi.draw.DrawBox(mirroredBox, { 
                        label: `Wajah: ${det.score.toFixed(2)}`, 
                        boxColor: 'rgba(59, 130, 246, 0.8)' // Warna biru keren
                    });
                    drawBox.draw(liveOverlay);
                });
            } catch (err) {
                // Abaikan error kalau model belum kelar dimuat
            }
        }, 150);
    });

    function playNotificationSound(type) {
        const sound = type === 'success' ? successAudio : errorAudio;
        sound.currentTime = 0;
        sound.play().catch(() => {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = type === 'success' ? 1000 : 220;
            gainNode.gain.value = 0.15;
            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + (type === 'success' ? 0.12 : 0.2));
        });
    }

    function getTodayCheckoutHour() {
        const day = new Date().getDay();
        return day === 5 ? '14:00' : '15:00';
    }

    function updateWaitingText() {
        statusText.innerText = 'Menunggu scan kartu...';
        statusText.style.color = 'var(--text-main)';
    }

    updateWaitingText();

    if (rfidInput) {
        rfidInput.focus();
    }

    function resetResultCard() {
        if (photoCompare) photoCompare.style.display = 'none';
        if (resultPlaceholder) resultPlaceholder.style.display = 'block';
        if (resultNama) resultNama.innerText = 'Belum Ada Data';
        if (resultClass) resultClass.style.display = 'none';
    }

    async function processScan(uid) {
        let committedOk = false;
        isProcessing = true;
        scanOverlay.classList.add('active');

        try {
            if (!video.videoWidth || !video.videoHeight) {
                throw new Error('Kamera belum siap. Tunggu webcam menyala lalu scan lagi.');
            }

            statusText.innerText = 'Memuat model verifikasi wajah…';
            statusText.style.color = 'var(--text-main)';
            await ensureFaceModels();

            statusText.innerText = 'Memproses…';
            const context = canvas.getContext('2d');
            
            // OPTIMASI PERFORMA: Resize resolusi tangkapan kamera agar proses AI jauh lebih cepat
            // Resolusi diturunkan ke 480 agar deteksi lebih ringan
            const scale = Math.min(1, 480 / video.videoWidth);
            canvas.width = video.videoWidth * scale;
            canvas.height = video.videoHeight * scale;
            
            // Mirror canvas agar hasil foto sama persis seperti yang dilihat di layar (webcam)
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            // Kembalikan ke normal agar teks AI tidak ikut terbalik
            context.setTransform(1, 0, 0, 1, 0, 0);
            const photoData = canvas.toDataURL('image/jpeg', 0.85);

            const preFd = new FormData();
            preFd.append('uid', uid);
            const preRes = await fetch('pages/scan_preflight.php', { method: 'POST', body: preFd });
            const preData = await preRes.json();

            if (preData.status !== 'ok') {
                throw new Error(preData.message || 'Kartu tidak valid.');
            }

            const masterOpts = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.25 });
            const liveOpts = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.35 });
            const tinyOpts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.2 });
            
            // OPTIMASI PERFORMA: Jalankan deteksi wajah secara paralel (bersamaan) untuk foto master dan webcam
            const [masterFace, liveFace] = await Promise.all([
                (async () => {
                    const masterImg = await faceapi.fetchImage(preData.foto_master_url);
                    let face = await faceapi.detectSingleFace(masterImg, tinyOpts).withFaceLandmarks().withFaceDescriptor();
                    if (!face) {
                        face = await faceapi.detectSingleFace(masterImg, masterOpts).withFaceLandmarks().withFaceDescriptor();
                    }
                    return face;
                })(),
                (async () => {
                    let face = await faceapi.detectSingleFace(canvas, tinyOpts).withFaceLandmarks().withFaceDescriptor();
                    if (!face) {
                        face = await faceapi.detectSingleFace(canvas, liveOpts).withFaceLandmarks().withFaceDescriptor();
                    }
                    return face;
                })()
            ]);

            if (!masterFace) {
                throw new Error('Wajah tidak terdeteksi pada Foto Terdaftar.');
            }
            if (!liveFace) {
                throw new Error('Wajah tidak terdeteksi di webcam. Hadapkan wajah ke kamera.');
            }

            const dist = faceapi.euclideanDistance(masterFace.descriptor, liveFace.descriptor);

            // Tambahkan visualisasi AI (Kotak dan Nilai) pada hasil kamera
            const boxColor = dist <= FACE_MATCH_THRESHOLD ? 'rgba(16, 185, 129, 1)' : 'rgba(239, 68, 68, 1)';
            const labelText = dist <= FACE_MATCH_THRESHOLD ? `Wajah Cocok (${dist.toFixed(2)})` : `Wajah Berbeda (${dist.toFixed(2)})`;
            const drawBox = new faceapi.draw.DrawBox(liveFace.detection.box, { label: labelText, boxColor: boxColor });
            drawBox.draw(canvas);

            const photoDataWithBox = canvas.toDataURL('image/jpeg', 0.85);

            if (dist > FACE_MATCH_THRESHOLD) {
                if (resultPlaceholder) resultPlaceholder.style.display = 'none';
                if (photoCompare) photoCompare.style.display = 'grid';
                if (resultPhotoMaster) resultPhotoMaster.src = preData.foto_master_url;
                if (resultPhotoLive) resultPhotoLive.src = photoDataWithBox;
                if (resultNama) resultNama.innerText = 'Tidak Cocok';
                if (resultClass) resultClass.style.display = 'none';
                
                const err = new Error('Verifikasi wajah gagal. Pastikan pemilik kartu yang menghadap kamera.');
                err.isMatchError = true;
                throw err;
            }

            const formData = new FormData();
            formData.append('uid', uid);
            formData.append('photo', photoDataWithBox);

            const response = await fetch('pages/scan_process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                committedOk = true;
                statusText.innerText = data.message;
                statusText.style.color = 'var(--success)';

                if (resultPlaceholder) resultPlaceholder.style.display = 'none';
                if (photoCompare) photoCompare.style.display = 'grid';
                if (resultPhotoMaster) {
                    resultPhotoMaster.src = data.siswa && data.siswa.foto_master_url
                        ? data.siswa.foto_master_url
                        : preData.foto_master_url;
                }
                if (resultPhotoLive) resultPhotoLive.src = photoDataWithBox;
                resultNama.innerText = data.siswa.nama_lengkap;
                resultClass.innerText = `${data.siswa.nama_kelas} - ${data.attendance_status}`;
                resultClass.style.display = 'inline-block';

                playNotificationSound('success');
            } else {
                throw new Error(data.message || 'Scan gagal.');
            }
        } catch (err) {
            console.error(err);
            playNotificationSound('error');
            statusText.innerText = err.message || 'Verifikasi wajah gagal.';
            statusText.style.color = 'var(--error)';
            if (!err.isMatchError) {
                resetResultCard();
                if (resultNama) resultNama.innerText = 'Verifikasi Gagal';
            }
        } finally {
            scanOverlay.classList.remove('active');
            setTimeout(() => {
                isProcessing = false;
                if (!committedOk) updateWaitingText();
                rfidInput.focus();
            }, 3500);
        }
    }

    if (rfidInput) {
        rfidInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const uid = this.value.trim();
                this.value = '';
                if (uid && !isProcessing) {
                    void processScan(uid);
                }
            }
        });
    }
});
