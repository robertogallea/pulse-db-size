<?php

use Laravel\Pulse\Facades\Pulse;

it('can_do_queries', function () {

    $this->artisan('pulse:check --once');

    $value = Pulse::values('db-size', ['tables'])->first();

    tap($value, function ($value) {
        expect($value)->not()->toBeNull();
        expect($value->value)->toBeJson();
    });
});
