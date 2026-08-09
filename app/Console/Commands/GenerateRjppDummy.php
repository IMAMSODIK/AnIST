<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mpdf\Mpdf;
use Faker\Factory as Faker;

class GenerateRjppDummy extends Command
{
    protected $signature = 'rjpp:generate-dummy
        {--company=PT PERURI (Persero) : Nama perusahaan}
        {--period=2025-2029 : Periode RJPP}
        {--pages=500 : Target minimal halaman}
        {--out=docs/strategic-reference/RJPP_PT_PERURI_2025-2029_DUMMY.pdf : Path output PDF}';

    protected $description = 'Generate dokumen dummy RJPP BUMN (PDF) untuk development & testing Strategic Recommendation AI. Dokumen sepenuhnya sintetis.';

    private $faker;
    private $company;
    private $period;
    private $startYear;
    private $endYear;

    public function handle(): int
    {
        $this->faker = Faker::create('id_ID');
        $this->company = $this->option('company');
        $this->period = $this->option('period');
        [$this->startYear, $this->endYear] = explode('-', $this->period);

        $outPath = base_path($this->option('out'));
        $dir = dirname($outPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->info("Membuat dokumen RJPP sintetis: {$this->company} ({$this->period})");
        $this->info("Target minimal halaman: ".$this->option('pages'));

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 25,
            'margin_right' => 20,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'orientation' => 'P',
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);
        if (! is_dir(storage_path('app/mpdf-tmp'))) {
            mkdir(storage_path('app/mpdf-tmp'), 0777, true);
        }

        $mpdf->SetTitle("RJPP {$this->company} {$this->period} (DUMMY)");
        $mpdf->SetAuthor('KPI Advisor - Document Generator');
        $mpdf->SetSubject('Rencana Jangka Panjang Perusahaan (Dokumen Sintetis)');

        $this->writeCoverPage($mpdf);
        $this->writeApprovalPage($mpdf);
        $this->writeTableOfContents($mpdf);
        $this->writeAbbreviationPage($mpdf);
        $this->writeExecutiveSummary($mpdf);

        $this->writeChapterPendahuluan($mpdf);
        $this->writeChapterProfil($mpdf);
        $this->writeChapterKondisiInternal($mpdf);
        $this->writeChapterKondisiEksternal($mpdf);
        $this->writeChapterAnalisisStrategis($mpdf);
        $this->writeChapterRencanaStrategis($mpdf);
        $this->writeChapterRencanaKerjaAnggaran($mpdf);
        $this->writeChapterManajemenRisiko($mpdf);
        $this->writeChapterPenutup($mpdf);

        $this->writeLampiranA_RKT($mpdf);
        $this->writeLampiranB_SKAI($mpdf);
        $this->writeLampiranC_Roadmap($mpdf);
        $this->writeLampiranD_LaporanKeuangan($mpdf);
        $this->writeLampiranE_Organisasi($mpdf);
        $this->writeLampiranF_Regulasi($mpdf);

        // Padding agar mencapai target halaman minimum
        $this->padToTargetPages($mpdf, (int) $this->option('pages'));

        $pageCount = $mpdf->page;
        $mpdf->Output($outPath, \Mpdf\Output\Destination::FILE);

        $this->info("Selesai. Halaman tercapai: {$pageCount}");
        $this->info("File: {$outPath}");

        return self::SUCCESS;
    }

    // ---------- helpers ----------

    private function h(string $html, bool $newPage = false): string
    {
        return ($newPage ? '<pagebreak />' : '').$html;
    }

    private function randomUniqueInitiatives(int $n): array
    {
        $verbs = ['Implementasi', 'Pengembangan', 'Peningkatan', 'Optimalisasi', 'Integrasi', 'Transformasi', 'Modernisasi', 'Digitasi', 'Audit', 'Penyusunan'];
        $objects = ['Sistem Mutu', 'Sistem Layanan', 'ERP Core', 'Data Warehouse', 'Customer Portal', 'Mobile Apps', 'Cyber Security', 'ISO 27001', 'Suplai Rendemen', 'KLBI', 'Layanan Reimburse', 'Timbangan Logam', 'BAST Digital', 'Pakta Integritas Digital', 'Sistem Pendukung Payroll', 'Sistem Aset Tetap', 'Perizinan Online', 'Helpdesk ITSM', 'Network Operation Center', 'Core Banking', 'Single Note Inspection', 'AI Vision Inspection', 'Blockhain Traceability'];
        $phases = ['', ' Tahap I', ' Tahap II', ' Fase Awal', ' Skala Perusahaan', ' End-to-End', ' Berbasis AI', ' Hibrida'];

        $seen = [];
        $out = [];
        $maxAttempts = 10000;
        $attempts = 0;
        while (count($out) < $n && $attempts < $maxAttempts) {
            $attempts++;
            $candidate = $this->faker->randomElement($verbs).' '.$this->faker->randomElement($objects).$this->faker->randomElement($phases);
            $key = strtolower($candidate);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $candidate;
        }

        while (count($out) < $n) {
            $out[] = 'Inisiatif Tambahan #'.(count($out) + 1);
        }

        return $out;
    }

