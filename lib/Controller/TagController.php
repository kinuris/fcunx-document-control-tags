<?php

declare(strict_types=1);

namespace OCA\DocumentControlTags\Controller;

use OCA\DocumentControlTags\Service\TagService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\Route;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class TagController extends Controller
{
    private $userId;
    private TagService $tagService;

    public function __construct(
        string $appName,
        IRequest $request,
        TagService $tagService,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->tagService = $tagService;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'GET', url: '/tag/{tagName}')]
    public function getFileByTag($tagName): DataResponse
    {
        $count = $this->tagService->getFileCountOfTag(urldecode($tagName));

        $data = [
            'status' => 'success',
            'count' => $count,
        ];

        return new DataResponse($data);
    }
}
