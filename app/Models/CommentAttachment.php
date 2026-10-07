<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'upload_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getIconClassAttribute(): string
    {
        $mime = strtolower($this->mime_type ?? '');
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/')) {
            return 'bi-file-earmark-image text-neon-cyan';
        }
        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'bi-file-earmark-pdf text-danger';
        }
        if (in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])) {
            return 'bi-file-earmark-zip text-neon-yellow';
        }
        if (in_array($ext, ['js', 'php', 'json', 'py', 'html', 'css', 'ts', 'sql', 'sh'])) {
            return 'bi-file-earmark-code text-neon-green';
        }
        if (in_array($ext, ['doc', 'docx', 'txt', 'rtf', 'md'])) {
            return 'bi-file-earmark-text text-neon-orange';
        }
        if (in_array($ext, ['xls', 'xlsx', 'csv'])) {
            return 'bi-file-earmark-spreadsheet text-neon-green';
        }

        return 'bi-file-earmark text-light';
    }
}
