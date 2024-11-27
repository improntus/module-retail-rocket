<?php
namespace Improntus\RetailRocket\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;

/**
 * Class Links
 *
 * @version 1.0.17
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block\Adminhtml
 */
class CronExecution extends Field
{
    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $_schedule;

    /**
     * CronExecution constructor.
     *
     * @param Context  $context
     * @param CollectionFactory $schedule
     */
    public function __construct(
        Context $context,
        CollectionFactory $schedule
    ) {
        $this->_schedule = $schedule;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        $element = null;

        $cronSchedule = $this->_schedule->create();
        $cronSchedule->addFieldToFilter('job_code',['eq'=>'retailrocket_generate_feed']);
        $cronSchedule->getSelect()->limit(10);

        $links = [];

        $html = '<div>';
        $html .= '<table>';

        $html .= "<thead><tr style='font-weight: bold;'>";
        $html .= "<td>" . __('Status') . "</td>";
        $html .= "<td>" . __('Created at') . "</td>";
        $html .= "<td>" . __('Scheduled At') . "</td>";
        $html .= "<td>" . __('Executed At') . "</td>";
        $html .= "<td>" . __('Finished At') . "</td>";
        $html .= "<td>" . __('Messages') . "</td>";
        $html .= "</tr></thead>";
        $html .= "<tbody>";

        foreach ($cronSchedule as $_schedules)
        {
            $statusColor = match ($_schedules->getStatus()) {
                Schedule::STATUS_SUCCESS => "style='color:#008000'",
                Schedule::STATUS_RUNNING => "style='color:#ff9008'",
                Schedule::STATUS_ERROR => "style='color:#ff0000'",
                Schedule::STATUS_MISSED => "style='color:#3f00ff'",
                Schedule::STATUS_PENDING => "style='color:#000000'",
                default => '',
            };

            $html .= "<tr>";
            $html .= "<td $statusColor>{$_schedules->getStatus()}</td>";
            $html .= "<td>{$_schedules->getCreatedAt()}</td>";
            $html .= "<td>{$_schedules->getScheduledAt()}</td>";
            $html .= "<td>{$_schedules->getExecutedAt()}</td>";
            $html .= "<td>{$_schedules->getFinishedAt()}</td>";
            $html .= "<td>{$_schedules->getMessages()}</td>";
            $html .= "</tr>";
        }

        $html .= '</tbody></table>';

        return $html . '</div>';
    }
}
