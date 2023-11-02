<?php

namespace App\Models;

use App\Traits\CustomSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Contact extends BaseModel
{
    use CustomSoftDelete;
    use HasFactory;
    use QueryCacheable;

    public $table = 'contacts';
    /**
     * Time in seconds to live Cache
     */
    public int $cacheFor = 3600;

    /**
     * The attributes that are mass assignable.
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * The attributes that should be casted to native types.
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'phone' => 'string',
        'email' => 'string'
    ];

    /**
     * Invalidate the cache automatically
     * upon update in the database.
     */
    protected static bool $flushCacheOnUpdate = true;

    /**
     * Check if the model uses the company id field
     */
    protected bool $hasCompanyId = false;

    /**
     * The attributes that should be hidden for serialization.
     * @var array
     */
    protected $hidden = [
        'deleted_by',
        'deleted_at'
    ];

    /**
     * Responsible for determining which relationships
     * will be used in queries
     */
    protected array $relationsBySearch = [
        'createdBy',
        'updatedBy'
    ];

    /**
     * Responsible for bringing the assembled
     * relationships without the need for a call
     */
    protected $with = [
        'createdBy',
        'deletedBy',
        'updatedBy',
    ];

    /**
     * The validation rules.
     */
    protected static array $rules = [
        'name' => 'required|string|max:50',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|string|max:100'
    ];

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')
            ->select(['id', 'name'])->setEagerLoads([]);
    }

    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by')
            ->select(['id', 'name'])->setEagerLoads([]);
    }

    public function deletedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by')
            ->select(['id', 'name'])->setEagerLoads([]);
    }
}
