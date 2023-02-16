<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\SavedConversations;
use App\Models\SavedMessage;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Message $message;
    private \Stringable $prompt;
    private Bot $bot;
    private ?int $loadingMessageId;
    private TelegraphChat $chat;
    private bool $getResponse;
    private bool $customPrompt;
    private bool $saveResponse;
    private bool $savePrompt;

    public function __construct(Bot $bot, TelegraphChat $chat, Message $message, \Stringable $prompt, bool $getResponse, ?int $loadingMessageId = null, bool $customPrompt = false, bool $savePrompt = true, bool $saveResponse = true)
    {
        $this->bot = $bot;
        $this->chat = $chat;
        $this->message = $message;
        $this->prompt = $prompt;
        $this->getResponse = $getResponse;
        $this->loadingMessageId = $loadingMessageId;
        $this->customPrompt = $customPrompt;
        $this->savePrompt = $savePrompt;
        $this->saveResponse = $saveResponse;
    }

    public function handle()
    {
        Log::info("Processing message: " . $this->prompt);
        Log::info("Getting saved conversation");
        $conversation = null;
        if ($this->savePrompt || !$this->saveResponse || !$this->customPrompt)
            $conversation = $this->getSavedConversation();
        // save current message to the database
        if ($this->savePrompt)
            $conversation->messages()->create([
                'message_id' => $this->message->id(),
                'sender_id' => $this->message->from()->id(),
                'chat_id' => $this->chat->chat_id,
                'text' => $this->prompt,
            ]);

        if (!$this->getResponse) {
            return;
        }

        $this->chat->action(ChatActions::TYPING)->send();
        Log::info("Getting AI response");
        $this->chat->edit($this->loadingMessageId)->html(__('botai.generating'))->send();
        $respText = $this->getResponse($conversation);

        Log::info("Sending response to user");
        $this->chat->deleteMessage($this->loadingMessageId)->send();
        // send the response to the user
        $responseMessageId = $this->chat->html($respText)->reply($this->message->id())->send()->telegraphMessageId();

        if ($this->saveResponse)
            $conversation->messages()->create([
                'message_id' => $responseMessageId,
                'sender_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'text' => $respText,
            ]);

        // set log to admin chat
        if ($this->chat->chat_id !== config('telegraph.admin_chat_id')) {
            $this->sendToAdmin(__('botai.response.admin.log', [
                'username' => $this->message->from()->username(),
                'prompt' => $this->prompt,
                'response' => $respText,
            ]));
        }
    }

    private function getSavedConversation(): SavedConversations
    {
        // if this is reply to a message, get the conversation from the replied message
        if ($this->message->replyToMessage()) {
            Log::info("This is a reply to a message", ['reply_to_message_id' => $this->message->replyToMessage()->id()]);
            $message = SavedMessage::where('message_id', $this->message->replyToMessage()->id())->first();
            // if the replied message is not in the database, create a new conversation
            if ($message) {
                Log::info("Found replied message in the database", ['conversation_id' => $message->conversation->id]);
                return $message->conversation;
            } else {
                Log::info("Replied message not found in the database, creating a new conversation");
                return SavedConversations::create([
                    'chat_id' => $this->message->chat()->id(),
                ]);
            }
        } else {
            Log::info("This is a new message, creating a new conversation");
            // this is a new message, create a new conversation
            return SavedConversations::create([
                'chat_id' => $this->message->chat()->id(),
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    private function getAiResponse(string $history): string
    {
        if ($this->customPrompt) {
            $prompt = $this->prompt;
        } else {
            $prompt = "The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. " . $history . " BOT: ";
        }
        Log::info('AI prompt', [$prompt]);
        if ($this->bot->maintenance) {
            return __('botai.response.error.maintenance');
        }
        $resp = Http::timeout(500)->withHeaders([
            'Authorization' => 'Bearer ' . env('AI_KEY'),
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.openai.com/v1/completions', [
                'prompt' => $prompt,
                'model' => 'text-davinci-003',
                'max_tokens' => 2048,
                'temperature' => 0.9,
                'top_p' => 1,
                "frequency_penalty" => 0.0,
                "presence_penalty" => 0.3,
                "stop" => [" USER:", " AI:"]
            ]);
        if (!$resp->successful()) {
            $this->sendToAdmin(
                __('botai.response.admin.error', [
                    'username' => $this->message->from()->username(),
                    'error' => json_encode($resp->json(), JSON_PRETTY_PRINT),
                ])
            );
            // if the API is overloaded, return a message
            if ($resp->status() === 429)
                return __('botai.response.error.overloaded');

            // if the prompt is too long, return a message that the conversation is too long
            if ($resp->status() === 400 && str_contains($resp->json()['error']['message'], 'Please reduce your prompt; or completion length'))
            {
                return __('botai.response.error.conversation_too_long');
            }
            return __('botai.response.error.unknown');
        }
        Log::info('AI response', [$resp->json()]);
        return trim($resp->json()['choices'][0]['text']);
    }

    private function sendToAdmin(string $string)
    {
        if ($chat_id = config('telegraph.admin_chat_id')) {
            if ($chat = TelegraphChat::where('chat_id', $chat_id)->first()) {
                $chat->html($string)->send();
            } else {
                Log::info('Admin chat not found in database, try adding it manually or start a conversation with the bot first.');
            }
        } else {
            Log::info('Admin user id not set, set TELEGRAPH_ADMIN_CHAT_ID in your .env file.');
        }
    }

    private function getResponse(SavedConversations|null $conversation): string
    {
        try {
            if ($this->customPrompt || !$conversation) {
                return $this->getAiResponse($this->prompt);
            }
            if (strlen(($history = $conversation->history())) > 5000)
                // if the history is too long, send a message that the conversation is too long
                return __('botai.response.error.conversation_too_long');
            return $this->getAiResponse($history);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            // if the AI fails, send a message that error processing the message occurred
            return __('botai.response.error.unknown');
        }
    }
}
