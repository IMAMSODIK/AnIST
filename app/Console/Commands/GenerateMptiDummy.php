<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mpdf\Mpdf;
use Faker\Factory as Faker;

class GenerateMptiDummy extends Command
{
    protected $signature = 'mpti:generate-dummy
        {--company=PT PERURI (Persero) : Nama perusahaan}
        {--period=2025-2029 : Periode MPTI}
        {--pages=500 : Target minimal halaman}
        {--out=docs/strategic-reference/MPTI_PT_PERURI_2025-2029_DUMMY.pdf : Path output PDF}';

    protected $description = 'Generate dokumen dummy MPTI (Master Plan Teknologi Informasi) BUMN (PDF) untuk development & testing. Dokumen sepenuhnya sintetis.';

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

        $this->info("Membuat dokumen MPTI sintetis: {$this->company} ({$this->period})");
        $this->info("Target minimal halaman: ".$this->option('pages'));

        if (! is_dir(storage_path('app/mpdf-tmp'))) {
            mkdir(storage_path('app/mpdf-tmp'), 0777, true);
        }

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
        $mpdf->SetTitle("MPTI {$this->company} {$this->period} (DUMMY)");
        $mpdf->SetAuthor('KPI Advisor - Document Generator');
        $mpdf->SetSubject('Master Plan Teknologi Informasi (Dokumen Sintetis)');

        $this->writeCoverPage($mpdf);
        $this->writeApprovalPage($mpdf);
        $this->writeTableOfContents($mpdf);
        $this->writeAbbreviationPage($mpdf);
        $this->writeExecutiveSummary($mpdf);

        $this->writeChapterPendahuluan($mpdf);
        $this->writeChapterProfilTI($mpdf);
        $this->writeChapterAnalisisTI($mpdf);
        $this->writeChapterArsitektur($mpdf);
        $this->writeChapterArahStrategiTI($mpdf);
        $this->writeChapterRoadmapPTI($mpdf);
        $this->writeChapterInvestasiTI($mpdf);
        $this->writeChapterManajemenRisikoTI($mpdf);
        $this->writeChapterPenutup($mpdf);

        $this->writeLampiranA_PtiDetail($mpdf);
        $this->writeLampiranB_InventarisAplikasi($mpdf);
        $this->writeLampiranC_InventarisInfrastruktur($mpdf);
        $this->writeLampiranD_RegulasiSOP($mpdf);

        $this->padToTargetPages($mpdf, (int) $this->option('pages'));

        $pageCount = $mpdf->page;
        $mpdf->Output($outPath, \Mpdf\Output\Destination::FILE);

        $this->info("Selesai. Halaman tercapai: {$pageCount}");
        $this->info("File: {$outPath}");

