<?php

return [
    'navigation' => [
        'label' => 'Progres',
    ],

    'guide' => [
        'label'         => 'Panduan',
        'close'         => 'Tutup',
        'modal_heading' => 'Panduan Laporan Progres Rekrutmen',
        'modal_content' => <<<'HTML'
<div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">1. Sumber Data</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>MPP (Request Man Power)</strong> — kebutuhan resmi per posisi. Hanya request dengan status <em>Approved</em>, <em>Hold</em>, atau <em>Pending</em> yang dihitung.</li>
            <li><strong>Job Posting</strong> — lowongan yang tersambung ke MPP. Satu lowongan bisa mewakili lebih dari satu MPP.</li>
            <li><strong>Kandidat</strong> — pelamar yang terdaftar di job application. Status: <em>In Progress</em>, <em>Hired</em>, <em>Rejected</em>, <em>Withdrawn</em>.</li>
            <li><strong>Aktivitas</strong> — riwayat seleksi (catat aktivitas / pindah tahap) yang direkam di pipeline rekrutmen.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">2. Filter Periode & Snapshot</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Snapshot MPP Sampai</strong> — tanggal "sampai" menjadi titik potong. MPP yang tanggal pengajuannya setelah tanggal ini <em>tidak dihitung</em>.</li>
            <li><strong>Aktivitas Dari</strong> — membatasi update aktivitas yang ditampilkan. Tidak mempengaruhi jumlah hired/kebutuhan karena itu berdasarkan snapshot.</li>
            <li>Metrik <strong>Sisa Kebutuhan, Butuh, Hired</strong> selalu berdasarkan snapshot, bukan range aktivitas.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">3. Perhitungan Kebutuhan (Butuh / Hired / Sisa)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Butuh</strong> = total headcount dari MPP <em>Approved + Hold</em> yang tersambung ke lowongan. Jika belum ada yang approved, fallback ke MPP <em>Pending</em> tertua.</li>
            <li><strong>Hired</strong> = jumlah kandidat unik yang berstatus <em>Hired</em> dan masuk sebelum tanggal snapshot.</li>
            <li><strong>Sisa</strong> = Butuh − Hired (minimal 0).</li>
            <li>Jika satu lowongan punya beberapa MPP, hired dialokasikan urut ke MPP berdasarkan tanggal pengajuan.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">4. Fokus Kerja (Tab)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Perlu Tindak Lanjut</strong> — sisa kebutuhan &gt; 0 dan request tidak hold.</li>
            <li><strong>Data Perlu Dirapikan</strong> — ada gap rantai data (MPP tanpa lowongan, lowongan expired, hired melebihi kebutuhan, dll).</li>
            <li><strong>Ada Update</strong> — ada aktivitas terekam pada periode filter.</li>
            <li><strong>Hold</strong> — semua MPP posisi berstatus hold.</li>
            <li><strong>Terpenuhi</strong> — hired ≥ kebutuhan.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">5. Export Excel (3 Sheet)</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li><strong>Overview MPP</strong> — snapshot per bulan: posisi open, kebutuhan, join bulan itu, sisa, hold, dan perusahaan. Baris hanya muncul jika posisi masih outstanding atau ada join di bulan tersebut.</li>
            <li><strong>Detail Posisi</strong> — snapshot akhir periode, dirinci per MPP request: tanggal req, umur, kebutuhan vs hired vs sisa, status pemenuhan, dan prioritas tindak lanjut.</li>
            <li><strong>Aktivitas Rekrutmen</strong> — daftar seluruh batch aktivitas seleksi pada periode terpilih, lengkap dengan tahap, jumlah kandidat lolos/gagal/pending, dan PIC.</li>
        </ul>
    </div>

    <div>
        <h4 class="font-semibold text-gray-950 dark:text-white">6. Cara Cross-Check</h4>
        <ul class="mt-1 list-disc space-y-1 pl-5">
            <li>Bandingkan angka <strong>Butuh</strong> di UI dengan kolom "Kebutuhan MPP" di sheet Detail Posisi — keduanya harus sama untuk snapshot yang sama.</li>
            <li>Kolom <strong>Hired</strong> di UI = jumlah nama di kolom "Karyawan Join" di Detail Posisi (pastikan periode filter cocok).</li>
            <li>Jika angka berbeda, periksa: (a) apakah filter periode sudah sama, (b) apakah status MPP sudah approved, (c) apakah ada kandidat hired yang tanggalnya di luar snapshot.</li>
            <li>Tab <strong>Data Perlu Dirapikan</strong> menandai posisi yang memiliki gap data — periksa posisi tersebut jika data laporan tidak konsisten.</li>
        </ul>
    </div>
</div>
HTML,
    ],
];
