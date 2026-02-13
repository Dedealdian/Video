<?php
session_start();
$dataFile = 'database_voucher.json';
$admin_pass = "warkop2026"; 

if (!file_exists($dataFile)) file_put_contents($dataFile, json_encode([]));
$vouchers = json_decode(file_get_contents($dataFile), true) ?: [];

// --- LOGIKA RESET / FINISH ---
if (isset($_GET['finish']) || isset($_GET['reset'])) {
    session_unset();
    session_destroy();
    header("hidden: index.php");
    exit;
}

// --- LOGIKA ADMIN ---
if (isset($_POST['login_admin'])) {
    if ($_POST['pass'] === $admin_pass) $_SESSION['admin_auth'] = true;
}
if (isset($_POST['generate']) && isset($_SESSION['admin_auth'])) {
    $hadiah_list = ['5.000', '10.000', '20.000', '50.000', '100.000', 'Zonk', 'Zonk'];
    for ($i = 0; $i < 5; $i++) {
        $vouchers[] = [
            "kode" => substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6),
            "hadiah" => $hadiah_list[array_rand($hadiah_list)],
            "status" => "active"
        ];
    }
    file_put_contents($dataFile, json_encode($vouchers));
    header("Hidden: index.php?menu=admin"); exit;
}

// --- LOGIKA LOGIN VOUCHER (HANGUS SAAT MASUK) ---
$msg = "";
if (isset($_POST['submit_voucher'])) {
    $input = strtoupper(trim($_POST['kode']));
    $found = false;
    foreach ($vouchers as &$v) {
        if ($v['kode'] === $input) {
            $found = true;
            if ($v['status'] === 'used') {
                $msg = "Maaf, Voucher sudah Kadaluarsa!";
            } else {
                // SET STATUS HANGUS DI DATABASE DETIK INI JUGA
                $v['status'] = 'used';
                file_put_contents($dataFile, json_encode($vouchers));
                
                $_SESSION['v_logged'] = true;
                $_SESSION['v_kode'] = $v['kode'];
                $_SESSION['v_hadiah'] = $v['hadiah'];
                header("Hiddden: index.php"); exit;
            }
        }
    }
    if (!$found) $msg = "KODE TIDAK DITEMUKAN!";
}

