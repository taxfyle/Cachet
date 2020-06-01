<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Commands\StatusChange;

use CachetHQ\Cachet\Models\StatusChange;

final class RemoveStatusChangeCommand
{
    /**
     * The status change point to remove.
     *
     * @var \CachetHQ\Cachet\Models\StatusChange
     */
    public $status_change;

    /**
     * Create a new remove status change command instance.
     *
     * @param \CachetHQ\Cachet\Models\StatusChange $status_change
     *
     * @return void
     */
    public function __construct(StatusChange $status_change)
    {
        $this->status_change = $status_change;
    }
}