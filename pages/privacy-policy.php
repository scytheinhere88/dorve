<?php
require_once __DIR__ . '/../config.php';

$stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE slug = 'privacy-policy' AND is_published = 1 LIMIT 1");
$stmt->execute();
$page = $stmt->fetch();

// SEO & Branding Optimization: Menekankan pada keamanan dan kepercayaan
$page_title = $page['meta_title'] ?? $page['title'] ?? 'Kebijakan Privasi Dorve House - Perlindungan Data & Keamanan Transaksi';
$page_description = $page['meta_description'] ?? 'Dorve House menjamin keamanan data pribadi dan transaksi Anda. Simak kebijakan privasi kami mengenai perlindungan konsumen, enkripsi data, dan penggunaan informasi pelanggan secara transparan.';
$page_keywords = 'kebijakan privasi, keamanan data, perlindungan konsumen, data pribadi, belanja aman, privasi pelanggan dorve house, toko baju terpercaya';
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
        text-align: justify; /* Menambah kesan rapi dan formal */
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
</style>

<div class="legal-container">
    <div class="legal-header">
        <h1>Kebijakan Privasi</h1>
        <p class="legal-updated">Terakhir diperbarui: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="legal-content">
        <h2>1. Pendahuluan</h2>
        <p>
            Selamat datang di <strong>Dorve House</strong>. Kami memahami bahwa privasi adalah hal yang sangat penting bagi Anda. Kepercayaan yang Anda berikan kepada kami merupakan prioritas utama dalam bisnis kami. Dokumen Kebijakan Privasi ini dibuat untuk menjelaskan secara transparan bagaimana Dorve House mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi Anda saat mengunjungi website kami atau melakukan transaksi pembelian produk fashion kami.
        </p>
        <p>
            Dengan mengakses situs web Dorve House, Anda menyetujui praktik data yang dijelaskan dalam kebijakan ini. Kami berkomitmen untuk mematuhi peraturan perlindungan data yang berlaku di Indonesia demi kenyamanan belanja Anda.
        </p>

        <h2>2. Informasi yang Kami Kumpulkan</h2>
        <p>
            Untuk memberikan layanan terbaik dan memproses pesanan Anda secara akurat, kami mengumpulkan beberapa jenis informasi:
        </p>

        <h3>2.1 Informasi Pribadi (Personal Data)</h3>
        <p>Data ini Anda berikan secara sukarela saat mendaftar akun, berlangganan newsletter, atau melakukan *checkout*:</p>
        <ul>
            <li><strong>Identitas:</strong> Nama lengkap, tanggal lahir (opsional), dan jenis kelamin.</li>
            <li><strong>Kontak:</strong> Alamat email aktif dan nomor telepon/WhatsApp (untuk konfirmasi pesanan).</li>
            <li><strong>Pengiriman:</strong> Alamat lengkap pengiriman (Jalan, Kecamatan, Kota, Kode Pos).</li>
            <li><strong>Transaksi:</strong> Riwayat pembelian produk Dorve House dan preferensi produk.</li>
        </ul>

        <h3>2.2 Informasi Teknis & Otomatis</h3>
        <p>Saat Anda menjelajahi koleksi kami, sistem kami secara otomatis merekam data teknis untuk meningkatkan kinerja website:</p>
        <ul>
            <li>Alamat Protokol Internet (IP Address).</li>
            <li>Tipe perangkat (Mobile/Desktop) dan browser yang digunakan.</li>
            <li>Halaman produk yang Anda lihat dan durasi kunjungan.</li>
            <li>Data *Cookies* dan preferensi sesi belanja.</li>
        </ul>

        <h2>3. Penggunaan Informasi Anda</h2>
        <p>Data yang kami kumpulkan digunakan semata-mata untuk meningkatkan pengalaman belanja Anda di Dorve House, meliputi:</p>
        <ul>
            <li><strong>Pemrosesan Pesanan:</strong> Memverifikasi pembayaran, mengemas barang, dan mengirimkan produk ke alamat Anda.</li>
            <li><strong>Layanan Pelanggan:</strong> Menjawab pertanyaan, menangani retur, atau menyelesaikan kendala pengiriman.</li>
            <li><strong>Personalisasi:</strong> Memberikan rekomendasi produk fashion yang sesuai dengan gaya dan minat Anda.</li>
            <li><strong>Keamanan:</strong> Mendeteksi dan mencegah potensi penipuan atau akses ilegal terhadap akun Anda.</li>
            <li><strong>Promosi (Opsional):</strong> Mengirimkan info *New Arrival*, diskon eksklusif, atau event spesial Dorve House (hanya jika Anda berlangganan).</li>
        </ul>

        <h2>4. Pembagian Informasi kepada Pihak Ketiga</h2>
        <p>
            <strong>Dorve House tidak akan pernah menjual data pribadi Anda kepada pihak manapun.</strong> Kami hanya membagikan data kepada mitra terpercaya yang membantu operasional kami, yaitu:
        </p>
        <ul>
            <li><strong>Jasa Ekspedisi:</strong> (Seperti JNE, J&T, SiCepat) Nama, nomor HP, dan alamat Anda diberikan untuk keperluan pengantaran paket.</li>
            <li><strong>Gerbang Pembayaran (Payment Gateway):</strong> Untuk memproses pembayaran aman (Transfer Bank, E-Wallet, Kartu Kredit).</li>
            <li><strong>Layanan IT:</strong> Mitra penyedia server dan pemeliharaan website untuk menjaga situs tetap online dan aman.</li>
        </ul>

        <h2>5. Keamanan & Perlindungan Data</h2>
        <p>
            Kami menerapkan langkah-langkah keamanan teknis yang ketat. Website Dorve House dilindungi oleh teknologi enkripsi <strong>Secure Socket Layer (SSL)</strong> untuk memastikan data yang dikirimkan antara browser Anda dan server kami tetap rahasia. Akses ke data pribadi Anda dibatasi hanya untuk karyawan Dorve House yang berkepentingan dalam memproses pesanan Anda.
        </p>

        <h2>6. Hak Kendali Anda</h2>
        <p>Sebagai pelanggan yang kami hargai, Anda memiliki hak penuh atas data Anda:</p>
        <ul>
            <li><strong>Akses & Koreksi:</strong> Anda dapat melihat dan mengubah data pribadi melalui halaman "Akun Saya".</li>
            <li><strong>Penghapusan:</strong> Anda berhak meminta penghapusan akun dan data pribadi Anda dari sistem kami (sesuai ketentuan hukum yang berlaku).</li>
            <li><strong>Berhenti Berlangganan (Unsubscribe):</strong> Anda dapat menolak menerima email promosi kapan saja dengan mengklik tautan *unsubscribe* di email kami.</li>
        </ul>

        <h2>7. Kebijakan Cookie</h2>
        <p>
            Website Dorve House menggunakan *Cookies* (file teks kecil) untuk "mengingat" Anda. Ini memungkinkan fitur seperti menyimpan barang di Keranjang Belanja (*Shopping Cart*) agar tidak hilang saat Anda menutup browser, serta menjaga Anda tetap *login*. Anda dapat mengatur penggunaan cookie melalui pengaturan browser Anda.
        </p>

        <h2>8. Privasi Anak-Anak</h2>
        <p>
            Layanan kami tidak ditujukan untuk anak-anak di bawah usia 13 tahun. Kami tidak secara sadar mengumpulkan data dari anak di bawah umur. Jika kami menemukan adanya data anak di bawah umur tanpa persetujuan orang tua, kami akan segera menghapus data tersebut.
        </p>

        <h2>9. Perubahan Kebijakan Privasi</h2>
        <p>
            Dorve House terus berkembang. Kami mungkin memperbarui Kebijakan Privasi ini dari waktu ke waktu untuk menyesuaikan dengan perubahan layanan atau peraturan hukum. Perubahan signifikan akan kami informasikan melalui email atau pengumuman di website. Kami menyarankan Anda untuk memeriksa halaman ini secara berkala.
        </p>

        <h2>10. Hubungi Kami</h2>
        <p>
            Jika Anda memiliki pertanyaan, kritik, atau kekhawatiran mengenai bagaimana Dorve House mengelola data privasi Anda, jangan ragu untuk menghubungi Tim Perlindungan Data kami:
        </p>
        <ul>
            <li><strong>Email:</strong> privacy@dorve.co</li>
            <li><strong>Telepon/WhatsApp:</strong> +62 812 3456 7890</li>
            <li><strong>Alamat Kantor:</strong> Jakarta, Indonesia</li>
        </ul>
        <p>
            Terima kasih telah mempercayakan pengalaman belanja fashion Anda kepada Dorve House.
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>