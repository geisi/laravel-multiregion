<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tpetry\MultiRegion\Drivers\Header;

it('responds with the replay header', function () {
    $response = (new Header('X-Primary-Region', 'https://primary.test'))
        ->replayAtPrimary(Request::create('http://app.test/posts?b=2&a=1'));

    expect($response)
        ->getStatusCode()->toBe(204)
        ->getContent()->toBe('')
        ->headers->get('X-Primary-Region')->toBe('https://primary.test/posts?b=2&a=1');
});
