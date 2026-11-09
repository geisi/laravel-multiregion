<?php

declare(strict_types=1);

namespace Tpetry\MultiRegion\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $delay
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('multiregion_lags')]
#[Fillable(['name', 'delay'])]
class Lag extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delay' => 'integer',
        ];
    }
}
