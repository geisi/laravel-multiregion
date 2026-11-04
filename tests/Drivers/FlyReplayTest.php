<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tpetry\MultiRegion\Drivers\FlyReplay;

it('responds with the fly-replay header', function () {
    $response = (new FlyReplay('region=ams'))
        ->replayAtPrimary(Request::create('http://app.test/posts'));

    expect($response)
        ->getStatusCode()->toBe(204)
        ->getContent()->toBe('')
        ->headers->get('fly-replay')->toBe('region=ams');
});
