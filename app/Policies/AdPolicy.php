<?php

namespace App\Policies;

use App\Models\Ad;
use App\Models\Manager;

class AdPolicy
{
    public function manage(Manager $m, Ad $a): bool
    {
        return $a->league->manager_id === $m->id;
    }

    public function view(Manager $m, Ad $a): bool
    {
        return $this->manage($m, $a);
    }
    public function update(Manager $m, Ad $a): bool
    {
        return $this->manage($m, $a);
    }
    public function delete(Manager $m, Ad $a): bool
    {
        return $this->manage($m, $a);
    }
}
