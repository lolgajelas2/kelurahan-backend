<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $url;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->url = config('services.fonnte.url');
    }

    /**
     * Normalize phone number to international format (628xxx)
     * Supports multiple formats:
     * - 08xxx -> 628xxx
     * - 8xxx -> 628xxx
     * - 628xxx -> 628xxx
     * - +628xxx -> 628xxx
     * 
     * @param string $phone
     * @return string
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove all non-numeric characters (spaces, dashes, plus, etc)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // If doesn't start with 62, add it
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send WhatsApp message using Fonnte API
     * 
     * @param string $phone Phone number (any format: 08xxx, 628xxx, +628xxx)
     * @param string $message Message content
     * @return array Response from Fonnte API
     * @throws \Exception
     */
    public function sendMessage($phone, $message)
    {
        // Normalize phone number
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        
        Log::info('Sending WhatsApp', [
            'original_phone' => $phone,
            'normalized_phone' => $normalizedPhone,
            'message_preview' => substr($message, 0, 50) . '...'
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->url, [
                'target' => $normalizedPhone,
                'message' => $message,
                'countryCode' => '62', // Indonesia
            ]);

            if (!$response->successful()) {
                Log::error('Fonnte API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to send WhatsApp message: ' . $response->body());
            }

            $result = $response->json();
            Log::info('WhatsApp sent successfully', ['response' => $result]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'error' => $e->getMessage(),
                'phone' => $normalizedPhone
            ]);
            throw $e;
        }
    }

    /**
     * Send notification when permohonan status is updated to "selesai"
     * 
     * @param object $permohonan
     * @return array|null
     */
    public function sendPermohonanSelesaiNotification($permohonan)
    {
        $message = "🎉 *Permohonan Selesai*\n\n";
        $message .= "Yth. Bapak/Ibu *{$permohonan->nama}*,\n\n";
        $message .= "Permohonan Anda telah selesai diproses.\n\n";
        $message .= "📋 *Detail Permohonan:*\n";
        $message .= "• Nomor Registrasi: {$permohonan->nomor_registrasi}\n";
        $message .= "• Layanan: {$permohonan->layanan->nama}\n";
        $message .= "• Status: *SELESAI* ✅\n\n";
        
        if (!empty($permohonan->catatan)) {
            $message .= "📝 *Catatan:*\n{$permohonan->catatan}\n\n";
        }
        
        $message .= "Silakan datang ke kantor kelurahan untuk mengambil dokumen Anda.\n\n";
        $message .= "Terima kasih telah menggunakan layanan kami.\n\n";
        $message .= "_Kelurahan Graha Indah_";

        try {
            return $this->sendMessage($permohonan->no_hp, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'permohonan_id' => $permohonan->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception, just log it
            // So the status update still succeeds even if WA fails
            return null;
        }
    }
}
