<?php

namespace App\Service;

use Exception;
use Predis\Client;

class RedisService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
        ]);
    }

    /**
     * @throws Exception
     */
    public function setHash(string $key, array $data, int $ttl = null): void
    {
        try {
            foreach ($data as $field => $value) {
                $this->client->hset($key, $field, $value);
            }

            if ($ttl !== null) {
                $this->client->expire($key, $ttl);
            }

            $this->client->publish('hash_written', json_encode(['key' => $key, 'data' => $data]));
        } catch (\Exception $e) {
            throw new Exception("Ошибка при записи хэша в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getHash(string $key): array
    {
        try {
            return $this->client->hgetall($key);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при чтении хэша из Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updateHashField(string $key, string $field, $value): void
    {
        try {
            $this->client->hset($key, $field, $value);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при обновлении поля в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function exists(string $key): int
    {
        try {
            return $this->client->exists($key);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при проверке ключа в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function subscribeToChannels(array $channels, callable $callback): void
    {
        try {
            $subscriber = $this->client->pubSubLoop();

            foreach ($channels as $channel) {
                $subscriber->subscribe($channel);
            }

            foreach ($subscriber as $message) {
                if ($message->kind === 'message') {
                    $callback($message);
                }
            }
        } catch (\Exception $e) {
            throw new Exception("Ошибка при подписке на каналы Redis: " . $e->getMessage());
        }
    }
}
