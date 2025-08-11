<?php

namespace SmartLogAnalyzer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    private array $alertData;
    private string $channel;

    public function __construct(array $alertData, string $channel)
    {
        $this->alertData = $alertData;
        $this->channel = $channel;
        
        $this->onQueue(config('smart-log-analyzer.processing.queue_connection', 'default'));
    }

    public function handle(): void
    {
        try {
            switch ($this->channel) {
                case 'email':
                    $this->sendEmailAlert();
                    break;
                case 'slack':
                    $this->sendSlackAlert();
                    break;
                case 'webhook':
                    $this->sendWebhookAlert();
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported alert channel: {$this->channel}");
            }

            Log::info('Alert sent successfully', [
                'channel' => $this->channel,
                'rule_id' => $this->alertData['rule']->id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send alert', [
                'channel' => $this->channel,
                'error' => $e->getMessage(),
                'rule_id' => $this->alertData['rule']->id ?? null,
            ]);

            throw $e;
        }
    }

    private function sendEmailAlert(): void
    {
        $rule = $this->alertData['rule'];
        $data = $this->alertData['data'];
        $timestamp = $this->alertData['timestamp'];

        $subject = $this->generateEmailSubject($rule, $data);
        $body = $this->generateEmailBody($rule, $data, $timestamp);

        foreach ($rule->recipients as $recipient) {
            Mail::raw($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                        ->subject($subject)
                        ->from(config('smart-log-analyzer.alerts.email.from', config('mail.from.address')));
            });
        }
    }

    private function sendSlackAlert(): void
    {
        $webhookUrl = config('smart-log-analyzer.alerts.slack.webhook_url');
        
        if (!$webhookUrl) {
            throw new \Exception('Slack webhook URL not configured');
        }

        $rule = $this->alertData['rule'];
        $data = $this->alertData['data'];

        $payload = [
            'text' => $this->generateSlackMessage($rule, $data),
            'username' => 'Smart Log Analyzer',
            'icon_emoji' => ':warning:',
            'attachments' => [
                [
                    'color' => $this->getSeverityColor($rule->severity),
                    'fields' => $this->generateSlackFields($rule, $data),
                    'footer' => 'Smart Log Analyzer',
                    'ts' => $this->alertData['timestamp']->timestamp,
                ]
            ]
        ];

        Http::post($webhookUrl, $payload);
    }

    private function sendWebhookAlert(): void
    {
        $webhookUrl = config('smart-log-analyzer.alerts.webhook.url');
        
        if (!$webhookUrl) {
            throw new \Exception('Webhook URL not configured');
        }

        $payload = [
            'event' => 'alert_triggered',
            'rule' => [
                'id' => $this->alertData['rule']->id,
                'name' => $this->alertData['rule']->name,
                'severity' => $this->alertData['rule']->severity,
                'trigger_type' => $this->alertData['rule']->trigger_type,
            ],
            'data' => $this->alertData['data'],
            'timestamp' => $this->alertData['timestamp']->toISOString(),
        ];

        Http::timeout(30)->post($webhookUrl, $payload);
    }

    private function generateEmailSubject($rule, $data): string
    {
        $severity = strtoupper($rule->severity);
        $appName = config('app.name', 'Application');
        
        return "[{$severity}] {$appName} - {$rule->name}";
    }

    private function generateEmailBody($rule, $data, $timestamp): string
    {
        $body = "Alert: {$rule->name}\n";
        $body .= "Severity: " . strtoupper($rule->severity) . "\n";
        $body .= "Triggered: {$timestamp->format('Y-m-d H:i:s T')}\n";
        $body .= "Type: {$rule->trigger_type}\n\n";

        $body .= "Description:\n{$rule->description}\n\n";

        $body .= "Alert Data:\n";
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            $body .= "- {$key}: {$value}\n";
        }

        $body .= "\n---\n";
        $body .= "This alert was generated by Smart Log Analyzer.\n";
        $body .= "Dashboard: " . route('smart-log-analyzer.dashboard') . "\n";

        return $body;
    }

    private function generateSlackMessage($rule, $data): string
    {
        $severity = strtoupper($rule->severity);
        return ":warning: *{$severity} Alert*: {$rule->name}";
    }

    private function generateSlackFields($rule, $data): array
    {
        $fields = [
            [
                'title' => 'Severity',
                'value' => strtoupper($rule->severity),
                'short' => true
            ],
            [
                'title' => 'Type',
                'value' => $rule->trigger_type,
                'short' => true
            ]
        ];

        if (isset($data['metric'])) {
            $fields[] = [
                'title' => 'Metric',
                'value' => $data['metric'],
                'short' => true
            ];
        }

        if (isset($data['detected_value'])) {
            $fields[] = [
                'title' => 'Value',
                'value' => $data['detected_value'],
                'short' => true
            ];
        }

        return $fields;
    }

    private function getSeverityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => '#36a64f',
            'low' => '#439FE0',
            default => '#36a64f'
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAlertJob failed permanently', [
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
            'rule_id' => $this->alertData['rule']->id ?? null,
            'attempts' => $this->attempts()
        ]);
    }
}