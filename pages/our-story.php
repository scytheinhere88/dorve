<?php
require_once __DIR__ . '/../config.php';

$lang = $_SESSION['lang'] ?? 'id';

$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'our-story' AND lang = ?");
$stmt->execute([$lang]);
$page = $stmt->fetch();

$page_title = $page['meta_title'] ?? $page['title'] ?? 'Tentang Dorve House - Toko Baju Online Terpercaya Indonesia';
$page_description = $page['meta_description'] ?? 'Kenali lebih dekat Dorve House, toko baju online terpercaya yang menghadirkan fashion pria, fashion wanita, dan fashion unisex kekinian. Komitmen kami: baju murah berkualitas premium dengan desain trendy untuk semua kalangan di Indonesia.';
$page_keywords = 'tentang dorve house, toko baju online terpercaya, brand fashion indonesia, fashion wanita, fashion pria, baju kekinian, baju trendy, baju online, model baju terbaru, fashion unisex, baju murah berkualitas, visi misi dorve house, cerita brand fashion';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .story-hero {
        height: 70vh;
        min-height: 500px;
        background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)),
                    url('/public/images/our-story.webp') center/cover;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        text-align: center;
    }

    .story-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 64px;
        margin-bottom: 20px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }

    .story-hero p {
        font-size: 20px;
        letter-spacing: 1px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .story-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 100px 40px;
    }

    .story-content {
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.9;
        font-size: 17px;
        color: var(--grey);
    }

    .story-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        color: var(--charcoal);
        margin: 60px 0 30px;
        text-align: center;
    }

    .story-content h3 {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        color: var(--charcoal);
        margin: 40px 0 20px;
    }

    .story-content p {
        margin-bottom: 24px;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 40px;
        margin: 80px 0;
    }

    .value-card {
        text-align: center;
        padding: 40px 30px;
        background: var(--cream);
        border-radius: 8px;
    }

    .value-icon {
        font-size: 48px;
        margin-bottom: 20px;
    }

    .value-card h4 {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 16px;
        color: var(--charcoal);
    }

    .value-card p {
        font-size: 15px;
        color: var(--grey);
        line-height: 1.7;
    }

    .cta-section {
        background: var(--blush);
        padding: 100px 40px;
        text-align: center;
        margin-top: 100px;
    }

    .cta-section h2 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 30px;
    }

    .cta-section p {
        font-size: 18px;
        color: var(--grey);
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-btn {
        display: inline-block;
        padding: 18px 60px;
        background: var(--charcoal);
        color: var(--white);
        text-decoration: none;
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 14px;
        transition: all 0.3s;
    }

    .cta-btn:hover {
        background: var(--latte);
        color: var(--charcoal);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .story-hero h1 {
            font-size: 40px;
        }

        .story-section {
            padding: 60px 24px;
        }

        .story-content h2 {
            font-size: 32px;
        }

        .values-grid {
            gap: 24px;
        }
    }
</style>

<div class="story-hero">
    <div>
        <h1><?php echo htmlspecialchars($page['title'] ?? 'Kisah Kami'); ?></h1>
        <p>Dibuat dengan Passion, Dirancang untuk Anda</p>
    </div>
</div>

<section class="story-section">
    <div class="story-content">
        <?php if ($page && $page['content']): ?>
            <?php echo $page['content']; ?>
        <?php else: ?>
            <h2>Perjalanan Dorve House</h2>
            <p>
                <strong>Dorve House</strong> lahir dari visi sederhana namun kuat: menciptakan <strong>toko baju online terpercaya</strong> yang menghadirkan <strong>fashion wanita</strong>, <strong>fashion pria</strong>, dan <strong>fashion unisex kekinian</strong> untuk semua kalangan di Indonesia. Didirikan pada tahun 2020, kami memulai perjalanan sebagai brand fashion lokal dengan komitmen penuh terhadap kualitas, desain yang timeless, dan harga yang terjangkau.
            </p>

            <p>
                Perjalanan kami dimulai ketika founder kami melihat kebutuhan pasar akan <strong>baju online</strong> berkualitas premium yang mudah diakses. Kami percaya bahwa setiap orang berhak mendapatkan <strong>baju kekinian</strong> dengan material terbaik tanpa harus menguras kantong. Dari sinilah Dorve House hadir sebagai solusi untuk Anda yang mencari <strong>baju murah berkualitas</strong> dengan desain yang selalu mengikuti <strong>model baju terbaru</strong>.
            </p>

            <p>
                Sebagai <strong>toko baju wanita online terpercaya</strong> dan <strong>toko baju pria online</strong>, kami memahami bahwa setiap pelanggan memiliki kebutuhan dan preferensi yang unik. Itulah mengapa koleksi kami dirancang untuk mencakup berbagai gaya—dari casual everyday hingga formal elegant. Kami <strong>jual baju wanita murah</strong> tanpa mengorbankan kualitas, dan <strong>jual baju pria murah</strong> dengan standar material yang sama tingginya. Kepercayaan Anda adalah aset paling berharga bagi kami.
            </p>

            <h2>Filosofi Fashion Kami</h2>
            <p>
                Di Dorve House, kami tidak hanya menjual <strong>baju wanita</strong> atau <strong>baju pria</strong>, tetapi kami menghadirkan gaya hidup. Setiap koleksi <strong>baju trendy</strong> yang kami rilis dirancang dengan mempertimbangkan kenyamanan, style, dan kepercayaan diri pemakainya. Kami tidak sekadar mengikuti trend—kami menciptakan klasik modern yang akan Anda kenakan dengan bangga musim demi musim.
            </p>

            <p>
                Koleksi kami sangat beragam untuk memenuhi kebutuhan fashion Anda. Untuk wanita, kami menyediakan <strong>dress wanita murah</strong> dengan berbagai model—dari mini dress hingga maxi dress, <strong>blouse wanita trendy</strong> untuk tampilan profesional di kantor, <strong>rok wanita kekinian</strong> yang cocok untuk berbagai acara, hingga <strong>celana wanita murah</strong> yang nyaman untuk aktivitas sehari-hari. Kami juga memiliki koleksi <strong>baju wanita big size murah</strong> karena kami percaya fashion adalah untuk semua bentuk tubuh.
            </p>

            <p>
                Untuk pria, pilihan kami tidak kalah lengkap. Mulai dari <strong>kemeja pria lengan panjang murah</strong> yang sempurna untuk meeting formal, <strong>kaos pria keren</strong> dengan desain unik untuk gaya casual, <strong>celana pria chino murah</strong> yang versatile, <strong>jaket pria keren</strong> untuk tampilan layered yang stylish, hingga <strong>hoodie pria keren</strong> yang nyaman untuk streetwear look. Setiap produk <strong>baju pria terbaru</strong> kami dirancang dengan mempertimbangkan comfort dan style para pria modern.
            </p>

            <p>
                Tidak hanya itu, Dorve House juga menghadirkan koleksi spesial untuk pasangan dan keluarga. Temukan <strong>baju couple murah</strong> yang romantis dan matching, <strong>kaos couple keren</strong> dengan desain eksklusif, <strong>hoodie couple matching</strong> untuk tampil kompak, <strong>jaket couple keren</strong> untuk musim dingin, hingga <strong>baju family gathering</strong> yang sempurna untuk acara keluarga. Fashion bersama orang-orang terkasih menjadi lebih berkesan dengan koleksi couple dan family kami.
            </p>

            <p>
                Sebagai <strong>toko baju online</strong> yang bertanggung jawab, sustainability dan ethical production adalah bagian integral dari DNA kami. Kami bermitra dengan manufacturer yang menerapkan praktik ramah lingkungan dan memastikan setiap pekerja mendapat perlakuan yang adil. Ketika Anda <strong>beli baju wanita online</strong> atau <strong>beli baju pria online</strong> di Dorve House, Anda tidak hanya mendapatkan produk fashion berkualitas—Anda juga berkontribusi pada masa depan fashion yang lebih baik dan berkelanjutan.
            </p>

            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">✨</div>
                    <h4>Kualitas Premium</h4>
                    <p>Setiap produk <strong>fashion wanita trendy</strong> dan <strong>fashion pria kekinian</strong> kami melalui quality control ketat untuk memastikan standar terbaik.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">🌿</div>
                    <h4>Ramah Lingkungan</h4>
                    <p>Komitmen kami terhadap sustainable fashion dengan bahan eco-friendly dan proses produksi yang bertanggung jawab.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">💎</div>
                    <h4>Desain Timeless</h4>
                    <p>Koleksi <strong>baju kekinian</strong> kami melampaui trend sesaat, menawarkan versatilitas dan daya tahan jangka panjang.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">🤝</div>
                    <h4>Produksi Etis</h4>
                    <p>Upah yang adil dan kondisi kerja yang aman untuk semua orang dalam supply chain <strong>toko baju online</strong> kami.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">❤️</div>
                    <h4>Customer Satisfaction</h4>
                    <p>Kepuasan Anda adalah prioritas utama kami, dari proses design hingga pengiriman <strong>baju online</strong> ke tangan Anda.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">🎨</div>
                    <h4>Craftsmanship</h4>
                    <p>Teknik artisan bertemu dengan inovasi modern dalam setiap kreasi <strong>baju trendy</strong> kami.</p>
                </div>
            </div>

            <h2>Komitmen Kami kepada Anda</h2>
            <p>
                Ketika Anda mengenakan produk Dorve House, Anda mengenakan lebih dari sekadar pakaian yang indah. Anda mengenakan komitmen terhadap kualitas, sustainability, dan gaya yang timeless. Anda mendukung praktik fashion yang etis dan menjadi bagian dari komunitas yang menghargai autentisitas dan keanggunan.
            </p>

            <p>
                Kami memahami bahwa berbelanja <strong>baju online</strong> memerlukan kepercayaan. Oleh karena itu, Dorve House memberikan jaminan kualitas pada setiap produk yang kami jual. Setiap <strong>fashion wanita trendy</strong> dan <strong>fashion pria kekinian</strong> yang kami tawarkan telah melalui quality control yang ketat. Dari pemilihan bahan, proses produksi, hingga packaging—semua dilakukan dengan standar tertinggi untuk memastikan Anda mendapatkan yang terbaik.
            </p>

            <p>
                Koleksi <strong>fashion wanita</strong> kami mencakup <strong>dress wanita</strong> untuk berbagai acara—dari casual brunch hingga formal dinner, <strong>blouse wanita trendy</strong> untuk tampilan profesional di kantor, hingga <strong>celana wanita murah</strong> yang nyaman untuk kenyamanan sehari-hari. Untuk pria, kami menghadirkan <strong>kemeja pria</strong> berkualitas yang tahan lama, <strong>kaos pria keren</strong> dengan desain yang eye-catching, dan <strong>hoodie pria</strong> yang stylish namun tetap comfortable. Tidak ketinggalan, koleksi <strong>baju couple</strong> eksklusif untuk Anda dan pasangan yang ingin tampil kompak dan romantic.
            </p>

            <p>
                Pengalaman berbelanja di <strong>toko baju online terpercaya</strong> kami dirancang untuk memberikan kemudahan maksimal. Website kami user-friendly dengan filter pencarian yang memudahkan Anda menemukan <strong>baju kekinian</strong> yang sesuai selera. Customer service kami siap membantu 24/7 melalui WhatsApp untuk menjawab pertanyaan seputar produk, size guide, hingga tracking pesanan. Kami juga menyediakan berbagai metode pembayaran yang aman—dari transfer bank, e-wallet, hingga COD untuk kenyamanan Anda.
            </p>

            <p>
                Kepuasan pelanggan adalah prioritas utama. Kami menawarkan free shipping untuk pembelian di atas amount tertentu, easy return policy jika produk tidak sesuai ekspektasi, dan reward points setiap pembelian yang bisa ditukar dengan diskon di transaksi berikutnya. Ketika Anda <strong>beli baju wanita online</strong> atau <strong>beli baju pria online</strong> di Dorve House, Anda bukan hanya mendapatkan produk berkualitas—Anda mendapatkan pengalaman berbelanja yang menyenangkan dari awal hingga akhir.
            </p>

            <h3>Bergabunglah dengan Keluarga Dorve House</h3>
            <p>
                Kisah Dorve House terus berkembang, dan kami merasa terhormat memiliki Anda sebagai bagian dari perjalanan ini. Sebagai <strong>toko baju online terpercaya</strong>, kami terus berinovasi menghadirkan <strong>model baju terbaru</strong> yang sesuai dengan kebutuhan dan gaya hidup modern Anda. Setiap musim, kami meluncurkan koleksi fresh yang mengikuti trend global namun tetap mempertahankan identitas lokal Indonesia.
            </p>

            <p>
                Komunitas Dorve House terdiri dari ribuan fashion enthusiast di seluruh Indonesia yang memiliki passion yang sama terhadap style dan kualitas. Kami bangga menjadi bagian dari perjalanan fashion Anda—dari outfit first date, pakaian wawancara kerja, hingga dress untuk special occasions. Testimoni dan feedback dari Anda adalah motivasi terbesar kami untuk terus berkembang dan memberikan yang terbaik.
            </p>

            <p>
                Ikuti kami di media sosial untuk update koleksi terbaru, styling tips, promo eksklusif, dan behind-the-scenes dari proses pembuatan <strong>baju murah berkualitas</strong> kami. Dapatkan first access ke flash sale, special discount untuk member setia, dan info tentang <strong>fashion pria kekinian</strong> serta <strong>fashion wanita</strong> yang akan launching. Bersama-sama, mari kita ciptakan dunia fashion yang lebih baik—satu outfit <strong>kekinian</strong> pada satu waktu.
            </p>

            <p>
                Terima kasih telah mempercayai Dorve House sebagai partner fashion Anda. Kami berkomitmen untuk terus menghadirkan <strong>baju trendy</strong>, <strong>baju pria terbaru</strong>, dan <strong>fashion unisex</strong> yang tidak hanya membuat Anda tampil percaya diri, tetapi juga memberikan value terbaik untuk investasi wardrobe Anda. Selamat datang di keluarga Dorve House—where style meets quality, and fashion meets passion.
            </p>
        <?php endif; ?>
    </div>
</section>

<div class="cta-section">
    <h2>Jelajahi Koleksi Kami</h2>
    <p>
        Rasakan perpaduan sempurna antara luxury, kenyamanan, dan sustainability.
        Temukan fashion favorit Anda hari ini.
    </p>
    <a href="/pages/new-collection.php" class="cta-btn">Belanja Koleksi Baru</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>