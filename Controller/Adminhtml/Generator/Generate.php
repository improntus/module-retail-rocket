<?php
namespace Improntus\RetailRocket\Controller\Adminhtml\Generator;

use Exception;
use Improntus\RetailRocket\Cron\Feed;
use Improntus\RetailRocket\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Generate
 *
 * @version 1.0.5
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
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * Generate constructor.
     * @param Context $context
     * @param Feed $feed
     * @param Data $retailRocketHelper
     */
    public function __construct(
        Context $context,
        Feed $feed,
        Data $retailRocketHelper
    ) {
        $this->_feed = $feed;
        $this->_retailRocketHelper = $retailRocketHelper;

        parent::__construct($context);
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        set_time_limit(18000);
        
        try {
            $feedGenerated = false;

            if($this->_retailRocketHelper->isSingleXmlFeedEnabled())
            {
                $this->_feed->generateByWebsite();
                $feedGenerated = true;
            }

            if($this->_retailRocketHelper->isStockIdEnabled())
            {
                $this->_feed->generateWithStockId();
                $feedGenerated = true;
            }

            if($feedGenerated)
            {
                $this->messageManager->addSuccessMessage(
                    __('Retail Rocket feed generation completed successfully.')
                );
            }
            else{
                $this->messageManager->addWarningMessage(
                    __('Retail Rocket feed xml is not enabled. Please check your configuration before continue.')
                );
            }
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