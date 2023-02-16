<?php

namespace App\Handlers;

use App\Jobs\ProcessMessageJob;
use App\Models\Bot;
use App\Models\SavedConversations;
use App\Models\SavedMessage;
use DefStudio\Telegraph\DTO\Chat;
use DefStudio\Telegraph\DTO\InlineQuery;
use DefStudio\Telegraph\DTO\InlineQueryResultArticle;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

/**
 * @property Bot $bot
 */
class ChatHandler extends WebhookHandler
{
    public function handle(Request $request, TelegraphBot $bot): void
    {
        Log::info('Handle', [$request]);
        parent::handle($request, $bot);
    }

    public function isAdminChat(): bool
    {
        return $this->chat->chat_id === config('telegraph.admin_chat_id');
    }

    public function start(): void
    {
        Log::info('Start', [$this->chat->chat_id]);
        $this->chat->markdown(__('botai.greeting'))->send();
    }

    /**
     * Set to maintenance mode
     * @return void
     */
    public function down(): void
    {
        if ($this->isAdminChat()) {
            $this->bot->update(['maintenance' => true]);
            $this->chat->html(__('botai.response.admin.maintenance.down'))->send();
        } else {
            $this->chat->html(__('botai.response.admin.no_access'))->send();

        }
    }

    /**
     * Set to normal mode
     * @return void
     */
    public function up(): void
    {
        if ($this->isAdminChat()) {
            $this->bot->update(['maintenance' => false]);
            $this->chat->html(__('botai.response.admin.maintenance.up'))->send();
        } else {
            // command not allowed
            $this->chat->html(__('botai.response.admin.no_access'))->send();
        }
    }

    public function question($text)
    {
        Log::info('Question', [$text]);
        if (strlen($text) == 0) {
            $this->chat->html(__('botai.empty'))->reply($this->message->id())->send();
            return;
        }
        $messageId = $this->chat->html(__('botai.queuing'))->reply($this->message->id())->send()->telegraphMessageId();

        ProcessMessageJob::dispatch($this->bot, $this->chat, $this->message, new Stringable($text), true, $messageId)->afterResponse();
    }

    protected function handleGroupChatCreated(): void
    {
        $this->chat->html(__('botai.group.created'))->send();
    }

    protected function handleInlineQuery(InlineQuery $inlineQuery): void
    {
        Log::debug('Inline query', [$inlineQuery->query()]);
//        Button::make('switch')->switchInlineQuery('foo');
        Telegraph::answerInlineQuery($inlineQuery->id(), [
            InlineQueryResultArticle::make('info', 'Find information', 'Find information about a topic')
                ->description('Find information about a topic')
                ->thumbUrl('https://img.icons8.com/officel/80/null/information.png')
                ->thumbHeight(64)
                ->thumbWidth(64)
        ])->cache(10)->send();
    }

    protected function shouldReceiveResponse(): bool
    {
        if ($this->message->replyToMessage() === null) {
            return $this->message->chat()->type() === 'private';
        }
        return $this->message->replyToMessage()->from()->username() === $this->bot->name;
    }

    public function summarize()
    {
//        $history = SavedMessage::where('chat_id', $this->chat->chat_id)->orderBy('message_id')->get()->map(function ($message) {
//            return ($message->sender_id == $this->bot->id ? 'AI: ' : 'USER: ') . $message->text;
//        });
        $history = SavedConversations::where('chat_id', $this->chat->chat_id)->orderBy('id', 'desc')->limit(10)->get()->load('messages')->map(function ($conversation) {
            return $conversation->messages->map(function ($message) {
                return ($message->sender_id == $this->bot->id ? 'AI: ' : 'USER: ') . $message->text;
            })->implode("\n");
        })->implode("\n\n");
        Log::debug('History', [$history]);
        $this->chat->html($history)->send();
        return;

        if ($history->count() == 0) {
            $this->chat->html(__('botai.no_history'))->reply($this->message->id())->send();
            return;
        }
        $messageId = $this->chat->html(__('botai.gathering_history'))->reply($this->message->id())->send()->telegraphMessageId();

        $history = "The following is a conversation between USER and AI. Create summary of this conversation in form of list" . $history->implode(' ');

        ProcessMessageJob::dispatch($this->bot, $this->chat, $this->message, new Stringable($history), true, $messageId, true, false, false)->afterResponse();
    }

    protected
    function handleChatMessage(Stringable $text): void
    {
        if ($this->request->has('message.group_chat_created')) {
            $this->handleGroupChatCreated();
            return;
        }
        $messageId = null;
        if ($shouldGetResponse = $this->shouldReceiveResponse()) {
            // send response that message is in queue
            if (strlen($text) == 0) {
                $this->chat->html(__('botai.empty'))->reply($this->message->id())->send();
                return;
            }
            $messageId = $this->chat->html(__('botai.queuing'))->reply($this->message->id())->send()->telegraphMessageId();
        }
        ProcessMessageJob::dispatch($this->bot, $this->chat, $this->message, $text, $shouldGetResponse, $messageId)->afterResponse();
    }


}
