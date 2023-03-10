<?php

return [
    'greeting' => "π€ Hi there! I'm Chatik, a chat bot powered by AI. I'm here to help you with your questions. π\n\n" .
        "ποΈ I'm still under construction, so please be patient. π’\n\n" .
        "π₯ Here are some of the features I offer:\n" .
        "\tβ¨Unique responses to each of your questions\n" .
        "\tπChat history, so you can always reply to my message to follow up on our conversation\n" .
        "\tπSmart search to help you find what you're looking for\n\n" .
        "And just between us, I'm always learning and updating my skills to provide you with the best possible experience. Let me know how I can assist you today!",
    'queuing' => "π€π€ Hmm, let me think about that...",
    'generating' => "π€ Generating the best response...",
    'empty' => "π€π€ I'm sorry, I don't understand what you're asking. π’",
    'no_history' => "π€π€ I'm sorry, I don't have any history to show you. π’",
    'gathering_history' => "π€π€ Let me gather some history for you...",
    'test' => "π€π£οΈ Wow, our conversation has been going on for quite some time! Let's wrap this one up and feel free to start a new conversation anytime. Thanks for chatting with me!",
    'response' => [
        'admin' => [
            'error' => "An error occurred while trying to get a response from the AI for @:username:\n:error",
            'log' => "@:username said:\n:prompt\n\nChatik responded:\n<pre>:response</pre>",
            'maintenance' => [
                'up' => "π€π§ Maintenance is over! I'm back online! π",
                'down' => "π€π§ I'm now in maintenance mode! "
            ],
            'no_access' => "π€π« You don't have access to this command! π",
        ],
        'error' => [
            'maintenance' => "π€π§ Sorry, I'm currently undergoing maintenance. I'll be back up and running soon! π",
            'overload' => "π₯ Sorry, I'm currently overloaded. Please try again later! π",
            'conversation_too_long' => "π€π£οΈ Wow, our conversation has been going on for quite some time! Let's wrap this one up and feel free to start a new conversation anytime. Thanks for chatting with me!",
            'unknown' => "π€π Oh no! I'm having trouble processing your message. Please try again later! π"
        ]

    ],
    'group' => [
        'created' => "π€ Hi there! I'm Chatik, a chat bot powered by AI. I'm here to help you with your questions. π",
    ]
];
