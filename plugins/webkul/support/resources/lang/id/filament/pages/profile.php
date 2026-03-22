<?php

return [
    'title'                   => 'Profil',
    'heading'                 => 'Profil',
    'subheading'              => 'Kelola pengaturan dan preferensi akun Anda.',
    'information_section'     => 'Informasi Profil',
    'information_description' => 'Perbarui informasi profil dan alamat email akun Anda.',
    'notification'            => [
        'success' => [
            'title' => 'Profil Diperbarui',
            'body'  => 'Profil Anda telah berhasil diperbarui.',
        ],
        'error' => [
            'title' => 'Pembaruan Profil Gagal',
            'body'  => 'Terjadi kesalahan saat memperbarui profil Anda.',
        ],
        'validation-error' => [
            'title' => 'Kesalahan Validasi',
        ],
    ],
    'actions' => [
        'save' => 'Simpan Perubahan',
    ],
    'fields' => [
        'avatar' => 'Foto Profil',
        'name'   => 'Nama',
        'email'  => 'Email',
    ],
    'password' => [
        'section'     => 'Perbarui Kata Sandi',
        'description' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.',
        'current'     => 'Kata Sandi Saat Ini',
        'new'         => 'Kata Sandi Baru',
        'confirm'     => 'Konfirmasi Kata Sandi',
        'helper'      => 'Panjangnya minimal harus 8 karakter.',
        'errors'      => [
            'current-required'  => 'Kata sandi saat ini diperlukan.',
            'current-incorrect' => 'Kata sandi saat ini salah. Silakan coba lagi.',
            'same-as-current'   => 'Kata sandi baru harus berbeda dengan kata sandi Anda saat ini.',
        ],
        'current-helper' => 'Masukkan kata sandi Anda saat ini untuk memverifikasi identitas Anda.',
        'notification'   => [
            'success' => [
                'title' => 'Kata Sandi Diperbarui',
                'body'  => 'Kata sandi Anda telah berhasil diperbarui.',
            ],
            'error' => [
                'title' => 'Pembaruan Kata Sandi Gagal',
                'body'  => 'Terjadi kesalahan saat memperbarui kata sandi Anda.',
            ],
        ],
    ],
];
