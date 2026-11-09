<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Monitoring;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $delay
 * @property array<string, mixed> $attributes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('multiregion_lags')]
#[Fillable(['delay', 'attributes'])]
class Lag extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delay' => 'integer',
            'attributes' => 'array',
        ];
    }
}
