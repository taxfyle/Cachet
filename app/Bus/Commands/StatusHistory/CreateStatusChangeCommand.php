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

/**
 * This is the create status change command.
 *
 * @author Nick Keenan <nkeenan38@gmail.com>
 */
final class CreateStatusChangeCommand
{
    /**
     * The component id.
     *
     * @var int
     */
    public $component_id;

    /**
     * The component status.
     *
     * @var int
     */
    public $component_status;

    /**
     * Meta key/value pairs.
     *
     * @var array
     */
    public $meta = [];

    /**
     * The validation rules.
     *
     * @var string[]
     */
    public $rules = [
        'component_id'     => 'required|int',
        'component_status' => 'required|int|min:0|max:4',
    ];

    /**
     * Create a new create incident command instance.
     *
     * @param int         $component_id
     * @param int         $component_status
     * @param array       $meta
     *
     * @return void
     */
    public function __construct($component_id, $component_status, array $meta = [])
    {
        $this->component_id = $component_id;
        $this->component_status = $component_status;
        $this->meta = $meta;
    }
}
