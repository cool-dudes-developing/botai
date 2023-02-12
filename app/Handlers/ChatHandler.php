<?php

namespace App\Handlers;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class ChatHandler extends \DefStudio\Telegraph\Handlers\WebhookHandler
{
    public function handle(Request $request, TelegraphBot $bot): void
    {
        Log::info('Chat', [$request->all()]);
        parent::handle($request, $bot);
    }

    public function start(): void
    {
        $this->chat->html("I'm Chatik, a chatbot created by @decepti. 😊\n\nI'm still under construction, so please be patient. 😢\n\nПривет Кристина! 😊")->send();
    }

    public function params($param): void
    {

        $this->chat->html('Params: ' . json_encode($param))->send();
    }

    private function getAiResponse($text, $maintenance = false)
    {
        if ($maintenance)
            return "I'm under construction, please wait. 😢";
        else {
            $resp = Http::timeout(500)->withHeaders([
                'Authorization' => 'Bearer ' . env('AI_KEY'),
                'Content-Type' => 'application/json',
            ])
                ->post('https://api.openai.com/v1/completions', [
                    'prompt' => $text,
                    'model' => 'text-davinci-003',
                    'max_tokens' => 2048,
                    'temperature' => 0.9,
                    'top_p' => 1,
                    "frequency_penalty" => 0.0,
                    "presence_penalty" => 0.3
                ]);
            Log::info('AI response', [$resp->json()]);
            return trim($resp->json()['choices'][0]['text']);
        }
    }

    protected
    function handleChatMessage(Stringable $text): void
    {
        $messageId = $this->chat->html("Generating...\nThis may take a while based on the complexity of your message. 😊")->send()->telegraphMessageId();

        // check if $text contains array of words
        if (Str::contains($text, config('telegraph.author'), true) || strtolower($text) == 'автор') {
            $respText = "I'm Chatik, a chatbot created by @decepti. 😊\n\nI'm still under construction, so please be patient. 😢\n\nПривет Кристина! 😊";
        } else {
            $respText = "Something went wrong. 😢";
            try {
                $respText = $this->getAiResponse($text);
            } catch (\Exception $e) {
                $respText = "An error occurred. 😢";
            }
        }

        $this->chat->edit($messageId)->html(
            'Human: ' . $text . "\nChatik: " . $respText
        )->send();

        if ($this->message->from()->username() != 'decepti')
            TelegraphChat::where('chat_id', 421348308)->first()->html(
                'Human: @' . $this->message->from()->username() . "\nsaid: " . $text . "\n\nChatik\nresponded:\n<pre>" . $respText . "</pre>"
            )->send();
    }
}
