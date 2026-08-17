<?php

namespace App\Services;

use RuntimeException;

/**
 * PdfDecryptService
 * -----------------
 * Dekripsi PDF yang dienkripsi Standard Security Handler (tanda "SECURED"
 * di viewer) dengan USER PASSWORD KOSONG — yakni dokumen yang hanya dikunci
 * owner-password / permission (no-copy, print-only, dsb). Kasus paling umum
 * pada dokumen RJPP/MPTI/OMTI instansi.
 *
 * Dibutuhkan karena:
 *  - poppler `pdftotext` (jalur utama) mendekripsi otomatis, TAPI binary
 *    tidak tersedia di shared hosting.
 *  - smalot/pdfparser (jalur fallback pure-PHP) melempar
 *    "Secured pdf file are currently not supported."
 *
 * Algoritma yang didukung (ISO 32000-1 bagian 7.6):
 *  - R2 / V1  : RC4 40-bit
 *  - R3 / V2  : RC4 128-bit
 *  - R4 / V4  : RC4 atau AES-128 (crypt filter StdCF)
 *  - R5/R6/V5 : AES-256 (SHA-256, password kosong tanpa hardening rounds)
 *
 * Proses: parse objek mentah -> turunkan file encryption key dari password
 * kosong -> dekripsi seluruh stream & string -> tulis ulang PDF bersih
 * (xref klasik + trailer tanpa /Encrypt) yang siap diparsing smalot.
 *
 * PDF dengan user password NON-KOSONG tidak didukung — akan menghasilkan
 * data sampah dan dilempar sebagai error yang jelas.
 */
class PdfDecryptService
{
    /** Padding standar ISO 32000-1 untuk password < 32 byte. */
    private const PADDING = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";

    private string $buf = '';

    private int $pos = 0;

    // ===========================  Public API  ===========================

    /** Apakah PDF terenkripsi (memiliki /Encrypt di trailer)? */
    public function isEncrypted(string $absPath): bool
    {
        $raw = $this->readFile($absPath);
        if ($raw === null || strpos($raw, '/Encrypt') === false) {
            return false;
        }

        $trailer = $this->findTrailerDict($raw);
        if ($trailer !== null && isset($trailer['/Encrypt'])) {
            return true;
        }

        // Cross-reference stream (PDF 1.5+): trailer ada di dict xref stream.
        foreach ($this->scanObjects($raw) as $obj) {
            $dict = $obj['value'];
            if ($this->isDict($dict) && ($dict['/Type'] ?? null) === '/XRef' && isset($dict['/Encrypt'])) {
                return true;
            }
        }

        return false;
    }

    /** Dekripsi ke file temporer; return path-nya (caller wajib unlink). */
    public function decryptToTemp(string $absPath): string
    {
        $out = @tempnam(sys_get_temp_dir(), 'pdfdec');
        if ($out === false) {
            throw new RuntimeException('Gagal membuat file sementara untuk dekripsi PDF.');
        }

        $this->decrypt($absPath, $out);

        return $out;
    }

