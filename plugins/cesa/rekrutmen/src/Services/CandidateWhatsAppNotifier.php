<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Models\JobApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CandidateWhatsAppNotifier
{
    /**
     * Send WhatsApp notification to candidate via WAG Hub gateway.
     *
     * @return array{success: bool, message: string, phone?: string, data?: mixed}
     */
    public function send(JobApplication $application, array $data): array
    {
        $phone = $this->resolveCandidatePhone($application);

        if (! $phone) {
            return [
                'success' => false,
                'message' => "Kandidat \"{$application->full_name}\" tidak memiliki nomor telepon / WhatsApp yang valid.",
            ];
        }

        $config = config('rekrutmen.notifications.whatsapp', []);
        $endpoint = Arr::get($config, 'endpoint', env('WAG_URL', 'https://waghub.mekayastudio.com'));
        $apiKey = Arr::get($config, 'api_key', env('WAG_TOKEN'));

        if (empty($endpoint) || empty($apiKey)) {
            Log::warning('Candidate WhatsApp notification skipped due to missing WAG Hub configuration.', [
                'endpoint' => $endpoint,
                'has_key'  => ! empty($apiKey),
            ]);

            return [
                'success' => false,
                'message' => 'Konfigurasi WAG Hub (WAG_URL atau WAG_TOKEN) belum disetel di file .env.',
            ];
        }

        $messageText = $this->buildCandidateMessage($application, $data);
        $timeout = (int) ($config['timeout'] ?? 10);
        $idempotencyKey = 'candidate-wa-'.$application->id.'-'.Str::random(8);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders([
                    'Authorization'   => 'Bearer '.$apiKey,
                    'Idempotency-Key' => $idempotencyKey,
                ])
                ->post(rtrim($endpoint, '/').'/api/v1/messages', [
                    'recipient' => [
                        'type'  => 'phone',
                        'value' => $phone,
                    ],
                    'message' => [
                        'type' => 'text',
                        'text' => $messageText,
                    ],
                    'purpose'          => 'notification',
                    'mode'             => 'sync',
                    'route_key'        => 'default',
                    'client_reference' => 'rekrutmen-candidate-'.$application->id,
                ]);

            if (! $response->successful()) {
                $errorMsg = $response->json('message') ?? $response->body();
                Log::error('WAG Hub returned error when sending candidate WhatsApp notification.', [
                    'phone'    => $phone,
                    'status'   => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengirim WhatsApp ke '.$phone.': '.$errorMsg,
                    'phone'   => $phone,
                ];
            }

            return [
                'success' => true,
                'message' => 'Pesan WhatsApp berhasil dikirim ke '.$phone,
                'phone'   => $phone,
                'data'    => $response->json(),
            ];
        } catch (Throwable $e) {
            Log::error('Exception occurred while sending candidate WhatsApp via WAG Hub.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menghubungi server WhatsApp gateway: '.$e->getMessage(),
                'phone'   => $phone,
            ];
        }
    }

    /**
     * Resolve and normalize phone number from JobApplication.
     */
    public function resolveCandidatePhone(JobApplication $application): ?string
    {
        $raw = $application->whatsapp_number
            ?: ($application->active_whatsapp
            ?: ($application->active_phone
            ?: ($application->phone ?? null)));

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return $this->formatPhone($raw);
    }

    /**
     * Normalize Indonesian and international phone numbers into standard digits (e.g. 628...).
     */
    public function formatPhone(string $phone): ?string
    {
        $trimmed = trim($phone);

        if ($trimmed === '') {
            return null;
        }

        // Remove all non-digits
        $digits = preg_replace('/[^\d]/', '', $trimmed);

        if (! is_string($digits) || strlen($digits) < 8) {
            return null;
        }

        // Handle 62 prefix
        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        // Handle leading 0 (e.g. 0812... -> 62812...)
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        // Handle leading 8 (e.g. 812... -> 62812...)
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Construct formatted WhatsApp message text with clear hierarchy and readable formatting.
     */
    protected function buildCandidateMessage(JobApplication $application, array $data): string
    {
        $candidateName = $application->full_name;
        $jobTitle = $application->jobPosting?->title ?? ($application->position ?? 'Posisi Lowongan');
        $companyName = 'OCEAN SPACE';
        $location = $application->jobPosting?->location ?? 'Indonesia';

        $actionUrl = trim($data['action_url'] ?? '');
        if (! empty($actionUrl) && ! str_starts_with($actionUrl, 'http://') && ! str_starts_with($actionUrl, 'https://')) {
            $actionUrl = 'https://'.$actionUrl;
        }

        $body = $data['body_message'] ?? '';
        $body = str_replace(
            ['{nama_pelamar}', '{posisi}', '{perusahaan}', '{lokasi}', '{link_aksi}'],
            [$candidateName, $jobTitle, $companyName, $location, $actionUrl],
            $body
        );

        $badgeText = strtoupper(trim($data['badge_text'] ?? 'NOTIFIKASI REKRUTMEN'));
        $schedule = trim($data['schedule'] ?? '');
        $venueOrMethod = trim($data['venue_or_method'] ?? '');
        $actionLabel = trim($data['action_label'] ?? '');
        $specialNote = trim($data['special_note'] ?? '');

        $lines = [];
        $lines[] = "Halo *{$candidateName}*,";
        $lines[] = '';
        $lines[] = $body;

        $details = [];
        $details[] = "• Posisi: {$jobTitle}";
        $details[] = "• Perusahaan: {$companyName}";

        if (! empty($location)) {
            $details[] = "• Penempatan: {$location}";
        }

        if (! empty($schedule)) {
            $details[] = "• Jadwal / Batas: {$schedule}";
        }

        if (! empty($venueOrMethod)) {
            $details[] = "• Lokasi / Media: {$venueOrMethod}";
        }

        if (! empty($actionUrl)) {
            $linkText = ! empty($actionLabel) ? "{$actionLabel}: " : 'Tautan Akses: ';
            $details[] = "• {$linkText}{$actionUrl}";
        }

        if (! empty($details)) {
            $lines[] = '';
            $lines[] = '*Detail Pelaksanaan:*';
            foreach ($details as $d) {
                $lines[] = $d;
            }
        }

        if (! empty($specialNote)) {
            $lines[] = '';
            $lines[] = "*Catatan:* {$specialNote}";
        }

        $lines[] = '';
        $lines[] = 'Terima kasih.';
        $lines[] = '*Tim Rekrutmen Complete Selular*';

        return implode("\n", $lines);
    }
}
