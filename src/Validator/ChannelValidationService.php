<?php

namespace App\Validator;

use App\DTO\Telegram\ChannelDTO;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChannelValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    )
    {
    }

    public function validateChannel(ChannelDTO $channel): array
    {
        $errors = $this->validator->validate($channel);

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        return $errorMessages;
    }
}