<?php
require_once __DIR__ . '/../config.php';

$stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE slug = 'shipping-policy' AND is_published = 1 LIMIT 1");
$stmt->execute();
$page = $stmt->fetch();

// SEO & Branding Optimization
$page_title = $page['meta_title'] ?? $page['title'] ?? 'Kebijakan Pengiriman Dorve House - Gratis Ongkir & Bisa COD';
$page_description = $page['meta_description'] ?? 'Informasi lengkap pengiriman Dorve House: Layanan COD (Bayar di Tempat), Gratis Ongkir seluruh Indonesia, JNE/J&T, dan garansi paket aman. Belanja fashion kekinian tanpa ragu.';
$page_keywords = 'gratis ongkir, cod, bayar ditempat, pengiriman cepat, jne, jnt, sicepat, dorve house, toko baju online, ekspedisi indonesia';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .legal-container {
        max-width: 900px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .legal-header {
        margin-bottom: 60px;
    }

    .legal-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        margin-bottom: 16px;
    }

    .legal-updated {
        color: var(--grey);
        font-size: 14px;
    }

    .legal-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin: 40px 0 20px;
    }

    .legal-content h3 {
        font-size: 20px;
        margin: 30px 0 16px;
        font-weight: 600;
    }

    .legal-content p {
        line-height: 1.8;
        color: var(--grey);
        margin-bottom: 20px;
    }

    .legal-content ul {
        margin: 20px 0;
        padding-left: 24px;
        line-height: 1.8;
        color: var(--grey);
    }

    .legal-content li {
        margin-bottom: 12px;
    }

    .shipping-table {
        width: 100%;
        border-collapse: collapse;
        margin: 30px 0;
    }

    .shipping-table th,
    .shipping-table td {
        padding: 16px;
        text-align: left;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .shipping-table th {
        background: var(--cream);
        font-weight: 600;
    }
</style>

<div class="legal-container">
    <div class="legal-header">
        <h1>Kebijakan Pengiriman</h1>
        <p class="legal-updated">Terakhir diperbarui: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="legal-content">
        <h2>1. Metode & Tarif Pengiriman</h2>
        <p>
            Dorve House berkomitmen memberikan pengalaman belanja terbaik. Kami bermitra dengan ekspedisi terpercaya untuk memastikan outfit favorit Anda sampai dengan aman dan tepat waktu. Berikut adalah opsi pengiriman yang tersedia untuk seluruh wilayah Indonesia:
        </p>

        <table class="shipping-table">
            <thead>
                <tr>
                    <th>Metode Pengiriman</th>
                    <th>Estimasi Tiba</th>
                    <th>Biaya Layanan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Reguler (JNE / J&T)</strong></td>
                    <td>3-5 hari kerja (Jabodetabek)<br>5-7 hari kerja (Luar Pulau)</td>
                    <td>Rp 25.000<br>GRATIS untuk order > Rp 500.000</td>
                </tr>
                <tr>
                    <td><strong>Express / Next Day</strong></td>
                    <td>1-2 hari kerja (Kota Besar)</td>
                    <td>Rp 50.000</td>
                </tr>
                <tr>
                    <td><strong>Gratis Ongkir</strong></td>
                    <td>3-5 hari kerja (Jabodetabek)<br>5-7 hari kerja (Luar Pulau)</td>
                    <td>GRATIS (Min. belanja Rp 500.000)</td>
                </tr>
            </tbody>
        </table>

        <h2>2. Waktu Pemrosesan Pesanan</h2>
        <p>
            Tim operasional Dorve House bekerja cepat untuk menyiapkan pesanan Anda. Pesanan yang masuk akan diproses dalam waktu 1-2 hari kerja (Senin-Jumat). Pesanan yang masuk pada hari libur nasional atau akhir pekan akan diproses pada hari kerja berikutnya.
        </p>
        <ul>
            <li><strong>Konfirmasi Pesanan:</strong> Otomatis via email/WhatsApp sesaat setelah pembayaran.</li>
            <li><strong>Pengemasan (Packing):</strong> 1-2 hari kerja dengan standar quality control Dorve House.</li>
            <li><strong>Input Resi:</strong> Nomor resi akan dikirimkan segera setelah paket diserahkan ke kurir.</li>
        </ul>

        <h2>3. Jangkauan Pengiriman</h2>
        <p>
            Dorve House melayani pengiriman ke seluruh pelosok Indonesia, dari Sabang sampai Merauke. Kami memastikan setiap pelanggan dapat menikmati koleksi terbaru kami dimanapun Anda berada.
        </p>

        <h3>3.1 Jabodetabek & Jawa Barat</h3>
        <ul>
            <li>DKI Jakarta (Seluruh Wilayah)</li>
            <li>Tangerang, Tangerang Selatan & Banten</li>
            <li>Bekasi, Depok, Bogor</li>
            <li>Bandung & Sekitarnya</li>
        </ul>

        <h3>3.2 Kota Besar & Luar Pulau</h3>
        <ul>
            <li>Surabaya, Semarang, Yogyakarta, Malang</li>
            <li>Medan, Palembang, Padang, Lampung</li>
            <li>Makassar, Denpasar (Bali), Lombok</li>
            <li>Serta seluruh ibukota provinsi dan kabupaten lainnya</li>
        </ul>

        <h2>4. Pelacakan Pesanan (Tracking)</h2>
        <p>
            Kami memberikan transparansi penuh atas perjalanan paket Anda. Setelah paket dikirim, Anda akan mendapatkan:
        </p>
        <ul>
            <li>Email konfirmasi pengiriman berisi Nomor Resi (AWB).</li>
            <li>Link untuk melacak status paket secara <em>real-time</em>.</li>
            <li>Estimasi tanggal paket akan diterima di tangan Anda.</li>
        </ul>
        <p>
            Anda juga dapat mengecek status pengiriman kapan saja melalui halaman "Pesanan Saya" di akun Dorve House Anda.
        </p>

        <h2>5. Penerimaan Paket</h2>
        <h3>5.1 Proses Pengantaran</h3>
        <p>
            Mitra kurir kami akan melakukan 2-3 kali percobaan pengiriman ke alamat tujuan. Jika pengiriman gagal:
        </p>
        <ul>
            <li>Kurir akan meninggalkan notifikasi atau menghubungi via WhatsApp.</li>
            <li>Paket mungkin akan disimpan di kantor cabang ekspedisi terdekat untuk diambil mandiri.</li>
            <li>Jika dalam 7 hari paket tidak diambil/diterima, paket akan dikembalikan (retur) ke gudang Dorve House.</li>
        </ul>

        <h3>5.2 Saat Paket Diterima</h3>
        <ul>
            <li>Mohon periksa kondisi kemasan luar paket sebelum menandatangani bukti terima.</li>
            <li><strong>Wajib melakukan Video Unboxing</strong> tanpa jeda saat membuka paket untuk klaim garansi.</li>
            <li>Pastikan ada perwakilan orang di rumah untuk menerima paket jika Anda sedang bepergian.</li>
        </ul>

        <h2>6. Batasan Pengiriman</h2>
        <p>
            Demi kelancaran, saat ini Dorve House menerapkan beberapa kebijakan lokasi:
        </p>
        <ul>
            <li>Kami menyarankan penggunaan alamat rumah atau kantor yang jelas (hindari penggunaan P.O. Box).</li>
            <li>Pengiriman saat ini hanya mencakup wilayah hukum Negara Kesatuan Republik Indonesia.</li>
        </ul>

        <h2>7. Hari Raya & Event Spesial</h2>
        <p>
            Pada periode sibuk seperti Bulan Ramadan, Idul Fitri, Natal, Tahun Baru, atau Event Belanja Nasional (12.12 / Payday Sale), estimasi pengiriman mungkin mengalami sedikit keterlambatan 2-3 hari kerja karena lonjakan volume di pihak ekspedisi. Tim Dorve House akan selalu mengupdate info terkini kepada Anda.
        </p>

        <h2>8. Kendala Pengiriman</h2>
        <h3>8.1 Keterlambatan</h3>
        <p>
            Jika paket belum tiba melewati batas estimasi waktu:
        </p>
        <ul>
            <li>Cek update posisi terakhir menggunakan nomor resi.</li>
            <li>Hubungi Customer Care Dorve House agar kami dapat membantu <em>follow-up</em> ke pihak ekspedisi.</li>
        </ul>

        <h3>8.2 Paket Hilang atau Rusak</h3>
        <p>
            Kepuasan Anda adalah prioritas Dorve House. Jika paket dinyatakan hilang oleh ekspedisi atau diterima dalam kondisi rusak:
        </p>
        <ul>
            <li>Segera dokumentasikan kondisi paket (Foto & Video).</li>
            <li>Hubungi kami dalam waktu 1x24 jam setelah status paket <em>Delivered</em>.</li>
            <li>Kami akan memproses penggantian barang baru atau pengembalian dana (refund) sesuai kebijakan yang berlaku.</li>
        </ul>

        <h2>9. Hubungi Kami</h2>
        <p>
            Butuh bantuan terkait pengiriman pesanan Dorve House Anda? Layanan pelanggan kami siap membantu:
        </p>
        <ul>
            <li>Email: support@dorve.co</li>
            <li>Telepon: +62 21 1234 5678</li>
            <li>WhatsApp: +62 812 3456 7890 (Fast Response)</li>
            <li>Jam Operasional: Senin - Jumat, 09:00 - 18:00 WIB</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>