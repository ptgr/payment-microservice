<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

class TokenRequest extends AbstractRequest
{
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    protected function getConstrains(): Collection
    {
        return new Assert\Collection([
            'transaction_name' => [new Assert\Length(min: 1, max: 60), new Assert\NotBlank],
            'vat' => [new Assert\Type('integer'), new Assert\PositiveOrZero, new Assert\NotBlank],
            'currency_code' => [new Assert\Length(3), new Assert\NotBlank],
            'provider_id' => [new Assert\Type('integer'), new Assert\NotBlank],
            'items' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\Count(min: 1),
                new Assert\All([
                    new Assert\Collection([
                        'external_id' => [new Assert\Type('integer'), new Assert\Positive, new Assert\NotBlank],
                        'name' => [new Assert\Type('string'), new Assert\NotBlank()],
                        'quantity' => [new Assert\Type('integer'), new Assert\Positive, new Assert\NotBlank],
                        'price' => [new Assert\Type('float'), new Assert\NotBlank()],
                        'discount' => [new Assert\Type('integer'), new Assert\PositiveOrZero, new Assert\NotBlank],
                        'shipping' => [new Assert\Type('integer'), new Assert\PositiveOrZero, new Assert\NotBlank],
                    ])
                ])
            ])
        ]);
    }
}