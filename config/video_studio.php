<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg & FFprobe Binaries
    |--------------------------------------------------------------------------
    */
    'ffmpeg_path' => env('FFMPEG_PATH', file_exists('/home/administrator/.local/bin/ffmpeg')
        ? '/home/administrator/.local/bin/ffmpeg'
        : (file_exists('/usr/bin/ffmpeg') ? '/usr/bin/ffmpeg' : 'ffmpeg')),

    'ffprobe_path' => env('FFPROBE_PATH', file_exists('/home/administrator/.local/bin/ffprobe')
        ? '/home/administrator/.local/bin/ffprobe'
        : (file_exists('/usr/bin/ffprobe') ? '/usr/bin/ffprobe' : 'ffprobe')),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    */
    'disk' => env('VIDEO_STUDIO_DISK', 'public'),
    'storage_dir' => 'video-studio',

    /*
    |--------------------------------------------------------------------------
    | Brand Settings
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => 'Dhansree',
        'tagline' => 'Dhansree Trends',
        'website' => 'dhansree.com',
        'colors' => [
            'maroon' => '#580000',
            'gold' => '#B08858',
            'cream' => '#FBFAF8',
            'dark' => '#140808',
        ],
        'logo_path' => public_path('brand/logo-header.png'),
        'font_bold' => file_exists(resource_path('fonts/LiberationSans-Bold.ttf'))
            ? resource_path('fonts/LiberationSans-Bold.ttf')
            : (file_exists('/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf')
                ? '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf'
                : (file_exists('/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf')
                    ? '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'
                    : (file_exists('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf')
                        ? '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
                        : null))),
        'font_regular' => file_exists(resource_path('fonts/LiberationSans-Regular.ttf'))
            ? resource_path('fonts/LiberationSans-Regular.ttf')
            : (file_exists('/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf')
                ? '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf'
                : (file_exists('/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf')
                    ? '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf'
                    : (file_exists('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf')
                        ? '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
                        : null))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Aspect Ratios & Presets
    |--------------------------------------------------------------------------
    */
    'aspect_ratios' => [
        '9:16' => [
            'label' => 'Instagram & FB Reels / Stories',
            'width' => 1080,
            'height' => 1920,
            'badge' => '9:16 (1080×1920)',
            'description' => 'Full-screen vertical video optimized for Instagram Reels, Facebook Reels, and Stories.',
        ],
        '1:1' => [
            'label' => 'Website & Square Feed',
            'width' => 1080,
            'height' => 1080,
            'badge' => '1:1 (1080×1080)',
            'description' => 'Classic square video for product page embeds and feed posts.',
        ],
        '4:5' => [
            'label' => 'Feed Advertisement (Portrait)',
            'width' => 1080,
            'height' => 1350,
            'badge' => '4:5 (1080×1350)',
            'description' => 'Optimal aspect ratio for Instagram & Facebook feed advertisements and high-conversion feeds.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'fashion_showcase' => [
            'name' => "Women's Fashion Showcase",
            'tag' => 'ELEGANT COLLECTION',
            'description' => 'Smooth cinematic zoom, refined Dhansree gold badge, soft transitions focusing on fabric and silhouette.',
            'transition' => 'fade',
            'badge_color' => '#B08858',
            'bg_tone' => '#140808',
        ],
        'new_arrivals' => [
            'name' => 'New Arrivals',
            'tag' => 'JUST LAUNCHED',
            'description' => 'Energetic stylish transitions, "NEW ARRIVAL" header badge, dynamic garment reveal.',
            'transition' => 'fade',
            'badge_color' => '#580000',
            'bg_tone' => '#180B0B',
        ],
        'festive_collection' => [
            'name' => 'Festive Collection',
            'tag' => 'FESTIVE SPECIAL',
            'description' => 'Rich royal maroon and gold tones, festive motif accents, celebrations and wedding wear focus.',
            'transition' => 'fade',
            'badge_color' => '#B08858',
            'bg_tone' => '#2A0606',
        ],
        'sale_offers' => [
            'name' => 'Sale and Special Offers',
            'tag' => 'SPECIAL OFFER',
            'description' => 'High-visibility price callout, limited-time banner, bold "Shop Now" call to action.',
            'transition' => 'fade',
            'badge_color' => '#C41C1C',
            'bg_tone' => '#120404',
        ],
        'clean_showcase' => [
            'name' => 'Clean Product Showcase',
            'tag' => 'DHANSREE',
            'description' => 'Minimalist cream & slate styling, distraction-free focus on genuine product images and details.',
            'transition' => 'fade',
            'badge_color' => '#580000',
            'bg_tone' => '#FBFAF8',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Duration Options (in seconds)
    |--------------------------------------------------------------------------
    */
    'durations' => [5, 8, 10, 12, 15, 20],
    'default_duration' => 10,

    /*
    |--------------------------------------------------------------------------
    | Background Audio Tracks
    |--------------------------------------------------------------------------
    */
    'audio_tracks' => [
        'festive_royal' => [
            'name' => 'Festive Royal Ambient',
            'file' => resource_path('audio/video-studio/festive-royal.mp3'),
            'description' => 'Rich royal atmosphere suited for sarees, festive kurtis & lehengas.',
        ],
        'runway_chill' => [
            'name' => 'Runway Chill Lounge',
            'file' => resource_path('audio/video-studio/runway-chill.mp3'),
            'description' => 'Subtle rhythmic fashion mood for contemporary women\'s wear.',
        ],
        'upbeat_modern' => [
            'name' => 'Upbeat Modern Pop',
            'file' => resource_path('audio/video-studio/upbeat-modern.mp3'),
            'description' => 'Bright, lively cadence great for new arrivals and promotional reels.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensible AI Fashion Motion Provider
    |--------------------------------------------------------------------------
    | Disabled by default until a provider (Runway, Kling, Luma, etc.) is configured.
    */
    'ai_motion' => [
        'enabled' => (bool) env('VIDEO_STUDIO_AI_ENABLED', false),
        'provider' => env('VIDEO_STUDIO_AI_PROVIDER', 'null'),
    ],
];
