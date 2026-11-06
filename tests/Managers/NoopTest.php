<?php

declare(strict_types=1);

use Tpetry\MultiRegion\Managers\Noop;

it('is always the primary region', function (?bool $primary) {
    config(['multiregion.primary' => $primary]);

    expect((new Noop)->isPrimary())
        ->toBeTrue();
})->with([
    'unconfigured' => [null],
    'primary' => [true],
    'replica' => [false],
]);

it('has always caught up', function () {
    expect((new Noop)->hasCaughtUp())
        ->toBeTrue();
});

it('never replays requests', function () {
    (new Noop)
        ->replayAtPrimary();
})->throws(LogicException::class, 'The noop multi-region driver never replays requests.');

it('adds the served by header', function () {
    config(['multiregion.served_by.value' => 'local']);

    $response = (new Noop)
        ->addServedByHeader(response('ok'));

    expect($response->headers->get('X-MultiRegion-ServedBy'))
        ->toBe('local');
});
