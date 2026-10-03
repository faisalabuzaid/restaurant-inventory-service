<?php

use App\Enums\PurchaseOrderStatus as Status;

/**
 * Every (from, to) pair is checked explicitly so that adding a shortcut
 * (e.g. draft -> closed) can never slip in unnoticed.
 */
dataset('transitions', function () {
    $allowed = [
        [Status::Draft, Status::Sent],
        [Status::Sent, Status::Received],
        [Status::Received, Status::Closed],
    ];

    foreach (Status::cases() as $from) {
        foreach (Status::cases() as $to) {
            $isAllowed = in_array([$from, $to], $allowed, true);
            yield "{$from->value} -> {$to->value}" => [$from, $to, $isAllowed];
        }
    }
});

it('only allows the linear transitions', function (Status $from, Status $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with('transitions');

it('treats everything except closed as open', function () {
    expect(Status::Draft->isOpen())->toBeTrue()
        ->and(Status::Sent->isOpen())->toBeTrue()
        ->and(Status::Received->isOpen())->toBeTrue()
        ->and(Status::Closed->isOpen())->toBeFalse();
});

it('accepts deliveries only once sent and until closed', function () {
    expect(Status::Draft->acceptsDeliveries())->toBeFalse()
        ->and(Status::Sent->acceptsDeliveries())->toBeTrue()
        ->and(Status::Received->acceptsDeliveries())->toBeTrue()
        ->and(Status::Closed->acceptsDeliveries())->toBeFalse();
});
