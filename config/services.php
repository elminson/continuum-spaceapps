<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Self-hosted model that writes the plain-language fire bulletin (Ollama API).
    'bulletin' => [
        // Who writes the bulletins: 'anthropic' when an API key is set, else the self-hosted model; each falls back to the other.
        'provider' => env('BULLETIN_PROVIDER', env('ANTHROPIC_API_KEY') ? 'anthropic' : 'ollama'),
        'url' => env('OLLAMA_URL'),
        'key' => env('OLLAMA_API_KEY'),
        'model' => env('BULLETIN_MODEL', 'gemma4:26b-a4b'),
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
        'timeout' => (int) env('BULLETIN_TIMEOUT', 20),
    ],

];
