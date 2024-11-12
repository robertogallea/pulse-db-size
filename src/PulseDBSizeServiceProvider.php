<?php

namespace Robertogallea\PulseDBSize;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Robertogallea\PulseDBSize\Livewire\Pulse\DBSizeCard;

class PulseDBSizeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'pulseDBSize');
    }

    public function boot()
    {
        Livewire::component('pulse.dbsize', DBSizeCard::class);
    }

    private function packagePath(string $path)
    {
        return __DIR__."/../$path";
    }
}
