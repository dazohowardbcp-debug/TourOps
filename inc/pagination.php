<?php
// Pagination helper utilities

function pagination_allowed_sizes(): array {
    return [10, 15, 30, 50];
}

function pagination_get_page_and_size(int $defaultPageSize = 15): array {
    $allowed = pagination_allowed_sizes();
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : $defaultPageSize;
    if (!in_array($perPage, $allowed, true)) {
        $perPage = $defaultPageSize;
    }
    $offset = ($page - 1) * $perPage;
    return [$page, $perPage, $offset];
}

function pagination_build_url(int $page, array $extra = []): string {
    $query = $_GET ?? [];
    $query['page'] = $page;
    foreach ($extra as $k => $v) { $query[$k] = $v; }
    return '?' . http_build_query($query);
}

function pagination_render_controls(int $page, int $totalPages, array $extra = [], bool $center = true): string {
    if ($totalPages <= 1) return '';
    $prevUrl = pagination_build_url(max(1, $page - 1), $extra);
    $nextUrl = pagination_build_url(min($totalPages, $page + 1), $extra);
    $justify = $center ? 'justify-content-center' : 'justify-content-between';
    $html = '<div class="d-flex ' . $justify . ' gap-2 mt-3 mb-2">';
    if ($page > 1) {
        $html .= '<a href="' . htmlspecialchars($prevUrl) . '" class="btn btn-outline-secondary btn-sm rounded-pill px-4">Previous</a>';
    }
    $html .= '<span class="align-self-center small text-muted">Page ' . $page . ' of ' . $totalPages . '</span>';
    if ($page < $totalPages) {
        $html .= '<a href="' . htmlspecialchars($nextUrl) . '" class="btn btn-outline-primary btn-sm rounded-pill px-4">Next</a>';
    }
    $html .= '</div>';
    return $html;
}

function pagination_render_inline_row(int $colspan, int $page, int $totalPages, array $extra = []): string {
    if ($totalPages <= 1) return '';
    $prevUrl = pagination_build_url(max(1, $page - 1), $extra);
    $nextUrl = pagination_build_url(min($totalPages, $page + 1), $extra);
    $html = '<tr><td colspan="' . $colspan . '" class="text-center py-3">';
    if ($page > 1) {
        $html .= '<a href="' . htmlspecialchars($prevUrl) . '" class="btn btn-outline-secondary btn-sm rounded-pill px-4 me-2">Previous</a>';
    }
    $html .= '<span class="align-middle small text-muted">Page ' . $page . ' of ' . $totalPages . '</span>';
    if ($page < $totalPages) {
        $html .= '<a href="' . htmlspecialchars($nextUrl) . '" class="btn btn-outline-primary btn-sm rounded-pill px-4 ms-2">Next</a>';
    }
    $html .= '</td></tr>';
    return $html;
}
