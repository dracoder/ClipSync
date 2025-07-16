<?php

namespace App\Mail\Clip;

use App\Models\Clip\ClipComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $clipOwner;
    public $commenter;
    public $clipTitle;
    public $commentText;
    public $commentUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($clipOwner, $commenter, $clipTitle, $commentText, $commentUrl)
    {
        $this->clipOwner = $clipOwner;
        $this->commenter = $commenter;
        $this->clipTitle = $clipTitle;
        $this->commentText = $commentText;
        $this->commentUrl = $commentUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('comment_notification_mail_subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.clip.comment-notification-mail',
            with: [
                'clipOwner' => $this->clipOwner,
                'commenter' => $this->commenter,
                'clipTitle' => $this->clipTitle,
                'commentText' => $this->commentText,
                'commentUrl' => $this->commentUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
