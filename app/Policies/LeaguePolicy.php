<?php

namespace App\Policies;

use App\Models\League;
use App\Models\Manager;

class LeaguePolicy
{
    public function viewAny(Manager $manager): bool
    {
        return true;
    }

    public function view(Manager $manager, League $league): bool
    {
        return $manager->id === $league->manager_id;
    }

    public function create(Manager $manager): bool
    {
        return true;
    }

    public function update(Manager $manager, League $league): bool
    {
        return $manager->id === $league->manager_id;
    }

    public function delete(Manager $manager, League $league): bool
    {
        return $manager->id === $league->manager_id;
    }
}
