<?php

namespace OCA\DocumentControlTags\Tests\Service;

use OCA\DocumentControlTags\Service\TagService;
use OCP\Files\IRootFolder;
use OCP\ITags;
use PHPUnit\Framework\TestCase;

class TagServiceTest extends TestCase
{
    private TagService $tagService;

    protected function setUp(): void
    {
        $tag = $this->createMock(ITags::class);
        $rootFolder = $this->createMock(IRootFolder::class);

        $this->tagService = new TagService($tag, $rootFolder);

        parent::setUp();
    }

    public function testGetFilesByTag(): void
    {
        $this->tagService->getFilesByTag('important', 'admin');
    }
}
