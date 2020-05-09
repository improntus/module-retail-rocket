<?php
namespace Improntus\RetailRocket\Controller\Adminhtml\Generator;

use Exception;
use Improntus\RetailRocket\Cron\Feed;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Generate
 *
 * @version 1.0.2
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Apptrian\FacebookCatalog\Controller\Adminhtml\Generator
 */
class Generate extends Action
{
    /**
     * @var Feed
     */
    protected $_feed;

    /**
     * Generate constructor.
     * @param Context $context
     * @param Feed $feed
     */
    public function __construct(
        Context $context,
        Feed $feed
    ) {
        $this->_feed = $feed;
        
        parent::__construct($context);
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        set_time_limit(18000);
        
        try {
            $this->_feed->generate();
            
            $this->messageManager->addSuccessMessage(
                __('Retail Rocket feed generation completed successfully.')
            );
        } catch (Exception $e) {
            $message = __('Retail Rocket feed generation failed.');
            $this->messageManager->addSuccessMessage($message);
            $this->messageManager->addSuccessMessage($e->getMessage());
        }
        
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            ['section' => 'retailrocket']
        );
    }
}