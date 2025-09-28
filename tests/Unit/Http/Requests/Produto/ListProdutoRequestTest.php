<?php

namespace Tests\Unit\Http\Requests\Produto;

use App\Http\Requests\Produto\ListProdutoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ListProdutoRequestTest extends TestCase
{
    public function test_prepare_for_validation_normalizes_categories_and_availability(): void
    {
        $request = ListProdutoRequest::create('/api/v1/produtos', 'GET', [
            'categorias' => 'Audio, Video ,  ',
            'disponivel' => 'true',
        ]);

        $request->setContainer($this->app);
        $request->setRedirector(app('redirect'));

        $this->invokePrepareForValidation($request);

        $this->assertSame(['Audio', 'Video'], $request->input('categorias'));
        $this->assertTrue($request->input('disponivel'));
    }

    public function test_rules_validate_payload(): void
    {
        $request = ListProdutoRequest::create('/api/v1/produtos', 'GET', [
            'per_page' => 200,
            'sort' => 'invalid',
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
        $this->assertArrayHasKey('sort', $validator->errors()->toArray());
    }

    private function invokePrepareForValidation(ListProdutoRequest $request): void
    {
        $reflection = new \ReflectionMethod($request, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($request);
    }
}
