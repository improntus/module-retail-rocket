<?php
namespace Improntus\RetailRocket\Model\Config;

use Magento\Framework\Exception\LocalizedException;
use Laminas\Validator\Regex;

/**
 * Class Cron
 *
 * @Version 1.0.18
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Solution Partner
 * @copyright Copyright (c) 2024 Improntus
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

        $validator = new Regex(['pattern' => '/^[0-9,\-\?\/\*\ ]+$/']);
        $validator->isValid($value); // returns true

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
