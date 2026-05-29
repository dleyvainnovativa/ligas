<?php

namespace App\Policies;

use App\Models\Cancha;
use App\Models\Manager;

class CanchaPolicy
{
    public function manage(Manager $m, Cancha $c): bool
    {
        return $c->jornada->group->league->manager_id === $m->id;
    }

    public function view(Manager $m, Cancha $c): bool
    {
        return $this->manage($m, $c);
    }
    public function update(Manager $m, Cancha $c): bool
    {
        return $this->manage($m, $c);
    }
    public function delete(Manager $m, Cancha $c): bool
    {
        return $this->manage($m, $c);
    }
}
