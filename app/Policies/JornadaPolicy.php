<?php

namespace App\Policies;

use App\Models\Jornada;
use App\Models\Manager;

class JornadaPolicy
{
    public function manage(Manager $m, Jornada $j): bool
    {
        return $j->group->league->manager_id === $m->id;
    }

    public function view(Manager $m, Jornada $j): bool
    {
        return $this->manage($m, $j);
    }
    public function update(Manager $m, Jornada $j): bool
    {
        return $this->manage($m, $j);
    }
    public function delete(Manager $m, Jornada $j): bool
    {
        return $this->manage($m, $j);
    }
}
