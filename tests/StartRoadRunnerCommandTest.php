<?php

namespace Laravel\Octane\Tests;

use Laravel\Octane\Commands\StartRoadRunnerCommand;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class StartRoadRunnerCommandTest extends TestCase
{
    public function test_default_worker_command_is_resolved_relative_to_the_base_path()
    {
        $app = $this->createApplication();

        $command = $this->command();

        $this->assertEquals(
            $app->basePath('vendor/bin/roadrunner-worker'),
            $command->workerCommand()
        );
    }

    public function test_configured_relative_worker_command_is_resolved_relative_to_the_base_path()
    {
        $app = $this->createApplication();

        $app['config']->set('octane.roadrunner.command', 'vendor/bin/custom-worker');

        $command = $this->command();

        $this->assertEquals(
            $app->basePath('vendor/bin/custom-worker'),
            $command->workerCommand()
        );
    }

    public function test_configured_absolute_worker_command_is_not_prefixed_with_the_base_path()
    {
        $app = $this->createApplication();

        $app['config']->set('octane.roadrunner.command', '/var/www/current/vendor/bin/roadrunner-worker');

        $command = $this->command();

        $this->assertEquals(
            '/var/www/current/vendor/bin/roadrunner-worker',
            $command->workerCommand()
        );
    }

    /** @dataProvider httpLogLevels */
    #[DataProvider('httpLogLevels')]
    public function test_http_log_entries_are_written_as_requests($level)
    {
        $output = new BufferedOutput;

        $server = Mockery::mock(Process::class);
        $server->shouldReceive('getIncrementalOutput')->andReturn(json_encode([
            'level' => $level,
            'msg' => 'http log',
            'logger' => 'http',
            'status' => 200,
            'method' => 'GET',
            'URI' => '/welcome',
            'remote_address' => '127.0.0.1:39809',
            'elapsed' => 12,
        ])."\n");
        $server->shouldReceive('getIncrementalErrorOutput')->andReturn('');
        $server->shouldReceive('clearOutput')->andReturnSelf();
        $server->shouldReceive('clearErrorOutput')->andReturnSelf();

        $this->command($output)->writeServerOutput($server);

        $this->assertMatchesRegularExpression('/200\s+GET \/welcome \.+ 12\.00 ms/', $output->fetch());
    }

    public static function httpLogLevels()
    {
        return [
            'RoadRunner 2025' => ['info'],
            'RoadRunner 3' => ['INFO'],
        ];
    }

    protected function command($output = null)
    {
        return new class($output) extends StartRoadRunnerCommand
        {
            public function __construct($output)
            {
                $this->output = $output;
            }

            public function workerCommand()
            {
                return parent::workerCommand();
            }

            public function writeServerOutput($server)
            {
                parent::writeServerOutput($server);
            }

            protected function getTerminalWidth()
            {
                return 50;
            }
        };
    }
}
