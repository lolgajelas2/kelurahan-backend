<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected $token;
    protected $url;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->url = config('services.fonnte.url');
    }

    /**
     * Kirim pesan WhatsApp
     * 
     * @param string $target Nomor WhatsApp tujuan (format: 628xxxxxxxxxx)
     * @param string $message Isi pesan
     * @return array Response dari Fonnte API
     */
    public function sendMessage($target, $message)
    {
        try {
            // Validasi nomor HP
            $target = $this->formatPhoneNumber($target);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->url, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Indonesia
            ]);

            $result = $response->json();

            Log::info('WhatsApp sent via Fonnte', [
                'target' => $target,
                'status' => $result['status'] ?? 'unknown',
                'response' => $result
            ]);

            return [
                'success' => $response->successful() && ($result['status'] ?? false),
                'data' => $result,
                'message' => $result['reason'] ?? 'WhatsApp berhasil dikirim'
            ];

        } catch (\Exception $e) {
            Log::error('Fonnte WhatsApp Error', [
                'error' => $e->getMessage(),
                'target' => $target ?? null
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengirim WhatsApp: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format nomor HP ke format internasional (628xxx)
     * 
     * @param string $phone
     * @return string
     */
    protected function formatPhoneNumber($phone)
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // Jika diawali +62, hapus +
        if (substr($phone, 0, 3) === '+62') {
            $phone = substr($phone, 1);
        }

        // Jika belum diawali 62, tambahkan
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Template pesan untuk notifikasi permohonan selesai
     * 
     * @param object $permohonan
     * @return string
     */
    public function templatePermohonanSelesai($permohonan)
    {
        $message = "*KELURAHAN GRAHA INDAH*\n\n";
        $message .= "✅ *Permohonan Anda Telah Selesai*\n\n";
        $message .= "Yth. Bapak/Ibu *{$permohonan->nama}*,\n\n";
        $message .= "Permohonan Anda dengan detail:\n";
        $message .= "📋 No. Registrasi: *{$permohonan->nomor_registrasi}*\n";
        $message .= "📝 Layanan: *{$permohonan->layanan->nama}*\n";
        $message .= "🗓️ Tanggal Selesai: *" . now()->format('d F Y, H:i') . " WIB*\n\n";
        
        if (!empty($permohonan->catatan)) {
            $message .= "📌 Catatan:\n{$permohonan->catatan}\n\n";
        }
        
        $message .= "Dokumen Anda sudah dapat diambil di Kantor Kelurahan Graha Indah.\n\n";
        $message .= "Terima kasih telah menggunakan layanan kami.\n\n";
        $message .= "---\n";
        $message .= "🏢 Kelurahan Graha Indah\n";
        $message .= "📞 Hubungi kami untuk informasi lebih lanjut";

        return $message;
    }

    /**
     * Template pesan untuk notifikasi status lainnya
     * 
     * @param object $permohonan
     * @param string $status
     * @return string
     */
    public function templateStatusUpdate($permohonan, $status)
    {
        $message = "*KELURAHAN GRAHA INDAH*\n\n";
        
        switch ($status) {
            case 'proses':
                $message .= "⏳ *Permohonan Sedang Diproses*\n\n";
                break;
            case 'ditolak':
                $message .= "❌ *Permohonan Ditolak*\n\n";
                break;
            default:
                $message .= "📢 *Update Status Permohonan*\n\n";
        }
        
        $message .= "Yth. Bapak/Ibu *{$permohonan->nama}*,\n\n";
        $message .= "Status permohonan Anda:\n";
        $message .= "📋 No. Registrasi: *{$permohonan->nomor_registrasi}*\n";
        $message .= "📝 Layanan: *{$permohonan->layanan->nama}*\n";
        $message .= "📊 Status: *" . strtoupper($status) . "*\n";
        $message .= "🗓️ Update: *" . now()->format('d F Y, H:i') . " WIB*\n\n";
        
        if (!empty($permohonan->catatan)) {
            $message .= "📌 Catatan:\n{$permohonan->catatan}\n\n";
        }
        
        $message .= "---\n";
        $message .= "🏢 Kelurahan Graha Indah\n";
        $message .= "📞 Hubungi kami untuk informasi lebih lanjut";

        return $message;
    }
}
