<?php

namespace App\Enums;

/**
 * Why a stock movement happened. Sign convention: deliveries are positive,
 * sales are negative, adjustments may be either.
 */
enum StockMovementType: string
{
    case Delivery = 'delivery';
    case Sale = 'sale';
    case Adjustment = 'adjustment';
}
