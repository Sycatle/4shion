<?php

declare(strict_types=1);

use App\Support\Money;

it('formats cents the French way', function (int $cents, string $expected): void {
    expect(Money::format($cents))->toBe($expected);
})->with([
    [0, "0,00\u{00A0}€"],
    [4990, "49,90\u{00A0}€"],
    [123456, "1\u{202F}234,56\u{00A0}€"],
]);
