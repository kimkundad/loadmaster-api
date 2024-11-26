<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class MessageSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $message;

    public function __construct($message)
    {
        $this->message = $message;

        // ส่งข้อความไปยัง Socket.IO
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://29dc-124-120-26-97.ngrok-free.app/msg', [
            'json' => [
                'event' => 'new-message',
                'room_id' => 2, // ระบุห้อง
                'data' => $this->message,
            ],
        ]);

        Log::info('MessageSent event triggered', [
            'response' => $response->getBody()->getContents(),
            'sent_data' => [
                'room_id' => $message->room_id,
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender_name,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */

}
