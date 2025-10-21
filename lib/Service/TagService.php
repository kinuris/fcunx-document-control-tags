<?php

declare(strict_types=1);

namespace OCA\DocumentControlTags\Service;

use OCP\Dashboard\Model\WidgetItem;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;

class TagService
{
    private ISystemTagManager $tagManager;
    private IDBConnection $db;
    private IURLGenerator $urlGen;
    private IRootFolder $rootFolder;
    private IUserSession $userSession;

    public function __construct(
        ISystemTagManager $tagManager,
        IDBConnection $db,
        IURLGenerator $urlGen,
        IRootFolder $rootFolder,
        IUserSession $userSession
    ) {
        $this->tagManager = $tagManager;
        $this->db = $db;
        $this->urlGen = $urlGen;
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
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
            $result[] = new WidgetItem(
                $tagName,
                (string) $count,
                '',
                $paths[$iter]
            );

            $iter++;
        }

        return $result;
    }

    public function getFileCountOfTag(string $tagName): int
    {
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

        // TODO: Handle folders 

        return count($records);
    }

    public function getArchivedTodayCount(): int
    {
        return 2;
    }

    public function getUploadedTodayCount(): int
    {
        return 1;
    }

    public function archiveFiles5Y()
    {
        $tagName = 'Archive: 5Y';
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
        foreach ($records as $record) {
            $node = $this->rootFolder->getFirstNodeById((int) $record['objectid']);

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

        $userId = $this->userSession->getUser()->getUID();
        $rootFolder = $this->rootFolder->getUserFolder($userId);

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

        var_dump($filesToArchive);
        die;
    }
}
