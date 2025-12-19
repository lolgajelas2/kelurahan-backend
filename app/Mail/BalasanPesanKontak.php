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

    /**
     * Create a new message instance.
     */
    public function __construct($kontak, $balasan)
    {
        $this->kontak = $kontak;
        $this->balasan = $balasan;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Balasan Pesan Anda - Kelurahan Graha Indah')
            ->view('emails.balasan_pesan_kontak')
            ->with([
                'kontak' => $this->kontak,
                'balasan' => $this->balasan,
            ]);
    }
}
