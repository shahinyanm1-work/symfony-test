<?php

declare(strict_types=1);

namespace App\Command;

use App\Interface\SearchServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-manticore',
    description: 'Seed Manticore search index with existing orders'
)]
class SeedManticoreCommand extends Command
{
    public function __construct(
        private readonly SearchServiceInterface $searchService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Seeding Manticore Search Index');

        try {
            $io->info('Starting index rebuild...');

            $success = $this->searchService->rebuildIndex();

            if ($success) {
                $io->success('Manticore index has been successfully rebuilt!');
                return Command::SUCCESS;
            } else {
                $io->error('Failed to rebuild Manticore index');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Error during index rebuild: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
