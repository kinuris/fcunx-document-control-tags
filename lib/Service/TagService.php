<?php

declare(strict_types=1);

namespace OCA\DocumentControlTags\Service;

use DateTime;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use Psr\Log\LoggerInterface;

class TagService
{
    private ISystemTagManager $tagManager;
    private IDBConnection $db;
    private IURLGenerator $urlGen;
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct(
        ISystemTagManager $tagManager,
        IDBConnection $db,
        IURLGenerator $urlGen,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->tagManager = $tagManager;
        $this->db = $db;
        $this->urlGen = $urlGen;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getWidgetItems(): array
    {
        $tags = ['Approved Document', 'Rejected Document', 'Requires University Document Controller Approval'];
        $approvedPath = $this->urlGen->imagePath('documentcontroltags', 'approved.png');
        $rejectedPath = $this->urlGen->imagePath('documentcontroltags', 'rejected.png');
        $pendingPath = $this->urlGen->imagePath('documentcontroltags', 'pending.png');

        $paths = [$approvedPath, $rejectedPath, $pendingPath];

        $result = [];
        $iter = 0;
        foreach ($tags as $tagName) {
            $count = $this->getFileCountOfTag($tagName);
            $id = $this->getTagId($tagName);
            $url = $this->urlGen->getAbsoluteURL('/apps/files/tags/' . $id);

            $result[] = new WidgetItem(
                $tagName,
                (string) $count,
                $url,
                $paths[$iter]
            );

            $iter++;
        }

        return $result;
    }

    public function getTagId(string $tagName): ?string
    {
        $existing = $this->tagManager->getAllTags();
        foreach ($existing as $tag) {
            if ($tag->getName() === $tagName) {
                return (string) $tag->getId();
            }
        }

        return null;
    }

    public function getFileCountOfTag(string $tagName): int
    {
        $existing = $this->tagManager->getAllTags();
        $exists = false;

        foreach ($existing as $tag) {
            if ($tag->getName() === $tagName) {
                $exists = true;
            }
        }

        if (!$exists) {
            return 0;
        }

        $tagHandle = $this->tagManager->getTag($tagName, true, true);

        $qb = $this->db->getQueryBuilder();
        $qb = $qb->select('*')
            ->from('systemtag_object_mapping')
            ->where(
                $qb->expr()
                    ->eq(
                        'systemtagid',
                        $qb->createNamedParameter($tagHandle->getId())
                    )
            );

        $result = $qb->executeQuery();
        $records = $result->fetchAll();
        
        $count = 0;
        foreach ($records as $record) {
            $node = $this->rootFolder->getFirstNodeById((int) $record['objectid']);

            if ($node !== null) 
                $count++;
        }

        return $count;
    }

    public function getArchivedTodayCount(): int
    {
        $userId = $this->userSession->getUser()->getUID();
        $rootFolder = $this->rootFolder->getUserFolder($userId);

        try {
            $targetNode = $rootFolder->get('Archived Documents');
        } catch (\OCP\Files\NotFoundException $e) {
            return 0;
        }

        if ($targetNode instanceof Folder) {
            $listing = $targetNode->getDirectoryListing();
            $count = 0;

            foreach ($listing as $node) {
                $path = $node->getPath();
                $pattern = '/\(Archived@([0-9]{2}-[0-9]{2}-[0-9]{2})\)/';

                if (preg_match($pattern, $path, $matches)) {
                    $date = $matches[1];
                }

                $format = 'd-m-y'; // 'y' for two-digit year, 'm' for month, 'd' for day
                $dateTime = DateTime::createFromFormat($format, $date);

                if ($dateTime && $dateTime->format('y-m-d') === date('y-m-d')) {
                    $count++;
                }
            }

            return $count;
        }

        return 0;
    }

    public function getUploadedTodayCount(): int
    {
        $tagName = "Requires Approval";
        $existing = $this->tagManager->getAllTags();
        $exists = false;

        foreach ($existing as $tag) {
            if ($tag->getName() === $tagName) {
                $exists = true;
            }
        }

        if (!$exists) {
            return 0;
        }

        $tagHandle = $this->tagManager->getTag($tagName, true, true);

        $qb = $this->db->getQueryBuilder();
        $qb = $qb->select('*')
            ->from('systemtag_object_mapping')
            ->where(
                $qb->expr()
                    ->eq(
                        'systemtagid',
                        $qb->createNamedParameter($tagHandle->getId())
                    )
            );

        $result = $qb->executeQuery();
        $records = $result->fetchAll();

        $count = 0;
        foreach ($records as $record) {
            $node = $this->rootFolder->getFirstNodeById((int) $record['objectid']);

            if ($node instanceof Folder) {
                $nodes = $node->getDirectoryListing();
                foreach ($nodes as $subNode) {
                    $uploadTime = $subNode->getUploadTime();
                    $uploadDate = date('y-m-d', $uploadTime);
                    $currentDate = date('y-m-d');

                    if ($uploadDate === $currentDate) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public function archiveFiles5Y()
    {
        $tagName = 'Archive: 5Y';
        $existing = $this->tagManager->getAllTags();
        $exists = false;

        foreach ($existing as $tag) {
            if ($tag->getName() === $tagName) {
                $exists = true;
            }
        }

        if (!$exists) {
            return 0;
        }

        $tagHandle = $this->tagManager->getTag($tagName, true, true);

        $qb = $this->db->getQueryBuilder();
        $qb = $qb->select('*')
            ->from('systemtag_object_mapping')
            ->where(
                $qb->expr()
                    ->eq(
                        'systemtagid',
                        $qb->createNamedParameter($tagHandle->getId())
                    )
            );

        $result = $qb->executeQuery();
        $records = $result->fetchAll();

        $filesToArchive = [];

        // NOTE: Skip the archiving process if document controller is not set
        $documentController = $this->config->getSystemValueString('documentcontroltags.univ_doc_controller');
        if ($documentController === '') {
            $this->logger->warning('University Document Controller is not set. Skipping archiving process. Please set it using `occ config:system:set \'documentcontroltags.univ_doc_controller\' --value="<username>"`');

            return;
        }

        foreach ($records as $record) {
            // TODO: Null when run as Background Job
            $rootFolder = $this->rootFolder->getUserFolder($documentController);
            $node = $rootFolder->getFirstNodeById((int) $record['objectid']);

            if (str_contains($node->getParent()->getPath(), '/Archived Documents') || str_contains($node->getParent()->getPath(), '/files_trashbin')) {
                continue;
            }

            if ($node->getUploadTime() >= strtotime('-5 years')) {
                continue;
            }

            // Fallback to modification time if upload time is not set
            if ($node->getUploadTime() === 0 && $node->getMTime() >= strtotime('-5 years')) {
                continue;
            }

            $filesToArchive[] = $node;
        }

        // TODO: No user when run as background job
        // $userId = $this->userSession->getUser()->getUID();
        $rootFolder = $this->rootFolder->getUserFolder($documentController);

        foreach ($filesToArchive as $file) {
            $archiveFolder = null;
            try {
                $archiveFolder = $rootFolder->get('Archived Documents');
            } catch (\OCP\Files\NotFoundException $e) {
                $archiveFolder = $rootFolder->newFolder('/Archived Documents');
            }

            $archiveFolder = $rootFolder->get('/Archived Documents');

            $currentDate = date('d-m-y');
            $originalName = $file->getName();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $archivedName = $baseName . ' (Archived@' . $currentDate . ')' . '.' . $extension;

            // Move file to Archive folder with new name
            $file->move($archiveFolder->getPath() . '/' . $archivedName);
        }
    }
}
