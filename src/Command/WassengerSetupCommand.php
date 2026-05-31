<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'wassenger:setup', description: 'Configura las variables de entorno para Wassenger')]
class WassengerSetupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apiKey = $io->ask('Introduce tu Wassenger API Key');
        $deviceId = $io->ask('Introduce tu Wassenger Device ID');

        $io->success('Configuración lista. Añade esto a tu .env:');
        $io->writeln("WASSENGER_API_KEY=$apiKey");
        $io->writeln("WASSENGER_DEVICE_ID=$deviceId");

        return Command::SUCCESS;
    }
}