    private function writeCoverPage(Mpdf $mpdf): void
    {
        $html = '
        <style>
            .cover { text-align:center; padding-top:160px; }
            .cover h1 { font-size:30pt; color:#1f2937; margin-top:20px; }
            .cover h2 { font-size:20pt; color:#4F46E5; margin-top:30px; }
            .cover h3 { font-size:14pt; color:#374151; margin-top:60px; }
            .cover .logo { width:120px; margin:0 auto 40px; border:3px solid #4F46E5; border-radius:50%; padding:25px; font-weight:bold; font-size:34pt; color:#4F46E5;}
            .footer-klasifikasi { margin-top:200px; text-align:right; color:#6b7280; font-size:10pt; }
        </style>
        <div class="cover">
            <div class="logo">DUMMY</div>
            <h1>RENCANA JANGKA PANJANG PERUSAHAAN</h1>
            <h2>'.htmlspecialchars($this->company).'</h2>
            <h3>PERIODE '.$this->period.'</h3>
            <div class="footer-klasifikasi">
                Dokumen Sintetis (Dummy) - KPI Advisor Development<br/>
                Tanggal: '.now()->format('d F Y').'<br/>
                Klasifikasi: Public (Sintetis)
            </div>
        </div>';
        $mpdf->WriteHTML($html);
        $mpdf->AddPage();
    }

    private function writeApprovalPage(Mpdf $mpdf): void
    {
        $html = '
        <h2 style="text-align:center;">HALAMAN PENGESAHAN</h2>
        <p style="text-align:center;">Rencana Jangka Panjang Perusahaan '.$this->company.' Periode '.$this->period.'</p>
        <p style="text-align:center; margin-bottom:30px;">Disahkan oleh:</p>
        <table style="width:100%; border-collapse:collapse;" border="0">
            <tr>
                <td style="width:33%; text-align:center; padding-bottom:80px;">
                    <b>Direktur Utama</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="width:34%; text-align:center; padding-bottom:80px;">
                    <b>Direktur SDM</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="width:33%; text-align:center; padding-bottom:80px;">
                    <b>Direktur Operasional</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
            </tr>
            <tr>
                <td style="text-align:center;">
                    <b>Direktur Keuangan</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="text-align:center;">
                    <b>Direktur Perencanaan</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="text-align:center;">
                    <b>Sekretaris Perusahaan</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
            </tr>
        </table>
        <p style="text-align:center; margin-top:30px;">Jakarta, '.now()->format('d F Y').'</p>
        <p style="text-align:center; margin-top:40px; font-style:italic;">Doc ID: RJPP-DUMMY-'.$this->startYear.'-'.$this->endYear.'-PUM-V1.0</p>
        <p style="text-align:center; font-size:9pt; color:#6b7280;">Catatan: Dokumen ini adalah dokumen sintetis untuk keperluan development & testing sistem KPI Advisor. Seluruh nama, tanggal, angka bersifat fiktif.</p>';
        $mpdf->WriteHTML($html);
    }

    private function writeTableOfContents(Mpdf $mpdf): void
    {
        $toc = [
            ['BAB I', 'PENDAHULUAN', 9],
            ['1.1', 'Latar Belakang', 9],
            ['1.2', 'Maksud dan Tujuan', 12],
            ['1.3', 'Ruang Lingkup', 13],
            ['1.4', 'Sistematika Penulisan', 13],
            ['BAB II', 'PROFIL PERUSAHAAN', 15],
            ['2.1', 'Sejarah dan Pendirian', 15],
            ['2.2', 'Kedudukan Hukum dan Tugas Pokok', 18],
            ['2.3', 'Visi, Misi, dan Nilai-Nilai', 20],
            ['2.4', 'Struktur Organisasi', 23],
            ['2.5', 'Unit Kerja', 25],
            ['BAB III', 'KONDISI INTERNAL PERUSAHAAN', 28],
            ['3.1', 'Kelembagaan', 28],
            ['3.2', 'Sumber Daya Manusia', 31],
            ['3.3', 'Keuangan', 38],
            ['3.4', 'Operasional dan Produksi', 45],
            ['3.5', 'Pemasaran', 52],
            ['3.6', 'Teknologi Informasi', 58],
            ['3.7', 'Tata Kelola', 64],
            ['3.8', 'Manajemen Risiko Internal', 68],
            ['BAB IV', 'KONDISI LINGKUNGAN EKSTERNAL', 72],
            ['4.1', 'Makro Ekonomi', 72],
            ['4.2', 'Industri', 78],
            ['4.3', 'Pesaing', 84],
            ['4.4', 'Regulasi', 88],
            ['4.5', 'SWOT Analysis', 92],
            ['BAB V', 'ANALISIS STRATEGIS', 98],
            ['5.1', "Porter's Five Forces", 98],
            ['5.2', 'PESTEL Analysis', 102],
            ['5.3', 'Value Chain Analysis', 106],
            ['5.4', 'VRIO Analysis', 109],
            ['5.5', 'Balanced Scorecard Baseline', 113],
            ['BAB VI', 'RENCANA STRATEGIS', 117],
            ['6.1', 'Arah Strategi', 117],
            ['6.2', 'Tujuan Jangka Panjang', 120],
            ['6.3', 'Sasaran Strategis', 123],
            ['6.4', 'Indikator Kinerja Kunci (KPI)', 127],
            ['6.5', 'Inisiatif Strategis', 156],
            ['6.6', 'Roadmap 5 Tahun', 168],
            ['BAB VII', 'RENCANA KERJA DAN ANGGARAN', 175],
            ['7.1', 'Program Prioritas', 175],
            ['7.2', 'Anggaran per Tahun', 180],
            ['7.3', 'Sumber Pendanaan', 188],
            ['BAB VIII', 'MANAJEMEN RISIKO', 192],
            ['BAB IX', 'PENUTUP', 200],
            ['LAMPIRAN A', 'Rencana Kerja Tahunan (RKT) per Tahun', 202],
            ['LAMPIRAN B', 'Daftar Sasaran Kinerja dan Inisiatif', 240],
            ['LAMPIRAN C', 'Roadmap Inisiatif Strategis', 280],
            ['LAMPIRAN D', 'Laporan Keuangan Historis (5 Tahun)', 320],
            ['LAMPIRAN E', 'Struktur Organisasi & Job Grade', 380],
            ['LAMPIRAN F', 'Regulasi', 440],
        ];

        $html = '<pagebreak /><h2 style="text-align:center;">DAFTAR ISI</h2><br/><table style="width:100%; font-size:11pt;">';
        foreach ($toc as $row) {
            $html .= '<tr><td style="width:15%;">'.htmlspecialchars($row[0]).'</td>'
                .'<td style="width:75%;">'.htmlspecialchars($row[1]).'</td>'
                .'<td style="width:10%; text-align:right;">'.$row[2].'</td></tr>';
        }
        $html .= '</table>';
        $mpdf->WriteHTML($html);
    }

    private function writeAbbreviationPage(Mpdf $mpdf): void
    {
        $items = [
            'BUMN' => 'Badan Usaha Milik Negara', 'Kemenkeu' => 'Kementerian Keuangan', 'RJPP' => 'Rencana Jangka Panjang Perusahaan',
            'RBB' => 'Rencana Bisnis dan Anggaran', 'RKT' => 'Rencana Kerja dan Anggaran Tahunan', 'ISK' => 'Indikator Sasaran Kinerja',
            'IKK' => 'Indikator Kinerja Kunci', 'KPI' => 'Key Performance Indicator', 'PE' => 'Persetujuan Emiten',
            'BSC' => 'Balanced Scorecard', 'MPTI' => 'Master Plan Teknologi Informasi', 'SAKIP' => 'Sistem Akuntabilitas Kinerja',
            'OASE' => 'Organisasi, Akuntabilitas, Sistem, Etika', 'GCG' => 'Good Corporate Governance', 'isk' => 'Program Inovasi',
            'TI' => 'Teknologi Informasi', 'RnD' => 'Research and Development', 'ROE' => 'Return on Equity',
            'ROA' => 'Return on Assets', 'EBITDA' => 'Earnings Before Interest Taxes Depreciation Amortization',
            'EVP' => 'Eksekutif Vice President', 'SVP' => 'Senior Vice President', 'VP' => 'Vice President',
            'ISKI' => 'Indikator Sasaran Kinerja Individu', 'TIK' => 'Teknologi Informasi dan Komunikasi',
            'BSO' => 'Business Services Office', 'KPIB' => 'Kinerja Pelayanan Bisnis', 'SLA' => 'Service Level Agreement',
            'PMO' => 'Project Management Office', 'ETL' => 'Extract Transform Load', 'ERP' => 'Enterprise Resource Planning',
        ];
        $html = '<pagebreak /><h2 style="text-align:center;">DAFTAR SINGKATAN</h2><br/><table style="width:100%; font-size:10pt;" border="0">';
        $i = 0;
        foreach ($items as $abbr => $exp) {
            if ($i % 2 === 0) {
                $html .= '<tr>';
            }
            $html .= '<td style="width:12%;"><b>'.htmlspecialchars($abbr).'</b></td><td style="width:38%;">'.htmlspecialchars($exp).'</td>';
            if ($i % 2 === 1) {
                $html .= '</tr>';
            }
            $i++;
        }
        if ($i % 2 === 1) {
            $html .= '<td colspan="2"></td></tr>';
        }
        $html .= '</table>';
        $mpdf->WriteHTML($html);
    }

    private function writeExecutiveSummary(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h2 style="text-align:center;">RINGKASAN EKSEKUTIF</h2>';
        $paras = [
            'Rencana Jangka Panjang Perusahaan (RJPP) '.$this->company.' periode '.$this->period.' merupakan dokumen perencanaan strategis lima tahunan yang menjadi landasan arah pengembangan usaha sektor pengelolaan percetakan uang dan logam. Dokumen ini disusun dengan mempertimbangkan dinamika makroekonomi, perubahan regulasi, perkembangan teknologi, serta tantangan internal perusahaan.',
            'Dalam periode '.$this->period.', perusahaan menetapkan tiga pilar strategi utama: (i) Modernisasi Proses Produksi, (ii) Diversifikasi Layanan, dan (iii) Penguatan Tata Kelola Berbasis Digital. Pilar-pilar tersebut diturunkan ke dalam tujuh sasaran strategis dengan dua puluh lima Indikator Kinerja Kunci (KPI) yang akan dipantau secara berkala.',
            'Total kebutuhan investasi strategis untuk pencapaian RJPP periode ini diperkirakan sebesar Rp 2,4 triliun, didanai melalui laba ditahan, kerjasama strategis, dan pinjaman jangka panjang. Estimasi tingkat pengembalian investasi agregat adalah 12,4% dengan payback period rata-rata 4,2 tahun.',
            'Manajemen risiko periode '.$this->period.' mengidentifikasi 47 risiko utama, dengan 8 risiko berada pada level ekstrem. Mitigasi prioritas diberikan pada risiko keamanan siber, ketergantungan bahan baku, dan disrupsi rantai pasok.',
            'Dokumen ini bersifat sintetis (DUMMY) dan dirancang untuk keperluan pengembangan dan pengujian sistem KPI Advisor. Seluruh data, angka, dan narasi bersifat fiktif namun mengikuti struktur standar dokumen RJPP BUMN.',
        ];
        foreach ($paras as $p) {
            $html .= '<p style="text-align:justify; text-indent:30px; line-height:1.6;">'.htmlspecialchars($p).'</p>';
        }
        $mpdf->WriteHTML($html);
    }

    // ---------- BAB I s.d. IX ----------

    private function writeChapterPendahuluan(Mpdf $mpdf): void
    {
        $sections = [
            '1.1 Latar Belakang' => [
                'Perkembangan lingkungan strategis nasional dan global pada periode '.$this->period.' menuntut perusahaan untuk lebih adaptif terhadap perubahan. Transformasi digital, perubahan preferensi pelanggan, serta regulasi baru di sektor keuangan dan perdagangan menempatkan perusahaan pada titik kritis strategis. Konsep pengelolaan percetakan uang dan logam tidak lagi sebatas produksi, melainkan mencakup rantai nilai ekosistem digital.',
                'PT PERURI (Persero) sebagai pelaksana tugas negara di bidang percetakan dan pengelolaan uang logam menghadapi tantangan unik: dual-peran sebagai entitas komersial sekaligus pelaksana amanat publik. Hal ini mendorong perlunya perencanaan jangka panjang yang menyeimbangkan aspek komersial, akuntabilitas, dan tata kelola.',
                'Dalam konteks tersebut, RJPP disusun sebagai panduan strategis lima tahunan yang menjembatani visi jangka panjang perusahaan dengan realitas operasional tahunan melalui RKB (Rencana Kerja dan Anggaran). RJPP '.$this->period.' menggantikan RJPP periode sebelumnya dan disusun dengan mengakomodasi dinamika pasca-pandemi serta perkembangan teknologi generasi keempat dan kelima.',
            ],
            '1.2 Maksud dan Tujuan' => [
                'Maksud penyusunan RJPP adalah memberikan arah strategis perusahaan dalam jangka panjang lima tahun yang terukur, terintegrasi, dan dapat dipertanggungjawabkan.',
                'Tujuan: (1) Menetapkan tujuan strategis dan sasaran kinerja yang terukur; (2) Mengidentifikasi inisiatif strategis prioritas; (3) Memberikan kerangka penganggaran lima tahunan; (4) Mengukur pencapaian melalui indikator kinerja; (5) Menjadi dasar penyusunan RKB tahunan.',
            ],
            '1.3 Ruang Lingkup' => [
                'Ruang lingkup RJPP mencakup seluruh unit kerja perusahaan, anak perusahaan, dan kerjasama strategis periode '.$this->period.'. Cakupan meliputi aspek kelembagaan, SDM, keuangan, operasional, pemasaran, teknologi informasi, tata kelola, dan manajemen risiko.',
            ],
            '1.4 Sistematika Penulisan' => [
                'Dokumen disusun dalam sembilan bab dan enam lampiran. Setiap bab memiliki sub-bab analitis yang menjawab aspek strategis tertentu, dari profil perusahaan hingga rencana kerja dan anggaran.',
            ],
        ];

        $this->writeChapter($mpdf, 'BAB I PENDAHULUAN', $sections);
    }

    private function writeChapterProfil(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB II PROFIL PERUSAHAAN</h1>';
        $html .= '<h2>2.1 Sejarah dan Pendirian</h2>';
        $html .= $this->paragraphs([
            'PT PERURI (Persero) didirikan berdasarkan Peraturan Pemerintah Nomor 32 Tahun 1969 jo. Peraturan Pemerintah Nomor 16 Tahun 2011. Perusahaan berkedudukan di Jakarta dengan fungsi utama melaksanakan tugas negara di bidang percetakan uang dan logam.',
            'Sejak berdiri hingga periode '.$this->period.' telah dilakukan beberapa kali restrukturisasi signifikan. Tahun 2018 dilakukan transformasi dari lembaga negara men menjadi perusahaan komersial dengan pendekatan korporasi modern. Tahun 2020, dilakukan reorganisasi unit kerja untuk menyelaraskan struktur dengan strategi digital.',
            'Tahun 2022, perusahaan memperkenalkan logo dan identitas baru sebagai simbolik transformasi. Pada tahun 2024 dilakukan integrasi unit usaha logam dengan unit percetakan untuk efisiensi end-to-end.',
        ]);
        $html .= '<h2>2.2 Kedudukan Hukum dan Tugas Pokok</h2>';
        $html .= $this->paragraphs([
            'Kedudukan hukum perusahaan diatur dalam Peraturan Pemerintah sebagai Perusahaan Umum Negara. Tugas pokok meliputi: mencetak uang rupiah, kertas berharga, dokumen negara, serta mengelola uang logam dan logam mulia.',
            'Fungsi turunan mencakup jasa percetakan sekuriti, sertifikasi dokumen, layanan digital terpercaya, dan layanan turunan lain sesuai amanat regulator.',
        ]);
        $html .= '<h2>2.3 Visi, Misi, dan Nilai-Nilai</h2>';
        $html .= '<p><b>Visi:</b> Menjadi penyelenggara rumah percetakan uang dan logam terdepan di Asia Tenggara yang dipercaya publik.</p>';
        $html .= '<p><b>Misi:</b></p><ul>';
        $misison = ['Menyelenggarakan produksi uang dan logam berkualitas tinggi dengan standar internasional.', 'Mengembangkan layanan digital terpercaya untuk ekosistem keuangan negara.', 'Mengelola SDM profesional dan tata kelola berintegritas.', 'Memberikan nilai tambah bagi pemangku kepentingan secara berkelanjutan.'];
        foreach ($misison as $m) {
            $html .= '<li>'.htmlspecialchars($m).'</li>';
        }
        $html .= '</ul>';
        $html .= '<p><b>Nilai-Nilai:</b> Integritas, Profesionalitas, Inovasi, Sinergi, dan Akuntabilitas (IPISA).</p>';
        $html .= '<h2>2.4 Struktur Organisasi</h2>';
        $html .= $this->paragraphs(['Struktur organisasi perusahaan terdiri dari Direktur Utama, Direktur Operasional, Direktur SDM, Direktur Keuangan, Direktur Perencanaan, dan Sekretaris Perusahaan. Setiap direktorat membawahi beberapa divisi, departemen, dan seksi.', 'Unit pengawasan internal berada pada Komisaris dan Komite Audit. Selain itu terdapat SPI (Satuan Pengawasan Internal) sebagai unit independen.', 'Total jumlah pegawai pada akhir periode sebelumnya adalah '.$this->faker->numberBetween(3500, 4200).' orang tersebar di kantor pusat dan beberapa cabang operasional.']);
        $html .= '<h2>2.5 Unit Kerja</h2>';
        $html .= '<p>Berikut adalah daftar unit kerja utama perusahaan:</p>';
        $html .= $this->smallTable(['Kode', 'Nama Unit Kerja', 'Tugas Utama'], [
            ['DKP-01', 'Direktorat Keuangan', 'Pengelolaan keuangan, treasury, dan anggaran'],
            ['DOP-02', 'Direktorat Operasional', 'Produksi, mutu, dan distribusi'],
            ['DSM-03', 'Direktorat SDM', 'Manajemen SDM, talent, dan budaya'],
            ['DPL-04', 'Direktorat Perencanaan', 'Strategi, investasi, dan inovasi'],
            ['SPI-05', 'Satuan Pengawasan Internal', 'Audit dan pengendalian internal'],
            ['ITC-06', 'Divisi Teknologi Informasi', 'Aplikasi, infrastruktur, dan keamanan digital'],
            ['PRD-07', 'Divisi Produksi', 'Pencetakan uang kertas dan logam'],
            ['MUT-08', 'Divisi Mutu', 'Quality control, standarisasi, dan laboratorium'],
            ['MKT-09', 'Divisi Pemasaran', 'Layanan komersial dan pengembangan pasar'],
            ['RND-10', 'Divisi RnD', 'Riset material dan pengembangan produk'],
        ]);
        $mpdf->WriteHTML($html);
    }

    private function writeChapterKondisiInternal(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB III KONDISI INTERNAL PERUSAHAAN', [
            '3.1 Kelembagaan' => [
                'Aspek kelembagaan perusahaan telah melalui beberapa kali penataan ulang. Kinerja tata kelola dinilai melalui penilaian GCG tahunan yang mencakup transparansi, akuntabilitas, pertanggungjawaban, kemandirian, dan keadilan.',
                'Pada periode lalu, nilai rata-rata GCG adalah '.$this->faker->randomFloat(2, 80, 92).'. Penguatan komite audit dan komite manajemen risiko telah dilakukan untuk meningkatkan tingkat kepatuhan.',
            ],
            '3.2 Sumber Daya Manusia' => $this->hrmNarratives(),
            '3.3 Keuangan' => $this->financeNarratives(),
            '3.4 Operasional dan Produksi' => $this->operationsNarratives(),
            '3.5 Pemasaran' => [
                'Portofolio layanan perusahaan terdiri dari layanan inti (cetak uang, logam, dokumen) dan layanan turunan (digital certificate, secure document, OMR, dan lainnya).',
                'Kontribusi pendapatan layanan inti '.$this->faker->numberBetween(65, 78).'% dan layanan turunan '.$this->faker->numberBetween(22, 35).'%. Strategi diversifikasi menargetkan peningkatan kontribusi turunan hingga 40% pada akhir periode.',
            ],
            '3.6 Teknologi Informasi' => $this->itNarratives(),
            '3.7 Tata Kelola' => $this->governanceNarratives(),
            '3.8 Manajemen Risiko Internal' => $this->riskNarratives(),
        ]);
    }

    private function hrmNarratives(): array
    {
        $years = range($this->startYear - 6, $this->startYear - 1);
        $rows = [];
        foreach ($years as $y) {
            $rows[] = [(string) $y, $this->faker->numberBetween(3500, 4200), $this->faker->numberBetween(180, 260), $this->faker->randomFloat(2, 1.8, 3.6).'%', $this->faker->randomFloat(2, 78, 92).'%'];
        }
        $table = $this->smallTable(['Tahun', 'Total SDM', 'Rekrutmen', 'Turnover', 'Training Coverage'], $rows);

        return [
            'Komposisi SDM perusahaan hingga akhir periode sebelumnya tercatat sekitar '.$this->faker->numberBetween(3500, 4200).' pegawai, terdiri dari pegawai tetap, pegawai tidak tetap, tenaga ahli, dan mitra kerja.',
            'Proyeksi kebutuhan SDM periode '.$this->period.' mempertimbangkan rencana pensiun, ekspansi unit digital, dan efisiensi operasional. Total kebutuhan sekitar 200-400 pegawai baru per tahun.',
            'Tabel ringkasan SDM 5 tahun terakhir:'.$table,
        ];
    }

    private function financeNarratives(): array
    {
        $years = range($this->startYear - 6, $this->startYear - 1);
        $rows = [];
        foreach ($years as $y) {
            $revenue = $this->faker->numberBetween(1500, 2800);
            $netProfit = $this->faker->numberBetween(80, 340);
            $rows[] = [$y, $revenue, $this->faker->numberBetween(1100, 2100), $netProfit, $this->faker->randomFloat(2, 3, 14).'%'];
        }
        $table = $this->smallTable(['Tahun', 'Pendapatan (M)', 'Beban (M)', 'Laba Bersih (M)', 'NPM %'], $rows);

        return [
            'Kinerja keuangan historis 5 tahun menunjukkan pertumbuhan pendapatan rata-rata '.$this->faker->randomFloat(2, 4, 9).'% per tahun. Margin laba bersih berada pada rentang '.$this->faker->randomFloat(2, 4, 13).'% hingga '.$this->faker->randomFloat(2, 10, 16).'%.',
            'Struktur permodalan perusahaan didominasi modal sendiri dengan rasio DAR '.$this->faker->randomFloat(2, 14, 35).'%.',
            'Tabel ringkasan keuangan 5 tahun (dalam Miliar Rupiah):'.$table,
            'Capex historis mencapai Rp '.$this->faker->numberBetween(180, 480).' miliar per tahun, didominasi investasi mesin produksi dan teknologi informasi.',
        ];
    }

    private function operationsNarratives(): array
    {
        $years = range($this->startYear - 6, $this->startYear - 1);
        $rows = [];
        foreach ($years as $y) {
            $rows[] = [$y, $this->faker->numberBetween(1200, 2400).' jtk', $this->faker->numberBetween(180, 320).' jt', $this->faker->randomFloat(2, 94, 99).'%', $this->faker->numberBetween(2, 9)];
        }
        $table = $this->smallTable(['Tahun', 'Produksi', 'Volume Logam', 'Yield %', 'Defect (PPM)'], $rows);

        return [
            'Kapasitas produksi uang kertas mencapai '.$this->faker->numberBetween(1200, 2400).' juta lembar per tahun, sedangkan kapasitas uang logam '.$this->faker->numberBetween(180, 320).' juta keping per tahun.',
            'Tingkat utilization rata-rata '.$this->faker->randomFloat(2, 65, 88).'%. Peningkatan kapasitas direncanakan melalui modernisasi mesin tahap kedua.',
            'Tabel ringkasan produksi 5 tahun:'.$table,
            'Sertifikasi mutu mencakup ISO 9001, ISO 14001, ISO 27001, dan OHSAS 18001. Audit eksternal dilakukan oleh lembaga independen.',
        ];
    }

    private function itNarratives(): array
    {
        $apps = [
            ['ERP Core', 'Oracle EBS', '2016', 'VP-TI', 'Maintenance'],
            ['Helpdesk', 'Internal', '2019', 'Divisi TI', 'Pengembangan'],
            ['GIS Logistik', 'Custom', '2020', 'Divisi Operasional', 'Maintenance'],
            ['Customer Portal', 'Custom', '2021', 'Divisi Pemasaran', 'Pengembangan'],
            ['Mobile Apps', 'Flutter', '2022', 'Divisi Pemasaran', 'Pengembangan'],
            ['Cyber SOC', 'SentinelOne', '2021', 'Divisi TI', 'Maintenance'],
            ['BI Dashboard', 'Power BI', '2020', 'Divisi Perencanaan', 'Maintenance'],
            ['Pakta Integritas Digital', 'Internal', '2023', 'Risk Management', 'Pengembangan'],
            ['Sistem Reimburse', 'Custom', '2024', 'Divisi SDM', 'Pengembangan'],
            ['Sistem Payroll', 'Custom', '2024', 'Divisi SDM', 'Pengembangan'],
        ];
        $table = $this->smallTable(['Aplikasi', 'Platform', 'Tahun', 'PIC', 'Status'], $apps);

        return [
            'Lingkungan teknologi informasi perusahaan mencakup 80+ aplikasi lintas direktorat. Tabel ringkasan aplikasi strategis:'.$table,
            'Status integrasi saat ini masih bersifat silo. Direktorat TI telah memulai program integrasi melalui API Gateway dan Master Data Management (MDM).',
            'Kekuatan TI: layanan helpdesk responsif, regional data center, dan kapabilitas SOC. Kelemahan: ketergantungan vendor ERP, keterbatasan kapasitas insight data untuk decision making.',
            'Roadmap prioritas MPTI '.$this->period.' melakukan modernisasi ERP, implementasi data warehouse enterprise, dan AI vision inspection untuk kontrol mutu produksi.',
        ];
    }

    private function governanceNarratives(): array
    {
        return [
            'Tata kelola perusahaan mengacu pada Pedoman GCG terbaru yang diatur berdasarkan PER-01/MBU/2011 jo. PER-09/MBU/2012.',
            'Komponen tata kelola meliputi: Komisaris, Direksi, Komite Audit, Komite Manajemen Risiko, Komite Nominasi & Remunerasi, SPI, dan Sekretariat Perusahaan.',
            'Indikator tata kelola mencakup: transparansi, akuntabilitas, responsibilitas, kemandirian, dan keadilan. Penilaian agregat tahun lalu '.$this->faker->randomFloat(2, 78, 92).'.',
            'Whistleblowing system (WBS) telah dijalankan melalui saluran independen dengan tingkat case resolution '.$this->faker->randomFloat(2, 75, 95).'%.',
        ];
    }

    private function riskNarratives(): array
    {
        return [
            'Manajemen risiko perusahaan mengacu pada ISO 31000 dan Pedoman Manajemen Risiko BUMN.',
            'Peta risiko tahun lalu mengidentifikasi 47 risiko aktif dengan 8 risiko ekstrem, 16 risiko tinggi, 17 risiko sedang, dan 6 risiko rendah.',
            'Risiko ekstrem meliputi: keamanan siber, ketergantungan bahan baku import, perubahan regulasi pemerintah, dan kebocoran dokumen keamanan.',
            'Mitigasi dilakukan melalui kombinasi penghindaran, pengurangan, transfer, dan penerimaan. Sebagian besar risiko ditangani dengan strategi pengurangan.',
        ];
    }

    private function writeChapterKondisiEksternal(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB IV KONDISI LINGKUNGAN EKSTERNAL', [
            '4.1 Makro Ekonomi' => [
                'Proyeksi pertumbuhan ekonomi Indonesia periode '.$this->period.' diestimasi mencapai rata-rata '.$this->faker->randomFloat(2, 4.5, 5.8).'% per tahun. Inflasi ditargetkan dalam koridor '.$this->faker->randomFloat(2, 2.5, 4).'%.',
                'Kebijakan moneter Bank Indonesia diperkirakan tetap akomodatif dengan suku bunga acuan di rentang '.$this->faker->randomFloat(2, 4, 6.5).'%.',
                'Nilai tukar Rupiah diasumsikan stabil di rentang Rp 14.500 - Rp 16.000 per USD dengan kecenderungan apresiasi bertahap pada periode kedua.',
            ],
            '4.2 Industri' => [
                'Industri percetakan keamanan global tumbuh '.$this->faker->randomFloat(2, 3.5, 6).'% per tahun, didorong permintaan keamanan dokumen digital dan kebutuhan anti-counterfeit.',
                'Tren utama industri: digitalisasi, integrasi keamanan fisik dan digital, dan layanan secure ID berbasis biometrik.',
            ],
            '4.3 Pesaing' => $this->competitorTable(),
            '4.4 Regulasi' => [
                'Regulasi utama yang relevan: UU BUMN, PP terkait Perum, Peraturan Menteri Keuangan tentang percetakan uang, UU PDP, UU ITE, dan regulasi keamanan siber.',
                'Tren regulasi: ketatnya kepatuhan terhadap UU PDP membuat perusahaan harus menata ulang manajemen data pelanggan.',
            ],
            '4.5 SWOT Analysis' => $this->swotNarrative(),
        ]);
    }

    private function competitorTable(): array
    {
        $rows = [
            ['CRANE.', 'USA', 'Pasar global', 'R&D kuat'],
            ['De La Rue', 'UK', 'Global player', 'Brand legacy'],
            ['Giesecke+Devrient', 'Jerman', 'Banknote + ID', 'Skala besar'],
            ['OVD Kinegram', 'Swiss', 'OVD', 'Spesialis foil'],
            ['KOMSCO', 'Korea', 'Asia regional', 'Cost competitive'],
            ['OBERTHUR', 'Prancis', 'ID & Banknote', 'Inovasi digital'],
        ];

        return ['Pemain kunci industri global:'.$this->smallTable(['Pemain', 'Origin', 'Segmen', 'Strength'], $rows)];
    }

    private function swotNarrative(): array
    {
        return [
            '<table style="width:100%; border-collapse:collapse;" border="1"><tr><td style="width:50%; padding:10px;"><b>STRENGTH</b><ul><li>Monopoly tugas negara</li><li>SDM kompeten</li><li>Sertifikasi lengkap</li><li>Infrastruktur TI memadai</li></ul></td><td style="width:50%; padding:10px;"><b>WEAKNESS</b><ul><li>Ketergantungan vendor</li><li>Silo data dan aplikasi</li><li>Proses manual masih dominan</li><li>Agile belum konsisten</li></ul></td></tr><tr><td style="width:50%; padding:10px;"><b>OPPORTUNITY</b><ul><li>Digital ID nasional</li><li>Layanan secure document ekspor</li><li>CBDC</li><li>AI mutu & otomasi</li></ul></td><td style="width:50%; padding:10px;"><b>THREAT</b><ul><li>Cyber ancamanAPT</li><li>Fluktuasi bahan baku</li><li>Regulasi baru ketat</li><li>Disrupsi pasokan</li></ul></td></tr></table>',
        ];
    }

    private function writeChapterAnalisisStrategis(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB V ANALISIS STRATEGIS', [
            "5.1 Porter's Five Forces" => [
                'Five Forces Analysis mencakup: (1) Threat of New Entrants - Rendah, karena barrier tinggi. (2) Bargaining Power of Suppliers - Sedang, karena ketergantungan bahan baku import. (3) Bargaining Power of Buyers - Rendah, karena monopoli negara. (4) Threat of Substitutes - Sedang, trend digitalisasi. (5) Rivalry - Rendah untuk pasar dalam negeri.',
            ],
            '5.2 PESTEL Analysis' => $this->pestelNarrative(),
            '5.3 Value Chain Analysis' => [
                'Aktivitas primer: Produksi, Mutu, Distribusi, Pemasaran, Layanan. Aktivitas pendukung: SDM, TI, Pengadaan, Infrastruktur, GCG.',
                'Penguatan rantai nilai dilakukan pada tahap mutu dan distribusi melalui digitalisasi kontrol mutu dan tracking logistik real-time.',
            ],
            '5.4 VRIO Analysis' => $this->vrioTable(),
            '5.5 Balanced Scorecard Baseline' => $this->bscBaselineTable(),
        ]);
    }

    private function pestelNarrative(): array
    {
        return [
            '<ul><li><b>Political:</b> Kebijakan strategis pemerintah mendukung BUMN. Politik nasional stabil.</li><li><b>Economic:</b> Pertumbuhan 5%, inflasi terkendali, atau stabil.</li><li><b>Social:</b> Peningkatan ekspektasi digitalisasi publik, kebutuhan secure ID.</li><li><b>Technological:</b> AI, blockchain, IoT, dan digital twin semakin matang.</li><li><b>Environmental:</b> ESG reporting menjadi kewajiban, carbon disclosure.</li><li><b>Legal:</b> UU PDP, UU ITE, Permen BUMN sektoral.</li></ul>',
        ];
    }

    private function vrioTable(): array
    {
        $rows = [
            ['License percetakan uang', 'Y', 'Y', 'Y', 'Y', 'Sustained Competitive Advantage'],
            ['SDM terlatih', 'Y', 'Y', 'N', 'Y', 'Temporary Competitive Advantage'],
            ['Mesin produksi', 'Y', 'Y', 'Y', 'N', 'Unused Competitive Advantage'],
            ['Brand legacy', 'Y', 'Y', 'Y', 'Y', 'Sustained Competitive Advantage'],
            ['Data historis mutu', 'Y', 'N', 'N', 'N', 'Competitive Parity'],
        ];

        return ['VRIO Analysis resource kunci:'.$this->smallTable(['Resource', 'Valuable', 'Rare', 'Inimitable', 'Organized', 'Implication'], $rows)];
    }

    private function bscBaselineTable(): array
    {
        return [
            'Baseline BSC periode sebelumnya:'.$this->smallTable(['Perspective', 'Objective', 'KPI', 'Baseline', 'Target '.$this->endYear], [
                ['Financial', 'Profitabilitas', 'ROE', '10.2%', '14%'],
                ['Financial', 'Pertumbuhan', 'Revenue CAGR', '4.8%', '8%'],
                ['Customer', 'Kepuasan', 'NPS', '45', '65'],
                ['Customer', 'Retensi', 'Retention', '92%', '95%'],
                ['Internal Process', 'Produksi', 'Yield', '96%', '99%'],
                ['Internal Process', 'Delivery', 'On-Time', '88%', '97%'],
                ['Learning', 'Kompetensi', 'Training Hours', '24', '48'],
                ['Learning', 'Inovasi', 'Adopsi inovasi', '30%', '70%'],
            ]),
        ];
    }

    private function writeChapterRencanaStrategis(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB VI RENCANA STRATEGIS</h1>';
        $html .= '<h2>6.1 Arah Strategi</h2>';
        $html .= $this->paragraphs([
            'Arah strategi RJPP '.$this->period.' difokuskan pada tiga pilar utama: (i) Modernisasi Proses Produksi, (ii) Diversifikasi Layanan, (iii) Penguatan Tata Kelola Digital.',
            'Pilar (i) menargetkan peningkatan yield, penurunan defect, dan otomasi mutu menggunakan AI vision inspection.',
            'Pilar (ii) menargetkan pengembangan layanan secure ID, sertifikasi digital, dan ekspor layanan ke negara kawasan regional.',
            'Pilar (iii) menargetkan transformasi data, integrasi GRC, dan penguatan cybersecurity posture.',
        ]);
        $html .= '<h2>6.2 Tujuan Jangka Panjang</h2>';
        $tjp = [
            'TJP-01: Meningkatkan kontribusi laba bersih dari layanan non-inti menjadi minimal 35% pada tahun '.$this->endYear.'.',
            'TJP-02: Mencapai tingkat utilization mesin produksi di atas 90% pada tahun '.$this->endYear.'.',
            'TJP-03: Menjadi perusahaan BUMN dengan tingkat kepatuhan PDP 100% pada tahun '.$this->endYear.'.',
            'TJP-04: Mencapai Trusted Digital Partner untuk layanan secure ID di kawasan ASEAN.',
            'TJP-05: Mengurangi emisi karbon operasional 25% pada tahun '.$this->endYear.' dibanding baseline.',
        ];
        $html .= '<ol>';
        foreach ($tjp as $t) {
            $html .= '<li>'.htmlspecialchars($t).'</li>';
        }
        $html .= '</ol>';
        $html .= '<h2>6.3 Sasaran Strategis</h2>';
        $sasaran = [
            ['SS-01', 'Modernisasi Proses Produksi', 'Internal Process'],
            ['SS-02', 'Diversifikasi Layanan Komersial', 'Customer'],
            ['SS-03', 'Penguatan Tata Kelola Digital', 'Internal Process'],
            ['SS-04', 'Peningkatan Profesionalisme SDM', 'Learning'],
            ['SS-05', 'Optimalisasi Struktur Biaya', 'Financial'],
            ['SS-06', 'Penguatan Cyber Security Posture', 'Internal Process'],
            ['SS-07', 'Sustainability & ESG', 'Customer'],
        ];
        $html .= $this->smallTable(['Kode', 'Sasaran Strategis', 'Perspective (BSC)'], $sasaran);

        $html .= '<h2>6.4 Indikator Kinerja Kunci (KPI)</h2>';
        $html .= $this->paragraphs(['Berikut adalah daftar 25 KPI yang akan dipantau selama periode RJPP '.$this->period.'. Setiap KPI memiliki target tahunan dan terhubung ke sasaran strategis.']);
        $kpiRows = $this->buildKpiRows();
        $html .= $this->smallTable(['Kode', 'Perspective', 'Measurement', 'Formula', 'Unit', 'Target '.$this->endYear, 'Weight'], $kpiRows);

        $html .= '<h2>6.5 Inisiatif Strategis</h2>';
        $html .= $this->paragraphs(['Total 60 inisiatif strategis telah diidentifikasi untuk mendukung pencapaian KPI. Detail inisiatif per KPI dapat dilihat pada Lampiran B.']);
        $initiatives = $this->randomUniqueInitiatives(60);
        $html .= '<ol>';
        foreach ($initiatives as $idx => $init) {
            $html .= '<li>IS-'.str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT).' : '.htmlspecialchars($init).'</li>';
        }
        $html .= '</ol>';

        $html .= '<h2>6.6 Roadmap 5 Tahun</h2>';
        $roadmap = [];
        $phases = ['Tahun 1: Fondasi', 'Tahun 2: Eksekusi', 'Tahun 3: Ekspansi', 'Tahun 4: Optimalisasi', 'Tahun 5: Konsolidasi'];
        $initSubset = array_values($initiatives);
        for ($y = 0; $y < 5; $y++) {
            $year = (int) $this->startYear + $y;
            $items = array_slice($initSubset, $y * 12, 12);
            $roadmap[] = [$phases[$y], implode('; ', $items)];
        }
        $html .= $this->smallTable(['Fase', 'Inisiatif Prioritas'], $roadmap);

        $mpdf->WriteHTML($html);
    }

    private function buildKpiRows(): array
    {
        $kpis = [
            ['FIN', 'Financial', 'Return on Equity (ROE)', 'Laba Bersih / Ekuitas × 100%', '%', 14.00, 15.00],
            ['FIN', 'Financial', 'Return on Assets (ROA)', 'Laba Bersih / Total Aset × 100%', '%', 8.50, 10.00],
            ['FIN', 'Financial', 'Net Profit Margin', 'Laba Bersih / Pendapatan × 100%', '%', 11.00, 14.00],
            ['FIN', 'Financial', 'Revenue Growth (CAGR 5Y)', '(Ending/Starting)^(1/5)-1', '%', 8.00, 10.00],
            ['FIN', 'Financial', 'Current Ratio', 'Aktiva Lancar / Utang Lancar', 'ratio', 1.80, 2.00],
            ['CUS', 'Customer', 'Customer Satisfaction (NPS)', 'NPS Survey', 'Score', 65, 70],
            ['CUS', 'Customer', 'Customer Retention', 'Existing Customer / Total Customer', '%', 95, 96],
            ['CUS', 'Customer', 'New Market Acquisition', 'New Logo / Total Customer', '%', 12, 20],
            ['CUS', 'Customer', 'On-Time Delivery', 'On-Time / Total Delivery', '%', 97, 99],
            ['CUS', 'Customer', 'Complaint Resolution Time', 'Avg resolve time', 'Hour', 24, 12],
            ['PRC', 'Internal Process', 'Production Yield', 'Output baik / Total output × 100%', '%', 98, 99.5],
            ['PRC', 'Internal Process', 'Defect Rate', 'Defect / Total Output', 'PPM', 80, 30],
            ['PRC', 'Internal Process', 'Equipment Utilization', 'Produksi / Kapasitas', '%', 90, 95],
            ['PRC', 'Internal Process', 'Energy Intensity', 'Energi / Output', 'kWh/lot', 12.0, 9.0],
            ['PRC', 'Internal Process', 'Carbon Footprint', 'Emisi CO2 total', 'tCO2e', 24000, 18000],
            ['PRC', 'Internal Process', 'Cybersecurity Incident Rate', 'Insiden Aktif / 1000 endpoint', 'ratio', 1.5, 0.5],
            ['PRC', 'Internal Process', 'Mean Time to Recover (MTTR)', 'Rata-rata MTTR', 'Hour', 8, 4],
            ['LNG', 'Learning', 'Training Hours per Pegawai', 'Total jam training / pegawai', 'Hour', 48, 60],
            ['LNG', 'Learning', 'Training Coverage', 'Pegawai dilatih / Total', '%', 95, 100],
            ['LNG', 'Learning', 'Innovation Adoption Rate', '% inovasi diadopsi', '%', 50, 70],
            ['LNG', 'Learning', 'Employee Engagement Index', 'Survey engagement', 'Index', 75, 85],
            ['LNG', 'Learning', 'Turnover Rate', 'Pegawai keluar / Total', '%', 2.5, 1.5],
            ['GOV', 'Internal Process', 'GCG Score', 'Penilaian agregat GCG', 'Score', 85, 92],
            ['GOV', 'Internal Process', 'Audit Follow-up Rate', 'Temuan selesai / total temuan', '%', 90, 98],
            ['GOV', 'Internal Process', 'Risk Mitigation Coverage', 'Risiko termitigasi / total risiko', '%', 85, 95],
        ];
        $rows = [];
        $i = 1;
        foreach ($kpis as $k) {
            $rows[] = ['KPI-'.$k[0].'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $k[1], $k[2], $k[3], $k[4], $k[5], $k[6]];
            $i++;
        }

        return $rows;
    }

    private function writeChapterRencanaKerjaAnggaran(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB VII RENCANA KERJA DAN ANGGARAN', [
            '7.1 Program Prioritas' => [
                'Program prioritas periode '.$this->period.' dikelompokkan ke dalam 3 klaster.',
                'Klaster Modernisasi: investasi mesin, AI vision, integrasi ERP tahap II.',
                'Klaster Diversifikasi: secure ID digital, layanan sertifikasi, ekspor layanan.',
                'Klaster Tata Kelola: GRC, Cyber SOC, dan people transformation.',
            ],
            '7.2 Anggaran per Tahun' => $this->budgetTable(),
            '7.3 Sumber Pendanaan' => [
                'Pendanaan RJPP '.$this->period.' didanai dari: (i) Laba Ditahan (60%), (ii) Pinjaman Jangka Panjang (25%), (iii) Kerjasama Strategis (10%), (iv) Capital Expenditure dari vendor financing (5%).',
                'Ratio Debt to Equity ditargetkan tetap di bawah 1.0 selama periode RJPP.',
            ],
        ]);
    }

    private function budgetTable(): array
    {
        $rows = [];
        for ($y = 0; $y < 5; $y++) {
            $year = (int) $this->startYear + $y;
            $rows[] = [$year, $this->faker->numberBetween(380, 540), $this->faker->numberBetween(120, 200), $this->faker->numberBetween(80, 160), $this->faker->numberBetween(600, 900)];
        }

        return ['Anggaran RJPP per tahun (dalam Miliar Rupiah):'.$this->smallTable(['Tahun', 'Capex', 'Opex TI', 'RnD', 'Total'], $rows)];
    }

    private function writeChapterManajemenRisiko(Mpdf $mpdf): void
    {
        $riskRows = [];
        for ($i = 1; $i <= 30; $i++) {
            $riskRows[] = [
                'R-'.$i,
                'Risiko '.$this->faker->randomElement(['Operasional', 'Strategis', 'Keuangan', 'Cyber', 'Regulatori', 'Pemasok', 'SDM']),
                $this->faker->randomElement(['Ekstrem', 'Tinggi', 'Sedang']),
                $this->faker->randomElement(['Pengurangan', 'Penghindaran', 'Transfer']),
            ];
        }
        $table = $this->smallTable(['Kode', 'Kategori', 'Level', 'Strategi Mitigasi'], $riskRows);

        $this->writeChapter($mpdf, 'BAB VIII MANAJEMEN RISIKO', [
            '8.1 Pendekatan' => ['Manajemen risiko perusahaan mengacu pada ISO 31000 dan pedoman Kemen BUMN.'],
            '8.2 Risk Register' => ['Daftar risiko utama: '.$table],
            '8.3 Key Risk Indicators (KRI)' => ['KRI mencakup indikator cyber (incident count), operasional (downtime), keuangan (cashflow coverage).'],
        ]);
    }

    private function writeChapterPenutup(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB IX PENUTUP', [
            '9.1 Penutup' => [
                'RJPP '.$this->period.' menjadi peta jalan strategis perusahaan. Keberhasilan eksekusi memerlukan komitmen seluruh pemangku kepentingan.',
                'Dokumen ini bersifat dinamis dan akan dievaluasi setiap tahun melalui RKB.',
                'Catatan: dokumen ini adalah dummy sintetis.',
            ],
        ]);
    }

    // ---------- LAMPIRAN ----------

    private function writeLampiranA_RKT(Mpdf $mpdf): void
    {
        for ($y = 0; $y < 5; $y++) {
            $year = (int) $this->startYear + $y;
            $html = '<pagebreak /><h1>LAMPIRAN A.'.$y.' - RENCANA KERJA TAHANAN '.$year.'</h1>';
            $html .= '<p>RKT tahun '.$year.' disusun dengan pendekatan cascading dari sasaran strategis RJPP. Setiap program kerja memiliki penanggung jawab, timeline, dan target kuantitatif.</p>';
            $rows = [];
            for ($r = 1; $r <= 40; $r++) {
                $rows[] = [
                    'RKT-'.$year.'-'.$r,
                    $this->faker->randomElement(['Modernisasi', 'Diversifikasi', 'Tata Kelola', 'SDM', 'Sustainability']),
                    $this->faker->randomElement(['Divisi TI', 'Divisi Produksi', 'Divisi SDM', 'Divisi Keuangan', 'Divisi RnD']),
                    $this->faker->randomElement(['Q1', 'Q2', 'Q3', 'Q4']),
                    $this->faker->randomFloat(2, 0.5, 25).' M',
                ];
            }
            $html .= $this->smallTable(['Kode Program', 'Klaster', 'Penanggung Jawab', 'Target Penyelesaian', 'Anggaran'], $rows);
            $html .= '<h3>Detail Aktivitas & Milestone</h3>';
            $html .= '<p>Untuk setiap program di atas, milestone rinci, KPI turunan, dan deliverable dapat dilihat dalam dokumen terpisah. Rincian ini memastikan eksekusi dapat diaudit.</p>';
            for ($k = 0; $k < 8; $k++) {
                $html .= '<h4>Program '.$year.' - '.($k + 1).'</h4>';
                $html .= $this->programDetailBlock($year, $k + 1);
            }
            $mpdf->WriteHTML($html);
        }
    }

    private function writeLampiranB_SKAI(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN B - SASARAN KINERJA AGRO INISIATIF (SKAI)</h1>';
        $html .= '<p>Lampiran ini berisi daftar lengkap inisiatif strategis beserta KPI turunan dan target tahunan.</p>';
        $baseKpis = $this->buildKpiRows();
        $initiatives = $this->randomUniqueInitiatives(60);
        for ($i = 0; $i < 60; $i++) {
            $kpi = $baseKpis[$i % count($baseKpis)];
            $html .= '<h3>IS-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT).' : </h3>';
            $html .= '<p><b>Inisiatif:</b> '.htmlspecialchars($initiatives[$i]).'</p>';
            $html .= '<h2>KPI terkait</h2>';
            $html .= '<p><b>KPI Terkait:</b> '.$kpi[0].' - '.$kpi[2].'</p>';
            $html .= '<p><b> target tahunan:</b></p>';
            $targetRows = [];
            for ($y = 0; $y < 5; $y++) {
                $targetRows[] = [(int) $this->startYear + $y, $this->faker->randomFloat(2, 50, 100), $this->faker->randomFloat(2, 50, 100)];
            }
            $html .= $this->smallTable(['Tahun', 'Target', 'Realisasi (dari AI)*'], $targetRows);
            $html .= '<p>* Realisasi akan diisi melalui sistem KPI Advisor berdasarkan analisa AI terhadap evidence yang diunggah.</p>';
            $html .= '<h4>Lingkup dan Pendekatan</h4>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(5)).'</p>';
            $html .= '<h4>Milestone dan Deliverable</h4>';
            $html .= $this->milestoneTable();
            $html .= '<h4>Anggaran dan Sumber Daya</h4>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(4)).'</p>';
        }
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranC_Roadmap(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN C - ROADMAP INISIATIF STRATEGIS</h1>';
        $html .= '<p>Roadmap menyajikan timeline eksekusi 60 inisiatif strategis selama 5 tahun.</p>';
        $initiatives = $this->randomUniqueInitiatives(60);
        $rows = [];
        for ($i = 0; $i < 60; $i++) {
            $start = $this->faker->numberBetween(1, 5);
            $dur = $this->faker->numberBetween(1, 3);
            $row = ['IS-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), $initiatives[$i]];
            for ($y = 1; $y <= 5; $y++) {
                $row[] = ($y >= $start && $y < $start + $dur) ? 'X' : '';
            }
            $rows[] = $row;
        }
        $html .= $this->smallTable(['Kode', 'Inisiatif', 'Y1', 'Y2', 'Y3', 'Y4', 'Y5'], $rows);
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranD_LaporanKeuangan(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN D - LAPORAN KEUANGAN HISTORIS 5 TAHUN</h1>';
        $statements = ['Laporan Laba Rugi', 'Laporan Posisi Keuangan', 'Laporan Arus Kas', 'Laporan Perubahan Modal', 'Catatan Atas Laporan Keuangan'];
        for ($y = -6; $y <= -1; $y++) {
            $year = (int) $this->startYear + $y;
            $html .= '<h2>'.$year.'</h2>';
            foreach ($statements as $stIdx => $stmt) {
                $html .= '<h3>'.$stmt.' - '.$year.'</h3>';
                $rows = [];
                for ($r = 0; $r < 25; $r++) {
                    $rows[] = [
                        $this->faker->randomElement(['Pendapatan', 'Beban', 'Aset Lancar', 'Aset Tetap', 'Kewajiban', 'Modal', 'Arus Kas Operasi', 'Arus Kas Investasi', 'Arus Kas Pendanaan']),
                        $this->faker->randomElement(['D', 'K']),
                        $this->faker->numberBetween(50, 5000),
                    ];
                }
                $html .= $this->smallTable(['Akun', 'D/K', 'Nilai (Juta)'], $rows);
            }
        }
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranE_Organisasi(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN E - STRUKTUR ORGANISASI & JOB GRADE</h1>';
        $html .= '<p>Berikut adalah ringkasan job grade dan tugas pokok masing-masing posisi di lingkungan perusahaan, sebagai bagian dari lampiran RJPP.'.$this->jobGradeTable().'</p>';
        $jobRows = [];
        for ($i = 0; $i < 120; $i++) {
            $jobRows[] = [
                'JG-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $this->faker->jobTitle,
                (string) $this->faker->numberBetween(1, 17),
                $this->randItem($this->unitPool),
                $this->faker->randomElement(['Taktis', 'Strategis', 'Operasional', 'Fungsional']),
                $this->organizationRoleSentence(),
            ];
        }
        $html .= $this->smallTable(['Kode', 'Nama Posisi', 'Grade', 'Unit', 'Level Kewenangan', 'Tugas Pokok'], $jobRows);
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranF_Regulasi(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN F - REGULASI TERKAIT</h1>';
        $regs = [
            'UU No. 19 Tahun 2003 tentang BUMN',
            'UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi',
            'UU No. 11 Tahun 2008 tentang ITE',
            'PP No. 16 Tahun 2011 tentang Perseroan (relevan bagi Perum)',
            'Peraturan Menteri BUMN tentang RJPP / RBB',
            'Peraturan Menteri Keuangan tentang Percetakan Uang',
            'Regulasi Bank Indonesia terkait uang rupiah',
            'ISO 31000:2018 - Manajemen Risiko',
            'ISO 27001:2022 - Keamanan Informasi',
            'ISO 9001:2015 - Manajemen Mutu',
            'ISO 14001:2015 - Manajemen Lingkungan',
            'ISO 45001:2018 - K3',
            'COBIT 2019 - Tata Kelola TI',
            'TOGAF 10 - Arsitektur Enterprise',
        ];
        for ($i = 0; $i < count($regs); $i++) {
            $html .= '<h3>'.($i + 1).'. '.htmlspecialchars($regs[$i]).'</h3>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->regulationImpact($regs[$i])).'</p>';
        }
        $mpdf->WriteHTML($html);
    }

    // ---------- padding ----------

    private function padToTargetPages(Mpdf $mpdf, int $target): void
    {
        $current = $mpdf->page;
        if ($current >= $target) {
            return;
        }
        $this->info("Padding halaman dari {$current} ke target {$target}...");
        $mpdf->WriteHTML('<pagebreak /><h1>LAMPIRAN G - DOKUMEN PENDUKUNG RINCI</h1>');
        $mpdf->WriteHTML('<p>Lampiran ini berisi paparan rinci atas program kerja, asumsi perencanaan, analisis dukungan, dan dokumen turunan sebagai bagian dari pelampiran RJPP. Seluruh konten bersifat sintetis namun disusun mengikuti struktur dokumen pendukung RJPP BUMN pada umumnya.</p>');
        $counter = 0;
        $max = 5000;
        while ($mpdf->page < $target && $counter < $max) {
            $counter++;
            $chunk = [];
            $chunk[] = '<h4>Dokumen Pendukung #'.$counter.' - '.$this->randItem(['Catatan Asumsi Strategis', 'Pendukung Analisis Risiko', 'Telaah Pemangku Kepentingan', 'Rincian Program Kerja', 'Latar Belakang Kuantitatif', 'Analisis Dampak Kebijakan', 'Proyeksi Operasional', 'Telaah Arsitektur Pendukung']).'</h4>';
            for ($i = 0; $i < 6; $i++) {
                $chunk[] = '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(5)).'</p>';
            }
            $chunk[] = $this->milestoneTable();
            for ($i = 0; $i < 4; $i++) {
                $chunk[] = '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(4)).'</p>';
            }
            $mpdf->WriteHTML(implode(' ', $chunk));
            if ($counter % 50 === 0) {
                $this->line("  Padding progress: halaman ".$mpdf->page." / {$target} (iterasi {$counter})");
            }
        }
    }

    // ---------- shared writer ----------

    /** Render blok detail tematik untuk sebuah program kerja tahunan. */
    private function programDetailBlock(int $year, int $programNo): string
    {
        $kodeProgram = 'RKT-'.$year.'-'.$programNo;

        $out = '<p><b>Kode Program:</b> '.$kodeProgram.'</p>';
        $out .= '<p><b>Penanggung Jawab:</b> '.$this->randItem($this->unitPool).'</p>';
        $out .= '<p><b>Mutu Tujuan:</b> '.ucfirst(strtolower($this->randItem($this->deliverablePool))).'</p>';

        $out .= '<h5>Lingkup</h5>';
        $out .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(5)).'</p>';

        $out .= '<h5>Milestone & Deliverable</h5>';
        $out .= $this->milestoneTable();

        $out .= '<h5>Rencana Anggaran</h5>';
        $angRows = [];
        for ($q = 1; $q <= 4; $q++) {
            $angRows[] = [
                'Q'.$q.' '.$year,
                $this->faker->randomFloat(2, 0.3, 8).' M',
                $this->faker->randomFloat(2, 0.1, 6).' M',
                $this->faker->randomElement(['On Track', 'Planned', 'In Progress']),
                $this->randItem($this->unitPool),
            ];
        }
        $out .= $this->smallTable(['Periode', 'Plafon Anggaran', 'Realisasi', 'Status', 'PIC'], $angRows);

        $out .= '<h5>Risiko Tertaksiran</h5>';
        $riskRows = [];
        $riskCount = $this->faker->numberBetween(2, 4);
        for ($r = 1; $r <= $riskCount; $r++) {
            $riskRows[] = [
                $kodeProgram.'-R'.$r,
                ucfirst($this->randItem($this->risikoPool)),
                $this->faker->randomElement(['Tinggi', 'Sedang', 'Rendah']),
                $this->randItem($this->mitigasiPool),
            ];
        }
        $out .= $this->smallTable(['Kode Risiko', 'Risiko', 'Tingkat', 'Mitigasi'], $riskRows);

        $out .= '<h5>KPI Turunan</h5>';
        $kpiRows = [];
        $kpiCount = $this->faker->numberBetween(2, 4);
        $kpisRef = $this->buildKpiRows();
        for ($k = 0; $k < $kpiCount; $k++) {
            $refKpi = $kpisRef[$k % count($kpisRef)];
            $kpiRows[] = [
                $refKpi[0],
                $refKpi[2],
                $refKpi[4],
                $this->faker->randomFloat(1, 70, 95),
                $this->faker->randomFloat(1, 80, 100),
            ];
        }
        $out .= $this->smallTable(['Kode KPI', 'Measurement', 'Unit', 'Target '.$year, 'Baseline'], $kpiRows);

        $out .= '<h5>Catatan Pelaksanaan</h5>';
        $out .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(4)).'</p>';

        return $out;
    }

    /** Hasilkan tabel milestone tematik. */
    private function milestoneTable(): string
    {
        $rows = [];
        $milestoneNames = [
            'Penyusunan ToR & Approval', 'Tender / Penunjukan Vendor',
            'Kick-off Meeting', 'Desain Teknis & SRS',
            'Pengembangan / Konstruksi', 'Internal Test',
            'User Acceptance Test (UAT)', 'Pelatihan Pengguna',
            'Go-Live & Hypercare', 'Evaluasi Pasca Implementasi',
            'Serah Terima & Operasionalisasi', 'Audit Pasca Proyek',
        ];
        $selected = $this->faker->randomElements($milestoneNames, $this->faker->numberBetween(5, 7), false);
        foreach ($selected as $idx => $milestone) {
            $rows[] = [
                'M'.($idx + 1),
                $milestone,
                $this->randItem($this->periodePool).' '.$this->faker->numberBetween($this->startYear, $this->endYear),
                $this->randItem($this->unitPool),
                $this->faker->randomElement(['Planned', 'In Progress', 'Completed', 'Pending Approval']),
                $this->faker->randomFloat(1, 0.5, $this->faker->numberBetween(5, 25)).' M',
            ];
        }

        return $this->smallTable(['Kode', 'Milestone', 'Target Waktu', 'Penanggung Jawab', 'Status', 'Biaya'], $rows);
    }

    /** Kalimat peran organisasi yang tematik untuk lampiran E. */
    private function organizationRoleSentence(): string
    {
        $verbs = ['memimpin', 'mengoordinasikan', 'menjalankan', 'memantau', 'menyusun', 'mengevaluasi', 'melaporkan'];
        $objects = [
            'pelaksanaan kebijakan strategis',
            'kegiatan operasional harian',
            'agenda transformasi digital',
            'program peningkatan kapabilitas SDM',
            'audit internal dan pengendalian mutu',
            'kinerja indikator sasaran kinerja',
            'proses pengadaan dan kerjasama vendor',
            'penerapan prinsip tata kelola yang sehat',
        ];

        return ucfirst($this->randItem($verbs)).' '.$this->randItem($objects).' pada lingkup '.$this->randItem($this->unitPool).' dengan akuntabilitas kepada '.$this->randItem(['Direktur Utama', 'Direktur Terkait', 'Komite Audit', 'Manajemen Senior']);
    }

    /** Tabel ringkas job grade global (puesta sebagai preamble lampiran E). */
    private function jobGradeTable(): string
    {
        $rows = [];
        for ($g = 1; $g <= 17; $g++) {
            $rows[] = [
                'G'.$g,
                $this->faker->randomElement(['Trainee', 'Junior Staff', 'Staff', 'Senior Staff', 'Supervisor', 'Asisten Manajer', 'Manajer', 'Senior Manajer', 'VP Asisten', 'Vice President', 'Senior VP', 'Eksekutif Vice President']),
                $this->faker->numberBetween(15, 90).' SKS',
                $this->faker->randomFloat(1, 5, 25).' jt',
                $this->faker->randomElement(['Taktis', 'Strategis', 'Operasional', 'Fungsional']),
            ];
        }

        return $this->smallTable(['Grade', 'Default Level', 'Min. Pengalaman', 'Band Gaji Min.', 'Kewenangan'], $rows);
    }

    /** Paragraf narasi dampak regulasi untuk lampiran F. */
    private function regulationImpact(string $reg): string
    {
        $lower = strtolower($reg);
        $themes = [];
        if (str_contains($lower, 'pdp') || str_contains($lower, 'pelindungan')) {
            $themes[] = 'kepatuhan terhadap pengolahan data pribadi';
            $themes[] = 'penunjukan Data Protection Officer';
        }
        if (str_contains($lower, 'ite')) {
            $themes[] = 'penegakan hukum transaksi elektronik';
            $themes[] = 'pengamanan dokumen digital';
        }
        if (str_contains($lower, 'iso') || str_contains($lower, 'cobit') || str_contains($lower, 'togaf')) {
            $themes[] = 'perbaikan tata kelola sistem informasi';
            $themes[] = 'penyelarasan arsitektur enterprise';
        }
        if (str_contains($lower, 'rjpp') || str_contains($lower, 'rbb')) {
            $themes[] = 'penyusunan dokumen perencanaan dan anggaran';
            $themes[] = 'pelaporan kinerja yang terukur';
        }
        if (str_contains($lower, 'risiko')) {
            $themes[] = 'penerapan kerangka manajemen risiko';
            $themes[] = 'identifikasi dan mitigasi risiko berkelanjutan';
        }
        if (empty($themes)) {
            $themes = ['pelaksanaan praktik terbaik internasional', 'penguatan kerangka akuntabilitas perusahaan'];
        }

        $theme = $this->faker->randomElement($themes);
        $perNext = $this->startYear.'-'.$this->endYear;

        $lead = 'Regulasi '.$reg.' menjadi rujukan perusahaan dalam menetapkan kebijakan internal pada periode '.$perNext.'. ';
        $body = 'Perusahaan memastikan kepatuhan melalui pemetaan gap analysis, penyusunan dokumen kebijakan turunan, serta sosialisasi kepada seluruh unit kerja. ';
        $close = 'Pelaksanaan pemenuhan '.$theme.' dipantau melalui indikator Indikator Kinerja Kunci (KPI) yang relevan dan dilaporkan kepada Direksi pada setiap rapat tinjauan manajemen triwulanan.';

        return $lead.$body.$this->narrativeParagraph(3).' '.$close;
    }

    private function writeChapter(Mpdf $mpdf, string $title, array $sections): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">'.htmlspecialchars($title).'</h1>';
        foreach ($sections as $head => $paragraphs) {
            $html .= '<h2>'.htmlspecialchars($head).'</h2>';
            foreach ($paragraphs as $p) {
                $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.$p.'</p>';
            }
        }
        $mpdf->WriteHTML($html);
    }

    private function paragraphs(array $arr): string
    {
        $out = '';
        foreach ($arr as $p) {
            $out .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($p).'</p>';
        }

        return $out;
    }

    private function smallTable(array $headers, array $rows): string
    {
        $out = '<table style="width:100%; border-collapse:collapse; font-size:9pt;" border="1" cellpadding="4"><thead><tr style="background:#e0e7ff;">';
        foreach ($headers as $h) {
            $out .= '<th>'.htmlspecialchars($h).'</th>';
        }
        $out .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $out .= '<tr>';
            foreach ($row as $cell) {
                $out .= '<td>'.htmlspecialchars((string) $cell).'</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';

        return $out;
    }

    // ---------- thematic narrative generator ----------

    /** Daftar unit kerja yang lazim di BUMN percetakan/keuangan. */
    private array $unitPool = [
        'Direktorat Operasional', 'Direktorat Keuangan', 'Direktorat SDM',
        'Direktorat Perencanaan', 'Divisi Produksi', 'Divisi Mutu',
        'Divisi TI', 'Divisi Pemasaran', 'Divisi RnD', 'Divisi Risiko',
        'Satuan Pengawasan Internal', 'Sekretariat Perusahaan',
        'Divisi Pengadaan', 'Divisi Logistik', 'Divisi Hukum',
    ];

    /** Template kalimat tematik program/aktivitas BUMN.
     *  Placeholder akan diganti secara dinamis. */
    private array $sentenceTemplates = [
        'Kegiatan dimulai dengan penyusunan Terms of Reference (ToR) oleh {unit} pada {periode} yang menetapkan lingkup, estimasi biaya, serta daftar deliverable utama.',
        'Tahap berikutnya adalah proses pengadaan melalui skema {metode} dengan target penunjukan penyedia pada {periode} dan tenggat kontrak paling lambat {deadline}.',
        'Penanggung jawab {unit} melakukan kick-off meeting bersama {unit2} untuk menyamakan pemahaman atas spesifikasi teknis, indikator keberhasilan, serta {metrik} yang akan dipantau setiap kuartal.',
        'Implementasi dilaksanakan secara bertahap melalui {fase} dengan validasi setiap milestone yang divalidasi melalui rapat tinjauan manajemen (management review).',
        'Risiko utama yang teridentifikasi pada tahap ini adalah {risiko}, sehingga strategi mitigasi disusun melalui {mitigasi} dan didokumentasikan dalam risk register proyek.',
        'Pengujian user acceptance test (UAT) dilakukan oleh {unit2} terhadap {deliverable}, dengan kriteria penerimaan mengacu pada dokumen spesifikasi kebutuhan (SRS) yang telah disepakati.',
        'Pelatihan pengguna dilaksanakan oleh {vendor} sebanyak {jumlah} sesi dengan target tingkat pemahaman minimal 80% berdasarkan hasil assesmen pasca-pelatihan.',
        'Go-live {deliverable} ditargetkan pada {periode} diikuti dengan periode hypercare selama 30 hari kerja untuk memastikan kestabilan layanan.',
        'Monitoring kinerja dilakukan melalui dashboard {metrik} yang dapat diakses oleh {unit} secara real-time, dengan eskalasi insiden ke {unit2} jika terdapat penyimpangan dari SLA.',
        'Laporan kemajuan program disampaikan kepada Direksi setiap bulan melalui mekanisme PMO, mencakup status fisik, serapan anggaran, serta realisasi indikator {metrik}.',
        'Audit internal dilakukan oleh {unit2} pada akhir {fase} untuk memastikan kepatuhan terhadap prosedur mutu, keamanan informasi, dan Good Corporate Governance.',
        'Lesson learned pada periode sebelumnya menjadi dasar perbaikan berkelanjutan melalui mekanisme retrospective meeting yang difasilitasi oleh {unit}.',
        'Dokumentasi as-built disusun oleh {vendor} dan diverifikasi oleh {unit2} sebagai bagian dari deliverable handover, mencakup diagram arsitektur, runbook operasional, dan daftar konfigurasi.',
        'Evaluasi pasca-implementasi dilakukan 90 hari setelah go-live untuk mengukur pencapaian {metrik} dibandingkan baseline serta mengidentifikasi potensi optimasi lanjutan.',
        'Anggaran program sebesar Rp {anggaran} miliar dialokasikan secara bertahap dengan {_persen} pada {fase} pertama dan sisanya disesuaikan dengan pencapaian milestone gate.',
        'Keterlibatan pemangku kepentingan dipastikan melalui forum koordinasi {periode} yang dipimpin oleh {unit} dan dihadiri oleh perwakilan {unit2}.',
        'Aspek keamanan informasi ditangani melalui penilaian risiko TI oleh {vendor} yang meliputi penetration testing, vulnerability assessment, dan review konfigurasi hardening.',
        'Integrasi dengan sistem existing (ERP, BI, dan helpdesk) dilakukan melalui API Gateway yang dikelola oleh {unit}, dengan target latency rata-rata di bawah {metrik_num} ms.',
        'Migrasi data dari sistem legacy dilakukan dengan strategi parallel run selama {jumlah} siklus operasional untuk membandingkan akurasi hasil sebelum cut-over definitif.',
        'Komitmen keberlanjutan (ESG) diintegrasikan ke dalam program melalui penilaian carbon footprint, efisiensi energi, serta dampakan sosial pada komunitas sekitar.',
        'Manajemen perubahan (change management) difasilitasi oleh {unit} melalui workshop stakeholder, communication plan, dan identifikasi change agent pada setiap unit kerja.',
        'Penjaminan mutu dokumen dilakukan oleh {unit2} dengan referensi ISO 9001:2015 dan SOP internal yang berlaku, dengan telaah oleh minimum dua reviewer independen.',
        'Eskalasi insiden tier-3 mengikuti matriks RACI yang menetapkan {unit} sebagai accountable, {unit2} sebagai responsible, dan {vendor} sebagai consulted party.',
        'Pengukuran hasil (outcome) menggunakan {metrik} sebagai indikator dampak, dengan baseline tahun lalu dan target tahun ini diturunkan dari Sasaran Strategis {kode_ss}.',
        'Pengembangan kapabilitas tim dilakukan melalui coaching oleh {vendor} dan sertifikasi kompetensi internal sebanyak {jumlah} pegawai pada bidang {kompetensi}.',
        'Kepatuhan terhadap UU Pelindungan Data Pribadi dipastikan melalui Data Protection Impact Assessment (DPIA) yang disusun oleh {unit2} dan diverifikasi oleh {vendor}.',
        'Kesiapan business continuity dipastikan melalui disaster recovery test setiap 6 bulan dengan target RTO {metrik_num} jam dan RPO maksimal 4 jam.',
        'Vendor performance evaluation dilakukan setiap kuartal dengan parameter kualitas deliverable, kepatuhan timeline, dan responsivitas terhadap eskalasi.',
        'Penutupan proyek diakhiri dengan serah terima ke unit operasional, transfer pengetahuan, danPemeliharaan garansi selama {garansi} bulan oleh {vendor}.',
        'Pengukuran manfaat (benefit realization) dilakukan 12 bulan setelah go-live oleh {unit2} untuk memastikan realization value sesuai business case awal.',
        'Edukasi pengguna akhir dilakukan melalui portal knowledge base berisi panduan operasional, FAQ, dan video tutorial yang dirilis oleh {vendor} dan diperbarui setiap {periode}.',
        'Sertifikasi keamanan diperbarui oleh {unit2} mengacu pada ISO/IEC 27001:2022 dengan lingkup improvement area hasil audit eksternal tahun sebelumnya.',
        'Pengaturan tata letak proses bisnis dilakukan dengan referensi TOGAF ADM Phase B (Business Architecture) dan dipresentasikan dalam workshop arsitektur {periode}.',
        'Benchmarking terhadap praktik terbaik industri dilakukan oleh {vendor} melalui study visit ke {unit2} serta riset literatur profesional.',
        'Knowledge transfer ke pegawai internal dilakukan secara terstruktur melalui pairing model dengan minimum {jumlah} pegawai yang dideklarasikan kompeten.',
        'Penyesuaian kebijakan internal dilakukan untuk mengakomodasi adopsi sistem baru, mencakup revisi SKDireksi terkait tata kelola {deliverable}.',
        'Pengarsipan seluruh artefak proyek dilakukan oleh {unit} ke dalam repository dokumentasi yang dapat diakses oleh {unit2} untuk keperluan audit dan continuity.',
    ];

    /** Daftar vendor fiktif untuk variasi kalimat. */
    private array $vendorPool = [
        'PT Integrasi Sinergi Teknologi', 'PT Mitra Inovasi Digital', 'PT Solusi Coretika Nusantara',
        'PT Tata Niaga Integrasi', 'PT Sarana Pratama',
    ];

    /** Kompetensi/keterampilan teknis tema TI/operasional. */
    private array $kompetensiPool = [
        'ITIL Foundation', 'COBIT 2019', 'ISO 27001 Lead Auditor', 'Prince2',
        'Data Analytics', 'Cloud Architecture', 'Cyber Security Operation',
        'Project Management Professional', 'Lean Six Sigma', 'Quality Assurance',
    ];

    /** Metrik/indikator kinerja kunci. */
    private array $metrikPool = [
        'On-Time Delivery', 'First Time Right', 'Yield Produksi', 'Mean Time to Recover',
        'System Uptime', 'Cyber Security Incident Rate', 'Customer Satisfaction Index',
        'Cost per Unit Produksi', 'Defect Rate', 'Call Resolution Rate',
        'Procurement Cycle Time', 'Training Coverage', 'Innovation Adoption Rate',
    ];

    /** Deliverable umum program TI/operasional BUMN. */
    private array $deliverablePool = [
        'sistem Mutu Digital', 'modul Finansial Enterprise', 'modul Distribusi & Logistik',
        'portal Kemitraan Online', 'aplikasi Monitoring Produksi', 'sistem Helpdesk ITSM',
        'platform Cyber SOC', 'platform Data Lake Enterprise', 'modul Enterprise Risk Management',
        'aplikasi Helpdesk V2', 'modul Self-Service HR', 'integrasi ERP - Payroll',
        'aplikasi Mobile Pandai', 'modul Vendor Management', 'platform GRC',
    ];

    /** Metode pengadaan. */
    private array $metodePool = ['Pemilihan Langsung', 'Tender Terbuka', 'Tender Terbatas', 'Penunjukan Langsung', 'E-Procurement LPSE', 'Konsultan Individu'];

    /** Jenis risiko operasional/strategis. */
    private array $risikoPool = [
        'keterlambatan vendor', 'perubahan regulasi', 'kendala integrasi sistem lama',
        'resistensi pengguna', 'kegagalan teknis', 'rencana anggaran yang membengkak',
        'kebocoran data sensitif', 'gangguan supply chain',
    ];

    /** Strategi mitigasi. */
    private array $mitigasiPool = [
        'penerapan SLA yang ketat', 'diversifikasi vendor', 'pelatihan change agent',
        'uji beban bertahap', 'review arsitektur independen', 'asuransi proyek',
        'mekanisme eskalasi berjenjang', 'penilaian risiko berkelanjutan',
    ];

    /** Fase/tahapan proyek. */
    private array $fasePool = ['fase inisiasi', 'fase desain', 'fase konstruksi', 'fase pengujian', 'fase deployment', 'fase pasca go-live', 'fase optimasi'];
    private array $periodePool = ['Q1', 'Q2', 'Q3', 'Q4', 'awal tahun', 'tengah tahun', 'akhir tahun', 'kuartal pertama', 'kuartal kedua'];
    private array $ssPool = ['SS-01', 'SS-02', 'SS-03', 'SS-04', 'SS-05', 'SS-06', 'SS-07'];

    private function randItem(array $arr): string
    {
        return (string) $this->faker->randomElement($arr);
    }

    /** Hasilkan satu kalimat tematik program/aktivitas BUMN. */
    private function narrativeSentence(): string
    {
        $tpl = $this->faker->randomElement($this->sentenceTemplates);

        $replacements = [
            '{unit}' => $this->randItem($this->unitPool),
            '{unit2}' => $this->randItem($this->unitPool),
            '{vendor}' => $this->randItem($this->vendorPool),
            '{metode}' => $this->randItem($this->metodePool),
            '{risiko}' => $this->randItem($this->risikoPool),
            '{mitigasi}' => $this->randItem($this->mitigasiPool),
            '{fase}' => $this->randItem($this->fasePool),
            '{periode}' => $this->randItem($this->periodePool),
            '{deadline}' => $this->randItem($this->periodePool),
            '{deliverable}' => $this->randItem($this->deliverablePool),
            '{metrik}' => $this->randItem($this->metrikPool),
            '{metrik_num}' => (string) $this->faker->numberBetween(50, 500),
            '{kode_ss}' => $this->randItem($this->ssPool),
            '{jumlah}' => (string) $this->faker->numberBetween(5, 60),
            '{anggaran}' => (string) $this->faker->numberBetween(1, 120),
            '{_persen}' => $this->faker->randomElement(['30%', '40%', '50%', '60%']).' dana',
            '{garansi}' => (string) $this->faker->numberBetween(3, 24),
            '{kompetensi}' => $this->randItem($this->kompetensiPool),
        ];

        // Hindari pasangan {unit} == {unit2} untuk variasi
        do {
            $replacements['{unit2}'] = $this->randItem($this->unitPool);
        } while ($replacements['{unit}'] === $replacements['{unit2}']);

        return strtr($tpl, $replacements);
    }

    /** Hasilkan satu paragraf tematik (kumpulan kalimat koheren). */
    private function narrativeParagraph(int $sentences = 5): string
    {
        $sentences = max(3, $sentences);
        $parts = [];
        $used = [];
        $attempts = 0;
        while (count($parts) < $sentences && $attempts < 30) {
            $attempts++;
            $s = $this->narrativeSentence();
            $key = strtolower(preg_replace('/\s+/', ' ', $s));
            if (in_array($key, $used, true)) {
                continue;
            }
            $used[] = $key;
            $parts[] = $s;
        }

        return implode(' ', $parts);
    }
}