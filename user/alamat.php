<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');
$user_id = (int)$_SESSION['user_id'];
$error = '';
$edit_id = (int)($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $label = trim($_POST['label'] ?? 'Rumah');
        $nama = trim($_POST['nama_penerima'] ?? '');
        $telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $kota = trim($_POST['kota'] ?? '');
        $kode_pos = trim($_POST['kode_pos'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        if ($label === '' || $nama === '' || $telepon === '' || $alamat === '' || $kota === '') {
            $error = 'Label, nama penerima, nomor telepon, alamat, dan kota wajib diisi.';
        } elseif (!array_key_exists($kota, shipping_city_rates())) {
            $error = 'Kota tujuan tidak valid.';
        } else {
            mysqli_begin_transaction($conn);
            try {
                if ($is_default) {
                    $stmt = mysqli_prepare($conn, "UPDATE user_addresses SET is_default=0 WHERE user_id=?");
                    mysqli_stmt_bind_param($stmt, 'i', $user_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
                }
                if ($id > 0) {
                    $stmt = mysqli_prepare($conn, "UPDATE user_addresses SET label=?, nama_penerima=?, no_telepon=?, alamat=?, kota=?, kode_pos=?, is_default=? WHERE id=? AND user_id=?");
                    mysqli_stmt_bind_param($stmt, 'ssssssiii', $label,$nama,$telepon,$alamat,$kota,$kode_pos,$is_default,$id,$user_id);
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO user_addresses (user_id,label,nama_penerima,no_telepon,alamat,kota,kode_pos,is_default) VALUES (?,?,?,?,?,?,?,?)");
                    mysqli_stmt_bind_param($stmt, 'issssssi', $user_id,$label,$nama,$telepon,$alamat,$kota,$kode_pos,$is_default);
                }
                mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
                if (!$is_default) {
                    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM user_addresses WHERE user_id=? AND is_default=1");
                    mysqli_stmt_bind_param($stmt,'i',$user_id); mysqli_stmt_execute($stmt); $cnt=(int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0]; mysqli_stmt_close($stmt);
                    if ($cnt === 0) {
                        $stmt=mysqli_prepare($conn,"SELECT id FROM user_addresses WHERE user_id=? ORDER BY id DESC LIMIT 1"); mysqli_stmt_bind_param($stmt,'i',$user_id); mysqli_stmt_execute($stmt); $r=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
                        if ($r) { $stmt=mysqli_prepare($conn,"UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?"); mysqli_stmt_bind_param($stmt,'ii',$r['id'],$user_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
                    }
                }
                mysqli_commit($conn); flash('success','Alamat berhasil disimpan.'); redirect('alamat.php');
            } catch (Throwable $e) { mysqli_rollback($conn); $error='Alamat gagal disimpan: '.$e->getMessage(); }
        }
    } elseif ($action === 'delete') {
        $id=(int)($_POST['id']??0);
        $stmt=mysqli_prepare($conn,"DELETE FROM user_addresses WHERE id=? AND user_id=?"); mysqli_stmt_bind_param($stmt,'ii',$id,$user_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        flash('success','Alamat berhasil dihapus.'); redirect('alamat.php');
    } elseif ($action === 'default') {
        $id=(int)($_POST['id']??0);
        mysqli_begin_transaction($conn);
        try {
            $stmt=mysqli_prepare($conn,"UPDATE user_addresses SET is_default=0 WHERE user_id=?"); mysqli_stmt_bind_param($stmt,'i',$user_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            $stmt=mysqli_prepare($conn,"UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?"); mysqli_stmt_bind_param($stmt,'ii',$id,$user_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            mysqli_commit($conn); flash('success','Alamat utama diperbarui.'); redirect('alamat.php');
        } catch(Throwable $e){ mysqli_rollback($conn); $error=$e->getMessage(); }
    }
}

$edit = null;
if ($edit_id > 0) { $stmt=mysqli_prepare($conn,"SELECT * FROM user_addresses WHERE id=? AND user_id=? LIMIT 1"); mysqli_stmt_bind_param($stmt,'ii',$edit_id,$user_id); mysqli_stmt_execute($stmt); $edit=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt); }
$stmt=mysqli_prepare($conn,"SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC"); mysqli_stmt_bind_param($stmt,'i',$user_id); mysqli_stmt_execute($stmt); $addresses=mysqli_stmt_get_result($stmt);
$page_title='Alamat Saya'; $asset_prefix='../'; $home_link='../index.php'; include '../includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="kicker" style="color:#9fdcff">AKUN</div><h1>Alamat Saya</h1><p>Simpan beberapa alamat dan pilih alamat yang paling sesuai saat checkout.</p></div></section>
<section class="content"><div class="container">
<?php if($error): ?><div class="flash error"><?=e($error)?></div><?php endif; ?>
<?php if($flash=get_flash()): ?><div class="flash <?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif; ?>
<div class="checkout-grid">
<div class="panel"><h2><?= $edit ? 'Edit Alamat' : 'Tambah Alamat Baru'; ?></h2>
<form method="POST"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0); ?>">
<div class="form-grid">
<div class="form-group"><label>Label Alamat</label><input name="label" required placeholder="Rumah, Kantor, Orang Tua..." value="<?=e($edit['label']??'Rumah')?>"></div>
<div class="form-group"><label>Nama Penerima</label><input name="nama_penerima" required value="<?=e($edit['nama_penerima']??'')?>"></div>
<div class="form-group"><label>No. Telepon</label><input name="no_telepon" required value="<?=e($edit['no_telepon']??'')?>"></div>
<div class="form-group"><label>Kota/Kabupaten</label><select name="kota" required><option value="">Pilih kota</option><?php foreach(array_keys(shipping_city_rates()) as $city): ?><option value="<?=e($city)?>" <?= (($edit['kota']??'')===$city)?'selected':'' ?>><?=e($city)?></option><?php endforeach; ?></select></div>
<div class="form-group full"><label>Alamat Lengkap</label><textarea name="alamat" required><?=e($edit['alamat']??'')?></textarea></div>
<div class="form-group"><label>Kode Pos</label><input name="kode_pos" value="<?=e($edit['kode_pos']??'')?>"></div>
<div class="form-group" style="display:flex;align-items:center;gap:10px"><input type="checkbox" name="is_default" value="1" style="width:auto" <?=!empty($edit['is_default'])?'checked':''?>> <label style="margin:0">Jadikan alamat utama</label></div>
</div><button class="btn btn-primary" type="submit">Simpan Alamat</button><?php if($edit): ?> <a class="btn btn-outline" href="alamat.php">Batal</a><?php endif; ?></form></div>
<div class="panel"><h2>Alamat Tersimpan</h2><p class="text-muted">Kamu bisa menyimpan lebih dari 2 alamat.</p>
<?php if(mysqli_num_rows($addresses)===0): ?><div class="empty"><p>Belum ada alamat tersimpan.</p></div><?php else: while($a=mysqli_fetch_assoc($addresses)): ?>
<div style="border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:14px;<?=!empty($a['is_default'])?'box-shadow:0 0 0 2px #dceeff':''?>">
<div style="display:flex;justify-content:space-between;gap:12px;align-items:center"><strong><?=e($a['label'])?></strong><?php if($a['is_default']): ?><span class="status success">Utama</span><?php endif; ?></div>
<div style="margin-top:8px"><strong><?=e($a['nama_penerima'])?></strong> · <?=e($a['no_telepon'])?></div><div class="text-muted" style="margin-top:5px"><?=nl2br(e($a['alamat']))?><br><?=e($a['kota'])?> <?=e($a['kode_pos'])?></div>
<div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-outline btn-sm" href="alamat.php?edit=<?= (int)$a['id']?>">Edit</a><?php if(!$a['is_default']): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="default"><input type="hidden" name="id" value="<?= (int)$a['id']?>"><button class="btn btn-outline btn-sm" type="submit">Jadikan Utama</button></form><?php endif; ?><form method="POST" style="display:inline" onsubmit="return confirm('Hapus alamat ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id']?>"><button class="btn btn-outline btn-sm" type="submit">Hapus</button></form></div>
</div>
<?php endwhile; endif; ?></div></div></div></section>
<?php mysqli_stmt_close($stmt); include '../includes/footer.php'; ?>
