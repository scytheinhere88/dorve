<?php
require_once __DIR__ . '/../config.php';

$stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE slug = 'terms' AND is_published = 1 LIMIT 1");
$stmt->execute();
$page = $stmt->fetch();

$page_title = $page['meta_title'] ?? $page['title'] ?? 'Syarat & Ketentuan - Dorve House | Toko Baju Online Terpercaya';
$page_description = $page['meta_description'] ?? 'Syarat dan ketentuan belanja di Dorve House: kebijakan return 14 hari, garansi produk, cara pembayaran aman, dan hak pelanggan. Belanja baju wanita dan baju pria online dengan nyaman dan terpercaya.';
$page_keywords = 'syarat ketentuan dorve house, kebijakan return, garansi produk, hak pelanggan, cara pembayaran, refund, tukar barang, toko baju online terpercaya, belanja fashion online';
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
        text-align: center;
        padding: 40px 0;
        border-bottom: 2px solid var(--latte);
    }

    .legal-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        margin-bottom: 16px;
        color: var(--charcoal);
    }

    .legal-updated {
        color: var(--grey);
        font-size: 14px;
        letter-spacing: 1px;
    }

    .legal-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin: 50px 0 24px;
        color: var(--charcoal);
        padding-bottom: 12px;
        border-bottom: 1px solid #E5E5E5;
    }

    .legal-content h3 {
        font-size: 20px;
        margin: 30px 0 16px;
        font-weight: 600;
        color: var(--charcoal);
    }

    .legal-content p {
        line-height: 1.9;
        color: var(--grey);
        margin-bottom: 20px;
        font-size: 16px;
    }

    .legal-content ul {
        margin: 20px 0;
        padding-left: 24px;
        line-height: 1.9;
        color: var(--grey);
    }

    .legal-content li {
        margin-bottom: 12px;
        font-size: 15px;
    }

    .legal-content strong {
        color: var(--charcoal);
        font-weight: 600;
    }

    .highlight-box {
        background: var(--off-white);
        padding: 24px;
        border-left: 4px solid var(--latte);
        margin: 30px 0;
        border-radius: 4px;
    }

    .highlight-box p {
        margin-bottom: 0;
        font-size: 15px;
    }

    @media (max-width: 768px) {
        .legal-container {
            padding: 0 24px;
            margin: 40px auto;
        }

        .legal-header h1 {
            font-size: 36px;
        }

        .legal-content h2 {
            font-size: 24px;
        }
    }
</style>

