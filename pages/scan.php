<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Scan RFID & Kehadiran</h2>
    </div>
    <div class="card-body">
        <div class="scan-container">
            
            <!-- Kamera & Scan Area -->
            <div>
                <div class="webcam-wrapper" id="webcam-container">
                    <video id="webcam-video" autoplay playsinline></video>
                    <div class="scan-overlay" id="scan-overlay"></div>
                </div>
                <div class="scan-status">
                    <p id="status-text" style="font-size: 1.1rem; font-weight: 500;">
                        Menunggu scan kartu RFID...
                    </p>
                </div>
            </div>

            <!-- Hasil Scan -->
            <div>
                <div class="student-card" id="result-card">
                    <div class="photo-compare" id="photo-compare" style="display: none;">
                        <div class="photo-compare-item">
                            <div class="photo-compare-label">Foto Terdaftar</div>
                            <img src="assets/img/placeholder.png" alt="Foto Terdaftar" class="photo-compare-img" id="result-photo-master">
                        </div>
                        <div class="photo-compare-item">
                            <div class="photo-compare-label">Foto Real-time</div>
                            <img src="assets/img/placeholder.png" alt="Foto Real-time" class="photo-compare-img" id="result-photo-live">
                        </div>
                    </div>
                    
                    <!-- SVG Placeholder saat belum ada scan -->
                    <svg id="result-placeholder" width="100" height="100" fill="none" stroke="var(--border)" viewBox="0 0 24 24" style="margin-bottom: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>

                    <h3 id="result-nama">Belum Ada Data</h3>
                    <div class="class-badge" style="display: none;" id="result-class"></div>
                </div>
            </div>
            
        </div>
        
        <!-- Input untuk Keyboard Emulation RFID -->
        <!-- Autofocus dan tetap fokus jika klik sembarangan -->
        <input type="text" id="rfid-input" autocomplete="off" autofocus>
        
        <canvas id="canvas" style="display:none;"></canvas>
    </div>
</div>

<script>
    // Memastikan input RFID selalu fokus
    document.addEventListener('click', function() {
        document.getElementById('rfid-input').focus();
    });
</script>
