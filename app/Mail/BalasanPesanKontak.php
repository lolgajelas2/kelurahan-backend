<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalasanPesanKontak extends Mailable
{
    use Queueable, SerializesModels;

    public $kontak;
    public $balasan;
    public $fromEmail;
    public $fromName;

    /**
     * Create a new message instance.
     */
    public function __construct($kontak, $balasan, $fromEmail = null, $fromName = null)
    {
        $this->kontak = $kontak;
        $this->balasan = $balasan;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Kirim email dengan format plain (tanpa nama)
        $mail = $this->to($this->kontak->email)
            ->subject('Balasan Pesan Anda - Kelurahan Graha Indah')
            ->view('emails.balasan_pesan_kontak')
            ->with([
                'kontak' => $this->kontak,
                'balasan' => $this->balasan,
            ]);
        
        // Override FROM jika diberikan (untuk Resend) - tanpa nama juga
        if ($this->fromEmail) {
            $mail->from($this->fromEmail);
        }
        
        return $mail;
    }
}
