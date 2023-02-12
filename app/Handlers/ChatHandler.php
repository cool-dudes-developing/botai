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

    /**
     * @throws \Exception
     */
    public function getAiResponse($text, $maintenance = false)
    {
        if ($maintenance)
            return "I'm under construction right now, please wait few minutes. 😢";
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
            if ($resp->status() != 200)
                throw new \Exception("AI error:\n<pre>" . json_encode($resp->json()) . "</pre>");
            Log::info('AI response', [$resp->json()]);
            return trim($resp->json()['choices'][0]['text']);
        }
    }

    protected
    function handleChatMessage(Stringable $text): void
    {
        $messageId = $this->chat->html("Generating...\nThis may take a while based on the complexity of your message. 😊")->send()->telegraphMessageId();
        // check if $text contains array of words
        if ($text->length() === 0) {
            $respText = "Please enter a text prompt to get an answer. 😊";
        } else if ($text->length() < 6) {
            $respText = "Please enter a longer text prompt to get an answer. 😊";
        } else if (Str::contains($text, config('telegraph.author'), true) || strtolower($text) == 'автор') {
            $respText = "I'm Chatik, a chatbot created by @decepti. 😊\n\nI'm still under construction, so please be patient. 😢\n\nПривет Кристина! 😊";
        } else {
            $respText = "Something went wrong. 😢";
            try {
                $respText = $this->getAiResponse($text, config('app.debug'));
            } catch (\Exception $e) {
                $respText = "An error occurred. 😢";
                TelegraphChat::where('chat_id', 421348308)->first()->html(
                    "An error occurred while trying to get a response from the AI for @" . $this->message->from()->username() . ":\n" .
                    $e->getMessage()
                )->send();
            }
        }

        $test = '<b>Human</b>: ' . $text . ($this->chat->chat_id == 838314601 ? "\n<b>Nazar</b>: Я люблю тебя ♥️" : '') . "\n<b>Chatik</b>: " . $respText;

        $this->chat->edit($messageId)->html(
            $test
        )->send();

        if ($this->message->from()->username() != 'decepti')
            TelegraphChat::where('chat_id', 421348308)->first()->html(
                '@' . $this->message->from()->username() . " said:\n" . $text . "\n\nChatik responded:\n<pre>" . $respText . "</pre>"
            )->send();
    }
}
