<?php

namespace App\Service;

use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\SettingsAbstract;

class MadelineProtoSettings extends SettingsAbstract
{
    public function __construct(
        private readonly string $apiId,
        private readonly string $apiHash
    )
    {
    }

    public function getApiId(): string
    {
        return $this->apiId;
    }

    public function getApiHash(): string
    {
        return $this->apiHash;
    }

    public function getSettings(): AppInfo
    {
        return (new AppInfo)
            ->setApiId($this->getApiId())
            ->setApiHash($this->getApiHash())
            ->setShowPrompt(false);
    }
}