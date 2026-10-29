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
    | Google AI Fashion Video Generation (Veo / Gemini API)
    |--------------------------------------------------------------------------
    | Realistic AI fashion model video generation where a model wears and showcases
    | the exact catalog suit from product reference images.
    */
    'google_video' => [
        'enabled' => (bool) env('GOOGLE_VIDEO_ENABLED', false),
        'api_key' => env('GOOGLE_VIDEO_API_KEY', env('GEMINI_API_KEY', '')),
        'model' => env('GOOGLE_VIDEO_MODEL', 'veo-2.0-generate-001'),
        'base_url' => env('GOOGLE_VIDEO_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'mock' => (bool) env('GOOGLE_VIDEO_MOCK', false),
        'estimated_cost_per_clip' => 0.15,
        'daily_limit' => (int) env('GOOGLE_VIDEO_DAILY_LIMIT', 10),
        'max_concurrent' => (int) env('GOOGLE_VIDEO_MAX_CONCURRENT', 2),
        'poll_interval_seconds' => (int) env('GOOGLE_VIDEO_POLL_INTERVAL', 8),
        'max_poll_seconds' => (int) env('GOOGLE_VIDEO_MAX_POLL', 400),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Fashion Shoot Predefined Styles
    |--------------------------------------------------------------------------
    | Tailored prompts for Indian ethnic wear (Anarkali, suits, dupattas, co-ords).
    */
    'fashion_styles' => [
        'runway_walk' => [
            'name' => 'Runway Walk',
            'badge' => 'Cinematic Walk',
            'description' => 'A model gracefully walking down a minimalist runway, highlighting fabric flow and drape.',
            'camera' => 'Smooth frontal tracking shot, eye-level, gliding backwards as the model walks forward.',
            'lighting' => 'Soft diffused runway lighting with gentle rim lights highlighting fabric textures.',
        ],
        'slow_motion_turn' => [
            'name' => 'Slow-Motion 360 Turn',
            'badge' => '360° Silhouette',
            'description' => 'Cinematic 360-degree slow motion rotation capturing the complete silhouette, front, back, and fall of the dupatta.',
            'camera' => 'Slow smooth orbital camera revolving 360 degrees around the model at waist level in 60fps slow motion.',
            'lighting' => 'Sophisticated 3-point fashion studio lighting with warm fill and golden rim lights.',
        ],
        'fabric_flow' => [
            'name' => 'Breeze & Fabric Flow',
            'badge' => 'Flow & Movement',
            'description' => 'Soft breeze catching the hem and dupatta of the suit, showing drape and movement of chiffon/georgette/silk.',
            'camera' => 'Low-angle medium shot drifting gently upward, capturing the graceful cascade of the fabric.',
            'lighting' => 'Airy daylight studio setting with warm sunbeams accentuating fabric translucency.',
        ],
        'editorial_studio' => [
            'name' => 'Editorial Fashion Studio',
            'badge' => 'Studio Editorial',
            'description' => 'High-fashion studio shoot with professional warm lighting, soft shadows, magazine aesthetic.',
            'camera' => 'Cinematic steadycam gliding around the model as she strikes poised, elegant fashion poses.',
            'lighting' => 'Warm luxury editorial lighting, subtle gradient background, soft sculptural shadows.',
        ],
        'festive_celebration' => [
            'name' => 'Festive Golden Hour',
            'badge' => 'Festive Regal',
            'description' => 'Warm golden lighting, festive courtyard setting with subtle bokeh, royal celebratory ambiance.',
            'camera' => 'Fluid cinematic dolly shot capturing model in regal motion with soft golden bokeh in background.',
            'lighting' => 'Golden hour natural sunlight with warm ambient fairy lights and regal palace architectural tones.',
        ],
        'close_up_motion' => [
            'name' => 'Artisan Detail in Motion',
            'badge' => 'Craft & Details',
            'description' => 'Medium to close-up glide focusing on intricate neck embroidery, border lace, and dupatta texture.',
            'camera' => 'Slow macro to medium camera glide starting from neckline embroidery, panning down to hem lace.',
            'lighting' => 'High-CRI precision spotlighting bringing out threadwork, zari, sequins, and fabric weave in vivid detail.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensible AI Fashion Motion Provider (Legacy / Fallback alias)
    |--------------------------------------------------------------------------
    */
    'ai_motion' => [
        'enabled' => (bool) env('VIDEO_STUDIO_AI_ENABLED', false),
        'provider' => env('VIDEO_STUDIO_AI_PROVIDER', 'null'),
    ],
];
