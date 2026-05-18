<?php

namespace Meridaura\PaymentManager\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakePaymentHandlerCommand extends GeneratorCommand
{
    protected $name = 'make:payment-handler';

    protected $description = 'Create a new custom payment handler class';

    protected $type = 'Payment Handler';
    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/payment-handler.stub';
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }
}