    /** Dekripsi $absPath dan tulis hasilnya ke $outPath. */
    public function decrypt(string $absPath, string $outPath): void
    {
        $raw = $this->readFile($absPath);
        if ($raw === null) {
            throw new RuntimeException("PDF tidak dapat dibaca: {$absPath}");
        }

        $objects = $this->scanObjects($raw);
        if ($objects === []) {
            throw new RuntimeException('Struktur objek PDF tidak dapat dibaca.');
        }

        $trailer = $this->findTrailerDict($raw) ?? $this->xrefStreamDictFrom($objects);
        if (! $this->isDict($trailer) || ! isset($trailer['/Encrypt'])) {
            throw new RuntimeException('PDF tidak terenkripsi — dekripsi tidak diperlukan.');
        }

        // Resolve kamus /Encrypt (umumnya indirect object di body file).
        $encVal = $trailer['/Encrypt'];
        $encNum = null;
        if (is_array($encVal) && ($encVal[0] ?? null) === 'r') {
            $encNum = (int) $encVal[1];
        }
        $enc = null;
        if ($encNum !== null && isset($objects[$encNum]) && $this->isDict($objects[$encNum]['value'])) {
            $enc = $objects[$encNum]['value'];
        } elseif ($this->isDict($encVal)) {
            $enc = $encVal; // direct dict (jarang)
        }
        if (! $this->isDict($enc)) {
            throw new RuntimeException('Kamus enkripsi (/Encrypt) PDF tidak ditemukan.');
        }

        // /ID elemen pertama (dipakai derivasi kunci R2-R4).
        $id0 = '';
        $idv = $trailer['/ID'] ?? null;
        if (is_array($idv) && isset($idv[0]) && $this->isString($idv[0])) {
            $id0 = (string) $idv[0][1];
        }

        [$fileKey, $cipher, $keyLen] = $this->deriveFileKey($enc, $id0);

        $emit = [];
        foreach ($objects as $num => $obj) {
            if ($num === $encNum) {
                continue; // kamus /Encrypt tidak pernah dienkripsi & dibuang
            }
            $dict = $this->isDict($obj['value']) ? $obj['value'] : [];
            $gen = (int) $obj['gen'];
            if (($dict['/Type'] ?? null) === '/XRef') {
                continue; // xref stream tidak dienkripsi; kita ganti xref klasik
            }

            if ($obj['stream'] !== null) {
                $objKey = $this->objKey($num, $gen, $fileKey, $cipher, $keyLen);
                $plain = $this->decryptBytes($obj['stream'], $objKey, $cipher);

                if (($dict['/Type'] ?? null) === '/ObjStm') {
                    // Object stream: dekripsi + inflate lalu pecah menjadi
                    // objek standalone agar xref klasik tetap valid.
                    $inflated = $this->inflate($plain, $dict['/Filter'] ?? null);
                    if ($inflated !== null) {
                        // String di dalam ObjStm memakai objnum/gen ObjStm-nya.
                        foreach ($this->expandObjStm($inflated, $dict, $num, $gen, $fileKey, $cipher, $keyLen) as $inum => $itext) {
                            $emit[$inum] = ['gen' => 0, 'text' => $itext];
                        }
                    }

                    continue;
                }

                $dict['/Length'] = strlen($plain);
                $this->decryptStringsInPlace($dict, $num, $gen, $fileKey, $cipher, $keyLen);
                $this->stripCryptFilter($dict);
                $emit[$num] = [
                    'gen' => $gen,
                    'text' => "{$num} {$gen} obj\n".$this->serialize($dict)."\nstream\n".$plain."\nendstream\nendobj",
                ];

                continue;
            }

            $value = $obj['value'];
            if (is_array($value)) {
                $this->decryptStringsInPlace($value, $num, $gen, $fileKey, $cipher, $keyLen);
            }
            $emit[$num] = ['gen' => $gen, 'text' => "{$num} {$gen} obj\n".$this->serialize($value)."\nendobj"];
        }

        if ($emit === []) {
            throw new RuntimeException('Tidak ada objek PDF yang berhasil diproses saat dekripsi.');
        }

        $this->writePdf($outPath, $emit, $trailer['/Root'] ?? null, $id0);
    }

    // =====================  Key derivation (ISO 32000-1)  =====================

