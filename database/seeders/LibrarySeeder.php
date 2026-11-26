<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\MembershipType;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // ---- Membership Context ----
        $members = Member::factory()->count(13)->create();
        Member::factory()->suspended()->create(['name' => 'Anggota Ditangguhkan']);

        // Bagi jenis keanggotaan bawaan (Umum, Mahasiswa, Dosen) secara bergantian ke anggota contoh
        $types = MembershipType::orderBy('max_loans')->get();
        $members->each(fn (Member $m, int $i) => $m->update(['membership_type_id' => $types[$i % $types->count()]->id]));

        // ---- Catalog Context ----
        // Koleksi: 9 buku terbitan Deepublish lengkap dengan sampul (database/seeders/covers).
        $this->ensureStorageLink();

        $publishers = [
            'deepublish' => Publisher::create(['name' => 'Penerbit Deepublish']),
            'ugm' => Publisher::create(['name' => 'UGM Press']),
        ];

        $categories = collect([
            'Teknik', 'Ilmu Komputer', 'Sains dan Teknologi', 'Hukum', 'Ekonomi', 'Akuntansi',
            'Ekonomi Bisnis', 'Manajemen Bisnis', 'Matematika', 'Teknik Informatika',
            'Kedokteran', 'Kesehatan', 'Kesehatan Lingkungan',
            'Fisiologi', 'Fisiologi Manusia', 'Kedokteran Gigi', 'Perpajakan', 'Sosial dan Politik',
            'Bahasa dan Sastra', 'Bahasa', 'Bahasa Indonesia',
            'Bahasa Inggris', 'Geografi', 'Agama Islam', 'Hukum Adat',
            'Sejarah Islam', 'Ekonomi Islam',
        ])->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name, 'slug' => Str::slug($name)])]);

        $catalog = [
            [
                'title' => 'Buku Ajar Komputer Terapan (Building Information Modeling/BIM)',
                'isbn' => '978-623-02-8659-9', 'year' => 2024, 'stock' => 5, 'cover' => 'komputer-terapan-bim.jpg',
                'categories' => ['Teknik'],
                'authors' => ['Febri Fahmi Hakim', 'Robi Fernando', 'Agung Bhakti Utama'],
                'description' => 'Modeling BIM 3D dan 5D untuk Bangunan Gedung dengan Autodesk Revit. SKU: DP10014A · Berat buku: 0,65 kg.',
            ],
            [
                'title' => 'Pengenalan Prompt Engineering',
                'isbn' => '978-623-02-8112-9', 'year' => 2024, 'stock' => 6, 'cover' => 'pengenalan-prompt-engineering.jpg',
                'categories' => ['Ilmu Komputer', 'Sains dan Teknologi'],
                'authors' => ['Yulius Denny Prabowo', 'Martinus Maslim', 'Pandu Dwi Luhur Pambudi'],
                'description' => 'SKU: DP10866A · Berat buku: 0,3 kg.',
            ],
            [
                'title' => 'Big Data Analysis dengan Phyton untuk Perguruan Tinggi',
                'isbn' => '978-623-02-7032-1', 'year' => 2023, 'stock' => 4, 'cover' => 'big-data-analysis-python.jpg',
                'categories' => ['Teknik', 'Ilmu Komputer'],
                'authors' => ["Efori Bu'ulolo", 'Mesran', 'Nelly Astuti Hasibuan', 'Soeb Aripin', 'Dito Putro Utomo', 'Rian Syahputra'],
                'description' => 'SKU: DP08396A · Berat buku: 0,3 kg.',
            ],
            [
                'title' => 'Internet of Things (IoT) dan Aplikasinya',
                'isbn' => '978-623-02-8891-3', 'year' => 2024, 'stock' => 5, 'cover' => 'iot-dan-aplikasinya.jpg',
                'categories' => ['Sains dan Teknologi'],
                'authors' => ['Adhan Efendi, M.Pd.', 'Khotimatul Barki, S.P.', 'Kevie Desderius, S.PWK.', 'Nabila Rizqia Novianda, S.Tr.,T.', 'Meli Puspita Sari S.Si.'],
                'description' => 'SKU: DP10248A · Berat buku: 0,5 kg.',
            ],
            [
                'title' => 'Kecerdasan Buatan (AI) dan Hukum di Masa Depan: Sebuah Pengantar',
                'isbn' => '978-623-02-9628-4', 'year' => 2024, 'stock' => 3, 'cover' => 'ai-dan-hukum.jpg',
                'categories' => ['Hukum'],
                'authors' => ['Anton D. Varma, S.H., M.Hum., CPL., CLA., CPCLE., CML., CCMS., CPM.'],
                'description' => 'SKU: DP11022A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber, ganti lewat menu Edit Buku (admin)
                'title' => 'Menguasai Jaringan Komputer pada Cisco & Mikrotik',
                'isbn' => '978-0-00-000001-9', 'year' => null, 'stock' => 6, 'cover' => 'cisco-mikrotik.jpg',
                'categories' => ['Ilmu Komputer'],
                'authors' => ['Putra Wanda'],
                'description' => 'Jaringan Komputer, Belajar Cisco Networking dengan Packet Tracert, Belajar Mikrotik Networking. SKU: DP03921A · Berat buku: 0,4 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Buku Referensi Statistika untuk Ekonomi dan Bisnis',
                'isbn' => '978-0-00-000002-6', 'year' => null, 'stock' => 4, 'cover' => 'statistika-ekonomi-bisnis.jpg',
                'categories' => ['Ekonomi'],
                'authors' => ['Dr. Nazifah Husainah, S.E., M.M.', 'Siti Hafnidar Harun, S.E., M.M.', 'Azimah Hanifah, S.E., M.Si.', 'Darto, S.E., M.M.'],
                'description' => 'SKU: DP06500A · Berat buku: 0,4 kg.',
            ],
            [
                'title' => 'Buku Ajar Akuntansi Accurate',
                'isbn' => '978-634-01-0080-8', 'year' => 2025, 'stock' => 5, 'cover' => 'akuntansi-accurate.jpg',
                'categories' => ['Akuntansi'],
                'authors' => ['Eliza Noviriani, SE., MSA., CAP.', 'Roshani, SE., M.Ak., CAP.'],
                'description' => 'SKU: DP11520A · Berat buku: 0,4 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber. Stok 1 agar contoh "stok habis" muncul setelah dipinjam.
                'title' => 'Financial Technology 5.0',
                'isbn' => '978-0-00-000003-3', 'year' => null, 'stock' => 1, 'cover' => 'financial-technology-5.jpg',
                'categories' => ['Ekonomi'],
                'authors' => ['Prof. Dr. Suryanto, S.E., M.Si.', 'Mohammad Yusuf, S.E., M.M.'],
                'description' => 'SKU: DP12138A · Berat buku: 0,2 kg.',
            ],
            // ---- Tambahan koleksi: bisnis, matematika, dan kedokteran/kesehatan ----
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Pengantar Manajemen Bisnis Digital',
                'isbn' => '978-0-00-000004-0', 'year' => null, 'stock' => 4, 'cover' => 'manajemen-bisnis-digital.jpg',
                'categories' => ['Ekonomi', 'Ekonomi Bisnis', 'Manajemen Bisnis'],
                'authors' => ['Sekar Widyasari Putri, S.A.B., M.Si.', 'Dominggo Bayu Baskara, S.T., M.M.T., PMP.', 'Riza Akhsani Setyo Prayoga, S.Kom., M.M.T.', 'Bagas Putra Pradana, S.Akt., M.I.M.', 'Rasyidi Faiz Akbar, S.E., M.M.', 'Tiar Anindya Putri, S.Kom., M.M.T.'],
                'description' => 'SKU: DP06335A · Berat buku: 0,6 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'Bisnis Internasional: Teori dan Aplikasi',
                'isbn' => '978-0-00-000005-7', 'year' => null, 'stock' => 5, 'cover' => 'bisnis-internasional.jpg',
                'categories' => ['Ekonomi'],
                'authors' => ['Dhiani Dyahjatmayanti'],
                'description' => 'SKU: DP07640A · Berat buku: 0,45 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'Buku Ajar Matematika Diskrit untuk Teknik Informatika',
                'isbn' => '978-0-00-000006-4', 'year' => null, 'stock' => 6, 'cover' => 'matematika-diskrit.jpg',
                'categories' => ['Matematika', 'Teknik Informatika'],
                'authors' => ['Claudia Betruchy Bada, S.Pd., M.Pd.', 'Yohanes B.M. Darkel, S.Kom., M.Kom.', 'Agustinus Lambertus Suban, S.Kom., M.T.', 'Benny Hansen Lifindra, S.Kom., M.Kom.'],
                'description' => 'Editor: Maria Yunita, S.Kom., M.Kom. SKU: DP13719A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'Kalkulus Diferensial: Pendekatan Blended Learning',
                'isbn' => '978-0-00-000007-1', 'year' => null, 'stock' => 4, 'cover' => 'kalkulus-diferensial.jpg',
                'categories' => ['Matematika'],
                'authors' => ['Markus Palobo'],
                'description' => 'SKU: DP03613A · Berat buku: 0,3 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'Asyiknya Belajar Matematika dengan GeoGebra',
                'isbn' => '978-0-00-000008-8', 'year' => null, 'stock' => 5, 'cover' => 'matematika-geogebra.jpg',
                'categories' => ['Matematika'],
                'authors' => ['Muhammad Iqbal Harisuddin, S.T., M.Pd.'],
                'description' => 'SKU: DP03127A · Berat buku: 0,3 kg.',
            ],
            [
                'title' => 'Kalkulus',
                'isbn' => '978-634-01-3064-5', 'year' => 2026, 'stock' => 6, 'cover' => 'kalkulus.jpg',
                'categories' => ['Matematika', 'Sains dan Teknologi'],
                'authors' => ['Primawati, S.Si., M.Si.', 'Fazrol Rozi, M.Sc.'],
                'description' => 'Berat buku: 0,15 kg.',
            ],
            [
                'title' => 'Buku Ajar Konsultasi Istimewa Kedokteran Keluarga',
                'isbn' => '978-634-01-2617-4', 'year' => 2026, 'stock' => 3, 'cover' => 'kedokteran-keluarga.jpg',
                'categories' => ['Kedokteran'],
                'authors' => ['Isti Ilmiati Fujiati', 'Oryzati Hilman'],
                'description' => 'SKU: DP14106A · Berat buku: 0,15 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'GERD: Gastroesophageal Reflux Disease',
                'isbn' => '978-0-00-000009-5', 'year' => null, 'stock' => 4, 'cover' => 'gerd.jpg',
                'categories' => ['Kedokteran'],
                'authors' => ['Rini R. Bachtiar'],
                'description' => 'SKU: DP11456A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara
                'title' => 'Limbah Medis Rumah Sakit dan Resistensi Antibiotik',
                'isbn' => '978-0-00-000010-1', 'year' => null, 'stock' => 3, 'cover' => 'limbah-medis.jpg',
                'categories' => ['Kesehatan Lingkungan'],
                'authors' => ['Dr. dr. Ludhang Pradipta Rizki, M.Biotech., Sp.MK(K).', 'Dr. dr. Agung Dwi Wahyu Widodo, M.Si., M.Ked.Klin., Sp.MK(K).'],
                'description' => 'SKU: DP12434A · Berat buku: 0,2 kg.',
            ],
            [
                'title' => 'Neurorestorasi Berbasis Puasa: Kunci Memperbaiki Otak dan Sistem Saraf',
                'isbn' => '978-634-01-2329-6', 'year' => 2026, 'stock' => 4, 'cover' => 'neurorestorasi.jpg',
                'categories' => ['Kedokteran', 'Kesehatan'],
                'authors' => ['Dr. dr. Jumraini, Sp.S., Subsp, N.R.E. (K)', 'Dr. H. Andi Muhammad Akmal, S.Ag., M.H.I.'],
                'description' => 'Editor: Dr. Tammasse, M.Hum. SKU: DP13815A · Berat buku: 0,3 kg.',
            ],

            // ---- Tambahan koleksi: kedokteran, hukum, serta bahasa dan sastra ----
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Terapi Target pada Kanker Ginjal: Bukti Klinis, Gagasan Praktik, dan Protokol Klinis untuk Regimen Terpilih',
                'isbn' => '978-0-00-000011-8', 'year' => null, 'stock' => 4, 'cover' => 'terapi-target-kanker-ginjal.jpg',
                'categories' => ['Kedokteran'],
                'authors' => ['Dr. dr. Ferry Safriadi, Sp.U(K)'],
                'description' => 'SKU: DP13533A · Berat buku: 0,15 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Bunga Rampai Fisiologi Kedokteran',
                'isbn' => '978-0-00-000012-5', 'year' => null, 'stock' => 3, 'cover' => 'bunga-rampai-fisiologi.jpg',
                'categories' => ['Fisiologi', 'Fisiologi Manusia', 'Kedokteran', 'Kedokteran Gigi'],
                'authors' => ['Dr. Tri Pitara Mahanggoro, M.Kes.'],
                'description' => 'Buku ajar bagi mahasiswa Program Studi Pendidikan Dokter, Pendidikan Dokter Gigi, Keperawatan, Kesehatan Masyarakat, Farmasi, dan bidang ilmu lain yang terkait. SKU: DP03811A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Postulat Hukum: Disertai Kaidah Hukum Perdata',
                'isbn' => '978-0-00-000013-2', 'year' => null, 'stock' => 5, 'cover' => 'postulat-hukum.jpg',
                'categories' => ['Hukum'],
                'authors' => ['Febry Indra Gunawan Sitorus'],
                'description' => 'SKU: DP11833A · Berat buku: 0,4 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Keadilan Restoratif (Restorative Justice) dalam Tindak Pidana Pajak',
                'isbn' => '978-0-00-000014-9', 'year' => null, 'stock' => 4, 'cover' => 'keadilan-restoratif.jpg',
                'categories' => ['Hukum', 'Perpajakan'],
                'authors' => ['Ikbal Thoha Saleh'],
                'description' => 'SKU: DP13645A · Berat buku: 0,15 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Memahami Advokasi Kebijakan: Konsep, Teori, dan Praktik dalam Mewujudkan Kebijakan yang Berpihak pada Publik',
                'isbn' => '978-0-00-000015-6', 'year' => null, 'stock' => 3, 'cover' => 'advokasi-kebijakan.jpg',
                'categories' => ['Hukum', 'Sosial dan Politik'],
                'authors' => ['Ramaditya Rahardian'],
                'description' => 'SKU: DP03757A · Berat buku: 0,25 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Hukum Keuangan Negara Darurat (Tata Kelola Keuangan Masa Darurat Bencana Nasional Covid-19)',
                'isbn' => '978-0-00-000016-3', 'year' => null, 'stock' => 4, 'cover' => 'hukum-keuangan-negara-darurat.jpg',
                'categories' => ['Hukum'],
                'authors' => ['Bustami, S.H., M.A.', 'Rini Fitriani, S.H., M.H.', 'Zaki Ulya, S.H., M.H.'],
                'description' => 'Editor: Dr. Fuadi, S.H., M.H. SKU: DP05460A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Pengenalan Penundaan Kewajiban Pembayaran Utang (PKPU) dan Kepailitan',
                'isbn' => '978-0-00-000017-0', 'year' => null, 'stock' => 3, 'cover' => 'pkpu-kepailitan.jpg',
                'categories' => ['Hukum'],
                'authors' => ['Dr. Darwis Anatami, S.H., M.H.'],
                'description' => 'Editor: Dr. Fadlan, S.H., M.H. dan Dr. Ramon Novrial, S.H., M.H. SKU: DP05367A · Berat buku: 0,25 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Analisis Kesalahan Berbahasa: Sebuah Pendekatan dalam Pengajaran Bahasa',
                'isbn' => '978-0-00-000018-7', 'year' => null, 'stock' => 5, 'cover' => 'analisis-kesalahan-berbahasa.jpg',
                'categories' => ['Bahasa dan Sastra'],
                'authors' => ['Mantasiah R.', 'Yusri'],
                'description' => 'SKU: DP04342A · Berat buku: 0,3 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Buku Ajar Pembelajaran Kritik Sastra',
                'isbn' => '978-0-00-000019-4', 'year' => null, 'stock' => 4, 'cover' => 'kritik-sastra.jpg',
                'categories' => ['Bahasa dan Sastra'],
                'authors' => ['Samsuddin, S.Pd., M.Hum.'],
                'description' => 'SKU: DP03135A · Berat buku: 0,1 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Pengantar Kajian Kebahasaan',
                'isbn' => '978-0-00-000020-0', 'year' => null, 'stock' => 6, 'cover' => 'kajian-kebahasaan.jpg',
                'categories' => ['Bahasa', 'Bahasa Indonesia'],
                'authors' => ['Markus Sampe', 'Rista Apriliya Devi', 'Maxsel Koro', 'Kurniayu T. R. A. Ratu', 'Putry Bire Logo'],
                'description' => 'SKU: DP12090A · Berat buku: 0,2 kg.',
            ],

            // ---- Tambahan koleksi: bahasa, geografi, teknik, dan keislaman ----
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Practical Translation 1',
                'isbn' => '978-0-00-000021-7', 'year' => null, 'stock' => 4, 'cover' => 'practical-translation-1.jpg',
                'categories' => ['Bahasa Inggris'],
                'authors' => ['Viktor Siumarlata', 'Yizrel Nani Sallata'],
                'description' => 'SKU: DP10220A · Berat buku: 0,45 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Pembelajaran Inovatif Bahasa dan Sastra: Mengembalikan Prinsip Bahasa dan Sastra sebagai Cara Berpikir Inovatif',
                'isbn' => '978-0-00-000022-4', 'year' => null, 'stock' => 3, 'cover' => 'pembelajaran-inovatif-bahasa-sastra.jpg',
                'categories' => ['Bahasa dan Sastra'],
                'authors' => ['Ninuk Lustyantie', 'Fernandes Arung'],
                'description' => 'SKU: DP04308A · Berat buku: 0,45 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Buku Ajar Sistem Informasi Geografis dan Aplikasinya',
                'isbn' => '978-0-00-000023-1', 'year' => null, 'stock' => 5, 'cover' => 'sig-dan-aplikasinya.jpg',
                'categories' => ['Geografi'],
                'authors' => ['Alfian Pujian Hadi, M.Sc.'],
                'description' => 'Konsep Dasar SIG, Data dalam Sistem Informasi Geografis, Data Spasial, Praktikum Pengolahan Data SIG. SKU: DP02426A · Berat buku: 0,5 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Teknik Sungai (Teori dan Dasar Perencanaan)',
                'isbn' => '978-0-00-000024-8', 'year' => null, 'stock' => 4, 'cover' => 'teknik-sungai.jpg',
                'categories' => ['Teknik'],
                'authors' => ['Muhammad Khalis Ilmi, S.T., M.Eng.'],
                'description' => 'SKU: DP08861A · Berat buku: 0,4 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Sistem Informasi Geografi dan Penginderaan Jauh (Studi Kasus Analisis Keruangan Menggunakan ArcGIS dan Envi)',
                'isbn' => '978-0-00-000025-5', 'year' => null, 'stock' => 3, 'cover' => 'sig-penginderaan-jauh.jpg',
                'categories' => ['Geografi', 'Sains dan Teknologi'],
                'authors' => ['Dede Prabowo Wiguna, S.Pd, M.Si.'],
                'description' => 'SKU: DP00982A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Combination of Geotechnical and Geophysics for Landslide Analysis Using Cracked Soil Approach',
                'isbn' => '978-0-00-000026-2', 'year' => null, 'stock' => 4, 'cover' => 'geotechnical-geophysics.jpg',
                'categories' => ['Geografi', 'Teknik'],
                'authors' => ['Dr. Stephanus Alexsander'],
                'description' => 'Theory and Analysis Book. SKU: DP12574A · Berat buku: 0,15 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Arahan Pengembangan Kawasan: Kasus di Sebagian Pesisir Pemalang',
                'publisher' => 'ugm',
                'isbn' => '978-0-00-000027-9', 'year' => null, 'stock' => 3, 'cover' => 'arahan-pengembangan-kawasan.jpg',
                'categories' => ['Geografi'],
                'authors' => ['Dyah Rahmawati Hizbaron', 'Muh Aris Marfai'],
                'description' => 'Berat buku: 0,7 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Strategi Belajar & Mengajar Geografi',
                'isbn' => '978-0-00-000028-6', 'year' => null, 'stock' => 5, 'cover' => 'strategi-belajar-mengajar-geografi.jpg',
                'categories' => ['Geografi', 'Sains dan Teknologi'],
                'authors' => ['Nurin Rochayati, S.Pd., M.Pd.', 'Dra. Agung Pramunarti, M.Si.'],
                'description' => 'SKU: DP02674A · Berat buku: 0,2 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Hukum Waris Islam',
                'isbn' => '978-0-00-000029-3', 'year' => null, 'stock' => 4, 'cover' => 'hukum-waris-islam.jpg',
                'categories' => ['Hukum', 'Agama Islam', 'Hukum Adat'],
                'authors' => ['Dr. Iman Jauhari, S.H., M.Hum.', 'Dr. T. Muhammad Ali Bahar, S.H., M.Kn.'],
                'description' => 'Editor: Dr. Azhari Yahya, S.H., M.C.L., M.A. SKU: DP05273A · Berat buku: 0,3 kg.',
            ],
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Islam & Sains: Paradigma Integrasi',
                'isbn' => '978-0-00-000030-9', 'year' => null, 'stock' => 3, 'cover' => 'islam-dan-sains.jpg',
                'categories' => ['Agama Islam'],
                'authors' => ['M. Anugrah Arifin'],
                'description' => 'SKU: DP02496A · Berat buku: 0,2 kg.',
            ],

            // ---- Tambahan koleksi: keislaman dan ekonomi Islam ----
            [
                // ISBN sementara: belum tercantum pada data sumber
                'title' => 'Menelusuri Jejak Islam Nusantara',
                'isbn' => '978-0-00-000031-6', 'year' => null, 'stock' => 4, 'cover' => 'jejak-islam-nusantara.jpg',
                'categories' => ['Agama Islam', 'Sejarah Islam'],
                'authors' => ['Dr. KH. Ahmad Mukri Aji, M.A.', 'Dr. Syarifah Gustiawati Mukri, M.E.I.', 'Zahrotunni\'mah, S.Ag., M.S.I.'],
                'description' => 'SKU: DP04524A · Berat buku: 0,3 kg.',
            ],
            [
                'title' => 'Pemikiran Ekonomi Sufi: Dari Hasan Al-Bashri Sampai Shihab Al-Din Suhrawardi',
                'isbn' => '978-634-01-3266-3', 'year' => 2026, 'stock' => 3, 'cover' => 'pemikiran-ekonomi-sufi.jpg',
                'categories' => ['Ekonomi', 'Ekonomi Islam'],
                'authors' => ['Dr. H. A. Luthfi Hamidi, M.Ag.', 'Rahmatulloh, S.Sy, M.E.', 'Havis Aravik, M.S.I, M.M.'],
                'description' => 'Berat buku: 0,3 kg.',
            ],
            [
                'title' => 'Integrative Maqāṣid-Based Human Development Theory: Rekonstruksi Paradigma Pembangunan Manusia Berbasis Nilai, Etika, dan Maqāṣid Al-sharīʿah',
                'isbn' => '978-634-01-3228-1', 'year' => 2026, 'stock' => 5, 'cover' => 'maqasid-human-development.jpg',
                'categories' => ['Agama Islam'],
                'authors' => ['Dr. Abd. Kadir Arno, S.E.Sy., M.Si.'],
                'description' => 'Berat buku: 0,2 kg.',
            ],
        ];

        $books = collect($catalog)->map(function (array $item) use ($publishers, $categories) {
            $book = Book::create([
                'publisher_id' => $publishers[$item['publisher'] ?? 'deepublish']->id,
                'title' => $item['title'],
                'isbn' => $item['isbn'],
                'published_year' => $item['year'],
                'total_stock' => $item['stock'],
                'stock' => $item['stock'],
                'is_active' => true,
                'description' => $item['description'],
                'cover_path' => $this->storeCover($item['cover']),
            ]);

            $authorIds = collect($item['authors'])
                ->map(fn ($name) => Author::firstOrCreate(['name' => $name])->id)
                ->all();
            $book->authors()->attach($authorIds);

            $book->categories()->attach(
                collect($item['categories'])->mapWithKeys(fn ($name) => [$categories[$name]->id => ['added_at' => now()]])->all()
            );

            return $book;
        })->values();

        // ---- Circulation Context: contoh peminjaman melalui LoanService (transaction + event) ----
        $loanService = app(LoanService::class);

        foreach ($members->take(4) as $i => $member) {
            $loanService->borrow($member->id, [
                ['book_id' => $books[$i * 2]->id, 'quantity' => 1],
                ['book_id' => $books[$i * 2 + 1]->id, 'quantity' => 1],
            ]);
        }

        // Satu peminjaman dibuat terlambat agar denda terlihat saat pengembalian
        $late = $loanService->borrow($members[5]->id, [['book_id' => $books[8]->id, 'quantity' => 1]]);
        Loan::whereKey($late->id)->update([
            'borrowed_at' => now()->subDays(12),
            'due_at' => now()->subDays(5),
        ]);

        // Akun user demo dipakai anggota ber-pinjaman terlambat agar "Pinjaman Saya" langsung terisi
        if ($demoUser = User::where('email', 'user@example.com')->first()) {
            $members[5]->update(['user_id' => $demoUser->id]);
        }
    }

    /** Salin sampul dari database/seeders/covers ke disk "public"; null jika file tidak ada. */
    private function storeCover(string $file): ?string
    {
        $source = database_path('seeders/covers/'.$file);

        if (! is_file($source)) {
            return null;
        }

        Storage::disk('public')->put('covers/'.$file, file_get_contents($source));

        return 'covers/'.$file;
    }

    /** Pastikan public/storage tertaut agar sampul bisa dibuka dari browser. */
    private function ensureStorageLink(): void
    {
        if (! is_link(public_path('storage')) && ! file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }
    }
}
