
</main>

<footer class="footer">
    <div class="container footer-grid">
        <div>
            <div class="brand footer-brand">
                <span class="brand-mark">RB</span>
                <span>
                    <strong>Ronggolawe</strong>
                    <small>Batik Tuban</small>
                </span>
            </div>
            <p>Produk batik khas Kabupaten Tuban dengan sentuhan tradisi dan tampilan modern.</p>
        </div>
        <div>
            <h4>Navigasi</h4>
            <a href="<?= $home_link ?? 'index.php'; ?>">Beranda</a>
            <a href="<?= $asset_prefix ?? ''; ?>bantuan.php">Pusat Bantuan</a>
            <?php if ($is_user): ?>
                <a href="<?= $asset_prefix ?? ''; ?>user/index.php">Katalog</a>
                <a href="<?= $asset_prefix ?? ''; ?>user/pesanan.php">Pesanan Saya</a>
            <?php endif; ?>
        </div>
        <div>
            <h4>Informasi</h4>
            <p>Jam layanan: 08.00–21.00 WIB</p>
            <p>Kabupaten Tuban, Jawa Timur</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?= date('Y'); ?> Ronggolawe Batik. Semua hak dilindungi.
    </div>
</footer>
</div>
<script>
(function () {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('mainNav');
    const backdrop = document.querySelector('.nav-backdrop');
    if (!toggle || !nav || !backdrop) return;

    function setMenu(open) {
        toggle.classList.toggle('is-open', open);
        nav.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }

    toggle.addEventListener('click', function () {
        setMenu(!nav.classList.contains('is-open'));
    });
    backdrop.addEventListener('click', function () { setMenu(false); });
    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () { setMenu(false); });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setMenu(false);
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 640) setMenu(false);
    });
})();
</script>

</body>
</html>
