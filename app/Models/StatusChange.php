<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Models;

use AltThree\Validator\ValidatingTrait;
use CachetHQ\Cachet\Models\Traits\HasMeta;
use CachetHQ\Cachet\Models\Traits\HasTags;
use CachetHQ\Cachet\Models\Traits\SearchableTrait;
use CachetHQ\Cachet\Models\Traits\SortableTrait;
use CachetHQ\Cachet\Presenters\StatusChangePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use McCool\LaravelAutoPresenter\HasPresenter;

/**
 * This is the status change model.
 *
 * @author Nick Keenan <nkeenan38@gmail.com>
 */
class StatusChange extends Model implements HasPresenter
{
    use HasMeta;
    use HasTags;
    use SearchableTrait;
    use SoftDeletes;
    use SortableTrait;
    use ValidatingTrait;

    /**
     * The accessors to append to the model's array form.
     *
     * @var string[]
     */
    protected $appends = [
    ];

    /**
     * The model's attributes.
     *
     * @var string[]
     */
    protected $attributes = [
        'component_status' => 0,
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var string[]
     */
    protected $casts = [
        'component_id'     => 'int',
        'component_status' => 'int',
        'deleted_at'       => 'date',
    ];

    /**
     * The fillable properties.
     *
     * @var string[]
     */
    protected $fillable = [
        'component_id',
        'component_status'
    ];

    /**
     * The validation rules.
     *
     * @var string[]
     */
    public $rules = [
        'component_id'     => 'required|int',
        'component_status' => 'required|int|min:0|max:4'
    ];

    /**
     * The searchable fields.
     *
     * @var string[]
     */
    protected $searchable = [
        'id',
        'component_id',
        'component_status',
    ];

    /**
     * The sortable fields.
     *
     * @var string[]
     */
    protected $sortable = [
        'id',
        'component_id',
        'component_status'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var string[]
     */
    protected $with = [
    ];

    /**
     * Get the component relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id', 'id');
    }

    /**
     * Get the presenter class.
     *
     * @return string
     */
    public function getPresenterClass()
    {
        return StatusChangePresenter::class;
    }
}
