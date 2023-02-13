<?php

namespace App\Handlers;

use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Enums\ChatActions;
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
            return "🔧 Sorry, I'm currently undergoing maintenance. I'll be back up and running soon! 😊";
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

    private function getReplyHistoryArray(): array
    {
        $history = [$this->message->text()];
        $reply = $this->message->replyToMessage();
        while ($reply) {
            $history[] = $reply->text();
            $reply = $reply->replyToMessage();
        }
        Log::info('History', $history);
        return $history;
    }

    protected
    function handleChatMessage(Stringable $text): void
    {
        $messageId = $this->chat->html("🤖⏳ Thanks for waiting! I'm working on generating the best response for you.\nIt should be ready in just a few moments. Hang tight!")->reply($this->messageId)->send()->telegraphMessageId();
        $this->chat->action(ChatActions::TYPING)->send();
        // check if $text contains array of words
        if ($text->length() === 0) {
            $respText = "🤖🤔 Hmm, it seems like I need a bit more information to give you the best answer.\nCan you please provide a longer prompt or more details about your question? Thanks!";
        } else if ($text->length() < 6) {
            $respText = "Please enter a longer text prompt to get an answer. 😊";
        } else if (Str::contains($text, config('telegraph.author'), true) || strtolower($text) == 'автор') {
            $respText = "👋🤖 Hey there! I'm Chatik a chat bot made by @decepti.\nI'm here to answer any questions you may have.\n<b>Just ask me anything</b>, and I'll do my best to give you a helpful response!";
        } else {
            try {

//                if ($this->message->replyToMessage()) {
//                    $respText = join("\n\n", $this->getReplyHistoryArray()) . "\n\n" . Str::random(10);
//                } else {
//                    $respText = Str::random(10);
//                }
                $respText = $this->getAiResponse($text, config('app.debug'));
            } catch (\Exception $e) {
                $respText = "🤖😕 Oh no! An error occurred. Sorry for the inconvenience. We are working to fix this issue ASAP.";
                TelegraphChat::where('chat_id', 421348308)->first()->html(
                    "An error occurred while trying to get a response from the AI for @" . $this->message->from()->username() . ":\n" .
                    $e->getMessage()
                )->send();
            }
        }

        $this->chat->edit($messageId)->html(
            $respText
        )->send();

        if ($this->message->from()->username() != 'decepti')
            TelegraphChat::where('chat_id', 421348308)->first()->html(
                '@' . $this->message->from()->username() . " said:\n" . $text . "\n\nChatik responded:\n<pre>" . $respText . "</pre>"
            )->send();
    }
}
