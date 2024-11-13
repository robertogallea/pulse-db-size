<?php

namespace Tests\Unit;

use Robertogallea\PulseDBSize\Recorders\DBSizeRecorder;

use function PHPUnit\Framework\assertNotNull;

it('can load configuration', function () {
    assertNotNull(config('pulse.recorders.').DBSizeRecorder::class);
});
