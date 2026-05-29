<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Manager;

class GroupPolicy
{
    public function manage(Manager $m, Group $g): bool
    {
        return $g->league->manager_id === $m->id;
    }

    public function view(Manager $m, Group $g): bool
    {
        return $this->manage($m, $g);
    }
    public function update(Manager $m, Group $g): bool
    {
        return $this->manage($m, $g);
    }
    public function delete(Manager $m, Group $g): bool
    {
        return $this->manage($m, $g);
    }
}
