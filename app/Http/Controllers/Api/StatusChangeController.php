<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Http\Controllers\Api;

use CachetHQ\Cachet\Bus\Commands\StatusChange\CreateStatusChangeCommand;
use CachetHQ\Cachet\Bus\Commands\StatusChange\RemoveStatusChangeCommand;
use CachetHQ\Cachet\Models\StatusChange;
use GrahamCampbell\Binput\Facades\Binput;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StatusChangeController extends AbstractApiController
{
    /**
     * Get all status change.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $statusChange = StatusChange::query();

        if ($sortBy = Binput::get('sort')) {
            $direction = Binput::has('order') && Binput::get('order') == 'desc';

            $statusChange->sort($sortBy, $direction);
        }

        $statusChange = $statusChange->paginate(Binput::get('per_page', 20));

        return $this->paginator($statusChange, Request::instance());
    }

    /**
     * Create a new status change point.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        try {
            $statusChange = execute(new CreateStatusChangeCommand(
                Binput::get('component_id'),
                Binput::get('component_status')
            ));
        } catch (QueryException $e) {
            throw new BadRequestHttpException();
        }

        return $this->item($statusChange);
    }

    /**
     * Destroys a status change point.
     *
     * @param \CachetHQ\Cachet\Models\StatusChange $statusChange
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(StatusChange $statusChange)
    {
        execute(new RemoveStatusChangeCommand($statusChange));

        return $this->noContent();
    }
}
