<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RuntimeException;

class SupervisorOverride extends Model
{
    use HasFactory;

    protected $table = 'supervisor_overrides';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Prevent any mutation after creation.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('SupervisorOverride records are immutable.');
        });

        static::deleting(function () {
            throw new RuntimeException('SupervisorOverride records cannot be deleted.');
        });
    }
}
