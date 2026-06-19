<?php

namespace App\Controllers\Traits;

/**
 * Trait untuk menangani pagination + sort yang berulang di banyak controller.
 *
 * Mengharapkan `$this->request` tersedia (dari BaseController).
 */
trait PaginationTrait
{
    /**
     * Parse pagination params dari request: page, limit, offset, sort, dir.
     *
     * @param array  $allowedSort  Daftar kolom yang boleh di-sort
     * @param string $defaultSort  Kolom sort default
     * @param string $defaultDir   Arah sort default ('ASC' atau 'DESC')
     * @param int    $limit        Jumlah item per halaman
     * @return array{page: int, limit: int, offset: int, sort: string, dir: string}
     */
    protected function parsePagination(
        array $allowedSort,
        string $defaultSort = 'id',
        string $defaultDir = 'ASC',
        int $limit = 20
    ): array {
        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $sort = $this->request->getGet('sort') ?? $defaultSort;
        $dir  = strtoupper($this->request->getGet('dir') ?? $defaultDir);
        if (!in_array($dir, ['ASC', 'DESC'])) $dir = $defaultDir;
        if (!in_array($sort, $allowedSort)) $sort = $defaultSort;

        return compact('page', 'limit', 'offset', 'sort', 'dir');
    }

    /**
     * Terapkan pagination N+1: fetch limit+1, pop extra, hitung hasNext.
     *
     * @param array $items  Hasil query dengan limit+1
     * @param int   $limit  Limit yang digunakan
     * @return array{items: array, has_next: bool}
     */
    protected function paginateResults(array $items, int $limit): array
    {
        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }

        return ['items' => $items, 'has_next' => $hasNext];
    }
}
