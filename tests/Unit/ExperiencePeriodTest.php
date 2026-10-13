<?php

declare(strict_types=1);

use App\Models\Experience;

it('formats the period', function (?string $start, ?string $end, ?string $expected): void {
    $experience = new Experience(['starts_on' => $start, 'ends_on' => $end]);

    expect($experience->period())->toBe($expected);
})->with([
    'ongoing' => ['2022-03-01', null, 'Mar 2022 — Present'],
    'closed' => ['2019-09-01', '2021-07-01', 'Sep 2019 — Jul 2021'],
    'graduation year only' => [null, '2020-07-01', '2020'],
    'no dates' => [null, null, null],
]);
