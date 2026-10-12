<?php

namespace Laravel\Octane\Tests;

use Laravel\Octane\Commands\Concerns\InstallsRoadRunnerDependencies;
use PHPUnit\Framework\Attributes\DataProvider;

class InstallsRoadRunnerDependenciesTest extends TestCase
{
    /** @dataProvider roadRunnerVersions */
    #[DataProvider('roadRunnerVersions')]
    public function test_roadrunner_binary_version_support($version, $supported)
    {
        $this->assertSame($supported, $this->installer()->roadRunnerVersionIsSupported($version));
    }

    public static function roadRunnerVersions()
    {
        return [
            ['2.12.3', false],
            ['2023.2.0', false],
            ['2023.3.0', true],
            ['2025.1.15', true],
            ['3.0.0', true],
            ['3.1.2', true],
        ];
    }

    public function test_roadrunner_packages_allow_both_roadrunner_stacks()
    {
        $this->assertSame([
            'spiral/roadrunner-http:^3.3.0 || ^4.2',
            'spiral/roadrunner-cli:^2.6.0 || ^3.0',
        ], $this->installer()->roadRunnerPackages());
    }

    protected function installer()
    {
        return new class
        {
            use InstallsRoadRunnerDependencies {
                roadRunnerVersionIsSupported as public;
                roadRunnerPackages as public;
            }
        };
    }
}
