<?php

namespace App\Command;

use App\Service\RedisEventListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisStartListeningCommand extends Command
{
    private RedisEventListener $redisEventListener;

    public function __construct(RedisEventListener $redisEventListener)
    {
        parent::__construct();
        $this->redisEventListener = $redisEventListener;
    }

    protected function configure(): void
    {
        $this->setName('redis:start-listening')
            ->setDescription('Начать прослушивание событий Redis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Запуск прослушивания Redis...');
        try {
            $this->redisEventListener->listenToHashEvents();
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
