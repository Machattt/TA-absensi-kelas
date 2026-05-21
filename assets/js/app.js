/**
 * Terminal scan: webcam capture + face verification (face-api.js) vs foto master,
 * lalu commit absensi ke scan_process.php.
 */
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

    /** Semakin kecil semakin ketat. Diubah ke 0.45 agar lebih presisi membedakan wajah yang mirip. */
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
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
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
            const tinyOpts = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.2 });
            
            const masterImg = await faceapi.fetchImage(preData.foto_master_url);

            let masterFace = await faceapi
                .detectSingleFace(masterImg, masterOpts)
                .withFaceLandmarks()
                .withFaceDescriptor();

            // Fallback ke TinyFaceDetector jika SsdMobilenetv1 gagal untuk foto master
            if (!masterFace) {
                masterFace = await faceapi
                    .detectSingleFace(masterImg, tinyOpts)
                    .withFaceLandmarks()
                    .withFaceDescriptor();
            }

            let liveFace = await faceapi
                .detectSingleFace(canvas, liveOpts)
                .withFaceLandmarks()
                .withFaceDescriptor();

            // Fallback ke TinyFaceDetector jika SsdMobilenetv1 gagal untuk webcam
            if (!liveFace) {
                liveFace = await faceapi
                    .detectSingleFace(canvas, tinyOpts)
                    .withFaceLandmarks()
                    .withFaceDescriptor();
            }

            if (!masterFace) {
                throw new Error('Wajah tidak terdeteksi pada Foto Terdaftar.');
            }
            if (!liveFace) {
                throw new Error('Wajah tidak terdeteksi di webcam. Hadapkan wajah ke kamera.');
            }

            const dist = faceapi.euclideanDistance(masterFace.descriptor, liveFace.descriptor);
            if (dist > FACE_MATCH_THRESHOLD) {
                throw new Error('Verifikasi wajah gagal. Pastikan pemilik kartu yang menghadap kamera.');
            }

            const formData = new FormData();
            formData.append('uid', uid);
            formData.append('photo', photoData);

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
                if (resultPhotoLive) resultPhotoLive.src = photoData;
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
            resetResultCard();
            if (resultNama) resultNama.innerText = 'Verifikasi Gagal';
        } finally {
            scanOverlay.classList.remove('active');
            setTimeout(() => {
                isProcessing = false;
                if (!committedOk) updateWaitingText();
                rfidInput.focus();
            }, 2200);
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
