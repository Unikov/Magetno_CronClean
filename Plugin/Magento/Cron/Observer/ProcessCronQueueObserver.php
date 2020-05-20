<?php

namespace Unikov\CleanCron\Plugin\Magento\Cron\Observer;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ProcessCronQueueObserver
{
    /**
     * @var ResourceConnection
     */
    protected $connection;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    private $scheduleFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;


    public function __construct(
        ResourceConnection $connection,
        LoggerInterface $logger,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->scheduleFactory = $scheduleFactory;
    }



    public function beforeExecute(
        \Magento\Cron\Observer\ProcessCronQueueObserver $subject
    ) {
        $connection = $this->connection->getConnection();
        $sql = "DELETE FROM cron_schedule WHERE  scheduled_at < Date_sub(Now(), interval 24 hour);";

        try {
            $connection->query($sql);
            $this->logger->info('Database table cron_schedule cleaned');
        } catch (\Zend_Db_Statement_Exception $exception) {
            $this->logger->critical(sprintf('Cron cleanup error: %s', $exception->getMessage()));
        }

        $runningLifetimeInMinutes = 180;

        $runningSchedules = $this->scheduleFactory->create()->getCollection()->addFieldToFilter(
            'status',
            \Magento\Cron\Model\Schedule::STATUS_RUNNING
        );

        $runningTimeLimit = $this->dateTime->gmtTimestamp() - $runningLifetimeInMinutes * ProcessCronQueueObserver::SECONDS_IN_MINUTE;
        foreach($runningSchedules as $schedule) {
            if (strtotime($schedule->getExecutedAt()) < $runningTimeLimit) {
                $schedule->setMessages(__('Schedule not finished after %3 minutes.', $runningLifetimeInMinutes));
                $schedule->setStatus(\Magento\Cron\Model\Schedule::STATUS_ERROR);
                $schedule->save();
            }
        }

        //Your plugin code
        return [];
    }
}

