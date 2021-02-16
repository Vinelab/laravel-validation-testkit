<?php

namespace Amerald\LaravelValidationTestkit;

use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;

trait TestsRequests
{
    /**
     * Request class to test.
     *
     * @return string
     */
    abstract protected function request(): string;

    /**
     * Input to be validated.
     *
     * Should include all expected fields, including optional ones.
     *
     * @return array
     */
    abstract protected function input(): array;

    /**
     * Define validation expectations.
     *
     * Examples:
     * $request->without('required_field')->shouldFail();
     * $request->without('optional_field')->shouldPass();
     * $request->with('some_field', 'wrongValue')->shouldFail();
     * $request->with('string_field', 'not:string')->shouldFail();
     * $request->without('array.*.field')->shouldFail();
     *
     * @param  Expectations  $request
     * @return Expectation[]
     */
    abstract protected function expectations(Expectations $request): array;

    /**
     * Provide test with the expectations.
     *
     * @return array
     */
    public function provideExpectations(): array
    {
        $request = new Expectations($this->input());

        return $this->expectations($request);
    }

    /**
     * Test request rules.
     *
     * @dataProvider provideExpectations
     * @param  Expectation  $expectation
     */
    public function testRequest(Expectation $expectation)
    {
        try {
            $request = $this->request();
            $request = new $request($expectation->input(), $expectation->input());
            $request->setContainer($this->app);
            $request->setRedirector(app(Redirector::class));

            $request->validate($request->rules());
            $passed = true;
        } catch (ValidationException $exception) {
            if ($expectation->shouldPass() !== false) {
                $message = implode("\n", $exception->validator->errors()->all());
            }
        }

        $this->assertEquals($expectation->shouldPass(), $passed ?? false, $message ?? '');
    }

    /**
     * Override PHPUnit getDataSetAsString() method to display custom failure messages.
     *
     * @param  bool  $includeData
     * @return string
     */
    public function getDataSetAsString($includeData = true): string
    {
        $input = $this->getProvidedData();
        $expectationName = array_keys($input)[0];
        $dataSetName = parent::getDataSetAsString($includeData);

        return preg_replace('/#\d+/', '"' . $expectationName . '"', $dataSetName);
    }
}
