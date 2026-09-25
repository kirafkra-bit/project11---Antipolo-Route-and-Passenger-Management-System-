<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = get_pdo();
$method = request_method();
$input = request_input();
$user = require_login();

if ($method !== 'GET') {
    require_csrf($input);
}

function complaint_select_sql(): string
{
    return 'SELECT c.id, c.passenger_id, c.trip_id, c.category, c.subject, c.description,
                   c.status, c.admin_response, c.handled_by, c.created_at, c.updated_at,
                   p.name AS passenger_name, u.username AS passenger_username,
                   a.username AS handled_by_username
            FROM complaints c
            INNER JOIN passengers p ON p.passengers_id = c.passenger_id
            INNER JOIN users u ON u.id = p.users_id
            LEFT JOIN users a ON a.id = c.handled_by';
}

function fetch_complaint(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(complaint_select_sql() . ' WHERE c.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

try {
    if ($method === 'GET') {
        $sql = complaint_select_sql() . ' WHERE 1=1';
        $params = [];
        if ($user['role'] === 'passenger') {
            if (empty($user['passenger_id'])) {
                json_error('Passenger profile was not found for this account.', 404);
            }
            $sql .= ' AND c.passenger_id = :passenger_id';
            $params['passenger_id'] = (int) $user['passenger_id'];
        } else {
            require_role(['admin']);
            $status = trim_string((string) query_param('status', ''));
            $search = trim_string((string) query_param('q', ''));
            if ($status !== '') {
                $sql .= ' AND c.status = :status';
                $params['status'] = $status;
            }
            if ($search !== '') {
                $sql .= ' AND (p.name LIKE :q OR c.subject LIKE :q2 OR c.category LIKE :q3)';
                $like = like_search($search);
                $params['q'] = $like;
                $params['q2'] = $like;
                $params['q3'] = $like;
            }
        }
        $sql .= ' ORDER BY c.created_at DESC, c.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_success($stmt->fetchAll());
    }

    if ($method === 'POST') {
        require_role(['passenger']);
        require_fields($input, ['category', 'subject', 'description']);
        if (empty($user['passenger_id'])) {
            json_error('Passenger profile was not found for this account.', 404);
        }
        $allowed = ['incorrect_fare', 'driver_behavior', 'reckless_driving', 'lost_item', 'unsafe_vehicle', 'other'];
        if (!in_array((string) $input['category'], $allowed, true)) {
            json_error('Please choose a valid concern type.', 422);
        }
        $tripId = null;
        if (isset($input['trip_id']) && trim_string($input['trip_id']) !== '') {
            $tripId = to_int($input['trip_id'], 'trip_id');
            if (!record_exists($pdo, 'trips', 'id', $tripId)
                || !count_where($pdo, 'SELECT COUNT(*) FROM trips WHERE id = :id AND passengers_id = :passenger_id', [
                    'id' => $tripId,
                    'passenger_id' => (int) $user['passenger_id'],
                ])) {
                json_error('That trip does not belong to your passenger account.', 422);
            }
        }
        $stmt = $pdo->prepare(
            'INSERT INTO complaints (passenger_id, trip_id, category, subject, description)
             VALUES (:passenger_id, :trip_id, :category, :subject, :description)'
        );
        $stmt->execute([
            'passenger_id' => (int) $user['passenger_id'],
            'trip_id' => $tripId,
            'category' => trim_string($input['category']),
            'subject' => trim_string($input['subject']),
            'description' => trim_string($input['description']),
        ]);
        json_success(fetch_complaint($pdo, (int) $pdo->lastInsertId()), 201);
    }

    if ($method === 'PUT') {
        require_role(['admin']);
        require_fields($input, ['id', 'status']);
        $id = to_int($input['id'], 'id');
        $statuses = ['submitted', 'in_review', 'resolved', 'rejected'];
        if (!in_array((string) $input['status'], $statuses, true)) {
            json_error('Please choose a valid complaint status.', 422);
        }
        if (!fetch_complaint($pdo, $id)) {
            json_error('Complaint not found.', 404);
        }
        $stmt = $pdo->prepare(
            'UPDATE complaints
             SET status = :status, admin_response = :admin_response, handled_by = :handled_by
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => trim_string($input['status']),
            'admin_response' => trim_string($input['admin_response'] ?? '') ?: null,
            'handled_by' => (int) $user['id'],
            'id' => $id,
        ]);
        json_success(fetch_complaint($pdo, $id));
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to save the complaint.');
}
