<?php

return [
    'greeting' => "🤖 Hi there! I'm Chatik, a chat bot powered by AI. I'm here to help you with your questions. 😊\n\n" .
        "🏗️ I'm still under construction, so please be patient. 😢\n\n" .
        "🔥 Here are some of the features I offer:\n" .
        "\t✨Unique responses to each of your questions\n" .
        "\t📝Chat history, so you can always reply to my message to follow up on our conversation\n" .
        "\t🔍Smart search to help you find what you're looking for\n\n" .
        "And just between us, I'm always learning and updating my skills to provide you with the best possible experience. Let me know how I can assist you today!",
    'queuing' => "🤖🤔 Hmm, let me think about that...",
    'generating' => "🤖 Generating the best response...",
    'empty' => "🤖🤔 I'm sorry, I don't understand what you're asking. 😢",
    'no_history' => "🤖🤔 I'm sorry, I don't have any history to show you. 😢",
    'gathering_history' => "🤖🤔 Let me gather some history for you...",
    'test' => "🤖🗣️ Wow, our conversation has been going on for quite some time! Let's wrap this one up and feel free to start a new conversation anytime. Thanks for chatting with me!",
    'response' => [
        'admin' => [
            'error' => "An error occurred while trying to get a response from the AI for @:username:\n:error",
            'log' => "@:username said:\n:prompt\n\nChatik responded:\n<pre>:response</pre>",
            'maintenance' => [
                'up' => "🤖🚧 Maintenance is over! I'm back online! 😊",
                'down' => "🤖🚧 I'm now in maintenance mode! "
            ],
            'no_access' => "🤖🚫 You don't have access to this command! 😊",
        ],
        'error' => [
            'maintenance' => "🤖🚧 Sorry, I'm currently undergoing maintenance. I'll be back up and running soon! 😊",
            'overload' => "🔥 Sorry, I'm currently overloaded. Please try again later! 😊",
            'conversation_too_long' => "🤖🗣️ Wow, our conversation has been going on for quite some time! Let's wrap this one up and feel free to start a new conversation anytime. Thanks for chatting with me!",
            'unknown' => "🤖😕 Oh no! I'm having trouble processing your message. Please try again later! 😊"
        ]

    ],
    'group' => [
        'created' => "🤖 Hi there! I'm Chatik, a chat bot powered by AI. I'm here to help you with your questions. 😊",
    ]
];
