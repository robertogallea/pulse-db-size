<?php

namespace Robertogallea\PulseDBSize\Livewire\Pulse;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class DBSizeCard extends Card
{
    public function render()
    {
        [[$dbSize, $totalSize, $tableCount, $maxSize], $time, $runAt] = $this->remember(function () {
            $dbSize = collect(json_decode($this->values('db-size', ['tables'])->first()->value))
                ->sortKeys();
            $totalSize = $dbSize->values()->sum();
            $tableCount = $dbSize->count();
            $maxSize = $dbSize->values()->max();

            return [$dbSize, $totalSize, $tableCount, $maxSize];
        });

        return view('pulseDBSize::db-size', [
            'dbSize' => $dbSize,
            'totalSize' => $totalSize,
            'tableCount' => $tableCount,
            'maxSize' => $maxSize,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
