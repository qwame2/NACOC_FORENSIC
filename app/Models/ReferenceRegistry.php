<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceRegistry extends Model
{
    protected $table = 'reference_registry';

    protected $fillable = [
        'reference',
        'category',
        'prefix',
        'year',
    ];

    /**
     * Atomically reserve a reference in the registry.
     *
     * @param string $reference
     * @param string $category
     * @param string|null $modelType
     * @param mixed $modelId
     * @return static
     */
    public static function reserve(string $reference, string $category = 'CASE', ?string $modelType = null, $modelId = null): self
    {
        $reference = trim($reference);
        $parts = explode('-', $reference);
        $prefix = $parts[0] ?? null;
        $year = (isset($parts[1]) && is_numeric($parts[1])) ? (int)$parts[1] : (int)date('Y');

        return static::firstOrCreate(
            ['reference' => $reference],
            [
                'category' => $category,
                'prefix'   => $prefix,
                'year'     => $year,
            ]
        );
    }

    /**
     * Check if a reference is already reserved in the registry.
     *
     * @param string $reference
     * @return bool
     */
    public static function isReserved(string $reference): bool
    {
        return static::where('reference', trim($reference))->exists();
    }
}
