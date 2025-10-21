<?php

declare(strict_types=1);

namespace OCA\DocumentControlTags\Service;

use OCP\Dashboard\Model\WidgetItem;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\SystemTag\ISystemTagManager;

class TagService
{
    private ISystemTagManager $tagManager;
    private IDBConnection $db;
    private IURLGenerator $urlGen;

    public function __construct(
        ISystemTagManager $tagManager,
        IDBConnection $db,
        IURLGenerator $urlGen,
    ) {
        $this->tagManager = $tagManager;
        $this->db = $db;
        $this->urlGen = $urlGen;
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
}
