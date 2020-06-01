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
use CachetHQ\Cachet\Presenters\ComponentPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jenssegers\Date\Date;
use Illuminate\Support\Collection;
use McCool\LaravelAutoPresenter\HasPresenter;

class Component extends Model implements HasPresenter
{
    use HasTags;
    use HasMeta;
    use SearchableTrait;
    use SoftDeletes;
    use SortableTrait;
    use ValidatingTrait;

    /**
     * List of attributes that have default values.
     *
     * @var mixed[]
     */
    protected $attributes = [
        'order'       => 0,
        'group_id'    => 0,
        'description' => '',
        'link'        => '',
        'enabled'     => true,
        'meta'        => null,
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var string[]
     */
    protected $casts = [
        'name'        => 'string',
        'description' => 'string',
        'status'      => 'int',
        'order'       => 'int',
        'link'        => 'string',
        'group_id'    => 'int',
        'enabled'     => 'bool',
        'meta'        => 'json',
        'deleted_at'  => 'date',
    ];

    /**
     * The fillable properties.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'link',
        'order',
        'group_id',
        'enabled',
        'meta',
    ];

    /**
     * The validation rules.
     *
     * @var string[]
     */
    public $rules = [
        'name'     => 'required|string',
        'status'   => 'required|int',
        'order'    => 'nullable|int',
        'group_id' => 'nullable|int',
        'link'     => 'nullable|url',
        'enabled'  => 'required|bool',
    ];

    /**
     * The searchable fields.
     *
     * @var string[]
     */
    protected $searchable = [
        'id',
        'name',
        'status',
        'order',
        'group_id',
        'enabled',
    ];

    /**
     * The sortable fields.
     *
     * @var string[]
     */
    protected $sortable = [
        'id',
        'name',
        'status',
        'order',
        'group_id',
        'enabled',
    ];

    /**
     * Get the group relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(ComponentGroup::class, 'group_id', 'id');
    }

    /**
     * Get the incidents relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incidents()
    {
        return $this->hasMany(Incident::class, 'component_id', 'id');
    }

    /**
     * Get the status changes relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statusChanges()
    {
        return $this->hasMany(StatusChange::class, 'component_id', 'id');
    }

    private function addToBreakdown(Collection &$breakdown, int $status, Date $start, Date $end) {
        $currentTime = $breakdown->get($status);
        $additionalTime = $end->format('U') - $start->format('U');
        $breakdown->put($status, $currentTime + $additionalTime);
    }


    /**
     * Get the status summary for each of the last 90 days.
     *
     * @return Illuminate\Support\Collection
     */
    public function statusHistory()
    {
        $startDate = Date::now()->subDays(89)->format('Y-m-d') . ' 00:00:00';
        // Get the original status
        $prevStatus = $this->statusChanges()->where('created_at', '<', $startDate)->first();
        if (is_null($prevStatus)) {
            $prevStatus = new StatusChange();
            $prevStatus->component_status = 0;
            $prevStatus->created_at = $startDate;
        }
        // Get the recent status changes
        $recentStatusChanges = $this->statusChanges()->where('created_at', '>', $startDate)->get();
        $dailySummaries = collect();
        for ($day=89; $day >= 0; $day--) { 
            $startDate = Date::createFromFormat('Y-m-d H:i:s', Date::now()->subDays($day)->format('Y-m-d') . ' 00:00:00');
            if ($day == 0) {
                // for today, use the current time instead of the end of the day
                $endDate = Date::now();
            } else {
                $endDate = Date::createFromFormat('Y-m-d H:i:s', Date::now()->subDays($day-1)->format('Y-m-d') . ' 00:00:00');
            }
            $dailyChanges = $recentStatusChanges->whereBetween('created_at', [
                $startDate,
                $endDate,
            ]);
            $dailySummary = collect([
                'summary' => max([$prevStatus->component_status, $dailyChanges->max('component_status')])
            ]);
            $breakdown = collect([
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0
            ]);
            switch ($dailyChanges->count()) {
                case 0:
                    // The previous status takes up the whole day
                    $this->addToBreakdown($breakdown, $prevStatus->component_status, $startDate, $endDate);
                    break;
                case 1:
                    // The previous status takes up the day until the single status change
                    $this->addToBreakdown($breakdown, $prevStatus->component_status, $startDate, Date::createFromFormat('Y-m-d H:i:s', $dailyChanges->first()->created_at));
                    // That newer status then takes up the rest of the day
                    $this->addToBreakdown($breakdown, $dailyChanges->first()->component_status, Date::createFromFormat('Y-m-d H:i:s', $dailyChanges->first()->created_at), $endDate);
                    $prevStatus = $dailyChanges->first();
                    break;
                default:
                    // The previous status takes up the day until the first status change that day
                    $this->addToBreakdown($breakdown, $prevStatus->component_status, $startDate, Date::createFromFormat('Y-m-d H:i:s', $dailyChanges->first()->created_at));
                    $prevStatus = $dailyChanges->first();
                    // Add the time of each status between status changes
                    foreach ($dailyChanges->slice(1) as $change) {
                        $this->addToBreakdown($breakdown, $prevStatus->component_status, Date::createFromFormat('Y-m-d H:i:s', $prevStatus->created_at), Date::createFromFormat('Y-m-d H:i:s', $change->created_at));
                        $prevStatus = $change;
                    }
                    // That last status change of the day takes up the rest of the time
                    $this->addToBreakdown($breakdown, $dailyChanges->last()->component_status, Date::createFromFormat('Y-m-d H:i:s', $dailyChanges->last()->created_at), $endDate);
                    $prevStatus = $dailyChanges->last();
                    break;
            }
            $dailySummary->put('breakdown', $breakdown);
            $description = $startDate->format('jS F Y');
            if ($dailySummary->get('summary') <= 1) {
                $description = $description . "<br>No downtime recorded.";
            } else {
                if ($breakdown->get(4) > 0) {
                    $description = $description . "<br>Major Outage for " . Date::now()->timespan(new Date('+' . $breakdown->get(4) . ' seconds'));
                }
                if ($breakdown->get(3) > 0) {
                    $description = $description . "<br>Partial Outage for " . Date::now()->timespan(new Date('+' . $breakdown->get(3) . ' seconds'));
                }
                if ($breakdown->get(2) > 0) {
                    $description = $description . "<br>Performance Issues for " . Date::now()->timespan(new Date('+' . $breakdown->get(2) . ' seconds'));
                }
            }
            // Abreviate the units of time
            $description = str_replace([" hours", " hour", " minutes", " minute", " seconds", " second", ","], ["h", "h", "m", "m", "s", "s"], $description);
            $dailySummary->put('description', $description);
            $dailySummaries->put($day, $dailySummary);
        }
        // total downtime is the sum of Partial and Major outages
        $totalDowntime = $dailySummaries->sum(function ($day) {
            $breakdown = $day->get('breakdown');
            $downtime = $breakdown->get(3) + $breakdown->get(4); // Add partial and major outage times
            return $downtime;
        });
        // recorded time does not include the Unknown status
        $totalRecordedTime = $dailySummaries->sum(function ($day) {
            $breakdown = $day->get('breakdown');
            $time = 0;
            for ($status = 1; $status <= 4; $status++) {
                $time += $breakdown->get($status);
            }
            return $time;
        });
        if ($totalRecordedTime === 0) {
            $formattedAvailability = "100";
        } else {
            $availability = (1.0 - ($totalDowntime / ($totalRecordedTime))) * 100.0;
            $formattedAvailability = rtrim(rtrim(number_format($availability, 3), '0'), '.'); // limits to 4 decimals, drops trailing zeros
            // Let's be precise about being imprecise. Won't look good when we show a status change occured but then round it out of existence
            if ($totalDowntime != 0 && $formattedAvailability == "100") {
                $formattedAvailability = "> 99.999";
            }
        }
        $statusHistory = collect([
            'availability' => $formattedAvailability,
            'days'         => $dailySummaries
        ]);
        return $statusHistory;
    }

    /**
     * Finds all components by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $status
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus(Builder $query, $status)
    {
        return $query->where('status', '=', $status);
    }

    /**
     * Finds all components which don't have the given status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $status
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotStatus(Builder $query, $status)
    {
        return $query->where('status', '<>', $status);
    }

    /**
     * Finds all components which are enabled.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled(Builder $query)
    {
        return $query->where('enabled', '=', true);
    }

    /**
     * Find all components which are within visible groups.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool                                  $authenticated
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAuthenticated(Builder $query, $authenticated)
    {
        return $query->when(!$authenticated, function (Builder $query) {
            return $query->whereDoesntHave('group', function (Builder $query) {
                $query->where('visible', ComponentGroup::VISIBLE_AUTHENTICATED);
            });
        });
    }

    /**
     * Finds all components which are disabled.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled(Builder $query)
    {
        return $query->where('enabled', '=', false);
    }

    /**
     * Finds all ungrouped components.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUngrouped(Builder $query)
    {
        return $query->enabled()
            ->where('group_id', '=', 0)
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Finds all grouped components.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGrouped(Builder $query)
    {
        return $query->enabled()
            ->where('group_id', '>', 0)
            ->groupBy('group_id');
    }

    /**
     * Get the presenter class.
     *
     * @return string
     */
    public function getPresenterClass()
    {
        return ComponentPresenter::class;
    }
}
