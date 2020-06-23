<?php
namespace Improntus\RetailRocket\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Feed
 * @package Improntus\RetailRocket\Console
 */
class Feed extends Command
{
    /**
     * @var \Improntus\RetailRocket\Cron\Feed
     */
    protected $_feed;

    /**
     * Feed constructor.
     * @param \Improntus\RetailRocket\Cron\Feed $feed
     * @param null $name
     */
    public function __construct(
        \Improntus\RetailRocket\Cron\Feed $feed,
        $name = null
    )
    {
        $this->_feed = $feed;

        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('retailrocket:feed:generate');
        $this->setDescription('Generate all xml feeds');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting feed generation");

        $this->_feed->execute();

        $output->writeln("End feed generation");
    }
}