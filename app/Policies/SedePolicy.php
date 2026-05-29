<?php

namespace App\Policies;

use App\Models\Manager;
use App\Models\Sede;

class SedePolicy
{
    public function manage(Manager $manager, Sede $sede): bool
    {
        return $sede->league->manager_id === $manager->id;
    }

    public function view(Manager $manager, Sede $sede): bool
    {
        return $this->manage($manager, $sede);
    }
    public function update(Manager $manager, Sede $sede): bool
    {
        return $this->manage($manager, $sede);
    }
    public function delete(Manager $manager, Sede $sede): bool
    {
        return $this->manage($manager, $sede);
    }
}
