<?php

return [
    'ffmpeg' => [
        'binaries' => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
        'threads' => env('FFMPEG_THREADS', 12),
    ],
    'ffprobe' => [
        'binaries' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
    ],
    'timeout' => 3600,
];
