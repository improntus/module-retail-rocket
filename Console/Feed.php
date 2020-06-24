<?php
namespace Improntus\RetailRocket\Console;

use Exception;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
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
     * @var State
     */
    protected $_appState;

    /**
     * Feed constructor.
     * @param \Improntus\RetailRocket\Cron\Feed $feed
     * @param State $appState
     * @param null $name
     */
    public function __construct(
        \Improntus\RetailRocket\Cron\Feed $feed,
        State $appState,
        $name = null
    )
    {
        $this->_feed = $feed;
        $this->_appState = $appState;

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
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting feed generation");
        $this->_appState->setAreaCode('frontend');

        try{
            $this->_feed->execute();
        }
        catch (Exception $e){
            $output->writeln($e->getMessage());
        }

        $output->writeln("End feed generation");
    }
}