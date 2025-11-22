<?php
// FILE: petugas/process_pindah_area.php
// REVISI: Fix Ikon Kendaraan (Motor/Mobil) di Visual Map

session_start();
include '../config.php'; 

// 1. Proteksi Akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'petugas' && $_SESSION['role'] !== 'admin')) {
    header('Location: login_petugas.php');
    exit;
}

$nama_petugas = $_SESSION['nama'] ?? 'Petugas';
$role_label   = ucfirst($_SESSION['role']);
$notification = null;

// --- LOGIKA DATA MAP UNTUK MODAL ---
$areas = [];
$sql_area = "SELECT * FROM area_parkir ORDER BY id ASC";
$res_area = $conn->query($sql_area);
if ($res_area) {
    while($row = $res_area->fetch_assoc()) $areas[] = $row;
}

// [PERBAIKAN QUERY] Tambahkan JOIN ke tabel kendaraan untuk ambil 'jenis'
$sql_slots = "SELECT s.*, l.plat_nomor, k.jenis AS jenis_kendaraan
              FROM slot_parkir s
              LEFT JOIN log_parkir l ON s.id = l.slot_id AND l.status = 'masuk'
              LEFT JOIN kendaraan k ON l.plat_nomor = k.plat_nomor
              ORDER BY s.area_id ASC, s.grid_row ASC, s.grid_col ASC";

$res_slots = $conn->query($sql_slots);
$slots_by_area = [];
if ($res_slots) {
    while($row = $res_slots->fetch_assoc()) {
        $slots_by_area[$row['area_id']][] = $row;
    }
}
// -----------------------------------------------------------

// =================================================================
// LOGIKA A: PROSES PEMINDAHAN (POST)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id     = (int) ($_POST['log_id'] ?? 0);
    $user_id    = (int) ($_POST['user_id'] ?? 0);
    $plat_nomor = $_POST['plat_nomor'] ?? '';
    $slot_baru  = trim($_POST['kode_slot_baru'] ?? '');
    
    if ($log_id <= 0 || empty($slot_baru)) {
        $notification = ['type' => 'error', 'text' => "Data tidak valid atau Slot belum dipilih."];
    } else {
        $conn->begin_transaction();
        try {
            // 1. Ambil Data Slot Lama
            $q_old = $conn->prepare("SELECT slot_id, kode_area FROM log_parkir WHERE id = ?");
            $q_old->bind_param('i', $log_id);
            $q_old->execute();
            $res_old = $q_old->get_result()->fetch_assoc();
            $slot_id_lama = $res_old['slot_id'];
            $kode_slot_lama = $res_old['kode_area'];
            $q_old->close();
            
            // 2. Cek Slot Baru
            $q_new = $conn->prepare("SELECT id, area_id, status FROM slot_parkir WHERE kode_slot = ?");
            $q_new->bind_param('s', $slot_baru);
            $q_new->execute();
            $res_new = $q_new->get_result()->fetch_assoc();
            $q_new->close();

            if (!$res_new) throw new Exception("Slot tujuan tidak ditemukan.");
            if ($res_new['status'] !== 'tersedia') throw new Exception("Slot $slot_baru sudah terisi!");
            
            $slot_id_baru = $res_new['id'];
            $area_id_baru = $res_new['area_id'];

            // Update Log
            $up_log = $conn->prepare("UPDATE log_parkir SET area_id = ?, slot_id = ?, kode_area = ? WHERE id = ?");
            $up_log->bind_param('iisi', $area_id_baru, $slot_id_baru, $slot_baru, $log_id);
            $up_log->execute();
            $up_log->close();

            // Update Slot Lama & Baru
            if ($slot_id_lama) $conn->query("UPDATE slot_parkir SET status = 'tersedia' WHERE id = $slot_id_lama");
            $conn->query("UPDATE slot_parkir SET status = 'terisi' WHERE id = $slot_id_baru");

            // Notifikasi User
            if ($user_id > 0) {
                $msg = "Kendaraan Anda ($plat_nomor) dipindahkan petugas dari $kode_slot_lama ke $slot_baru.";
                $notif = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, type) VALUES (?, ?, 0, 'pindah_area')");
                $notif->bind_param('is', $user_id, $msg);
                $notif->execute();
            }

            $conn->commit();
            $_SESSION['notif_petugas'] = ['type' => 'success', 'text' => "Sukses pindah ke $slot_baru"];
            header("Location: data_parkir.php"); 
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $notification = ['type' => 'error', 'text' => "Gagal: " . $e->getMessage()];
        }
    }
}

// =================================================================
// LOGIKA B: AMBIL DATA (GET)
// =================================================================
$log_id_get = (int) ($_GET['id'] ?? 0);
$data_parkir = null;

