<?php

namespace App\Command\Tag;

use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class TagCreateCommand extends Command
{
    protected static $defaultName = 'app:tag:create';
    protected static $defaultDescription = 'Add a short description for your command';
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::OPTIONAL, 'Enter the Tag Title here')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getArgument('title');

        if ($title) {
            $io->note(sprintf('You passed an argument: %s', $title));
        }else{
            $titleQ = new Question('Enter the Tag Name: ', 'Demo Tag');
            $title = $io->askQuestion( $titleQ);
        }
        $tag = new Tag();
        $tag->setTitle($title);

        $disableQ = new ConfirmationQuestion('Do you want to DISABLE the Tag', false);
        $tag->setDisabled($io->askQuestion($disableQ));

        $this->em->persist($tag);
        $this->em->flush();


        $io->success(sprintf('The Tag %s was added sucessfully',$title));

        return Command::SUCCESS;
    }
}
