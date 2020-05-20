<?php declare(strict_types=1);


namespace Unikov\CleanCron\Plugin\Magento\Cron\Observer;


class ProcessCronQueueObserver
{

    public function beforeExecute(
        \Magento\Cron\Observer\ProcessCronQueueObserver $subject
    ) {
        //Your plugin code
        return [];
    }
}