    /**
     * Turunkan file encryption key dari user password KOSONG.
     *
     * @return array{0: string, 1: string, 2: int} [fileKey, cipher, keyLen]
     */
    private function deriveFileKey(array $enc, string $id0): array
    {
        $r = (int) ($enc['/R'] ?? 2);
        $v = (int) ($enc['/V'] ?? 0);

        if ($r >= 5) {
            // AES-256 (R5/R6): kunci dari /U key-salt (byte 40..48) + /UE.
            $u = $this->stringValue($enc['/U'] ?? null);
            $ue = $this->stringValue($enc['/UE'] ?? null);
            if (strlen($u) < 48 || strlen($ue) < 32) {
                throw new RuntimeException('PDF AES-256: data kunci /U atau /UE tidak lengkap.');
            }
            $keySalt = substr($u, 40, 8);
            // Password user kosong (Algoritma 2A/2B, zero IV, tanpa padding).
            $k = $this->calculateHashR6($r, '', $keySalt);
            $dec = openssl_decrypt(
                $ue,
                'aes-256-cbc',
                $k,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                str_repeat("\0", 16),
            );
            if ($dec === false || strlen($dec) < 32) {
                throw new RuntimeException('Gagal menurunkan kunci AES-256 — kemungkinan PDF memakai user password (tidak didukung).');
            }

            return [substr($dec, 0, 32), 'aes256', 32];
        }

        $cfm = '/V2'; // default RC4
        if ($v === 4) {
            $cf = $enc['/CF'] ?? null;
            if ($this->isDict($cf) && $this->isDict($cf['/StdCF'] ?? null)) {
                $cfm = $cf['/StdCF']['/CFM'] ?? '/V2';
            }
            if ($cfm === '/None') {
                return ['', 'identity', 0];
            }
        }

        $bits = (int) ($enc['/Length'] ?? 40);
        if ($v === 1) {
            $bits = 40;
        }
        $n = $cfm === '/AESV2' ? 16 : max(5, (int) floor($bits / 8));
        if ($n > 16) {
            $n = 16;
        }

        // Algoritma 3.2 (PDF Reference): satu MD5 atas
        // padding_pw + /O + P(uint32 LE) + ID[0]; R>=3: 50 iterasi.
        $o = $this->stringValue($enc['/O'] ?? null);
        $p = (int) ($enc['/P'] ?? 0);
        if ($p < 0) {
            $p += 4294967296;
        }
        $m = md5(self::PADDING.$o.pack('V', $p).$id0, true);

        // V4 dengan EncryptMetadata=false menambahkan 0xFFFFFFFF.
        $metaEnc = $enc['/EncryptMetadata'] ?? true;
        if ($v >= 4 && ($metaEnc === false || $metaEnc === 'false')) {
            $m = md5($m."\xFF\xFF\xFF\xFF", true);
        }

        if ($r >= 3) {
            for ($i = 0; $i < 50; $i++) {
                $m = substr(md5(substr($m, 0, $n), true), 0, $n);
            }
        }

        return [substr($m, 0, $n), $cfm === '/AESV2' ? 'aes128' : 'rc4', $n];
    }

    /** Object key (Algoritma 1) untuk objek num/gen.
     *  Catatan: V>=5 (AES-256) memakai file key langsung (ISO 32000-2
     *  Algorithm 3.1a) — tidak ada derivasi per-objek. */
    private function objKey(int $num, int $gen, string $fileKey, string $cipher, int $keyLen): string
    {
        if ($cipher === 'aes256') {
            return $fileKey;
        }

        $tail = pack('C3', $num & 0xFF, ($num >> 8) & 0xFF, ($num >> 16) & 0xFF)
            .pack('v', $gen & 0xFFFF);

        if (str_starts_with($cipher, 'aes')) {
            $tail .= 'sAlT';
        }

        return substr(md5($fileKey.$tail, true), 0, min($keyLen + 5, 16));
    }

    private function decryptBytes(string $data, string $objKey, string $cipher): string
    {
        if ($data === '') {
            return '';
        }

        return match ($cipher) {
            'rc4' => $this->rc4($objKey, $data),
            'aes128' => $this->aesObject($data, $objKey, 128),
            'aes256' => $this->aesObject($data, $objKey, 256),
            default => $data, // identity
        };
    }

    /** Dekripsi string/stream objek AES: 16 byte PERTAMA adalah IV acak
     *  (ISO 32000-1 crypt filter AESV2/AESV3), lalu CBC + strip PKCS#7. */
    private function aesObject(string $data, string $key, int $bits): string
    {
        $len = strlen($data);
        if ($len <= 16) {
            return '';
        }

        $iv = substr($data, 0, 16);
        $ct = substr($data, 16);
        if (strlen($ct) % 16 !== 0) {
            $ct = substr($ct, 0, strlen($ct) - (strlen($ct) % 16));
            if ($ct === '') {
                return '';
            }
        }

        $plain = openssl_decrypt(
            $ct,
            "aes-{$bits}-cbc",
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv,
        );
        if ($plain === false) {
            return '';
        }

        // Strip PKCS#7 padding bila valid.
        $pad = ord(substr($plain, -1));
        if ($pad >= 1 && $pad <= 16 && substr($plain, -$pad) === str_repeat(chr($pad), $pad)) {
            $plain = substr($plain, 0, -$pad);
        }

        return $plain;
    }

