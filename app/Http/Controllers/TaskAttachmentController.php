<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function download(TaskAttachment $attachment)
    {
        abort_unless(auth()->user()->can('view', $attachment->task), 403);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
