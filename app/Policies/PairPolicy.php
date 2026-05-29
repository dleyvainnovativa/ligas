<?php

namespace App\Policies;

use App\Models\Manager;
use App\Models\Pair;

class PairPolicy
{
    public function manage(Manager $m, Pair $p): bool
    {
        return $p->league->manager_id === $m->id;
    }

    public function view(Manager $m, Pair $p): bool
    {
        return $this->manage($m, $p);
    }
    public function update(Manager $m, Pair $p): bool
    {
        return $this->manage($m, $p);
    }
    public function delete(Manager $m, Pair $p): bool
    {
        return $this->manage($m, $p);
    }
}
