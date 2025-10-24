<?php

namespace OCA\DocumentControlTags\BackgroundJobs;

use OCA\DocumentControlTags\Service\TagService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use Psr\Log\LoggerInterface;

class DocumentArchiver extends TimedJob
{
    private LoggerInterface $logger;
    private TagService $tagService;
    private INotifier $notifier;

    public function __construct(
        ITimeFactory $time,
        LoggerInterface $logger,
        TagService $tagService,
    ) {
        parent::__construct($time);

        $this->logger = $logger;
        $this->tagService = $tagService;

        // Run every 8 hours
        $this->setInterval(60 * 5);
        $this->setTimeSensitivity(IJob::TIME_SENSITIVE);
    }

    protected function run($argument)
    {
        $this->logger->info('DocumentArchiver archiveFiles5Y() Started', ['app' => 'documentcontroltags']);
        $this->tagService->archiveFiles5Y();
        $this->logger->info('DocumentArchiver archiveFiles5Y() Completed', ['app' => 'documentcontroltags']);
    }
}
