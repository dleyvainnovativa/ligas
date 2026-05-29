<?php

namespace App\Policies;

use App\Models\Manager;
use App\Models\Player;

class PlayerPolicy
{
    public function manage(Manager $manager, Player $player): bool
    {
        return $player->league->manager_id === $manager->id;
    }

    public function view(Manager $manager, Player $player): bool
    {
        return $this->manage($manager, $player);
    }
    public function update(Manager $manager, Player $player): bool
    {
        return $this->manage($manager, $player);
    }
    public function delete(Manager $manager, Player $player): bool
    {
        return $this->manage($manager, $player);
    }
}
