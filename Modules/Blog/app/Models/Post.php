<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Blog\Database\Factories\PostFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'user_id',
    ];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
