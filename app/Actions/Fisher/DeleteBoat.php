<?php

namespace App\Actions\Fisher;

use App\Models\Boat;

class DeleteBoat
{
    public function execute(Boat $boat): void
    {
        abort_if($boat->is_active, 409, 'Deactivate the boat before deletion.');
        $boat->delete();
    }
}
