<?php

namespace App\Actions\Fisher;

use App\Models\Boat;

class UpdateBoat
{
    public function execute(Boat $boat, array $attributes): Boat
    {
        $boat->update($attributes);

        return $boat->fresh();
    }
}