if ($log_id_get > 0) {
    $sql_detail = "SELECT lp.id, lp.user_id, lp.plat_nomor, lp.kode_area, 
                          k.jenis, k.warna, u.nama, ap.nama_area
                   FROM log_parkir lp
                   LEFT JOIN kendaraan k ON lp.plat_nomor = k.plat_nomor
                   LEFT JOIN users u ON lp.user_id = u.id
                   LEFT JOIN area_parkir ap ON lp.area_id = ap.id
                   WHERE lp.id = ?";
    $stmt = $conn->prepare($sql_detail);
    $stmt->bind_param('i', $log_id_get);
    $stmt->execute();
    $data_parkir = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindah Area</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="dashboard_petugas.css"> 
    <link rel="stylesheet" href="visual_map.css">
    
    <style>
        /* Style Form */
        .form-wrapper { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 24px; padding: 24px; box-shadow: var(--shadow-lg); border: 1px solid var(--gray-200); }
        .form-header-icon { width: 64px; height: 64px; background: #FFF7ED; color: #F97316; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 16px; }
        .detail-box { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 16px; padding: 16px; margin-bottom: 24px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--gray-200); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-size: 0.85rem; color: var(--gray-500); font-weight: 500; }
        .detail-value { font-size: 0.9rem; font-weight: 700; color: var(--secondary); text-align: right; }
        .font-mono { font-family: 'SF Mono', monospace; letter-spacing: 0.5px; }
        
        /* Input Trigger */
        .input-trigger { width: 100%; padding: 14px 16px; text-align: left; background: var(--white); border: 2px solid var(--gray-200); border-radius: 12px; color: var(--gray-500); font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: var(--transition); margin-bottom: 24px; }
        .input-trigger:hover { border-color: var(--primary); background: var(--gray-50); }
        .input-trigger.filled { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        
        .btn-group { display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; }
        .btn-cancel { text-align: center; padding: 14px; border-radius: 12px; border: 1px solid var(--gray-300); color: var(--gray-600); text-decoration: none; font-weight: 700; font-size: 0.9rem; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(232, 93, 59, 0.25); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(232, 93, 59, 0.35); }

        /* Bottom Sheet */
        .bottom-sheet-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 999; opacity:0; visibility:hidden; transition:0.3s; backdrop-filter: blur(4px); }
        .bottom-sheet-overlay.active { opacity:1; visibility:visible; }
        .bottom-sheet { position: fixed; left:0; right:0; bottom:0; background:#fff; border-radius:24px 24px 0 0; box-shadow:0 -10px 40px rgba(0,0,0,0.2); z-index:1000; transform:translateY(100%); transition:transform 0.4s cubic-bezier(0.16,1,0.3,1); max-height:85vh; display:flex; flex-direction:column; }
        .bottom-sheet.active { transform:translateY(0); }
        .sheet-header { padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
        .sheet-content { padding:20px; overflow-y:auto; flex:1; background:#f8fafc; }
        .sheet-content .parking-grid { transform: scale(0.95); transform-origin: top center; margin-top:0; }
        .area-grid-container { display:none; }
        .area-grid-container.active { display:block; animation:fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity:0;transform:translateY(10px);} to {opacity:1;transform:translateY(0);} }
    </style>
</head>
<body>

    <div class="header-right" style="display:none;"></div>
    
    <main class="main-content" style="padding-top: 40px;">
        <div class="dashboard-content">
            
            <?php if ($data_parkir): ?>
                <div class="form-wrapper">
                    <div class="form-header-icon"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                    <h2 style="text-align:center; margin-bottom:6px; color:var(--secondary); font-size: 1.25rem;">Pindahkan Kendaraan</h2>
                    <p style="text-align:center; color:var(--gray-500); margin-bottom:24px; font-size:0.85rem;">Pilih lokasi parkir baru melalui peta</p>

                    <?php if ($notification): ?>
                        <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:12px; margin-bottom:20px; font-size:0.9rem; display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-circle-exclamation"></i> <?= $notification['text'] ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="pindahForm">
                        <input type="hidden" name="log_id" value="<?= $data_parkir['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $data_parkir['user_id'] ?>">
                        <input type="hidden" name="plat_nomor" value="<?= $data_parkir['plat_nomor'] ?>">
                        <input type="hidden" name="kode_slot_baru" id="kode_slot_baru">

                        <div class="detail-box">
                            <div class="detail-row">
                                <span class="detail-label">Plat Nomor</span>
                                <span class="detail-value font-mono" style="font-size:1.1rem;"><?= htmlspecialchars($data_parkir['plat_nomor']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Pemilik</span>
                                <span class="detail-value"><?= htmlspecialchars($data_parkir['nama'] ?? 'Tamu') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Kendaraan</span>
                                <span class="detail-value"><?= htmlspecialchars(ucfirst($data_parkir['jenis']) . ' - ' . $data_parkir['warna']) ?></span>
                            </div>
                            <div class="detail-row" style="border-top: 1px dashed var(--primary); margin-top: 8px; padding-top: 12px;">
                                <span class="detail-label" style="color:var(--primary);">Lokasi Lama</span>
                                <span class="detail-value" style="color:var(--primary);"><?= htmlspecialchars($data_parkir['kode_area']) ?></span>
                            </div>
                        </div>

                        <div style="margin-bottom: 10px; font-weight: 600; font-size: 0.9rem; color: var(--gray-600);">Pilih Lokasi Baru</div>
                        
                        <button type="button" class="input-trigger" id="btnTriggerSlot" onclick="openSlotSheet()">
                            <span id="slotLabelText">-- Pilih Lewat Peta --</span>
                            <i class="fa-solid fa-map-location-dot" style="font-size: 1.2rem;"></i>
                        </button>

                        <div class="btn-group">
                            <a href="data_parkir.php" class="btn-cancel">Batal</a>
                            <button type="submit" class="btn-submit" onclick="return validateForm()">Simpan <i class="fa-solid fa-check"></i></button>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div style="text-align:center; padding:60px;">
                    <h3>Data Tidak Ditemukan</h3>
                    <a href="data_parkir.php" class="btn-cancel" style="display:inline-block; margin-top:20px;">Kembali</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div class="bottom-sheet-overlay" id="sheetOverlay" onclick="closeSlotSheet()"></div>
    <div class="bottom-sheet" id="slotBottomSheet">
        <div class="sheet-header">
            <h3 style="font-size:1.1rem; font-weight:700; color:var(--secondary);">Pilih Slot Tujuan</h3>
            <button onclick="closeSlotSheet()" style="background:none; border:none; font-size:1.5rem; color:var(--gray-400);"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding: 10px 20px 0;">
            <div class="area-tabs">
                <?php foreach($areas as $index => $area): ?>
                <button type="button" class="area-tab <?= $index === 0 ? 'active' : '' ?>" 
                        onclick="switchSheetTab('<?= $area['id'] ?>', this)">
                    <?= htmlspecialchars($area['nama_area']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sheet-content">
            <?php foreach($areas as $index => $area): 
                $current_slots = $slots_by_area[$area['id']] ?? [];
            ?>
            <div class="area-grid-container <?= $index === 0 ? 'active' : '' ?>" id="grid-area-<?= $area['id'] ?>">
                <div class="parking-grid" style="--rows: 6; --cols: 5;">
                    <?php foreach($current_slots as $slot): 
                        $status = ($slot['status'] === 'terisi') ? 'occupied' : (($slot['status'] === 'rusak') ? 'maintenance' : 'available');
                        
                        // [PERBAIKAN IKON]: Cek jenis kendaraan
                        $icon = 'fa-check';
                        if ($status === 'occupied') {
                            $jenis = strtolower($slot['jenis_kendaraan'] ?? '');
                            $icon = ($jenis == 'motor') ? 'fa-motorcycle' : 'fa-car';
                        } elseif ($status === 'maintenance') {
                            $icon = 'fa-triangle-exclamation';
                        }
                        
                        $isCurrent = ($slot['kode_slot'] === $data_parkir['kode_area']);
                        $isClickable = ($status === 'available');
                        $cursor = $isClickable ? "cursor: pointer;" : "cursor: not-allowed; opacity: 0.6;";
                        $onClick = $isClickable ? "selectSlot('{$slot['kode_slot']}')" : "";
                    ?>
                    <div class="parking-slot <?= $status ?>" 
                         style="grid-row: <?= $slot['grid_row'] ?>; grid-column: <?= $slot['grid_col'] ?>; <?= $cursor ?>"
                         onclick="<?= $onClick ?>">
                        <div class="slot-header">
                            <span class="slot-code"><?= $slot['kode_slot'] ?></span>
                        </div>
                        <div class="slot-body">
                            <i class="fa-solid <?= $icon ?>"></i>
                            <?php if($status === 'occupied'): ?>
                                <span class="visible-plat" style="font-size:0.7rem;"><?= htmlspecialchars($slot['plat_nomor']) ?></span>
                            <?php endif; ?>
                            <?php if($isCurrent): ?><small style="color:red; font-weight:bold; margin-top:4px;">LAMA</small><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="road-marking" style="grid-row: 2; grid-column: 1 / -1;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function openSlotSheet() {
            document.getElementById('sheetOverlay').classList.add('active');
            document.getElementById('slotBottomSheet').classList.add('active');
        }
        function closeSlotSheet() {
            document.getElementById('sheetOverlay').classList.remove('active');
            document.getElementById('slotBottomSheet').classList.remove('active');
        }
        function switchSheetTab(areaId, btnElement) {
            document.querySelectorAll('.area-grid-container').forEach(el => el.classList.remove('active'));
            document.getElementById('grid-area-' + areaId).classList.add('active');
            document.querySelectorAll('.sheet-header .area-tab, .bottom-sheet .area-tab').forEach(el => el.classList.remove('active'));
            btnElement.classList.add('active');
        }
        function selectSlot(kodeSlot) {
            document.getElementById('kode_slot_baru').value = kodeSlot;
            const btn = document.getElementById('btnTriggerSlot');
            document.getElementById('slotLabelText').innerText = "Tujuan: " + kodeSlot;
            btn.classList.add('filled');
            closeSlotSheet();
        }
        function validateForm() {
            const slot = document.getElementById('kode_slot_baru').value;
            if(!slot) {
                Swal.fire('Peringatan', 'Silakan pilih slot tujuan terlebih dahulu lewat peta.', 'warning');
                openSlotSheet();
                return false;
            }
            return true;
        }
    </script>

</body>
</html>