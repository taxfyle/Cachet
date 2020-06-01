<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Handlers\Events\Component;

use CachetHQ\Cachet\Bus\Events\Component\ComponentStatusWasChangedEvent;
use CachetHQ\Cachet\Models\Component;
use CachetHQ\Cachet\Models\StatusChange;

class StatusChangeStorageHandler
{
    /**
     * Handle the event.
     *
     * @param \CachetHQ\Cachet\Bus\Events\Component\ComponentStatusWasChangedEvent $event
     *
     * @return void
     */
    public function handle(ComponentStatusWasChangedEvent $event)
    {
        $component = $event->component;
        $status = $event->new_status;
        StatusChange::create([
          'component_id'     => $component->id, 
          'component_status' => $status
        ]);
    }
}
