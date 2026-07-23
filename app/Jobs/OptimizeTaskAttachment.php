<?php

namespace App\Jobs;

use App\Models\TaskAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OptimizeTaskAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $attachmentId)
    {
    }

    public function handle(): void
    {
        $attachment = TaskAttachment::find($this->attachmentId);

        if (! $attachment || ! $attachment->is_image) {
            return;
        }

        $disk = Storage::disk($attachment->disk);
        if (! $disk->exists($attachment->path)) {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($disk->path($attachment->path));

            if ($image->width() > 1920) {
                $image->scaleDown(width: 1920);
                $encoded = $image->encode();
                $disk->put($attachment->path, (string) $encoded);
                $attachment->update(['size' => strlen((string) $encoded)]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
