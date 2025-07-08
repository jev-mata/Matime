<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMember;
use App\Service\ColorService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(), 
        ];
    }  

    public function forOrganization(Organization $org): self
    {
        return $this->state(fn() => [
            'organization_id' => $org->id,
        ]);
    }

    public function withClient(?Client $client = null): self
    {
        return $this->state(fn() => [
            'client_id' => $client?->id ?? Client::factory(),
        ]);
    }
 
}
