<?php

namespace Tests;

use Illuminate\Support\Facades\Config;
use JetBrains\PhpStorm\NoReturn;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Robertogallea\PulseDBSize\PulseDBSizeServiceProvider;
use Robertogallea\PulseDBSize\Recorders\DBSizeRecorder;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    #[NoReturn]
    protected function setUp(): void
    {
        parent::setUp();
        Pulse::handleExceptionsUsing(function ($exception) {
            dd($exception->getMessage());
        });
    }

    public function getPackageProviders($app)
    {
        return [
            PulseDBSizeServiceProvider::class,
            PulseServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        Config::set('pulse.recorders', [
            DBSizeRecorder::class => [
                'enabled' => true,
                'throttle' => 15,
            ],
        ]);
    }
}
