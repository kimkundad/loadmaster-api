<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PDFMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data; // Data for the email content
    public $pdfContent; // PDF binary content

    /**
     * Create a new message instance.
     */
    public function __construct(array $data, $pdfContent)
    {
        $this->data = $data;
        $this->pdfContent = $pdfContent;
    }


    public function build()
    {
        return $this->view('emails.pdfmail') // Email template view
                    ->subject('ใบเสร็จรับเงิน บริษัท โหลดมาสเตอร์ โลจิสติกส์ จำกัด')
                    ->with('data', $this->data) // Pass data to the view
                    ->attachData($this->pdfContent, 'receipt.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }


}
