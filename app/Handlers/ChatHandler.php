<?php

namespace App\Handlers;

use App\Models\SavedConversations;
use App\Models\SavedMessage;
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
        $prompt = "The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. " . $text . " BOT: ";
        Log::info('AI request', [$prompt]);
        if ($maintenance)
            return "🔧 Sorry, I'm currently undergoing maintenance. I'll be back up and running soon! 😊";
        else {
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
            if ($resp->status() != 200) {
                if ($resp->status() == 429)
                    return "🔧 I'm experiencing high traffic right now. Please try again later. 😊";
                throw new \Exception("AI error:\n<pre>" . json_encode($resp->json()) . "</pre>");
            }
            Log::info('AI response', [$resp->json()]);
            return trim($resp->json()['choices'][0]['text']);
        }
    }

    private function sendToAdmin($text)
    {
        if ($chat_id = config('telegraph.admin_chat_id')) {
            if ($chat = TelegraphChat::where('chat_id', $chat_id)->first()) {
                $chat->html($text)->send();
            } else {
                Log::info('Admin chat not found in database, try adding it manually or start a conversation with the bot first.');
            }
        } else {
            Log::info('Admin user id not set, set TELEGRAPH_ADMIN_CHAT_ID in your .env file.');
        }
    }

    protected
    function handleChatMessage(Stringable $text): void
    {
        $messageId = $this->chat->html("🤖⏳ Thanks for waiting! I'm working on generating the best response for you.\nIt should be ready in just a few moments. Hang tight!")->reply($this->messageId)->send()->telegraphMessageId();
        $this->chat->action(ChatActions::TYPING)->send();
        // check if $text contains array of words
        if ($text->length() === 0) {
            $respText = "🤖🤔 Hmm, it seems like I need a bit more information to give you the best answer.\nCan you please provide a longer prompt or more details about your question? Thanks!";
        } else if (Str::contains($text, config('telegraph.author'), true) || strtolower($text) == 'автор') {
            $respText = "👋🤖 Hey there! I'm Chatik a chat bot made by @decepti.\nI'm here to answer any questions you may have.\n<b>Just ask me anything</b>, and I'll do my best to give you a helpful response!";
        } else {
            try {

                // check if message is a reply to another message
                if ($this->message->replyToMessage()) {
                    // get conversation from reply message
                    $message = SavedMessage::where('message_id', $this->message->replyToMessage()->id())->first();
                    if ($message) {
                        $conversation = $message->conversation;
                    } else {
                        $conversation = SavedConversations::create(['chat_id' => $this->chat->chat_id]);
                    }

                } else {
                    $conversation = SavedConversations::create(['chat_id' => $this->chat->chat_id]);
                }
                $conversation->messages()->create([
                    'sender_id' => $this->message->from()->id(),
                    'message_id' => $this->message->id(),
                    'text' => $text
                ]);
                $respText = $this->getAiResponse($conversation->history(), config('app.debug'));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $respText = "🤖😕 Oh no! An error occurred. Sorry for the inconvenience. We are working to fix this issue ASAP.";
                $this->sendToAdmin("An error occurred while trying to get a response from the AI for @" . $this->message->from()->username() . ":\n" .
                    $e->getMessage());
            } finally {
                if (isset($conversation)) {
                    $conversation->messages()->create([
                        'sender_id' => 0,
                        'message_id' => $messageId,
                        'text' => $respText
                    ]);
                }
            }
        }


        $this->chat->edit($messageId)->html(
            $respText
        )->send();

        if ($this->chat->chat_id != config('telegraph.admin_chat_id'))
            $this->sendToAdmin('@' . $this->message->from()->username() . " said:\n" . $text . "\n\nChatik responded:\n<pre>" . $respText . "</pre>");
    }
}