    /** ISO 32000-2 Algorithm 2A hash (R6): SHA-256 awal + hardening loop
     *  memakai AES-128-CBC sebagai PRF. R5: cukup SHA-256. */
    private function calculateHashR6(int $r, string $password, string $salt, string $udata = ''): string
    {
        $k = hash('sha256', $password.$salt.$udata, true);
        if ($r < 6) {
            return $k;
        }

        $hashes = ['sha256', 'sha384', 'sha512'];
        $count = 0;
        while (true) {
            $count++;
            $k1 = str_repeat($password.$k.$udata, 64);
            $e = openssl_encrypt(
                $k1,
                'aes-128-cbc',
                substr($k, 0, 16),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                substr($k, 16, 16),
            );
            if ($e === false) {
                throw new RuntimeException('Gagal menghitung hash R6 (AES PRF).');
            }
            $sum = 0;
            for ($i = 0; $i < 16; $i++) {
                $sum += ord($e[$i]);
            }
            $k = hash($hashes[$sum % 3], $e, true);
            if ($count >= 64 && ord(substr($e, -1)) <= $count - 32) {
                break;
            }
        }

        return substr($k, 0, 32);
    }

    private function rc4(string $key, string $data): string
    {
        $s = [];
        for ($i = 0; $i <= 255; $i++) {
            $s[$i] = $i;
        }
        $j = 0;
        $kl = strlen($key) ?: 1;
        for ($i = 0; $i <= 255; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % $kl])) & 255;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
        }

        $bytes = unpack('C*', $data) ?: [];
        $out = [];
        $i = $j = 0;
        foreach ($bytes as $b) {
            $i = ($i + 1) & 255;
            $j = ($j + $s[$i]) & 255;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
            $out[] = $b ^ $s[($s[$i] + $s[$j]) & 255];
        }

        return pack('C*', ...$out);
    }

    // =========================  Raw PDF scanning  =========================

    /**
     * Scan seluruh `N G obj ... endobj` dengan kecermatan stream-length
     * agar bytes terenkripsi (yang bisa berisi "endobj" palsu) dilewati.
     *
     * @return array<int, array{gen: int, value: mixed, stream: ?string}>
     */
    private function scanObjects(string $raw): array
    {
        $out = [];
        $offset = 0;
        $rawLen = strlen($raw);

        while (preg_match('/(\d+)\s+(\d+)\s+obj\b/', $raw, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $num = (int) $m[1][0];
            $gen = (int) $m[2][0];
            $matchEnd = $m[0][1] + strlen($m[0][0]);
            $matchStart = $m[0][1];

            $this->initParser($raw, $matchEnd);
            $this->skipWs();
            $value = $this->parseValue();
            $valueEnd = $this->pos;

            $stream = null;
            $this->skipWs();
            if (substr($raw, $this->pos, 6) === 'stream') {
                $p = $this->pos + 6;
                if ($raw[$p] === "\r") {
                    $p++;
                }
                if (($raw[$p] ?? '') === "\n") {
                    $p++;
                }

                $length = $this->isDict($value) ? $this->resolveLength($value, $raw) : null;
                if ($length !== null) {
                    $stream = substr($raw, $p, $length);
                    $end = strpos($raw, 'endobj', $p + $length);
                    $offset = $end === false ? min($p + $length, $rawLen) : $end + 6;
                } else {
                    $endS = strpos($raw, 'endstream', $p);
                    if ($endS === false) {
                        break;
                    }
                    $stream = preg_replace('/[\r\n]+$/', '', substr($raw, $p, $endS - $p));
                    $end = strpos($raw, 'endobj', $endS);
                    $offset = $end === false ? $endS + 9 : $end + 6;
                }
            } else {
                $end = strpos($raw, 'endobj', $valueEnd);
                $offset = $end === false ? $valueEnd : $end + 6;
            }

            // Objek terakhir menang (incremental update / linearized).
            $out[$num] = ['gen' => $gen, 'value' => $value, 'stream' => $stream];

            if ($offset <= $matchStart) {
                $offset = $matchStart + 4;
            }
        }

        return $out;
    }

    private function findTrailerDict(string $raw): ?array
    {
        $pos = strrpos($raw, 'trailer');
        if ($pos === false) {
            return null;
        }
        $this->initParser($raw, $pos + 7);
        $this->skipWs();
        if (substr($this->buf, $this->pos, 2) !== '<<') {
            return null;
        }
        $v = $this->parseValue();

        return $this->isDict($v) ? $v : null;
    }

    private function xrefStreamDictFrom(array $objects): ?array
    {
        $trailer = null;
        foreach ($objects as $obj) {
            $v = $obj['value'];
            if ($this->isDict($v) && ($v['/Type'] ?? null) === '/XRef') {
                $trailer = $v; // terakhir menang
            }
        }

        return $trailer;
    }

    private function resolveLength(array $dict, string $raw): ?int
    {
        $l = $dict['/Length'] ?? null;
        if (is_int($l)) {
            return $l;
        }
        if (is_float($l)) {
            return (int) $l;
        }
        if (is_array($l) && ($l[0] ?? null) === 'r') {
            $saved = [$this->buf, $this->pos];
            $re = '/\b'.(int) $l[1].'\s+'.(int) $l[2].'\s+obj\b/';
            if (preg_match($re, $raw, $m, PREG_OFFSET_CAPTURE)) {
                $this->initParser($raw, $m[0][1] + strlen($m[0][0]));
                $v = $this->parseValue();
                [$this->buf, $this->pos] = $saved;

                return is_int($v) ? $v : (is_float($v) ? (int) $v : null);
            }
            [$this->buf, $this->pos] = $saved;
        }

        return null;
    }

    // =====================  ObjStm expansion  =====================

    /**
     * Pecah isi object stream (sudah diinflate) menjadi objek standalone.
     * Jumlah pasangan ada di dict /N; offset data objek pertama ada di dict
     * /First; isi stream dimulai LANGSUNG dengan 2N integer (objnum, offset).
     * Return map num => teks objek utuh "N 0 obj ... endobj".
     */
    private function expandObjStm(string $data, array $dict, int $stmNum, int $stmGen, string $fileKey, string $cipher, int $keyLen): array
    {
        $count = (int) ($dict['/N'] ?? 0);
        $first = (int) ($dict['/First'] ?? 0);
        if ($count < 1 || $count > 100000 || $first < 0 || $first > strlen($data)) {
            return [];
        }

        preg_match_all('/\d+/', substr($data, 0, $first), $ints);
        $ints = $ints[0];
        if (count($ints) < 2 * $count) {
            return [];
        }

        $body = substr($data, $first);

        $entries = [];
        $k = 0;
        for ($i = 0; $i < $count; $i++) {
            $entries[] = [(int) $ints[$k++], (int) $ints[$k++]];
        }

        // Panjang tiap objek = offset entri berikutnya - offset entri ini.
        $out = [];
        foreach ($entries as $i => [$objNum, $off]) {
            $next = $entries[$i + 1][1] ?? strlen($body);
            $chunk = substr($body, $off, max(0, $next - $off));
            if (trim($chunk) === '') {
                continue;
            }
            $this->initParser($chunk, 0);
            $val = $this->parseValue();
            if (is_array($val)) {
                $this->decryptStringsInPlace($val, $stmNum, $stmGen, $fileKey, $cipher, $keyLen);
            }
            $out[$objNum] = "{$objNum} 0 obj\n".$this->serialize($val)."\nendobj";
        }

        return $out;
    }

    // =========================  Mini PDF parser  =========================

    private function initParser(string $buf, int $pos): void
    {
        $this->buf = $buf;
        $this->pos = $pos;
    }

    private function skipWs(): void
    {
        $len = strlen($this->buf);
        while ($this->pos < $len) {
            $c = $this->buf[$this->pos];
            if ($c === ' ' || $c === "\t" || $c === "\r" || $c === "\n" || $c === "\f" || $c === "\0") {
                $this->pos++;

                continue;
            }
            if ($c === '%') { // komentar sampai EOL
                $this->pos += strcspn(substr($this->buf, $this->pos), "\r\n") ?: 1;

                continue;
            }

            break;
        }
    }

    /**
     * Parse satu nilai PDF pada posisi kursor.
     *
     * Representasi: dict -> assoc array; array -> list; name "/X" -> string
     * berawalan '/'; string literal/hex -> ['s', bytes]; ref -> ['r',n,g];
     * angka -> int/float; keyword -> true/false/null.
     */
    private function parseValue(): mixed
    {
        $this->skipWs();
        $len = strlen($this->buf);
        if ($this->pos >= $len) {
            return null;
        }

        $c = $this->buf[$this->pos];

        if ($c === '<' && ($this->buf[$this->pos + 1] ?? '') === '<') {
            return $this->parseDict();
        }
        if ($c === '[') {
            return $this->parseArray();
        }
        if ($c === '/') {
            return $this->parseName();
        }
        if ($c === '(') {
            return ['s', $this->parseLiteralString()];
        }
        if ($c === '<') {
            return ['s', $this->parseHexString()];
        }

        // angka / ref "N G R" / keyword
        if (preg_match('/\G[-+]?\d*\.?\d+/', $this->buf, $m, 0, $this->pos)) {
            $txt = $m[0];
            $num = str_contains($txt, '.') ? (float) $txt : (int) $txt;
            $after = $this->pos + strlen($txt);
            if (preg_match('/\G\s+(\d+)\s+R\b/', $this->buf, $rm, 0, $after)) {
                $this->pos = $after + strlen($rm[0]);

                return ['r', $num, (int) $rm[1]];
            }
            $this->pos = $after;

            return $num;
        }

        if (preg_match('/\G[a-zA-Z]+/', $this->buf, $m, 0, $this->pos)) {
            $word = $m[0];
            $this->pos += strlen($word);

            return match ($word) {
                'true' => true,
                'false' => false,
                default => null,
            };
        }

        $this->pos++; // skip karakter tak dikenal agar tidak infinite loop

        return null;
    }

    private function parseDict(): array
    {
        $this->pos += 2;
        $dict = [];
        $len = strlen($this->buf);

        while ($this->pos < $len) {
            $this->skipWs();
            if (substr($this->buf, $this->pos, 2) === '>>') {
                $this->pos += 2;

                break;
            }
            if ($this->buf[$this->pos] !== '/') {
                $this->pos++;

                continue;
            }
            $key = $this->parseName();
            $dict[$key] = $this->parseValue();
        }

        return $dict;
    }

    private function parseArray(): array
    {
        $this->pos++;
        $arr = [];
        $len = strlen($this->buf);

        while ($this->pos < $len) {
            $this->skipWs();
            if ($this->buf[$this->pos] === ']') {
                $this->pos++;

                break;
            }
            $arr[] = $this->parseValue();
        }

        return $arr;
    }

    private function parseName(): string
    {
        $p = ++$this->pos;
        $n = strcspn(substr($this->buf, $p), " \t\r\n\f\0()<>[]{}/%");
        $this->pos = $p + $n;

        return '/'.substr($this->buf, $p, $n);
    }

    private function parseLiteralString(): string
    {
        $this->pos++; // '('
        $out = '';
        $depth = 1;
        $len = strlen($this->buf);

        while ($this->pos < $len) {
            $ch = $this->buf[$this->pos++];

            if ($ch === '\\') {
                if ($this->pos >= $len) {
                    break;
                }
                $e = $this->buf[$this->pos++];

                if (ctype_digit($e)) {
                    $oct = $e;
                    while (strlen($oct) < 3 && isset($this->buf[$this->pos]) && ctype_digit($this->buf[$this->pos])) {
                        $oct .= $this->buf[$this->pos++];
                    }
                    $out .= chr((int) octdec($oct));
                } elseif ($e === 'n') {
                    $out .= "\n";
                } elseif ($e === 'r') {
                    $out .= "\r";
                } elseif ($e === 't') {
                    $out .= "\t";
                } elseif ($e === 'b') {
                    $out .= "\x08";
                } elseif ($e === 'f') {
                    $out .= "\x0C";
                } elseif ($e === "\r") {
                    if (($this->buf[$this->pos] ?? '') === "\n") {
                        $this->pos++;
                    }
                } elseif ($e === "\n") {
                    // line continuation — tidak menambah byte
                } else {
                    $out .= $e; // termasuk ( ) \\ dan karakter lain literal
                }

                continue;
            }

            if ($ch === '(') {
                $depth++;
                $out .= '(';

                continue;
            }
            if ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
                $out .= ')';

                continue;
            }
            $out .= $ch;
        }

        return $out;
    }

    private function parseHexString(): string
    {
        $end = strpos($this->buf, '>', $this->pos + 1);
        if ($end === false) {
            $end = strlen($this->buf);
        }
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', substr($this->buf, $this->pos + 1, $end - $this->pos - 1));
        $this->pos = $end + 1;
        if (strlen($hex) % 2 !== 0) {
            $hex .= '0';
        }

        return hex2bin($hex) ?: '';
    }

    // =========================  Serialize & mutate  =========================

    private function serialize(mixed $v): string
    {
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if ($v === null) {
            return 'null';
        }
        if (is_string($v)) {
            return str_starts_with($v, '/') ? $v : '<'.bin2hex($v).'>';
        }
        if (is_array($v)) {
            if ($this->isString($v)) {
                return '<'.bin2hex($v[1]).'>';
            }
            if (($v[0] ?? null) === 'r' && count($v) === 3) {
                return $v[1].' '.$v[2].' R';
            }
            if (array_is_list($v)) {
                return '['.implode(' ', array_map([$this, 'serialize'], $v)).']';
            }
            $out = '<<';
            foreach ($v as $k => $val) {
                $out .= ' '.$k.' '.$this->serialize($val);
            }

            return $out.' >>';
        }

        return 'null';
    }

    /** Dekripsi seluruh string terenkripsi di dalam sebuah nilai (rekursif). */
    private function decryptStringsInPlace(mixed &$value, int $num, int $gen, string $fileKey, string $cipher, int $keyLen): void
    {
        if (! is_array($value)) {
            return;
        }
        if ($this->isString($value)) {
            if ($cipher !== 'identity') {
                $value[1] = $this->decryptBytes($value[1], $this->objKey($num, $gen, $fileKey, $cipher, $keyLen), $cipher);
            }

            return;
        }
        if (($value[0] ?? null) === 'r') {
            return;
        }
        foreach ($value as &$v) {
            $this->decryptStringsInPlace($v, $num, $gen, $fileKey, $cipher, $keyLen);
        }
        unset($v);
    }

    private function stripCryptFilter(array &$dict): void
    {
        $f = $dict['/Filter'] ?? null;
        if ($f === '/Crypt') {
            unset($dict['/Filter'], $dict['/DecodeParms']);

            return;
        }
        if (is_array($f) && in_array('/Crypt', $f, true)) {
            $parms = $dict['/DecodeParms'] ?? null;
            $newF = [];
            $newP = [];
            foreach (array_values($f) as $i => $name) {
                if ($name === '/Crypt') {
                    continue;
                }
                $newF[] = $name;
                if (is_array($parms)) {
                    $newP[] = $parms[$i] ?? null;
                }
            }
            $dict['/Filter'] = count($newF) === 1 ? $newF[0] : $newF;
            if (is_array($parms)) {
                $dict['/DecodeParms'] = $newP;
            }
        }
    }

    private function inflate(string $data, mixed $filter): ?string
    {
        $hasFlate = $filter === '/FlateDecode'
            || (is_array($filter) && in_array('/FlateDecode', $filter, true));
        if (! $hasFlate) {
            return $data; // tidak terkompresi
        }
        $out = @gzuncompress($data);
        if ($out === false) {
            $out = @gzinflate($data);
        }
        if ($out === false) {
            $out = @gzdecode($data);
        }

        return $out === false ? null : $out;
    }

    // =========================  Writer & helpers  =========================

    private function writePdf(string $outPath, array $emit, mixed $root, string $id0): void
    {
        $size = max(array_keys($emit)) + 1;
        $out = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($emit as $num => $obj) {
            $offsets[$num] = strlen($out);
            $out .= $obj['text']."\n";
        }

        $xrefPos = strlen($out);
        $out .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $out .= isset($offsets[$i])
                ? sprintf("%010d %05d n \n", $offsets[$i], $emit[$i]['gen'])
                : "0000000000 00000 f \n";
        }

        $trailer = '<< /Size '.$size;
        if ($root !== null) {
            $trailer .= ' /Root '.$this->serialize($root);
        }
        if ($id0 !== '') {
            $hex = bin2hex($id0);
            $trailer .= " /ID[<{$hex}><{$hex}>]";
        }
        $trailer .= ' >>';

        $out .= "trailer\n{$trailer}\nstartxref\n{$xrefPos}\n%%EOF";

        if (@file_put_contents($outPath, $out) === false) {
            throw new RuntimeException("Gagal menulis PDF hasil dekripsi: {$outPath}");
        }
    }

    private function readFile(string $absPath): ?string
    {
        $data = @file_get_contents($absPath);

        return $data === false ? null : $data;
    }

    private function isDict(mixed $v): bool
    {
        return is_array($v) && ! array_is_list($v);
    }

    private function isString(mixed $v): bool
    {
        return is_array($v) && count($v) === 2 && ($v[0] ?? null) === 's' && is_string($v[1]);
    }

    private function stringValue(mixed $v): string
    {
        return $this->isString($v) ? (string) $v[1] : '';
    }
}
