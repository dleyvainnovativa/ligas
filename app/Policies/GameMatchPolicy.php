<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\Manager;

class GameMatchPolicy
{
    public function manage(Manager $m, GameMatch $g): bool
    {
        return $g->cancha->jornada->group->league->manager_id === $m->id;
    }

    public function view(Manager $m, GameMatch $g): bool
    {
        return $this->manage($m, $g);
    }
    public function update(Manager $m, GameMatch $g): bool
    {
        return $this->manage($m, $g);
    }
    public function delete(Manager $m, GameMatch $g): bool
    {
        return $this->manage($m, $g);
    }
}
