<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class LegalController extends BaseController
{
    public function index(?string $p = null): void
    {
        $this->terminos($p);
    }

    public function terminos(?string $p = null): void
    {
        $restaurante = $this->resolveRestaurante();
        $terms = (new LegalContentService())->getTerms($restaurante);
        $pageTitle = $terms['title'];

        $this->render('public/legal/terminos', compact('terms', 'restaurante', 'pageTitle'));
    }

    private function resolveRestaurante(): ?array
    {
        $slug = trim((string)$this->get('slug', ''));
        if ($slug === '') {
            return null;
        }

        try {
            return (new RestauranteModel())->getBySlug($slug) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