<div class="legal-container">
    <div class="legal-header">
        <h1>Syarat & Ketentuan</h1>
        <p class="legal-updated">Terakhir diperbarui: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="legal-content">
        <div class="highlight-box">
            <p>
                Selamat datang di <strong>Dorve House</strong>, <strong>toko baju online terpercaya</strong> untuk fashion pria dan wanita di Indonesia. 
                Dengan mengakses dan menggunakan website kami, Anda setuju untuk terikat dengan syarat dan ketentuan yang berlaku. 
                Mohon baca dengan seksama sebelum melakukan transaksi <strong>belanja baju online</strong> di platform kami.
            </p>
        </div>

        <h2>1. Ketentuan Umum</h2>
        <p>
            Syarat dan ketentuan ini mengatur penggunaan website Dorve House dan pembelian produk <strong>fashion wanita</strong>, 
            <strong>fashion pria</strong>, dan <strong>fashion unisex</strong> melalui platform online kami. Dengan mengakses website ini, 
            Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui untuk terikat dengan semua ketentuan yang berlaku.
        </p>
        <p>
            Dorve House berhak untuk mengubah, memodifikasi, atau memperbarui syarat dan ketentuan ini kapan saja tanpa pemberitahuan terlebih dahulu. 
            Perubahan akan berlaku efektif segera setelah dipublikasikan di website. Penggunaan website setelah perubahan dianggap sebagai 
            persetujuan Anda terhadap syarat dan ketentuan yang telah dimodifikasi.
        </p>

        <h2>2. Pendaftaran Akun</h2>
        <h3>2.1 Pembuatan Akun</h3>
        <p>
            Untuk melakukan pembelian di <strong>toko baju online</strong> kami, Anda dapat berbelanja sebagai guest atau mendaftar akun member. 
            Pendaftaran akun memberikan berbagai keuntungan termasuk tracking pesanan, wishlist, dan akses ke program loyalty reward.
        </p>
        <p>
            Saat mendaftar akun, Anda bertanggung jawab untuk:
        </p>
        <ul>
            <li>Memberikan informasi yang akurat, lengkap, dan terkini</li>
            <li>Menjaga kerahasiaan password dan informasi akun Anda</li>
            <li>Bertanggung jawab atas semua aktivitas yang terjadi di bawah akun Anda</li>
            <li>Segera memberitahu kami jika terjadi penggunaan akun tanpa izin</li>
            <li>Tidak menggunakan akun untuk aktivitas ilegal atau melanggar hukum</li>
        </ul>

        <h3>2.2 Usia Minimum</h3>
        <p>
            Anda harus berusia minimal 17 tahun atau memiliki persetujuan orang tua/wali untuk melakukan transaksi di website kami. 
            Dengan melakukan pembelian, Anda menyatakan bahwa Anda memenuhi persyaratan usia ini.
        </p>

        <h2>3. Produk dan Harga</h2>
        <h3>3.1 Informasi Produk</h3>
        <p>
            Kami berusaha memberikan deskripsi, gambar, dan informasi produk <strong>baju kekinian</strong> yang akurat. Namun, kami tidak menjamin bahwa:
        </p>
        <ul>
            <li>Warna produk 100% identik dengan gambar karena perbedaan pengaturan layar device</li>
            <li>Deskripsi produk sepenuhnya bebas dari kesalahan atau kekurangan</li>
            <li>Produk akan selalu tersedia dalam stok</li>
            <li>Ukuran produk sempurna untuk setiap individu</li>
        </ul>

        <h3>3.2 Harga dan Ketersediaan</h3>
        <p>
            Semua harga yang tercantum dalam website adalah dalam mata uang Rupiah (IDR) dan sudah termasuk PPN. Harga dapat berubah sewaktu-waktu 
            tanpa pemberitahuan sebelumnya. Kami berhak untuk:
        </p>
        <ul>
            <li>Memperbaiki kesalahan harga yang terjadi di sistem</li>
            <li>Membatalkan pesanan jika terjadi kesalahan harga signifikan</li>
            <li>Menolak atau membatalkan pesanan jika produk tidak tersedia</li>
            <li>Membatasi kuantitas pembelian per customer untuk produk tertentu</li>
        </ul>

        <div class="highlight-box">
            <p>
                <strong>Catatan Penting:</strong> Harga yang berlaku adalah harga pada saat checkout selesai dan pembayaran dikonfirmasi, 
                bukan pada saat produk dimasukkan ke keranjang belanja.
            </p>
        </div>

        <h2>4. Pemesanan dan Pembayaran</h2>
        <h3>4.1 Proses Pemesanan</h3>
        <p>
            Proses pemesanan di Dorve House dirancang untuk memberikan pengalaman <strong>belanja online</strong> yang mudah dan nyaman:
        </p>
        <ul>
            <li><strong>Pilih Produk:</strong> Browse koleksi <strong>baju wanita</strong>, <strong>baju pria</strong>, atau <strong>baju couple</strong> kami</li>
            <li><strong>Tambah ke Keranjang:</strong> Pilih size dan quantity yang diinginkan</li>
            <li><strong>Review Keranjang:</strong> Pastikan semua item dan detail pesanan sudah benar</li>
            <li><strong>Checkout:</strong> Isi informasi pengiriman dan pilih metode pembayaran</li>
            <li><strong>Konfirmasi Pembayaran:</strong> Selesaikan pembayaran sesuai instruksi</li>
        </ul>

        <h3>4.2 Metode Pembayaran</h3>
        <p>
            Kami menerima berbagai metode pembayaran untuk kemudahan Anda:
        </p>
        <ul>
            <li><strong>Transfer Bank:</strong> BCA, Mandiri, BNI, BRI, CIMB Niaga, Permata</li>
            <li><strong>E-Wallet:</strong> GoPay, OVO, DANA, ShopeePay, LinkAja</li>
            <li><strong>Virtual Account:</strong> Semua bank yang tersedia</li>
            <li><strong>Credit/Debit Card:</strong> Visa, Mastercard, JCB</li>
            <li><strong>COD (Cash on Delivery):</strong> Tersedia untuk area tertentu dengan minimum pembelian</li>
            <li><strong>Cicilan 0%:</strong> Tersedia untuk kartu kredit tertentu dengan minimum transaksi</li>
        </ul>

        <h3>4.3 Konfirmasi Pesanan</h3>
        <p>
            Setelah pembayaran dikonfirmasi, Anda akan menerima:
        </p>
        <ul>
            <li>Email konfirmasi pesanan dengan detail lengkap</li>
            <li>Nomor order untuk tracking pengiriman</li>
            <li>Estimasi waktu pengiriman berdasarkan lokasi</li>
            <li>Invoice digital untuk keperluan pribadi Anda</li>
        </ul>

        <h2>5. Pengiriman dan Ongkos Kirim</h2>
        <h3>5.1 Waktu Proses</h3>
        <p>
            Kami memproses pesanan dengan cepat untuk kepuasan Anda:
        </p>
        <ul>
            <li><strong>Regular Processing:</strong> 1-2 hari kerja setelah pembayaran dikonfirmasi</li>
            <li><strong>Pre-order Items:</strong> Sesuai dengan estimasi yang tertera pada deskripsi produk</li>
            <li><strong>Custom Orders:</strong> 7-14 hari kerja tergantung kompleksitas</li>
        </ul>

        <h3>5.2 Estimasi Pengiriman</h3>
        <p>
            Waktu pengiriman bervariasi berdasarkan lokasi tujuan:
        </p>
        <ul>
            <li><strong>Jakarta & Sekitarnya:</strong> 2-3 hari kerja</li>
            <li><strong>Pulau Jawa (luar Jakarta):</strong> 3-5 hari kerja</li>
            <li><strong>Luar Pulau Jawa:</strong> 5-7 hari kerja</li>
            <li><strong>Area Terpencil:</strong> 7-14 hari kerja</li>
            <li><strong>Express Shipping:</strong> 1-2 hari kerja (biaya tambahan berlaku)</li>
        </ul>

        <h3>5.3 Biaya Pengiriman</h3>
        <ul>
            <li><strong>Gratis Ongkir:</strong> Untuk pembelian di atas Rp 500.000</li>
            <li><strong>Flat Rate:</strong> Rp 20.000 untuk Jakarta & sekitarnya</li>
            <li><strong>Regular Rate:</strong> Dihitung otomatis berdasarkan berat dan tujuan</li>
            <li><strong>Express Rate:</strong> 2x dari biaya regular shipping</li>
        </ul>

        <div class="highlight-box">
            <p>
                <strong>Catatan Pengiriman:</strong> Kami tidak bertanggung jawab atas keterlambatan yang disebabkan oleh force majeure, 
                cuaca buruk, bencana alam, atau masalah pada pihak ekspedisi. Pastikan alamat pengiriman Anda lengkap dan benar.
            </p>
        </div>

        <h2>6. Kebijakan Return & Penukaran</h2>
        <h3>6.1 Ketentuan Return</h3>
        <p>
            Kepuasan Anda adalah prioritas kami. Anda dapat melakukan return dalam <strong>14 hari kalender</strong> sejak produk diterima dengan ketentuan:
        </p>
        <ul>
            <li>Produk dalam kondisi baru, belum dipakai, dan belum dicuci</li>
            <li>Tag/label produk masih terpasang dan utuh</li>
            <li>Kemasan original masih lengkap</li>
            <li>Disertai bukti pembelian (invoice/email order)</li>
            <li>Tidak ada noda, bau, atau tanda-tanda pemakaian</li>
        </ul>

        <h3>6.2 Produk yang Tidak Dapat Dikembalikan</h3>
        <p>
            Beberapa produk tidak dapat dilakukan return karena alasan kesehatan dan kebersihan:
        </p>
        <ul>
            <li>Produk sale/clearance (kecuali cacat produksi)</li>
            <li>Produk yang dibeli dengan voucher promosi (hanya store credit)</li>
            <li>Produk yang sudah dipakai, dicuci, atau dimodifikasi</li>
            <li>Produk custom/made-to-order</li>
            <li>Pakaian dalam dan intimates</li>
            <li>Aksesoris yang packaging-nya sudah dibuka</li>
        </ul>

        <h3>6.3 Proses Return & Refund</h3>
        <p>
            Untuk melakukan return:
        </p>
        <ul>
            <li><strong>Step 1:</strong> Hubungi customer service via WhatsApp/Email dalam 14 hari</li>
            <li><strong>Step 2:</strong> Kirimkan foto produk dan alasan return</li>
            <li><strong>Step 3:</strong> Tim kami akan review dan memberikan approval/rejection</li>
            <li><strong>Step 4:</strong> Jika approved, kirim produk ke alamat return center kami</li>
            <li><strong>Step 5:</strong> Refund diproses dalam 7-14 hari kerja setelah produk diterima</li>
        </ul>

        <h3>6.4 Penukaran Produk</h3>
        <p>
            Anda dapat menukar produk dengan size atau warna berbeda (subject to availability) dengan ketentuan yang sama dengan return. 
            Biaya pengiriman untuk penukaran ditanggung oleh customer kecuali terjadi kesalahan dari pihak kami.
        </p>

        <h2>7. Produk Cacat atau Salah Kirim</h2>
        <p>
            Jika Anda menerima produk yang cacat atau berbeda dengan pesanan:
        </p>
        <ul>
            <li>Segera hubungi customer service dalam <strong>3x24 jam</strong> sejak produk diterima</li>
            <li>Kirimkan foto/video produk sebagai bukti</li>
            <li>Kami akan melakukan pengecekan dan verifikasi</li>
            <li>Jika terbukti cacat produksi atau kesalahan kami, kami akan mengganti produk atau refund 100%</li>
            <li>Biaya pengiriman return untuk produk cacat ditanggung Dorve House</li>
        </ul>

        <h2>8. Hak Kekayaan Intelektual</h2>
        <p>
            Seluruh konten di website ini termasuk namun tidak terbatas pada teks, gambar, logo, design, foto produk, dan video adalah 
            hak milik Dorve House dan dilindungi oleh undang-undang hak cipta dan kekayaan intelektual. Anda tidak diperkenankan untuk:
        </p>
        <ul>
            <li>Menyalin, mereproduksi, atau mendistribusikan konten tanpa izin tertulis</li>
            <li>Menggunakan logo atau brand identity Dorve House untuk kepentingan komersial</li>
            <li>Mengambil foto produk untuk dijual kembali di platform lain</li>
            <li>Melakukan reverse engineering pada website atau sistem kami</li>
        </ul>

        <h2>9. Privasi dan Perlindungan Data</h2>
        <p>
            Kami sangat menghargai privasi Anda. Informasi pribadi yang Anda berikan (nama, alamat, email, nomor telepon) akan digunakan untuk:
        </p>
        <ul>
            <li>Memproses dan mengirimkan pesanan Anda</li>
            <li>Komunikasi terkait pesanan dan customer service</li>
            <li>Marketing dan promosi (dengan persetujuan Anda)</li>
            <li>Meningkatkan layanan dan pengalaman belanja</li>
        </ul>
        <p>
            Kami tidak akan menjual, menyewakan, atau membagikan data pribadi Anda kepada pihak ketiga tanpa persetujuan Anda, 
            kecuali diperlukan oleh hukum. Untuk informasi lebih detail, silakan baca Kebijakan Privasi kami.
        </p>

        <h2>10. Pembatasan Tanggung Jawab</h2>
        <p>
            Dorve House tidak bertanggung jawab atas:
        </p>
        <ul>
            <li>Kerugian tidak langsung, insidental, atau konsekuensial dari penggunaan website atau produk</li>
            <li>Gangguan layanan karena maintenance, update sistem, atau force majeure</li>
            <li>Kehilangan data atau informasi akibat masalah teknis di luar kontrol kami</li>
            <li>Keterlambatan atau kegagalan pengiriman yang disebabkan oleh pihak ekspedisi atau bea cukai</li>
            <li>Kesalahan informasi yang diberikan oleh customer (alamat salah, nomor telepon tidak aktif, dll)</li>
        </ul>

        <h2>11. Program Loyalty & Promosi</h2>
        <p>
            Dorve House secara berkala mengadakan program promosi, diskon, dan flash sale. Ketentuan khusus berlaku untuk setiap program:
        </p>
        <ul>
            <li>Voucher/kode promo tidak dapat digabungkan kecuali disebutkan sebaliknya</li>
            <li>Produk sale/diskon tidak dapat dikembalikan untuk refund (hanya store credit atau exchange)</li>
            <li>Point reward memiliki masa berlaku tertentu</li>
            <li>Kami berhak membatalkan transaksi yang melanggar syarat dan ketentuan promosi</li>
        </ul>

        <h2>12. Hukum yang Berlaku</h2>
        <p>
            Syarat dan ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia. Setiap perselisihan yang timbul 
            dari atau terkait dengan syarat dan ketentuan ini akan diselesaikan melalui musyawarah. Jika tidak tercapai kesepakatan, 
            perselisihan akan diselesaikan di pengadilan yang berwenang di Jakarta, Indonesia.
        </p>

        <h2>13. Hubungi Kami</h2>
        <p>
            Jika Anda memiliki pertanyaan mengenai syarat dan ketentuan ini atau memerlukan bantuan terkait pesanan Anda, 
            silakan hubungi customer service kami:
        </p>
        <ul>
            <li><strong>Email:</strong> dorveofficial@gmail.com</li>
            <li><strong>WhatsApp:</strong> +62 813-7737-8859 (Senin-Jumat, 09:00-15:00 WIB)</li>
            <li><strong>Instagram:</strong> @dorve.id</li>
            <li><strong>Alamat:</strong> - </li>
        </ul>

        <div class="highlight-box">
            <p>
                <strong>Terima kasih telah memilih Dorve House</strong> sebagai destinasi <strong>belanja fashion online</strong> Anda. 
                Kami berkomitmen untuk memberikan produk <strong>baju kekinian</strong> berkualitas dan layanan terbaik untuk kepuasan Anda.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>