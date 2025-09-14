<?php

namespace App\Models\Post;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentLike extends Model
{
    protected $table = 'comment_likes';

    protected $fillable = ['comment_id', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
