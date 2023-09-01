<?php

namespace App\Request;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GroupSequence;

abstract class AbstractRequest
{
    protected ValidatorInterface $validator;

    private ConstraintViolationListInterface $validationList;

    public function __construct(
        private array $input
    )
    {
        $this->validator = Validation::createValidator();
    }

    protected abstract function getConstrains(): Collection;

    public function validate(): static
    {
        $this->validationList = $this->validator->validate($this->input, $this->getConstrains(), new GroupSequence(['Default', 'custom']));
        return $this;
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->validationList as $list) {
            $errors[$list->getPropertyPath()] = $list->getMessage();
        }
        return $errors;
    }

    public function getErrorsForResponse(): array
    {
        $errors = $this->getErrors();
        if (empty($errors))
            return [];

        return [
            'message' => 'Validation failed.',
            'validation' => $errors
        ];
    }
}