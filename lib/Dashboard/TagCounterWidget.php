<?php

declare(strict_types=1);

namespace OCA\DocumentControlTags\Dashboard;

use OCA\DocumentControlTags\AppInfo\Application;
use OCA\DocumentControlTags\Service\TagService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IAPIWidget;

use OCP\Util;

class TagCounterWidget implements IAPIWidget
{
    private TagService $tagService;
    private IInitialState $initialStateService;
    private ?string $userId;

    public function __construct(
        TagService $tagService,
        IInitialState $initialStateService,
        ?string $userId
    ) {
        $this->tagService = $tagService;
        $this->initialStateService = $initialStateService;
        $this->userId = $userId;
    }

    public function getId(): string
    {
        return 'documentcontroltags-tag-counter-widget';
    }

    public function getTitle(): string
    {
        return 'Document Control';
    }

    public function getOrder(): int
    {
        return 10;
    }

    public function getIconClass(): string
    {
        return 'icon-folder';
    }

    public function getUrl(): ?string
    {
        return null;
    }

    public function load(): void
    {
        if ($this->userId !== null) {
            $items = $this->getItems($this->userId);
            $this->initialStateService->provideInitialState(
                'dashboard',
                $items
            );
        }

        Util::addScript(Application::APP_ID, Application::APP_ID . '-tagCounterWidget');
        Util::addStyle(Application::APP_ID, 'dashboard');
    }

    public function getItems(string $userId, ?string $since = null, int $limit = 7): array
    {
        return $this->tagService->getWidgetItems();
    }
}
