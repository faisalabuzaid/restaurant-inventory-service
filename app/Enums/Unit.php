<?php

namespace App\Enums;

/**
 * Unit of measure for an ingredient. Recipe lines, purchase order lines and
 * stock movements are all expressed in the ingredient's own unit; there is
 * deliberately no conversion between units.
 */
enum Unit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Millilitre = 'ml';
    case Litre = 'l';
    case Piece = 'pcs';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $unit) => $unit->value, self::cases());
    }
}
