<?php
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 9999;
    $offset = ($page - 1) * $limit;
    
    // Filter
    $where = [];
    $params = [];
    
    if (!empty($_GET['genre'])) {
        $where[] = "genre LIKE ?";
        $params[] = "%{$_GET['genre']}%";
    }
    
    if (!empty($_GET['year'])) {
        $where[] = "release_year = ?";
        $params[] = $_GET['year'];
    }
    
    if (!empty($_GET['search'])) {
        $where[] = "(title LIKE ? OR director LIKE ? OR synopsis LIKE ?)";
        $search = "%{$_GET['search']}%";
        $params = array_merge($params, [$search, $search, $search]);
    }
    
    // Sort
    $sortColumn = $_GET['sort'] ?? 'created_at';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC');
    
    $allowedSort = ['title', 'release_year', 'average_rating', 'created_at', 'total_reviews'];
    $sortColumn = in_array($sortColumn, $allowedSort) ? $sortColumn : 'created_at';
    $sortOrder = in_array($sortOrder, ['ASC', 'DESC']) ? $sortOrder : 'DESC';
    
    // Query
    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT * FROM movies $whereSql ORDER BY $sortColumn $sortOrder LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll();
    
    // Total count
    $countSql = "SELECT COUNT(*) as total FROM movies $whereSql";
    $stmt = $conn->prepare($countSql);
    $stmt->execute(array_slice($params, 0, -2));
    $total = $stmt->fetch()['total'];
    
    sendResponse(true, [
        'movies' => $movies,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    
} catch(PDOException $e) {
    sendResponse(false, null, 'Server error', 500);
}
?>