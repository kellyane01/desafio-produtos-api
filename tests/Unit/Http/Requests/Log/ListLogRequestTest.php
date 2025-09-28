<?php

namespace Tests\Unit\Http\Requests\Log;

use App\Http\Requests\Log\ListLogRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ListLogRequestTest extends TestCase
{
    public function test_rules_require_to_date_after_from_date(): void
    {
        $request = ListLogRequest::create('/api/v1/logs', 'GET', [
            'from' => '2024-01-10',
            'to' => '2024-01-05',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to', $validator->errors()->toArray());
    }

    public function test_rules_accept_valid_payload(): void
    {
        $request = ListLogRequest::create('/api/v1/logs', 'GET', [
            'model' => 'App\\Models\\Produto',
            'model_id' => 10,
            'action' => 'update',
            'user_id' => 2,
            'from' => '2024-01-01',
            'to' => '2024-01-15',
            'per_page' => 50,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }
}