// --- CEK JIKA USER REFRESH (KICK OUT) ---
// Jika sudah di dalam game, tapi voucher sudah 'used', sebenarnya aman karena session masih ada.
// Namun karena permintaan Anda "jika refresh kembali ke login", maka tombol tutup/finish adalah kuncinya.
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Warkop69 - Cinematic Premium</title>
    <style>
        * { box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: #000; font-family: 'Segoe UI', sans-serif; overflow: hidden; }

        .main-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('pohon.png') no-repeat center center; background-size: cover; z-index: 1; }

        /* 5 PILIHAN ANGPAO (GERAKAN OMBAK) */
        .angpao-container { position: fixed; bottom: 120px; width: 100%; display: flex; justify-content: center; gap: 10px; z-index: 100; }
        .angpao-click { 
            width: 60px; height: 100px; background: url('angpao_item.png') no-repeat center center; background-size: 100% 100%;
            cursor: pointer; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.5)); 
            animation: wave 2.5s infinite ease-in-out;
        }
        
        /* DELAY UNTUK EFEK OMBAK */
        .angpao-click:nth-child(1) { animation-delay: 0s; }
        .angpao-click:nth-child(2) { animation-delay: 0.2s; }
        .angpao-click:nth-child(3) { animation-delay: 0.4s; }
        .angpao-click:nth-child(4) { animation-delay: 0.6s; }
        .angpao-click:nth-child(5) { animation-delay: 0.8s; }

        @keyframes wave { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-30px); } }

        /* THEATER LAYER */
        #theater { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; }
        .env-wrapper { position: relative; width: 140px; height: 200px; perspective: 1000px; }
        .env-body { position: absolute; bottom: 0; width: 100%; height: 150px; background: url('angpao_item.png') no-repeat bottom center; background-size: 100% auto; border-radius: 0 0 10px 10px; z-index: 10; border: 1px solid #ffd700; }
        .env-lid { position: absolute; top: 20px; width: 100%; height: 40px; background: #b71c1c; border: 2px solid #ffd700; border-radius: 10px 10px 0 0; z-index: 11; transform-origin: top; transition: 0.6s; }

        /* ISI SURAT KOTAK (GAMBAR KOIN) */
        .paper { 
            position: absolute; bottom: 15px; left: 5%; width: 90%; height: 180px; 
            background: url('desain_kertas.png') no-repeat center center; background-size: 100% 100%;
            z-index: 5; transition: 2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; align-items: center; padding-top: 15px; color: #fff; text-align: center;
        }

        /* ZOOM KERTAS JADI KOTAK */
        .zoom-paper { 
            position: fixed !important; width: 320px !important; height: 320px !important;
            top: 50%; left: 50%; transform: translate(-50%, -50%) scale(1.1) !important; 
            z-index: 2000 !important; box-shadow: 0 0 50px rgba(0,0,0,1); 
            padding: 15px 20px; border: 4px solid #ffd700; border-radius: 15px;
        }

        .paper .line-1 { font-size: 10px; font-weight: bold; text-shadow: 1px 1px 2px #000; }
        .paper .line-2 { font-size: 16px; font-weight: 900; color: #ffd700; margin: 5px 0; text-shadow: 2px 2px 4px #000; }
        .zoom-paper .line-1 { font-size: 15px; margin-top: 5px; }
        .zoom-paper .line-2 { font-size: 34px; margin: 10px 0; }
        .zoom-paper .line-3 { font-size: 13px; }

        /* LOGIN BOX HITAM 3 BARIS */
        .login-footer { position: fixed; bottom: 15px; width: 100%; display: flex; justify-content: center; z-index: 500; }
        .black-bar-3rows { 
            background: rgba(0,0,0,0.92); padding: 15px 20px; border-radius: 20px; 
            width: 90%; max-width: 360px; text-align: center; border: 2px solid #333;
            display: flex; flex-direction: column; gap: 8px;
        }
        .logo-img { max-width: 110px; height: auto; align-self: center; }
        input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #1a1a1a; color: #fff; text-align: center; font-weight: bold; font-size: 16px; }
        .btn-open { background: #d32f2f; color: #fff; padding: 12px; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; width: 100%; }
        .claim-info { color: #888; font-size: 10px; font-weight: bold; margin-top: 2px; }

        .btn-claim { display: block; background: #27ae60; color: white; padding: 12px; text-decoration: none; border-radius: 10px; font-weight: bold; margin-top: 25px; border: 2px solid #fff; width: 90%; align-self: center; }
        .close-x { position: absolute; top: 10px; right: 15px; color: red; font-weight: bold; cursor: pointer; font-size: 24px; z-index: 2001; }

        /* ANIMASI */
        .open-lid { transform: rotateX(160deg); }
        .pull-paper { transform: translateY(-165px); }
    </style>
</head>
<body>

<audio id="bgMusic" loop src="https://assets.mixkit.co/music/preview/mixkit-winter-lofi-94.mp3"></audio>
<audio id="sndSreet" src="https://assets.mixkit.co/active_storage/sfx/1110/1110-preview.mp3"></audio>
<audio id="sndCling" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"></audio>

<div class="main-bg"></div>

<?php if (isset($_GET['menu']) && $_GET['menu'] == 'admin'): ?>
    <div class="login-footer">
        <div class="black-bar-3rows">
            <h3 style="color:#fff; margin:0;">ADMIN</h3>
            <?php if (!isset($_SESSION['admin_auth'])): ?>
                <form method="POST" style="width:100%"><input type="password" name="pass" placeholder="PIN"><button name="login_admin" class="btn-open" style="margin-top:10px;">LOGIN</button></form>
            <?php else: ?>
                <form method="POST" style="width:100%"><button name="generate" class="btn-open" style="background:green">BUAT 5 VOUCHER</button></form>
                <div style="height:100px; overflow-y:auto; font-size:10px; margin-top:10px; color:#fff;"><table border="1" width="100%"><?php foreach(array_reverse($vouchers) as $v): ?><tr><td><?=$v['kode']?></td><td><?=$v['hadiah']?></td><td><?=$v['status']?></td></tr><?php endforeach; ?></table></div>
                <a href="index.php?reset=1" style="color:cyan; font-size:12px; margin-top:10px;">Ke Halaman Utama</a>
            <?php endif; ?>
        </div>
    </div>

<?php elseif (isset($_SESSION['v_logged'])): ?>
    <!-- 5 KARTU ANGPAO OMBAK -->
    <div class="angpao-container" id="areaKlik">
        <div class="angpao-click" onclick="startCinematic(this)"></div>
        <div class="angpao-click" onclick="startCinematic(this)"></div>
        <div class="angpao-click" onclick="startCinematic(this)"></div>
        <div class="angpao-click" onclick="startCinematic(this)"></div>
        <div class="angpao-click" onclick="startCinematic(this)"></div>
    </div>

    <div id="theater">
        <div class="env-wrapper">
            <div class="env-lid" id="lid"></div>
            <div class="env-body"></div>
            <div class="paper" id="paper">
                <div class="close-x" id="btnX" style="display:none;" onclick="tutup()">X</div>
                <div id="isiSurat"></div>
            </div>
        </div>
    </div>

    <script>
        document.body.addEventListener('click', () => { document.getElementById('bgMusic').play(); }, {once:true});
        function startCinematic(el) {
            document.getElementById('sndCling').play();
            document.getElementById('areaKlik').style.display = 'none';
            document.getElementById('theater').style.display = 'flex';
            const h = "<?php echo $_SESSION['v_hadiah']; ?>";
            const isi = document.getElementById('isiSurat');
            
            if(h === 'Zonk') {
                isi.innerHTML = `<div class="line-2" style="margin-top:25px;">Coba lagi saja</div><button style="margin-top:40px; padding:12px; background:#d32f2f; color:#fff; border:none; border-radius:8px; font-weight:bold; width:85%;" onclick="tutup()">TUTUP</button>`;
            } else {
                isi.innerHTML = `<div class="line-1">Kamu Mendapatkan</div><div class="line-2">Rp ${h}</div><div class="line-3">Dari Warkop69</div><a href="https://t.me/Warkop69offic" target="_blank" class="claim-btn">CLAIM SEKARANG</a>`;
            }

            setTimeout(() => {
                document.getElementById('lid').classList.add('open-lid');
                setTimeout(() => {
                    document.getElementById('sndSreet').play();
                    document.getElementById('paper').classList.add('pull-paper');
                    setTimeout(() => {
                        document.getElementById('paper').classList.add('zoom-paper');
                        document.getElementById('btnX').style.display = 'block';
                        document.getElementById('bgMusic').pause();
                    }, 2000); 
                }, 700);
            }, 500);
        }
        function tutup() { window.location.href = "?finish=1"; }
    </script>

<?php else: ?>
    <!-- HALAMAN LOGIN 3 BARIS -->
    <div class="login-footer">
        <div class="black-bar-3rows">
            <div style="display:flex; justify-content:center;">
                <?php if(file_exists('logo.gif')): ?>
                    <img src="logo.gif" class="logo-img">
                <?php else: ?>
                    <span style="color:#ffd700; font-weight:bold; font-size:22px;">WARKOP69</span>
                <?php endif; ?>
            </div>
            
            <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                <input type="text" name="kode" placeholder="KODE VOUCHER" required autocomplete="off">
                <button name="submit_voucher" class="btn-open" onclick="document.getElementById('bgMusic').play()">BUKA ANGPAO SEKARANG</button>
            </form>

            <div class="claim-info">claim dari live chat</div>
            <?php if($msg) echo "<p style='color:yellow; font-size:12px; margin:0;'>$msg</p>"; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>