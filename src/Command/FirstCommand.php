<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-email',
    description: 'Envoie un email avec Symfony Mailer.',
)]
class FirstCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('expediteur@example.com')
            ->to('benmoussamohsen@gmail.com')
            ->subject('Test d\'envoi d\'email')
            ->text('Ceci est un email envoyé depuis une commande Symfony.')
            ->html('<p><strong>Email envoyé via Symfony Mailer !</strong></p>');

        try {
            $this->mailer->send($email);
            $output->writeln('<info>Email envoyé avec succès !</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur lors de l\'envoi : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
