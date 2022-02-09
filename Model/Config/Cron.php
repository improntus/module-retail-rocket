<?php
namespace Improntus\RetailRocket\Model\Config;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class Cron
 *
 * @version 1.0.11
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Model\Config
 */
class Cron extends \Magento\Framework\App\Config\Value
{
    /**
     * @return $this|\Magento\Framework\App\Config\Value
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function beforeSave()
    {
        $value     = $this->getValue();
        $validator = \Zend_Validate::is(
            $value,
            'Regex',
            ['pattern' => '/^[0-9,\-\?\/\*\ ]+$/']
        );

        if (!$validator) {
            $message = __(
                'Please correct Cron Expression: "%1".',
                $value
            );
            throw new LocalizedException($message);
        }

        return $this;
    }
}
