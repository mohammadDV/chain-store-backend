<?php

namespace Application\Api\Page\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Page\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function __construct(protected PageService $pageService)
    {
        //
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $this->pageService->getNavigation(),
        ], Response::HTTP_OK);
    }

    public function show(string $slug): JsonResponse
    {
        $page = $this->pageService->getBySlug($slug);

        if ($page === null) {
            return response()->json([
                'status' => 0,
                'message' => __('site.page_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 1,
            'data' => $page,
        ], Response::HTTP_OK);
    }
}
