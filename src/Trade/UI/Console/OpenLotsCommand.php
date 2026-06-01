<?php

declare(strict_types=1);

namespace Trade\UI\Console;

use CoreKit\Application\Bus\CommandBusInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Trade\Application\Lot\Command\OpenDueLots;

final class OpenLotsCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('trade:open-lots')
            ->setDescription('Open lots that have reached their opening time');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new DateTimeImmutable();

        $result = $this->commandBus->dispatch(
            new OpenDueLots\Command(now: $now)
        );

        if ($result->totalProcessed === 0) {
            $io->info('No lots to open');
            return Command::SUCCESS;
        }

        if ($result->failed > 0) {
            $io->warning(sprintf(
                'Processed %d lot(s): %d opened, %d failed',
                $result->totalProcessed,
                $result->successfullyOpened,
                $result->failed
            ));
            return Command::FAILURE;
        }

        $io->success(sprintf('Successfully opened %d lot(s)', $result->successfullyOpened));

        return Command::SUCCESS;
    }
}
