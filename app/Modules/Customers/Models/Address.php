<?php

namespace App\Modules\Customers\Models;

use App\Models\User;
use Database\Factories\Customers\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(AddressFactory::class)]
class Address extends Model
{
    use HasFactory;

    public const MAX_PER_USER = 10;

    protected $fillable = ['user_id', 'label', 'name', 'phone', 'line1', 'line2', 'landmark', 'city', 'state', 'pincode', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The address as a plain array: what an order snapshots. */
    public function snapshot(): array
    {
        return $this->only(['name', 'phone', 'line1', 'line2', 'landmark', 'city', 'state', 'pincode']);
    }

    /** Multi-line text for display. */
    public function formatted(): string
    {
        return collect([$this->line1, $this->line2, $this->landmark, "{$this->city}, {$this->state} {$this->pincode}"])->filter()->implode("\n");
    }
}
