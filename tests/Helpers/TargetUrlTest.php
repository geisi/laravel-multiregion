<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tpetry\MultiRegion\Helpers\TargetUrl;

it('builds the same path and query string at the destination', function (string $destination, string $expected) {
    $request = Request::create('http://replica.test:8080/posts/%C3%A4?tags[]=x&tags[]=y&a=1&a=2&flag&user.name=x&q=a+b');

    expect(TargetUrl::buildUrl($request, $destination))->toBe($expected);
})->with([
    'host without slash' => ['https://primary.test', 'https://primary.test/posts/%C3%A4?tags%5B%5D=x&tags%5B%5D=y&a=1&a=2&flag&user.name=x&q=a+b'],
    'host with slash' => ['https://primary.test/', 'https://primary.test/posts/%C3%A4?tags%5B%5D=x&tags%5B%5D=y&a=1&a=2&flag&user.name=x&q=a+b'],
    'host with port' => ['http://primary.test:8080', 'http://primary.test:8080/posts/%C3%A4?tags%5B%5D=x&tags%5B%5D=y&a=1&a=2&flag&user.name=x&q=a+b'],
    'host with path' => ['https://primary.test/ignored', 'https://primary.test/posts/%C3%A4?tags%5B%5D=x&tags%5B%5D=y&a=1&a=2&flag&user.name=x&q=a+b'],
]);

it('builds the url without a query string', function () {
    expect(TargetUrl::buildUrl(Request::create('http://replica.test/posts'), 'https://primary.test'))
        ->toBe('https://primary.test/posts');
});
