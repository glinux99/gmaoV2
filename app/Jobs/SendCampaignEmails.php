<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\SentEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;
    public $recipients;

    public $tries = 3;
    public $timeout = 600;

    public function __construct(Campaign $campaign, $recipients)
    {
        $this->campaign = $campaign;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($this->recipients as $subscriber) {
            try {
                // Envoi de l'email
                Mail::html($this->campaign->content, function ($message) use ($subscriber) {
                    $message->to($subscriber->email)
                            ->subject($this->campaign->subject);
                });

                // Enregistrement dans sent_emails avec subscriber_id
                SentEmail::create([
                    'campaign_id'     => $this->campaign->id,
                    'subscriber_id'   => $subscriber->id,
                    'recipient_email' => $subscriber->email,
                    'subject'         => $this->campaign->subject,
                    'body'            => $this->campaign->content,
                    'sent_at'         => now(),
                ]);

                $successCount++;
                Log::info("Campaign {$this->campaign->id} sent to {$subscriber->email}");
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Failed to send campaign email to {$subscriber->email}: " . $e->getMessage());
            }

            usleep(100000); // Pause de 0,1 seconde
        }

        $this->campaign->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        Log::info("Campaign {$this->campaign->id} completed: {$successCount} success, {$failCount} failed.");
    }

    public function failed(\Throwable $exception)
    {
        Log::error("SendCampaignEmails job failed for campaign {$this->campaign->id}: " . $exception->getMessage());
        $this->campaign->update(['status' => 'draft']);
    }

    /**
     * Callback quand le job échoue.
     */
}
