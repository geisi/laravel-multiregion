<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Monitoring;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lag>
 */
class LagFactory extends Factory
{
    protected $model = Lag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attributes' => config()->array('multiregion.monitoring.model.attributes'),
        ];
    }
}
