<?php

namespace Tests\Unit\Search;

use App\Search\SearchHealthReporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SearchHealthReporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('cache.default', 'array');
        Cache::flush();
    }

    public function tearDown(): void
    {
        Cache::flush();
        Mockery::close();

        parent::tearDown();
    }

    public function test_record_failure_updates_cache_and_logs_warning(): void
    {
        $logger = Mockery::mock();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                TestCase::assertTrue(Str::contains($message, 'Elasticsearch'));
                TestCase::assertSame('index_missing', $context['reason']);
                TestCase::assertSame('unhealthy', $context['status']);

                return true;
            });

        Log::shouldReceive('channel')
            ->once()
            ->with('search')
            ->andReturn($logger);

        $reporter = new SearchHealthReporter();
        $reporter->recordFailure('index_missing', ['filters' => ['search' => 'notebook']]);

        $status = Cache::get('search:elasticsearch:status');
        $this->assertSame('unhealthy', $status['status']);
        $this->assertSame('index_missing', $status['reason']);
        $this->assertArrayHasKey('timestamp', $status);
    }

    public function test_record_failure_throttles_logs_within_interval(): void
    {
        $logger = Mockery::mock();
        $logger->shouldReceive('warning')->once();

        Log::shouldReceive('channel')
            ->once()
            ->with('search')
            ->andReturn($logger);

        $reporter = new SearchHealthReporter();
        $reporter->recordFailure('no_node_available');
        $reporter->recordFailure('no_node_available');

        $status = Cache::get('search:elasticsearch:status');
        $this->assertSame('unhealthy', $status['status']);
    }

    public function test_record_success_marks_health_and_logs_recovery(): void
    {
        $warningLogger = Mockery::mock();
        $warningLogger->shouldReceive('warning')->once();

        $infoLogger = Mockery::mock();
        $infoLogger->shouldReceive('info')->once()->with('Elasticsearch voltou a responder.');

        $call = 0;
        Log::shouldReceive('channel')
            ->twice()
            ->with('search')
            ->andReturnUsing(function () use (&$call, $warningLogger, $infoLogger) {
                return $call++ === 0 ? $warningLogger : $infoLogger;
            });

        $reporter = new SearchHealthReporter();
        $reporter->recordFailure('index_missing');
        $reporter->recordSuccess();

        $status = Cache::get('search:elasticsearch:status');
        $this->assertSame('healthy', $status['status']);
    }
}