        return self::SUCCESS;
    }

    // ---------- depan dokumen ----------

    private function writeCoverPage(Mpdf $mpdf): void
    {
        $html = '
        <style>
            .cover { text-align:center; padding-top:160px; }
            .cover h1 { font-size:30pt; color:#1f2937; margin-top:20px; }
            .cover h2 { font-size:20pt; color:#10B981; margin-top:30px; }
            .cover h3 { font-size:14pt; color:#374151; margin-top:60px; }
            .cover .logo { width:120px; margin:0 auto 40px; border:3px solid #10B981; border-radius:50%; padding:25px; font-weight:bold; font-size:34pt; color:#10B981;}
            .footer-klasifikasi { margin-top:200px; text-align:right; color:#6b7280; font-size:10pt; }
        </style>
        <div class="cover">
            <div class="logo">DUMMY</div>
            <h1>MASTER PLAN TEKNOLOGI INFORMASI</h1>
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
        <p style="text-align:center;">Master Plan Teknologi Informasi '.$this->company.' Periode '.$this->period.'</p>
        <p style="text-align:center; margin-bottom:30px;">Disahkan oleh:</p>
        <table style="width:100%; border-collapse:collapse;" border="0">
            <tr>
                <td style="width:50%; text-align:center; padding-bottom:80px;">
                    <b>Direktur Utama</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="width:50%; text-align:center; padding-bottom:80px;">
                    <b>Komite Strategi Teknologi Informasi</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
            </tr>
            <tr>
                <td style="text-align:center;">
                    <b>Vice President Teknologi Informasi</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
                <td style="text-align:center;">
                    <b>Satuan Pengawasan Internal</b><br/><br/><br/><br/><br/>
                    (__________________________)
                </td>
            </tr>
        </table>
        <p style="text-align:center; margin-top:30px;">Jakarta, '.now()->format('d F Y').'</p>
        <p style="text-align:center; margin-top:40px; font-style:italic;">Doc ID: MPTI-DUMMY-'.$this->startYear.'-'.$this->endYear.'-V1.0</p>
        <p style="text-align:center; font-size:9pt; color:#6b7280;">Catatan: Dokumen ini adalah dokumen sintetis untuk development & testing sistem KPI Advisor. Seluruh nama, tanggal, angka bersifat fiktif.</p>';
        $mpdf->WriteHTML($html);
    }

    private function writeTableOfContents(Mpdf $mpdf): void
    {
        $toc = [
            ['BAB I', 'PENDAHULUAN', 9],
            ['1.1', 'Latar Belakang Penyusunan MPTI', 9],
            ['1.2', 'Maksud dan Tujuan MPTI', 11],
            ['1.3', 'Ruang Lingkup & Hubungan dengan RJPP', 12],
            ['1.4', 'Metodologi & Kerangka Penyusunan', 13],
            ['1.5', 'Sistematika Penulisan', 14],
            ['BAB II', 'PROFIL TEKNOLOGI INFORMASI', 16],
            ['2.1', 'Tata Kelola TI Saat Ini', 16],
            ['2.2', 'Inventarisasi Aplikasi', 19],
            ['2.3', 'Inventarisasi Infrastruktur TI', 28],
            ['2.4', 'Sumber Daya Manusia TI', 35],
            ['2.5', 'Anggaran TI Historis', 40],
            ['2.6', 'Layanan TI & Service Level', 44],
            ['BAB III', 'ANALISIS LINGKUNGAN TI', 48],
            ['3.1', 'Analisis Internal TI (SWOT)', 48],
            ['3.2', 'Analisis Tren Teknologi Eksternal', 54],
            ['3.3', 'Benchmarking Internal & Eksternal', 62],
            ['3.4', 'Gap Analysis Tata Kelola (COBIT)', 68],
            ['3.5', 'Penilaian Maturity TI', 74],
            ['BAB IV', 'ARSITEKTUR ENTERPRISE', 78],
            ['4.1', 'Pendekatan TOGAF ADM', 78],
            ['4.2', 'Arsitektur Bisnis', 82],
            ['4.3', 'Arsitektur Data', 90],
            ['4.4', 'Arsitektur Aplikasi', 98],
            ['4.5', 'Arsitektur Teknologi & Infrastruktur', 106],
            ['4.6', 'Arsitektur Keamanan Informasi', 114],
            ['BAB V', 'ARAH DAN STRATEGI TI', 120],
            ['5.1', 'Visi & Misi TI', 120],
            ['5.2', 'Arah Kebijakan_TI (PTI) 5 Tahun', 122],
            ['5.3', 'Sasaran Strategis TI', 126],
            ['5.4', 'Identifikasi Trend Pendukung', 130],
            ['BAB VI', 'ROADMAP DAN INISIATIF PTI', 132],
            ['6.1', 'Klasifikasi Inisiatif PTI', 132],
            ['6.2', 'Prioritisasi Inisiatif', 140],
            ['6.3', 'Roadmap 5 Tahun PTI', 146],
            ['6.4', 'KPI Pendukung Inisiatif TI', 152],
            ['BAB VII', 'INVESTASI TEKNOLOGI INFORMASI', 158],
            ['7.1', 'Estimasi Capex & Opex per Tahun', 158],
            ['7.2', 'Business Case Inisiatif Prioritas', 162],
            ['7.3', 'Total Cost of Ownership (TCO)', 170],
            ['7.4', 'Sumber Pendanaan', 174],
            ['BAB VIII', 'MANAJEMEN RISIKO TI', 176],
            ['8.1', 'Risiko TI Utama & Mitigasi', 176],
            ['8.2', 'Key Risk Indicators (KRI) TI', 182],
            ['BAB IX', 'PENUTUP', 186],
            ['LAMPIRAN A', 'Detail Inisiatif PTI (60 Inisiatif)', 188],
            ['LAMPIRAN B', 'Inventarisasi Aplikasi Lengkap', 260],
            ['LAMPIRAN C', 'Inventarisasi Infrastruktur Lengkap', 320],
            ['LAMPIRAN D', 'Regulasi, SOP & Standar TI', 380],
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
            'MPTI' => 'Master Plan Teknologi Informasi', 'RJPP' => 'Rencana Jangka Panjang Perusahaan',
            'RBB' => 'Rencana Bisnis dan Anggaran', 'PTI' => 'Pengembangan Teknologi Informasi',
            'TIK' => 'Teknologi Informasi dan Komunikasi', 'TOGAF' => 'The Open Group Architecture Framework',
            'ADM' => 'Architecture Development Method', 'COBIT' => 'Control Objectives for Information and Related Technologies',
            'ITIL' => 'IT Infrastructure Library', 'BI' => 'Business Intelligence',
            'ERP' => 'Enterprise Resource Planning', 'EA' => 'Enterprise Architecture',
            'KPI' => 'Key Performance Indicator', 'KRI' => 'Key Risk Indicator',
            'SLA' => 'Service Level Agreement', 'ITSM' => 'IT Service Management',
            'SOC' => 'Security Operation Center', 'IAM' => 'Identity and Access Management',
            'PAM' => 'Privileged Access Management', 'DLP' => 'Data Loss Prevention',
            'EDR' => 'Endpoint Detection and Response', 'SIEM' => 'Security Information and Event Management',
            'API' => 'Application Programming Interface', 'MDM' => 'Master Data Management',
            'ETL' => 'Extract Transform Load', 'DW' => 'Data Warehouse',
            'DRP' => 'Disaster Recovery Plan', 'BCP' => 'Business Continuity Plan',
            'RTO' => 'Recovery Time Objective', 'RPO' => 'Recovery Point Objective',
            'IaaS' => 'Infrastructure as a Service', 'PaaS' => 'Platform as a Service',
            'SaaS' => 'Software as a Service', 'TCO' => 'Total Cost of Ownership',
            'ROI' => 'Return on Investment', 'PMO' => 'Project Management Office',
            'DPIA' => 'Data Protection Impact Assessment', 'GRC' => 'Governance Risk Compliance',
            'RACI' => 'Responsible Accountable Consulted Informed', 'CDN' => 'Content Delivery Network',
            'CI/CD' => 'Continuous Integration / Continuous Deployment', 'IaC' => 'Infrastructure as Code',
            'RPA' => 'Robotic Process Automation', 'AI' => 'Artificial Intelligence',
            'ML' => 'Machine Learning', 'IoT' => 'Internet of Things',
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
            'Master Plan Teknologi Informasi (MPTI) '.$this->company.' periode '.$this->period.' merupakan dokumen perencanaan strategis bidang TI lima tahunan yang menyelaraskan arah TI dengan tujuan perusahaan sebagaimana ditetapkan dalam RJPP '.$this->period.'. Dokumen ini menetapkan arah Pengembangan Teknologi Informasi (PTI) yang sejalan dengan tujuan bisnis dan mendukung tercapainya Sasaran Strategis perusahaan.',
            'MPTI '.$this->period.' disusun menggunakan kerangka The Open Group Architecture Framework (TOGAF) ADM dan diukur kematangannya melalui COBIT 2019. Penyusunan mencakup empat pilar utama: (i) Modernisasi Layanan TIinternal, (ii) Konsolidasi Data & Aplikasi, (iii) Penguatan Tata Kelola dan Keamanan Informasi, (iv) Adopsi Teknologi Strategis (AI, IoT, dan Otomasi).',
            'Total kebutuhan investasi TI selama periode MPTI diperkirakan Rp 480 miliar, terdiri dari Capex Rp 280 miliar dan Opex Rp 200 miliar. Estimasi agregat ROI inisiatif TI adalah 18,4%, dengan rata-rata payback period 3,6 tahun.',
            'MPTI mengidentifikasi 60 inisiatif PTI yang diprioritaskan. Inisiatif prioritas tertinggi mencakup modernisasi ERP, pembangunan Data Warehouse Enterprise, penguatan Security Operation Center (SOC), dan implementasi AI Vision Inspection untuk mendukung mutu produksi.',
            'Manajemen risiko TI mengidentifikasi 32 risikoTI aktif dengan 6 risiko pada level ekstrem, khususnya pada aspek keamanan siber, kelangsungan layanan, dan ketergantungan vendor. Mitigasi dilakukan melalui penguatan BCP/DR, sertifikasi ISO 27001, dan strategi multi-vendor.',
            'Dokumen ini bersifat sintetis (DUMMY) dan disusun mengikuti struktur dokumen MPTI BUMN pada umumnya untuk keperluan development dan testing sistem KPI Advisor. Seluruh data, angka, dan narasi bersifat fiktif.',
        ];
        foreach ($paras as $p) {
            $html .= '<p style="text-align:justify; text-indent:30px; line-height:1.6;">'.htmlspecialchars($p).'</p>';
        }
        $mpdf->WriteHTML($html);
    }

    // ---------- BAB ----------

    private function writeChapterPendahuluan(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB I PENDAHULUAN', [
            '1.1 Latar Belakang Penyusunan MPTI' => [
                'Transformasi digital telah menjadi agenda strategis nasional, termasuk di lingkungan BUMN. Percepatan adopsi teknologi informasi menuntut perusahaan menyusun perencanaan TI jangka panjang yang terstruktur, terukur, dan terhubung langsung dengan sasaran bisnis RJPP.',
                'MPTI '.$this->period.' disusun melanjutkan MPTI periode sebelumnya. Beberapa temuan utama periode sebelumnya: masih adanya silo aplikasi, ketergantungan tinggi vendor ERP, keterbatasan kapabilitas data analytics, serta kebutuhan penguatan keamanan informasi pasca perkembangan ancaman siber.',
                'Penyusunan MPTI '.$this->period.' memperhatikan tren teknologi terkini berupa Artificial Intelligence (AI), hyper-automation, generative AI, Zero Trust Architecture, Edge Computing, dan quantum-safe cryptography. Tren ini akan dianalisis keterjangkauannya melalui bab Analisis Lingkungan TI.',
            ],
            '1.2 Maksud dan Tujuan MPTI' => [
                'Maksud penyusunan MPTI adalah memberikan arah strategis TI dalam jangka panjang lima tahun yang terukur, terintegrasi, dan dapat dipertanggungjawabkan.',
                'Tujuan MPTI: (1) Menyelaraskan arah TI dengan RJPP; (2) Menetapkan arah PTI prioritas; (3) Memberikan kerangka penganggaran TI 5 tahun; (4) Mengukur kinerja TI melalui KPI dan KRI; (5) Menjadi dasar penyusunan Rencana Kerja dan Anggaran TI tahunan.',
            ],
            '1.3 Ruang Lingkup & Hubungan dengan RJPP' => [
                'Ruang lingkup MPTI mencakup seluruh aspek TI perusahaan, termasuk tata kelola, aplikasi, infrastruktur, data, keamanan, SDM TI, dan inisiatif PTI. MPTI turunan langsung dari RJPP dan menjadi dasar penyusunan Rencana Kerja dan Anggaran (RKB) TI tahunan.',
                'Hubungan langsung ke sasaran strategis RJPP: SS-03 Penguatan Tata Kelola Digital dan SS-06 Penguatan Cyber Security Posture menjadi dua sasaran utama yang diturunkan ke arah PTI MPTI.',
            ],
            '1.4 Metodologi & Kerangka Penyusunan' => [
                'Metodologi penyusunan MPTI mengacu pada kerangka TOGAF ADM (Architecture Development Method) untuk arsitektur enterprise dan COBIT 2019 untuk tata kelola TI ISO 27001, ISO 20000, dan ISO 31000 digunakan sebagai pendukung pada aspek keamanan, layanan, dan risiko.',
                'Tahapan ADM yang digunakan meliputi Preliminary Phase, Architecture Vision, Business Architecture, Information Systems Architecture (Data & Application), Technology Architecture, Opportunities & Solutions, Migration Planning, dan Implementation Governance.',
            ],
            '1.5 Sistematika Penulisan' => [
                'Dokumen MPTI disusun dalam sembilan bab dan empat lampiran. Bab-bab menjawab aspek tertentu secara analitis, mulai dari profil TI saat ini, analisis lingkungan, arsitektur enterprise, hingga roadmap PTI dan investasi TI.',
            ],
        ]);
    }

    private function writeChapterProfilTI(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB II PROFIL TEKNOLOGI INFORMASI</h1>';

        $html .= '<h2>2.1 Tata Kelola TI Saat Ini</h2>';
        $html .= $this->paragraphs([
            'Tata kelola TI perusahaan mengacu pada kerangka COBIT 2019 dan diselaraskan dengan prinsip Good Corporate Governance. Struktur tata kelola terdiri dari Komite Strategi TI, Steering TI, PMO TI, dan unit pelaksana TI.',
            'Komite Strategi TI dipimpin oleh Direktur Utama dengan anggota Direktur Terkait dan VP TI. Steering TI bertugas mengarahkan portofolio proyek TI, sementara PMO TI memantau eksekusi.',
            'Penilaian maturity COBIT terakhir menunjukkan rata-rata level 2,9 (Defined Process) dengan beberapa domain belum mencapai level 3 (Managed). Domain yang memerlukan peningkatan meliputi APO (Align, Plan and Organize) dan BAI (Build, Acquire and Implement).',
        ]);

        $html .= '<h2>2.2 Inventarisasi Aplikasi</h2>';
        $html .= $this->paragraphs([
            'Total 84 aplikasi aktif digunakan oleh perusahaan. Sebanyak 32 aplikasi bersifat strategis kritikal, 28 aplikasi menengah, dan 24 aplikasi pendukung. Distribusi kepemilikan: 53 custom development, 18 COTS, dan 13 Software-as-a-Service (SaaS).',
            'Status kesehatan aplikasi: 62% sehat, 23% perlu pemeliharaan, 12% perlu modernisasi, 3% sudah tidak terpakai dan akan dipensiunkan.',
        ]);
        $appRows = [];
        $appNames = ['ERP Core', 'Helpdesk ITSM', 'GIS Logistik', 'Customer Portal', 'Mobile Apps', 'Cyber SOC', 'BI Dashboard', 'Pakta Integritas Digital', 'Sistem Reimburse', 'Sistem Payroll', 'Sistem Aset Tetap', 'Perizinan Online', 'NOC Monitoring', 'Single Note Inspection', 'AI Vision Inspection', 'Blockchain Traceability', 'Vendor Management', 'E-Learning', 'Recruitment Portal', 'GRC Platform'];
        foreach ($appNames as $i => $name) {
            $appRows[] = [
                'APP-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $name,
                $this->faker->randomElement(['Custom', 'Oracle', 'SAP', 'Microsoft', 'Power BI', 'SentinelOne', 'ServiceNow', 'Flutter', 'MuleESB']),
                $this->faker->randomElement([$this->startYear - 6, $this->startYear - 4, $this->startYear - 3, $this->startYear - 2, $this->startYear - 1]),
                $this->faker->randomElement(['Kritikal', 'Penting', 'Pendukung']),
                $this->faker->randomElement(['Sehat', 'Perlu Pemeliharaan', 'Perlu Modernisasi']),
                $this->randItem($this->unitPool),
            ];
        }
        $html .= $this->smallTable(['Kode', 'Aplikasi', 'Platform', 'Tahun', 'Klasifikasi', 'Status', 'PIC'], $appRows);

        $html .= '<h2>2.3 Inventarisasi Infrastruktur TI</h2>';
        $html .= $this->paragraphs([
            'Infrastruktur TI perusahaan terdiri dari 2 Data Center primer di Jakarta dan Bandung, 1 Disaster Recovery Center (DRC) di Surabaya, dengan total 145 server fisik, 380 VM, dan 12 hyperconverged nodes.',
            'Konektivitas jaringan terdiri dari 3 ISP dengan total bandwidth 8 Gbps, dukungan SD-WAN di 12 cabang, dan WiFi 6 di 8 lokasi utama.',
        ]);
        $infraRows = [];
        $infraTypes = ['Server Fisik', 'Virtual Machine', 'Storage SAN', 'Storage NAS', 'Hyperconverged', 'Firewall NGFW', 'Router', 'Switch Core', 'Access Point WiFi 6', 'Load Balancer', 'Backup Appliance', 'UPS'];
        foreach ($infraTypes as $i => $type) {
            $infraRows[] = [
                'INF-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $type,
                $this->faker->numberBetween(5, 400),
                $this->faker->randomElement(['Aktif', 'Cadangan', 'Pensiun']),
                $this->faker->numberBetween(1, 7).' tahun',
                $this->randItem(['Jakarta', 'Bandung', 'Surabaya']),
            ];
        }
        $html .= $this->smallTable(['Kode', 'Tipe', 'Jumlah Unit', 'Status', 'Usia', 'Lokasi'], $infraRows);

        $html .= '<h2>2.4 Sumber Daya Manusia TI</h2>';
        $html .= $this->paragraphs([
            'Total pegawai TI per '.($this->startYear - 1).' adalah 187 orang, terdiri dari 38 SDM aplikasi, 42 SDM infrastruktur, 28 SDM security, 35 SDM proyek (PMO), dan 44 SDM operasional ITSM (helpdesk & support).',
            'Komposisi pengalaman: senior (lebih dari 10 tahun) 28%, mid (5-10 tahun) 35%, junior (kurang dari 5 tahun) 37%. Penilaian kompetensi menunjukkan kebutuhan penguatan pada area cloud architecture, AI/ML engineering, dan DevOps.',
        ]);

        $html .= '<h2>2.5 Anggaran TI Historis</h2>';
        $budgetRows = [];
        for ($y = -5; $y <= -1; $y++) {
            $year = (int) $this->startYear + $y;
            $capex = $this->faker->numberBetween(35, 80);
            $opex = $this->faker->numberBetween(25, 55);
            $budgetRows[] = [$year, $capex, $opex, $capex + $opex, $this->faker->randomFloat(2, 1.5, 4).'%'];
        }
        $html .= '<p>Anggaran TI 5 tahun historis (dalam Miliar Rupiah):</p>'.$this->smallTable(['Tahun', 'Capex', 'Opex', 'Total', '% dari Pendapatan'], $budgetRows);

        $html .= '<h2>2.6 Layanan TI & Service Level</h2>';
        $html .= $this->paragraphs([
            'Layanan TI perusahaan terdaftar dalam Service Catalog yang menetapkan SLA per layanan. Pencapaian SLA rata-rata 92,4% dengan SLA kepatuhan tertinggi pada layanan email dan collaboration, terendah pada layanan ERP dan BI.',
        ]);
        $slaRows = [];
        $services = ['Email Server', 'ERP Core', 'BI Dashboard', 'Helpdesk Tier 1', 'Helpdesk Tier 2', 'Network Core', 'VPN Remote', 'Identity Provider', 'Backup Service', 'DRC Failover'];
        foreach ($services as $i => $svc) {
            $slaRows[] = [
                'SVC-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $svc,
                $this->faker->randomElement(['99.9%', '99.5%', '99.0%', '98.5%']),
                $this->faker->randomFloat(2, 88, 99.8).'%',
                $this->faker->randomElement(['Tepat SLA', 'Slight Miss', 'Violation']),
            ];
        }
        $html .= $this->smallTable(['Kode', 'Layanan', 'SLA Target', 'Realisasi', 'Status'], $slaRows);

        $mpdf->WriteHTML($html);
    }

    private function writeChapterAnalisisTI(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB III ANALISIS LINGKUNGAN TI', [
            '3.1 Analisis Internal TI (SWOT)' => [
                '<table style="width:100%; border-collapse:collapse;" border="1"><tr><td style="width:50%; padding:10px;"><b>STRENGTH</b><ul><li>SDM TI kompeten dan tersertifikasi</li><li>Memiliki SOC aktif</li><li>Service catalog terdefinisi</li><li>3 data center strategis</li></ul></td><td style="width:50%; padding:10px;"><b>WEAKNESS</b><ul><li>Aplikasi silo</li><li>Ketergantungan vendor ERP</li><li>Master data belum terkonsolidasi</li><li>Maturity COBIT masih &lt; 3</li><li>BI belum enterprise-wide</li></ul></td></tr><tr><td style="padding:10px;"><b>OPPORTUNITY</b><ul><li>AI vision untuk mutu</li><li>Cloud adoption</li><li>Hyper-automation</li><li>Generative AI</li><li>Zero Trust Security</li></ul></td><td style="padding:10px;"><b>THREAT</b><ul><li>Ancaman APT</li><li>Regulasi PDP ketat</li><li>Brain drain SDM TI</li><li>Kenaikan biaya vendor</li><li>Solusi legacy tidak didukung</li></ul></td></tr></table>',
            ],
            '3.2 Analisis Tren Teknologi Eksternal' => $this->techTrendNarratives(),
            '3.3 Benchmarking Internal & Eksternal' => [
                'Benchmarking internal terhadap unit kerja menunjukkan adopsi TI tertinggi pada Direktorat Keuangan dan TI sendiri, terendah pada Direktorat RnD dan beberapa cabang operasional.',
                'Benchmarking eksternal dilakukan terhadap 6 BUMN sektor serupa (cetak uang/keuangan/sekuriti). Hasil: perusahaan mengungguli benchmark pada aspek cyber security posture namun tertinggal pada aspek digital customer engagement.',
            ],
            '3.4 Gap Analysis Tata Kelola (COBIT)' => $this->gapCobitTable(),
            '3.5 Penilaian Maturity TI' => $this->maturityTable(),
        ]);
    }

    private function techTrendNarratives(): array
    {
        $trends = [
            ['Artificial Intelligence & Generative AI', 'Adopsi AI secara umum sudah mencapai level praktis: computer vision untuk QC, NLP untuk helpdesk, predictive maintenance untuk mesin. Generative AI berpotensi mempercepat analisis dokumen, code generation, dan knowledge assistance. Risiko: hallucination, privasi data, dan intellectual property.'],
            ['Zero Trust Architecture', 'Pendekatan trust-but-verify digantikan dengan never-trust-always-verify, micro-segmentation, dan continuous authentication. Standar NIST SP 800-207 menjadi acuan implementasi.'],
            ['Hyper-automation & RPA', 'RPA cocok untuk proses rule-based berulang (F&A, HR), sedangkan orchestration layer menggabungkan RPA, AI, dan workflow untuk end-to-end automation. Potensi penghematan FTE 8-15% pada area back-office.'],
            ['Cloud Native', 'Pendekatan cloud-first menjadi pilihan untuk workload baru, dengan strategi hybrid-cloud untuk workload lama. Container (Kubernetes), serverless, dan Infrastructure-as-Code mempercepat lead time delivery.'],
            ['Blockchain Traceability', 'Untuk produk dengan rantai pasok terpercaya seperti uang logam dan dokumen sekuriti, blockchain consortium memberikan traceability immutable. Trade-off: throughput masih rendah dibanding database konvensional.'],
            ['Cybersecurity Mesh & SOC Modernization', 'Decoupling security controls dari perimeter jaringan, XDR (Extended Detection and Response), dan SOC modern berbasis SOAR (Security Orchestration Automation Response).'],
            ['Data Fabric & Data Democratization', 'Konsep active metadata dan knowledge graph untuk federasi data. Data democratization melalui self-service analytics dengan governance (Data Mesh, Data Contracts).'],
            ['Internet of Things (IoT) & Edge Analytics', 'IoT sensor untuk produksi mendukung real-time monitoring, dengan edge analytics untuk inferensi lokal berlatensi rendah.'],
        ];

        $out = [];
        foreach ($trends as $t) {
            $out[] = '<b>'.$t[0].'</b><br/>'.htmlspecialchars($t[1]);
        }

        return $out;
    }

    private function gapCobitTable(): array
    {
        $domains = ['EDM (Evaluate, Direct and Monitor)', 'APO (Align, Plan and Organise)', 'BAI (Build, Acquire and Implement)', 'DSS (Deliver, Service and Support)', 'MEA (Monitor, Evaluate and Assess)'];
        $rows = [];
        foreach ($domains as $d) {
            $rows[] = [
                $d,
                $this->faker->randomFloat(1, 2, 3).'.x',
                '4.x',
                'Gap '.($this->faker->numberBetween(1, 2)).' level',
                $this->faker->randomElement(['Inisiasi', 'Eksekusi Sebagian', 'Sedang Berjalan']),
            ];
        }

        return ['Gap analysis COBIT 2019:'.$this->smallTable(['Domain', 'Maturity Saat Ini', 'Target Maturity', 'Gap', 'Status Aksi'], $rows)];
    }

    private function maturityTable(): array
    {
        $aspects = ['Tata Kelola TI', 'Manajemen Aplikasi', 'Infrastruktur', 'Data Management', 'Keamanan Informasi', 'Manajemen Layanan (ITSM)', 'Inovasi & Adopsi Teknologi Baru'];
        $rows = [];
        foreach ($aspects as $a) {
            $rows[] = [$a, $this->faker->randomFloat(1, 2.4, 3.2), $this->faker->randomFloat(1, 4.0, 4.8)];
        }

        return ['Penilaian maturity TI:'.$this->smallTable(['Aspek', 'Maturity Saat Ini', 'Target '.$this->endYear], $rows)];
    }

    private function writeChapterArsitektur(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB IV ARSITEKTUR ENTERPRISE</h1>';
        $html .= '<h2>4.1 Pendekatan TOGAF ADM</h2>';
        $html .= $this->paragraphs([
            'Penyusunan arsitektur enterprise menggunakan kerangka TOGAF ADM sebagai metodologi utama, dengan deliverable arsitektur mencakup Architecture Vision, Business/Data/Application/Technology Architecture, dan Opportunities & Solutions. Setiap iterasi dilakukan secara incremental dan diatur melalui Architecture Board.',
        ]);

        $html .= '<h2>4.2 Arsitektur Bisnis</h2>';
        $html .= $this->paragraphs([
            'Arsitektur bisnis memetakan capability map perusahaan, value stream, dan organisasi. Capability dibagi menjadi 4 klaster utama: Operasional Produksi, Layanan Komersial, Tata Kelola, dan Pengembangan Teknologi.',
        ]);
        $capRows = [];
        $caps = ['Pencetakan Uang Kertas', 'Pengelolaan Uang Logam', 'Sertifikasi & Dokumen Sekuriti', 'Logistik & Distribusi', 'Mutu & Compliance', 'Layanan Digital Pelanggan', 'Manajemen SDM', 'Manajemen Keuangan', 'Layanan TI Internal', 'RnD & Material'];
        foreach ($caps as $i => $c) {
            $capRows[] = [
                'CAP-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                $c,
                $this->faker->randomElement(['Strategis', 'Operasional', 'Pendukung']),
                $this->randItem($this->unitPool),
                $this->faker->randomElement(['Initiating', 'Mature', 'Optimizing']),
            ];
        }
        $html .= $this->smallTable(['Kode Capability', 'Capability', 'Tipe', 'Owner', 'Status'], $capRows);

        $html .= '<h2>4.3 Arsitektur Data</h2>';
        $html .= $this->paragraphs([
            'Arsitektur data menjelaskan model data enterprise, master data, data domain, dan sumber daya analytics. Domain data utama: Pelanggan, Produk, Transaksi, Aset, SDM, Keuangan, Risiko, dan Teknis Operasional.',
            'Strategi konsolidasi data melalui Master Data Management (MDM) dengan pendekatan registry model, didukung Data Warehouse enterprise untuk analytics dan Data Lake untuk semi-structured & unstructured data.',
        ]);
        $domainRows = [];
        $domains = ['Pelanggan', 'Produk', 'Transaksi', 'Aset', 'SDM', 'Keuangan', 'Risiko', 'Teknis'];
        foreach ($domains as $d) {
            $domainRows[] = [$d, $this->faker->randomElement(['Master', 'Transaksi', 'Referensi']), $this->faker->randomElement(['Terpusat', 'Distribusi', 'Federasi']), $this->faker->randomElement(['Tertata', 'Perlu Konsolidasi', 'Silo'])];
        }
        $html .= $this->smallTable(['Domain', 'Tipe Data', 'Mode Pengelolaan', 'Status Saat Ini'], $domainRows);

        $html .= '<h2>4.4 Arsitektur Aplikasi</h2>';
        $html .= $this->paragraphs([
            'Arah arsitektur aplikasi menuju composable, microservices-ready, API-first, dan event-driven. Aplikasi lama dengan arsitektur monolith akan dipensiunkan secara bertahap, atau di-strangler-pattern dengan API Gateway dan BFF (Backend for Frontend).',
            'Komponen platform: API Gateway, Identity Provider (IdP), Integration Platform (iPaaS), Event Streaming Platform, dan Application Monitoring (APM).',
        ]);

        $html .= '<h2>4.5 Arsitektur Teknologi & Infrastruktur</h2>';
        $html .= $this->paragraphs([
            'Arah infrastruktur: hybrid cloud dengan public cloud untuk workload elastis (Dev/Test, BI data mart) dan private cloud untuk workload kritikal transaksional dan regulated data.',
            'Strategi data center: konsolidasi dari 3 DC menjadi 2 DC primer aktif-aktif dengan 1 DRC. Container platform berbasis Kubernetes for aplikasi cloud-native, dan SDN untuk networking abstraksi.',
        ]);

        $html .= '<h2>4.6 Arsitektur Keamanan Informasi</h2>';
        $html .= $this->paragraphs([
            'Arsitektur keamanan mengacu pada Zero Trust Architecture (NIST SP 800-207) dan ISO 27001:2022. Komponen: IAM, PAM, EDR, SIEM/SOAR, DLP, Email Security, Web Gateway, dan Cloud Security Posture Management (CSPM).',
            'Segmentation: micro-segmentation berbasis workload identity, dengan enforcement NSG/Tier-0 firewall dan service mesh untuk mTLS.',
        ]);

        $mpdf->WriteHTML($html);
    }

    private function writeChapterArahStrategiTI(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB V ARAH DAN STRATEGI TI</h1>';
        $html .= '<h2>5.1 Visi & Misi TI</h2>';
        $html .= '<p><b>Visi TI:</b> Menjadi enabler transformasi digital terpercaya yang mempercepat tercapainya tujuan perusahaan.</p>';
        $html .= '<p><b>Misi TI:</b></p><ul>';
        $misi = [
            'Menyelenggarakan layanan TI yang andal, aman, dan responsif.',
            'Mengelola teknologi sebagai aset strategis untuk mendukung pertumbuhan bisnis.',
            'Mendorong inovasi digital dan adopsi teknologi baru secara terukur.',
            'Menjamin kepatuhan terhadap regulasi dan standar keamanan informasi.',
            'Membangun SDM TI yang kompeten dan adaptif terhadap perubahan.',
        ];
        foreach ($misi as $m) {
            $html .= '<li>'.htmlspecialchars($m).'</li>';
        }
        $html .= '</ul>';

        $html .= '<h2>5.2 Arah Kebijakan_TI (PTI) 5 Tahun</h2>';
        $html .= $this->paragraphs([
            'Arah PTI dijabarkan ke dalam 8 arah utama berikut ini yang akan diturunkan ke inisiatif prioritas dan KPI TI pada bab selanjutnya.',
        ]);
        $arahRows = [
            ['PTI-01', 'Penguatan Layanan TI Internal', 'SLA, ITSM, ITIL, kapabilitas helpdesk'],
            ['PTI-02', 'Konsolidasi Data & Aplikasi', 'MDM, DW, API, migrasi legacy'],
            ['PTI-03', 'Modernisasi ERP Core', 'upgrade platform, modul baru, integrasi'],
            ['PTI-04', 'Pengu Tata Kelola TI', 'COBIT 2019 target maturity 4, GRC'],
            ['PTI-05', 'Penguatan Keamanan Informasi', 'Zero Trust, SOC 24/7, ISO 27001'],
            ['PTI-06', 'Pengembangan SDM TI', 'kompetensi cloud, AI/ML, DevOps'],
            ['PTI-07', 'Digitalisasi Bisnis & Layanan', 'mobile apps, portal, RPA, otomasi'],
            ['PTI-08', 'Adopsi Teknologi Baru', 'AI, IoT, blockchain, edge, generative AI'],
        ];
        $html .= $this->smallTable(['Kode', 'Arah PTI', 'Fokus'], $arahRows);

        $html .= '<h2>5.3 Sasaran Strategis TI</h2>';
        $ssRows = [
            ['SS-TI-01', 'Meningkatkan SLA layanan TI mencapai 99,5% pada akhir periode'],
            ['SS-TI-02', 'Mencapai maturity COBIT level 4 minimal untuk domain APO dan BAI'],
            ['SS-TI-03', 'Mencapai certification ISO 27001 lingkup enterprise'],
            ['SS-TI-04', 'Mengkonsolidasikan 84 aplikasi menjadi 55 pada akhir periode'],
            ['SS-TI-05', 'Mencapai Zero Fatal Cybersecurity Incident pada '.$this->endYear],
            ['SS-TI-06', 'Mencapai self-service data analytics untuk 80% kebutuhan panduan'],
            ['SS-TI-07', 'Meningkatkan kompetensi SDM TI dengan minimal 60% tersertifikasi'],
            ['SS-TI-08', 'Mencapai adopsi digital 70% untuk layanan prioritas pelanggan'],
        ];
        $html .= $this->smallTable(['Kode', 'Sasaran Strategis TI'], $ssRows);

        $html .= '<h2>5.4 Identifikasi Trend Pendukung</h2>';
        $html .= $this->paragraphs([
            'Tren teknologi pendukung MPTI '.$this->period.' telah dianalisis pada BAB III. Tren yang akan diadopsi berjenjang dalam 5 tahun mencakup: AI/ML, generative AI, Zero Trust, hyper-automation, cloud-native, data fabric, IoT & edge analytics, dan blockchain traceability.',
        ]);

        $mpdf->WriteHTML($html);
    }

    private function writeChapterRoadmapPTI(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1 style="text-align:center;">BAB VI ROADMAP DAN INISIATIF PTI</h1>';

        $html .= '<h2>6.1 Klasifikasi Inisiatif PTI</h2>';
        $html .= $this->paragraphs(['Inisiatif PTI dikelompokkan ke dalam 8 arah PTI dengan total 60 inisiatif strategis.']);
        $klasRows = [];
        foreach (['PTI-01', 'PTI-02', 'PTI-03', 'PTI-04', 'PTI-05', 'PTI-06', 'PTI-07', 'PTI-08'] as $p) {
            $klasRows[] = [$p, $this->faker->numberBetween(5, 11), $this->faker->numberBetween(40, 100).' M', $this->faker->randomElement(['Tinggi', 'Sedang'])];
        }
        $html .= $this->smallTable(['Arah PTI', 'Jumlah Inisiatif', 'Estimasi Investasi', 'Prioritas'], $klasRows);

        $html .= '<h2>6.2 Prioritisasi Inisiatif</h2>';
        $html .= $this->paragraphs([
            'Prioritas inisiatif diukur dengan kriteria: dampak terhadap sasaran strategis TI, urgensi bisnis, kompleksitas, estafet dari periode sebelumnya, dan ketergantungan dengan inisiatif lain.',
            'Hasil prioritisasi: 12 inisiatif prioritas tertinggi (P1), 24 prioritas sedang (P2), 24 prioritas pendukung (P3).',
        ]);

        $html .= '<h2>6.3 Roadmap 5 Tahun PTI</h2>';
        $initiatives = $this->randomUniqueInitiatives(60);
        $roadmapRows = [];
        for ($y = 0; $y < 5; $y++) {
            $year = (int) $this->startYear + $y;
            $phase = $this->faker->randomElement(['Tahun 1: Fondasi', 'Tahun 2: Eksekusi', 'Tahun 3: Ekspansi', 'Tahun 4: Optimalisasi', 'Tahun 5: Konsolidasi']);
            $items = array_slice($initiatives, $y * 12, 12);
            $roadmapRows[] = [$year.' - '.$phase, implode('; ', $items)];
        }
        $html .= $this->smallTable(['Fase / Tahun', 'Inisiatif Prioritas'], $roadmapRows);

        $html .= '<h2>6.4 KPI Pendukung Inisiatif TI</h2>';
        $html .= $this->paragraphs(['Berikut KPI pendukung TI yang akan dipantau untuk mengukur pencapaian MPTI. KPI ini menjadi input bagi sistem KPI Advisor.']);
        $kpiRows = $this->buildKpiTI();
        $html .= $this->smallTable(['Kode', 'Perspective', 'Measurement', 'Formula', 'Unit', 'Target '.$this->endYear, 'Weight'], $kpiRows);

        $mpdf->WriteHTML($html);
    }

    private function buildKpiTI(): array
    {
        $kpis = [
            ['FIN', 'Financial TI', 'TI ROI', 'Net Benefit TI / Total Investasi TI × 100%', '%', 18.0, 10.0],
            ['FIN', 'Financial TI', 'TI Opex Ratio', 'Opex TI / Total Opex', '%', 8.0, 5.0],
            ['FIN', 'Financial TI', 'Cost per Ticket', 'Total Cost / Tiket Helpdesk', 'Rp', 180000, 120000],
            ['CUS', 'Layanan TI', 'Service Availability', 'Uptime / Total Time', '%', 99.5, 12.0],
            ['CUS', 'Layanan TI', 'Customer Satisfaction TI (CSAT)', 'Satisfied / Total Survey', '%', 90, 10.0],
            ['CUS', 'Layanan TI', 'First Call Resolution', 'Resolved Tier 1 / Total Ticket', '%', 75, 6.0],
            ['CUS', 'Layanan TI', 'Mean Time to Resolve (MTTR)', 'Avg Resolve Time', 'Hour', 6, 8.0],
            ['CUS', 'Layanan TI', 'Application Adoption Rate', 'User Aktif / Total Berlisensi', '%', 85, 6.0],
            ['PRC', 'Tata Kelola TI', 'COBIT Maturity Average', 'Aggregated COBIT', 'Level', 4.0, 8.0],
            ['PRC', 'Tata Kelola TI', 'Project On-Time Delivery', 'On-Time / Total Project', '%', 90, 6.0],
            ['PRC', 'Tata Kelola TI', 'Project On-Budget', 'On-Budget / Total Project', '%', 85, 4.0],
            ['PRC', 'Tata Kelola TI', 'Change Success Rate', 'Successful Changes / Total', '%', 95, 4.0],
            ['PRC', 'Data & Aplikasi', 'Application Consolidation', 'Aplikasi Aktif', 'count', 55, 5.0],
            ['PRC', 'Data & Aplikasi', 'API Uptime', 'API Uptime / Total', '%', 99.5, 4.0],
            ['PRC', 'Data & Aplikasi', 'Self-Service Analytics Adoption', 'KPI via Dashboard / Total', '%', 80, 4.0],
            ['PRC', 'Keamanan Informasi', 'Cybersecurity Incident Rate', 'Incident / Endpoint', 'ratio', 0.5, 8.0],
            ['PRC', 'Keamanan Informasi', 'Patch Compliance', 'Patched within SLA / Total', '%', 95, 5.0],
            ['PRC', 'Keamanan Informasi', 'Zero-Day Exploit Mitigation Time', 'Time to Mitigate', 'Hour', 24, 5.0],
            ['PRC', 'Keamanan Informasi', 'Vulnerability Remediation SLA', 'On-SLA / Total', '%', 90, 5.0],
            ['PRC', 'Keamanan Informasi', 'Awareness Training Coverage', 'Trained / Total Pegawai', '%', 100, 4.0],
            ['PRC', 'Continuity', 'DR Test Pass Rate', 'Pass / Total Test', '%', 100, 3.0],
            ['PRC', 'Continuity', 'Backup Success Rate', 'Successful Backup / Total', '%', 99, 3.0],
            ['LNG', 'SDM TI', 'SDM TI Certification Coverage', 'Tersertifikasi / Total SDM TI', '%', 60, 6.0],
            ['LNG', 'SDM TI', 'Training Hours per SDM TI', 'Total Hours / SDM TI', 'Hour', 60, 4.0],
            ['LNG', 'SDM TI', 'SDM TI Retention', 'Pegawai Bertahan / Total', '%', 90, 5.0],
        ];
        $rows = [];
        $i = 1;
        foreach ($kpis as $k) {
            $rows[] = ['KPI-'.$k[0].'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), $k[1], $k[2], $k[3], $k[4], $k[5], $k[6]];
            $i++;
        }

        return $rows;
    }

    private function writeChapterInvestasiTI(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB VII INVESTASI TEKNOLOGI INFORMASI', [
            '7.1 Estimasi Capex & Opex per Tahun' => $this->investasiTable(),
            '7.2 Business Case Inisiatif Prioritas' => $this->businessCaseNarratives(),
            '7.3 Total Cost of Ownership (TCO)' => $this->tcoNarratives(),
            '7.4 Sumber Pendanaan' => [
                'Pendanaan investasi TI dialokasikan dari anggaran Capex dan Opex TI tahunan yang bersumber dari laba ditahan perusahaan. Untuk inisiatif strategis berdampak besar, opsi pendanaan tambahan dipertimbangkan melalui vendor financing, leasing, atau kerjasama investasi.',
            ],
        ]);
    }

    private function investasiTable(): array
    {
        $rows = [];
        $totalC = 0;
        $totalO = 0;
        for ($y = 0; $y < 5; $y++) {
            $year = (int) $this->startYear + $y;
            $capex = $this->faker->numberBetween(45, 70);
            $opex = $this->faker->numberBetween(35, 50);
            $totalC += $capex;
            $totalO += $opex;
            $rows[] = [$year, $capex, $opex, $capex + $opex, $this->faker->randomFloat(2, 1.8, 3.2).'%'];
        }
        $rows[] = ['Total', $totalC, $totalO, $totalC + $totalO, '-'];

        return ['Estimasi investasi TI 5 tahun (Miliar Rupiah):'.$this->smallTable(['Tahun', 'Capex', 'Opex', 'Total', '% dari Pendapatan'], $rows)];
    }

    private function businessCaseNarratives(): array
    {
        $cases = [
            'Modernisasi ERP Core: investasi Rp 80 miliar, payback 4 tahun, ROI 17%. Manfaat utama: konsolidasi modul, efisiensi proses 30%, eliminasi 8 aplikasi silo.',
            'Pembangunan Data Warehouse Enterprise: investasi Rp 45 miliar, payback 3,5 tahun, ROI 19%. Manfaat utama: real-time reporting, target 80% self-service analytics.',
            'Penguatan Security Operation Center (SOC): investasi Rp 32 miliar, payback 5 tahun. Manfaat utama: mengurangi incident rate hingga 50% pada akhir periode.',
            'AI Vision Inspection: investasi Rp 25 miliar, payback 2,8 tahun, ROI 24%. Manfaat utama: yield produksi +1,5 pp, defect rate -40%.',
            'Mobile Apps & Customer Portal: investasi Rp 28 miliar, payback 3 tahun, ROI 22%. Manfaat utama: peningkatan adopsi digital layanan prioritas mencapai 70%.',
        ];
        $out = [];
        foreach ($cases as $idx => $case) {
            $out[] = '<b>Business Case #'.($idx + 1).'.</b> '.htmlspecialchars($case);
        }

        return $out;
    }

    private function tcoNarratives(): array
    {
        return [
            'Total Cost of Ownership (TCO) dihitung melalui metode capital dan operasional 5 tahun, mencakup investment awal, lisensi tahunan, support & maintenance, training, SDM, dan biaya pensiun aplikasi.',
            'Pendekatan TCO dijadikan acuan pengambilan keputusan make-or-buy untuk setiap inisiatif PTI. Untuk inisiatif strategis dan kritikal diutamakan model build/own, sementara untuk inisiatif komoditas digunakan model subscribe/buy.',
        ];
    }

    private function writeChapterManajemenRisikoTI(Mpdf $mpdf): void
    {
        $riskRows = [];
        $risks = ['Cyber Attack (Ransomware/APT)', 'Downtime Layanan Kritikal', 'Kebocoran Data Sensitif', 'Ketergantungan Vendor ERP', 'Brain Drain SDM TI', 'Kegagalan Integrasi Sistem Baru', 'Keterlambatan Vendor', 'Standby Failure DRC', 'Regulasi PDP Compliance Gap', 'Cloud Vendor Lock-in'];
        foreach ($risks as $i => $r) {
            $riskRows[] = [
                'RTI-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                $r,
                $this->faker->randomElement(['Ekstrem', 'Tinggi', 'Sedang']),
                $this->faker->randomElement(['Pengurangan', 'Penghindaran', 'Transfer']),
                $this->randItem($this->mitigasiPool),
            ];
        }

        $kriRows = [];
        $kris = ['Jumlah Cyber Incident / Bulan', 'Downtime Menit / Bulan', 'Patch SLA Compliance %', 'Backup Success Rate %', 'DR Test Pass Rate %', 'Open Audit Findings Count', 'Avg MTTR (Hour)', '% Pegawai Lulus Awareness Training'];
        foreach ($kris as $i => $k) {
            $kriRows[] = ['KRI-'.($i + 1), $k, $this->faker->randomElement(['≤ 2', '≤ 30', '≥ 95%', '≥ 99%', '100%']), $this->faker->randomFloat(1, 60, 95)];
        }

        $this->writeChapter($mpdf, 'BAB VIII MANAJEMEN RISIKO TI', [
            '8.1 Risiko TI Utama & Mitigasi' => ['Risk register TI:'.$this->smallTable(['Kode', 'Risiko', 'Level', 'Strategi', 'Mitigasi'], $riskRows)],
            '8.2 Key Risk Indicators (KRI) TI' => ['Key Risk Indicators TI:'.$this->smallTable(['Kode', 'KRI', 'Ambang', 'Realisasi'], $kriRows)],
        ]);
    }

    private function writeChapterPenutup(Mpdf $mpdf): void
    {
        $this->writeChapter($mpdf, 'BAB IX PENUTUP', [
            '9.1 Penutup' => [
                'MPTI '.$this->period.' menjadi peta jalan transformasi TI perusahaan lima tahun ke depan. Keberhasilan eksekusi memerlukan komitmen Direksi, dukungan anggaran yang berkelanjutan, dan kolaborasi lintas unit kerja.',
                'MPTI akan dievaluasi tiap tahun dan diperbarui dengan pendekatan rolling plan melalui RKB TI tahunan.',
                'Catatan: dokumen ini adalah sintetis (dummy) untuk pengembangan sistem KPI Advisor.',
            ],
        ]);
    }

    // ---------- LAMPIRAN ----------

    private function writeLampiranA_PtiDetail(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN A - DETAIL INISIATIF PTI</h1>';
        $html .= '<p>Lampiran ini berisi rincian 60 inisiatif PTI mencakup lingkup, milestone, anggaran, risiko, dan KPI turunan.</p>';
        $initiatives = $this->randomUniqueInitiatives(60);
        for ($i = 0; $i < 60; $i++) {
            $html .= '<h3>PTI-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT).' : '.htmlspecialchars($initiatives[$i]).'</h3>';
            $html .= '<p><b>Arah PTI:</b> '.$this->randItem(['PTI-01', 'PTI-02', 'PTI-03', 'PTI-04', 'PTI-05', 'PTI-06', 'PTI-07', 'PTI-08']).'</p>';
            $html .= '<p><b>Prioritas:</b> '.$this->faker->randomElement(['P1', 'P2', 'P3']).'</p>';
            $html .= '<h4>Lingkup & Pendekatan</h4>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(5)).'</p>';
            $html .= '<h4>Milestone & Deliverable</h4>';
            $html .= $this->milestoneTable();
            $html .= '<h4>Anggaran</h4>';
            $angRows = [];
            for ($y = 0; $y < 5; $y++) {
                $year = (int) $this->startYear + $y;
                $angRows[] = [$year, $this->faker->randomFloat(2, 0.5, 15).' M', $this->faker->randomFloat(2, 0.1, 12).' M', $this->faker->randomElement(['Planned', 'In Progress', 'Completed'])];
            }
            $html .= $this->smallTable(['Tahun', 'Plafon', 'Realisasi', 'Status'], $angRows);
            $html .= '<h4>Risiko Tertaksiran</h4>';
            $riskRows = [];
            for ($r = 1; $r <= 3; $r++) {
                $riskRows[] = ['RTI-'.$i.'-'.$r, ucfirst($this->randItem($this->risikoPool)), $this->faker->randomElement(['Tinggi', 'Sedang', 'Rendah']), $this->randItem($this->mitigasiPool)];
            }
            $html .= $this->smallTable(['Kode', 'Risiko', 'Level', 'Mitigasi'], $riskRows);
            $html .= '<h4>KPI Turunan</h4>';
            $kpiRows = [];
            $tiKpi = $this->buildKpiTI();
            $kpiSample = $this->faker->randomElements($tiKpi, 4, false);
            foreach ($kpiSample as $k) {
                $kpiRows[] = [$k[0], $k[2], $k[4], $k[5]];
            }
            $html .= $this->smallTable(['Kode', 'Measurement', 'Unit', 'Target'], $kpiRows);
            $html .= '<h4>Catatan Pelaksanaan</h4>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->narrativeParagraph(4)).'</p>';
            $html .= '<pagebreak />';
        }
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranB_InventarisAplikasi(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN B - INVENTARISASI APLIKASI LENGKAP</h1>';
        $html .= '<p>Berikut inventarisasi seluruh 84 aplikasi aktif pada awal periode MPTI '.$this->period.' lengkap dengan pemilik, klasifikasi, dan strategi lifecycle.</p>';
        $appRows = [];
        $vendors = ['Custom', 'Oracle', 'SAP', 'Microsoft', 'Power BI', 'SentinelOne', 'ServiceNow', 'Flutter', 'MuleESB', 'OutSystems', 'Red Hat OpenShift', 'Elastic Stack'];
        $units = array_values($this->unitPool);
        for ($i = 1; $i <= 84; $i++) {
            $appRows[] = [
                'APP-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                $this->faker->randomElement(['Sistem Mutu', 'Sistem Layanan', 'Aplikasi Operasional', 'Portal', 'Mobile Apps', 'Modul ERP', 'Modul HR', 'Modul Finance', 'Modul Aset', 'AI Engine']).' '.$i,
                $this->faker->randomElement($vendors),
                $this->faker->numberBetween($this->startYear - 8, $this->startYear - 1),
                $this->faker->randomElement($units),
                $this->faker->randomElement(['Kritikal', 'Penting', 'Pendukung']),
                $this->faker->randomElement(['Sehat', 'Pemeliharaan', 'Modernisasi', 'Pensiun']),
                $this->faker->randomElement(['Build', 'Buy', 'Subscribe', 'Outsource']),
            ];
        }
        $html .= $this->smallTable(['Kode', 'Aplikasi', 'Platform', 'Tahun', 'PIC', 'Klasifikasi', 'Lifecycle', 'Sumber'], $appRows);
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranC_InventarisInfrastruktur(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN C - INVENTARISASI INFRASTRUKTUR LENGKAP</h1>';
        $html .= '<p>Daftar lengkap asset infrastruktur TI: server fisik, virtual machine, storage, network, security appliance, dan peripheral.</p>';
        $infraRows = [];
        $types = ['Server Fisik', 'Virtual Machine', 'Storage SAN', 'Storage NAS', 'Hyperconverged Node', 'Firewall NGFW', 'Web Application Firewall', 'Router', 'Switch Core', 'Switch Access', 'Access Point WiFi 6', 'Load Balancer', 'Backup Appliance', 'UPS', 'GPU Compute'];
        for ($i = 1; $i <= 150; $i++) {
            $type = $this->faker->randomElement($types);
            $infraRows[] = [
                'INF-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                $type,
                $this->faker->randomElement(['Cisco', 'HPE', 'Dell EMC', 'NetApp', 'Pure Storage', 'F5', 'Palo Alto', 'Fortinet', 'Juniper', 'Nutanix', 'Sangfor', 'Check Point']),
                $this->faker->randomElement(['Jakarta-DC1', 'Bandung-DC2', 'Surabaya-DRC', 'Cabang', 'Cloud']),
                $this->faker->numberBetween(1, 8).' thn',
                $this->faker->randomElement(['Aktif', 'Cadangan', 'Pensiun']),
                $this->faker->randomFloat(2, 5, 500).' jt',
            ];
        }
        $html .= $this->smallTable(['Kode', 'Tipe', 'Vendor', 'Lokasi', 'Usia', 'Status', 'Nilai Buku'], $infraRows);
        $mpdf->WriteHTML($html);
    }

    private function writeLampiranD_RegulasiSOP(Mpdf $mpdf): void
    {
        $html = '<pagebreak /><h1>LAMPIRAN D - REGULASI, SOP & STANDAR TI</h1>';
        $docs = [
            'Peraturan Direksi No. 01 - Tata Kelola Teknologi Informasi',
            'Peraturan Direksi No. 02 - Manajemen Keamanan Informasi',
            'Peraturan Direksi No. 03 - Manajemen Layanan TI (ITSM)',
            'Peraturan Direksi No. 04 - Pengelolaan Aplikasi',
            'Peraturan Direksi No. 05 - Pengelolaan Infrastruktur TI',
            'Peraturan Direksi No. 06 - Manajemen Proyek TI',
            'SOP TI-001 Pengadaan Aplikasi & Layanan TI',
            'SOP TI-002 Manajemen Akses (IAM)',
            'SOP TI-003 Manajemen Patch & Vulnerability',
            'SOP TI-004 Backup & Recovery',
            'SOP TI-005 Disaster Recovery Test',
            'SOP TI-006 Incident Response & SOC',
            'SOP TI-007 Change Management',
            'SOP TI-008 Release Management (CI/CD)',
            'SOP TI-009 Vendor Management TI',
            'SOP TI-010 Awareness & Training TI',
            'SOP TI-011 Master Data Management',
            'SOP TI-012 Data Loss Prevention',
            'SOP TI-013 Cloud Service Onboarding',
            'SOP TI-014 Encryption & Key Management',
            'SOP TI-015 Data Protection Impact Assessment (DPIA)',
        ];
        for ($i = 0; $i < count($docs); $i++) {
            $html .= '<h3>'.($i + 1).'. '.htmlspecialchars($docs[$i]).'</h3>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->regulationImpact($docs[$i])).'</p>';
        }
        $html .= '<h2>Standar Internasional Rujukan</h2>';
        $standards = ['ISO/IEC 27001:2022 - Information Security Management', 'ISO/IEC 27017 - Cloud Security', 'ISO/IEC 27018 - PII in Public Cloud', 'ISO 20000-1 - IT Service Management', 'ISO 31000:2018 - Risk Management', 'ISO 9001:2015 - Quality Management', 'COBIT 2019 - IT Governance', 'TOGAF 10 - Enterprise Architecture', 'ITIL 4 - IT Service Management', 'NIST SP 800-207 - Zero Trust Architecture', 'NIST SP 800-53 - Security Controls', 'CIS Critical Security Controls v8'];
        for ($i = 0; $i < count($standards); $i++) {
            $html .= '<h3>'.htmlspecialchars($standards[$i]).'</h3>';
            $html .= '<p style="text-align:justify; text-indent:25px; line-height:1.5;">'.htmlspecialchars($this->regulationImpact($standards[$i])).'</p>';
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
        $mpdf->WriteHTML('<pagebreak /><h1>LAMPIRAN E - DOKUMEN PENDUKUNG RINCI TI</h1>');
        $mpdf->WriteHTML('<p>Lampiran ini berisi paparan rinci atas inisiatif TI, asumsi teknis, analisis dukungan, dan dokumen turunan sebagai bagian dari pelampiran MPTI. Seluruh konten bersifat sintetis namun disusun mengikuti struktur dokumen pendukung MPTI BUMN pada umumnya.</p>');
        $counter = 0;
        $max = 5000;
        while ($mpdf->page < $target && $counter < $max) {
            $counter++;
            $chunk = [];
            $chunk[] = '<h4>Dokumen Pendukung TI #'.$counter.' - '.$this->randItem(['Catatan Asumsi Strategis TI', 'Telaah Risiko Teknologi', 'Telaah Kebutuhan SDM TI', 'Rincian Inisiatif PTI', 'Latar Belakang Teknis Kuantitatif', 'Analisis Dampak Teknologi Baru', 'Proyeksi Infrastruktur', 'Telaah Arsitektur Pendukung']).'</h4>';
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
        $out = '<table style="width:100%; border-collapse:collapse; font-size:9pt;" border="1" cellpadding="4"><thead><tr style="background:#d1fae5;">';
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

    // ---------- thematic narrative generator (TI-focused) ----------

    private array $unitPool = [
        'Direktorat Operasional', 'Direktorat Keuangan', 'Direktorat SDM',
        'Direktorat Perencanaan', 'Divisi TI', 'Divisi Security', 'Divisi Aplikasi',
        'Divisi Infrastruktur', 'Divisi Data & Analytics', 'PMO TI',
        'Sub-Divisi ITSM', 'Sub-Divisi SOC', 'Satuan Pengawasan Internal',
        'Sekretariat Perusahaan', 'Komite Strategi TI',
    ];

    private array $vendorPool = [
        'PT Integrasi Sinergi Teknologi', 'PT Mitra Inovasi Digital', 'PT Solusi Coretika Nusantara',
        'PT Tata Niaga Integrasi', 'PT Sarana Pratama', 'PT Cloud Nusantara',
        'PT Cyber Mahardika', 'PT Data Pratama',
    ];

    private array $kompetensiPool = [
        'ITIL Foundation', 'COBIT 2019 Implementation', 'ISO 27001 Lead Auditor', 'Prince2 Practitioner',
        'Cloud Architecture (AWS/Azure)', 'Cyber Security Operation',
        'Project Management Professional', 'Lean Six Sigma', 'Quality Assurance',
        'Data Engineering & Analytics', 'DevOps & SRE', 'AI/ML Engineering',
        'Kubernetes Administrator', 'TOGAF Certified', 'Ethical Hacking (CEH)',
    ];

    private array $metrikPool = [
        'Service Availability', 'First Call Resolution', 'MTTR', 'MTBF', 'System Uptime',
        'Cyber Security Incident Rate', 'Patch Compliance', 'Application Adoption Rate',
        'Cost per Ticket', 'Backup Success Rate', 'DR Test Pass Rate',
        'Project On-Time Delivery', 'Change Success Rate', 'API Latency',
        'Cloud Spend Efficiency', 'Vulnerability Remediation SLA',
    ];

    private array $deliverablePool = [
        'platform Integrasi Enterprise', 'modul Application Lifecycle Management',
        'modul AI Vision Inspection', 'platform Cyber SOC Modernization', 'platform Data Warehouse',
        'modul Identity & Access Management (IAM)', 'aplikasi Mobile Pandai', 'platform Master Data Management',
        'modul Privileged Access Management (PAM)', 'aplikasi Vendor Management',
        'platform GRC Berbasis Cobit 2019', 'modul ERP Core Modernization', 'platform ITSM Self-Service',
        'aplikasi Knowledge Base', 'modul Business Continuity & DR', 'platform API Gateway',
        'aplikasi Endpoint Detection & Response', 'modul Self-Service Analytics',
        'aplikasi Boom & Line Inspection', 'platform Blockchain Traceability',
    ];

    private array $metodePool = ['Pemilihan Langsung', 'Tender Terbuka', 'Tender Terbatas', 'Penunjukan Langsung', 'E-Procurement LPSE', 'Konsultan Individu', 'Multi-Vendor Tender'];
    private array $risikoPool = ['keterlambatan vendor', 'perubahan regulasi', 'kendala integrasi sistem lama', 'resistensi pengguna', 'kegagalan teknis', 'anggaran membengkak', 'kebocoran data sensitif', 'gangguan layanan kritikal', 'peredaran ancaman APT', 'kelalaian konfigurasi cloud'];
    private array $mitigasiPool = ['penerapan SLA yang ketat', 'diversifikasi vendor', 'pelatihan change agent', 'uji beban bertahap', 'review arsitektur independen', 'asuransi siber', 'mekanisme eskalasi berjenjang', 'penilaian risiko berkelanjutan', 'strategi multi-region disaster recovery', 'pelaksanaan chaos engineering'];
    private array $fasePool = ['fase inisiasi', 'fase desain arsitektur', 'fase pengembangan', 'fase pengujian', 'fase deployment', 'fase optimasi', 'fase hypercare'];
    private array $periodePool = ['Q1', 'Q2', 'Q3', 'Q4', 'awal tahun', 'tengah tahun', 'akhir tahun'];
    private array $ssPool = ['SS-TI-01', 'SS-TI-02', 'SS-TI-03', 'SS-TI-04', 'SS-TI-05', 'SS-TI-06', 'SS-TI-07', 'SS-TI-08'];

    private function randItem(array $arr): string
    {
        return (string) $this->faker->randomElement($arr);
    }

    private function randomUniqueInitiatives(int $n): array
    {
        $verbs = ['Implementasi', 'Pengembangan', 'Peningkatan', 'Optimalisasi', 'Integrasi', 'Transformasi', 'Modernisasi', 'Digitasi', 'Audit', 'Penyusunan', 'Migrasi', 'Konsolidasi'];
        $objects = ['ERP Core', 'Data Warehouse Enterprise', 'AI Vision Inspection Mutu', 'Identity & Access Management', 'Privileged Access Management', 'Cyber SOC Modernization', 'API Gateway & Service Mesh', 'Master Data Management', 'Mobile Apps Pelanggan', 'Customer Portal', 'Sistem Pengelolaan Aset TI', 'BCP & Disaster Recovery', 'Cloud Migration', 'Hyperconverged Infrastructure', 'SD-WAN Modernization', 'Helpdesk ITSM Upgrade', 'Business Intelligence Self-Service', 'Robotic Process Automation', 'Generative AI Knowledge Assistant', 'Edge Analytics IoT', 'Blockchain Traceability Logam', 'Service Mesh Deployment', 'Zero Trust Network Access', 'Konsolidasi Aplikasi Legacy', 'MDM Cloud Platform', 'Vendor Management Sistem', 'GRC Platform', 'AI Anomaly Detection Mutu', 'Pakta Integritas Digital', 'Sistem Reimburse Digital', 'Timbangan Logam IoT', 'BAST Digital', 'Single Note Inspection', 'Sistem Pendukung Payroll', 'Helpdesk Tier 1 Outsourcing', 'NOC 24/7 ', 'Privileged Access Vault', 'SAST/DAST Pipeline', 'Container Platform Kubernetes', 'Disaster Recovery Cloud'];
        $phases = ['', ' Tahap I', ' Tahap II', ' Fase Awal', ' Skala Enterprise', ' End-to-End', ' Berbasis AI', ' Hibrida Cloud', ' Berbasis Standar NIST'];

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
            $out[] = 'Inisiatif PTI Tambahan #'.(count($out) + 1);
        }

        return $out;
    }

    private array $sentenceTemplates = [
        'Kegiatan dimulai dengan penyusunan Architecture Vision oleh {unit} pada {periode} yang menetapkan lingkup PTI, estafet baseline, dan daftar stakeholder utama.',
        'Tahap berikutnya adalah proses pengadaan melalui skema {metode} dengan target penunjukan penyedia pada {periode} dan tenggat kontrak paling lambat {deadline}.',
        'Penanggung jawab {unit} melakukan kick-off meeting bersama {unit2} untuk menyamakan pemahaman atas spesifikasi teknis {deliverable}, indikator keberhasilan, serta {metrik} yang akan dipantau setiap kuartal.',
        'Implementasi dilaksanakan secara bertahap melalui {fase} dengan validasi setiap milestone yang divalidasi melalui rapat tinjauan arsitektur (Architecture Review Board).',
        'Risiko utama yang teridentifikasi pada tahap ini adalah {risiko}, sehingga strategi mitigasi disusun melalui {mitigasi} dan didokumentasikan dalam risk register inisiatif PTI.',
        'Pengujian UAT dilakukan oleh {unit2} terhadap {deliverable}, dengan kriteria penerimaan mengacu pada dokumen SRS dan Use Case yang telah disepakati.',
        'Pelatihan pengguna dilaksanakan oleh {vendor} sebanyak {jumlah} sesi dengan target tingkat pemahaman minimal 80% berdasarkan asesmen pasca-pelatihan.',
        'Go-live {deliverable} ditargetkan pada {periode} diikuti dengan periode hypercare selama 30 hari kerja untuk memastikan kestabilan layanan.',
        'Monitoring kinerja dilakukan melalui dashboard {metrik} yang dapat diakses oleh {unit} secara real-time, dengan eskalasi insiden ke {unit2} jika terdapat penyimpangan dari SLA.',
        'Laporan kemajuan PTI disampaikan kepada Komite Strategi TI setiap bulan melalui mekanisme PMO TI, mencakup status fisik, serapan anggaran, dan realisasi {metrik}.',
        'Audit internal dilakukan oleh {unit2} pada akhir {fase} untuk memastikan kepatuhan terhadap ISO 27001:2022, ISO 20000-1, dan COBIT 2019.',
        'Penyusunan Security Risk Assessment dilakukan oleh {vendor} terhadap {deliverable}, mencakup penetration testing, vulnerability assessment, dan review kontrol keamanan.',
        'Migrasi data dari sistem legacy dilakukan dengan strategi parallel run selama {jumlah} siklus operasional untuk memastikan akurasi hasil sebelum cut-over definitif.',
        'Komitmen continuity dipastikan melalui disaster recovery test setiap 6 bulan dengan target RTO {metrik_num} jam dan RPO maksimal 4 jam, mengacu ke ISO 22301.',
        'Manajemen perubahan (change management) difasilitasi oleh {unit} melalui workshop stakeholder, communication plan, dan identifikasi change agent pada setiap unit kerja.',
        'Integrasi dengan sistem existing (ERP, BI, dan helpdesk) dilakukan melalui API Gateway yang dikelola oleh {unit}, dengan target latency rata-rata di bawah {metrik_num} ms.',
        'Eskalasi insiden tier-3 mengikuti matriks RACI yang menetapkan {unit} sebagai accountable, {unit2} sebagai responsible, dan {vendor} sebagai consulted party.',
        'Pengukuran hasil menggunakan {metrik} sebagai indikator dampak, dengan baseline periode lalu dan target tahun ini diturunkan dari {kode_ss}.',
        'Pengembangan kapabilitas tim dilakukan melalui coaching oleh {vendor} dan sertifikasi kompetensi internal sebanyak {jumlah} pegawai pada bidang {kompetensi}.',
        'Kepatuhan terhadap UU Pelindungan Data Pribadi dipastikan melalui Data Protection Impact Assessment (DPIA) yang disusun oleh {unit2} dan diverifikasi oleh {vendor}.',
        'Pengarsipan seluruh artefak PTI dilakukan oleh {unit} ke dalam repository dokumentasi yang dapat diakses oleh {unit2} untuk keperluan audit dan continuity.',
        'Penerapan Zero Trust dilakukan dengan micro-segmentation work-load, continuous authentication, dan enforcement berbasis workload identity.',
        'Implementasi Infrastructure-as-Code dilakukan menggunakan Terraform dan ArgoCD, dijalankan pada cluster Kubernetes {vendor} dengan GitOps pipeline.',
        'Observability {deliverable} dipastikan melalui integrasi APM, log aggregation, dan distributed tracing ke platform {vendor} yang diakses oleh {unit2}.',
        'Kesiangan change advisory board (CAB) dilakukan setiap minggu oleh {unit} untuk menyetujui deploy ke environment produksi {deliverable}.',
    ];

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
            '{kompetensi}' => $this->randItem($this->kompetensiPool),
        ];
        do {
            $replacements['{unit2}'] = $this->randItem($this->unitPool);
        } while ($replacements['{unit}'] === $replacements['{unit2}']);

        return strtr($tpl, $replacements);
    }

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

    private function milestoneTable(): string
    {
        $rows = [];
        $milestoneNames = [
            'Penyusunan ToR & Business Case', 'Tender / Penunjukan Vendor',
            'Kick-off Meeting & ARB Review', 'Desain Arsitektur & SRS',
            'Proof of Concept', 'Pengembangan / Konstruksi',
            'Unit Test & Integration Test', 'User Acceptance Test (UAT)',
            'Performance & Security Test', 'Pelatihan Pengguna & CAB Approval',
            'Go-Live & Hypercare', 'Evaluasi Pasca Implementasi',
            'Audit Pasca Implementasi', 'Operasionalisasi & Serah Terima',
        ];
        $selected = $this->faker->randomElements($milestoneNames, $this->faker->numberBetween(5, 8), false);
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

    private function regulationImpact(string $reg): string
    {
        $lower = strtolower($reg);
        $themes = [];
        if (str_contains($lower, 'keamanan') || str_contains($lower, 'security')) {
            $themes[] = 'pengamanan informasi dan response insiden siber';
            $themes[] = 'penerapan kontrol ISO 27001';
        }
        if (str_contains($lower, 'layanan') || str_contains($lower, 'itsm') || str_contains($lower, 'service')) {
            $themes[] = 'penyelarasan layanan TI dengan kebutuhan bisnis';
        }
        if (str_contains($lower, 'aplikasi')) {
            $themes[] = 'tata kelola pengembangan dan pemeliharaan aplikasi';
        }
        if (str_contains($lower, 'infrastruktur') || str_contains($lower, 'cloud')) {
            $themes[] = 'konsolidasi infrastruktur dan adopsi cloud yang aman';
        }
        if (str_contains($lower, 'proyek') || str_contains($lower, 'project')) {
            $themes[] = 'tata kelola proyek TI yang terukur';
        }
        if (str_contains($lower, 'iso') || str_contains($lower, 'cobit') || str_contains($lower, 'togaf') || str_contains($lower, 'itil') || str_contains($lower, 'nist')) {
            $themes[] = 'penyelarasan tata kelola TI dengan standar internasional';
        }
        if (empty($themes)) {
            $themes = ['pelaksanaan praktik terbaik TI', 'penguatan kerangka akuntabilitas TI'];
        }
        $theme = $this->faker->randomElement($themes);
        $perNext = $this->startYear.'-'.$this->endYear;
        $lead = 'Dokumen '.$reg.' menjadi rujukan perusahaan dalam menetapkan kebijakan internal TI pada periode '.$perNext.'. ';
        $body = 'Pemenuhan terhadap dokumen ini dicoai melalui pemetaan gap analysis, penyusunan dokumen kebijakan turunan, dan sosialisasi kepada pemangku TI. ';
        $close = 'Pelaksanaan '.$theme.' dipantau melalui Indikator Kinerja Kunci (KPI) TI yang relevan dan dilaporkan kepada Komite Strategi TI pada setiap rapat tinjauan triwulanan.';

        return $lead.$body.$this->narrativeParagraph(3).' '.$close;
    }
}