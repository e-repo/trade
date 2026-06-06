<?php

declare(strict_types=1);

namespace Trade\UI\Console;

use Carbon\Carbon;
use CoreKit\Application\Bus\CommandBusInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Trade\Application\Lot\Command\CloseDueLots;

final class CalculateWinnersCommand extends Command
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
            ->setName('trade:calculate-winners')
            ->setDescription('Close expired lots and determine winners');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = Carbon::now()->toDateTimeImmutable();

        $result = $this->commandBus->dispatch(
            new CloseDueLots\Command(now: $now)
        );

        if ($result->totalProcessed === 0) {
            $io->info('No lots to close');
            return Command::SUCCESS;
        }

        if ($result->failed > 0) {
            $io->warning(sprintf(
                'Processed %d lot(s): %d closed, %d failed',
                $result->totalProcessed,
                $result->successfullyClosed,
                $result->failed
            ));
            return Command::FAILURE;
        }

        $io->success(sprintf('Successfully closed %d lot(s)', $result->successfullyClosed));

        return Command::SUCCESS;
    }
}
