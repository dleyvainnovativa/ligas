<?php

namespace App\Policies;

use App\Models\Manager;
use App\Models\Pista;

class PistaPolicy
{
    public function manage(Manager $manager, Pista $pista): bool
    {
        return $pista->sede->league->manager_id === $manager->id;
    }

    public function view(Manager $manager, Pista $pista): bool
    {
        return $this->manage($manager, $pista);
    }
    public function update(Manager $manager, Pista $pista): bool
    {
        return $this->manage($manager, $pista);
    }
    public function delete(Manager $manager, Pista $pista): bool
    {
        return $this->manage($manager, $pista);
    }
}
