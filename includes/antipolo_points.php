<?php
/**
 * Transitional seed data and database loader for Antipolo map points.
 *
 * Run database/migrations/001_antipolo_points.sql, then the database becomes
 * the source of truth for names and coordinates.
 */

function antipolo_points_from_database(PDO $pdo): array
{
    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $tableCheck->execute(['table_name' => 'antipolo_points']);
    if ((int) $tableCheck->fetchColumn() === 0) {
        return antipolo_point_seed();
    }

    $stmt = $pdo->query(
        'SELECT id, point_key AS `key`, name, latitude AS lat, longitude AS lng
         FROM antipolo_points
         WHERE is_active = 1
         ORDER BY name ASC'
    );

    return $stmt->fetchAll();
}

function antipolo_point_seed(): array
{
    return [
    ['id' => 'simbahan', 'name' => 'Antipolo Simbahan (Antipolo Cathedral)', 'lat' => 14.5875, 'lng' => 121.1768],
    ['id' => 'robinsons_antipolo', 'name' => 'Robinsons Antipolo', 'lat' => 14.5849, 'lng' => 121.1762],
    ['id' => 'masinag', 'name' => 'Masinag', 'lat' => 14.6247, 'lng' => 121.1211],
    ['id' => 'sm_city_masinag', 'name' => 'SM City Masinag', 'lat' => 14.6248, 'lng' => 121.1214],
    ['id' => 'lrt_2_antipolo', 'name' => 'LRT-2 Antipolo Station', 'lat' => 14.6251, 'lng' => 121.1210],
    ['id' => 'cogeo', 'name' => 'Cogeo', 'lat' => 14.6157, 'lng' => 121.1350],
    ['id' => 'cogeo_gate_2', 'name' => 'Cogeo Gate 2', 'lat' => 14.6250, 'lng' => 121.1430],
    ['id' => 'padilla', 'name' => 'Padilla', 'lat' => 14.6170, 'lng' => 121.1470],
    ['id' => 'paenaan', 'name' => 'Paenaan', 'lat' => 14.6320, 'lng' => 121.1530],
    ['id' => 'antipolo_hills', 'name' => 'Antipolo Hills', 'lat' => 14.6300, 'lng' => 121.1600],
    ['id' => 'mayamot', 'name' => 'Mayamot', 'lat' => 14.6260, 'lng' => 121.1050],
    ['id' => 'francisville', 'name' => 'Francisville', 'lat' => 14.6530, 'lng' => 121.1300],
    ['id' => 'langhaya', 'name' => 'Langhaya', 'lat' => 14.6500, 'lng' => 121.1600],
    ['id' => 'sampaloc', 'name' => 'Sampaloc', 'lat' => 14.6260, 'lng' => 121.1760],
    ['id' => 'bayan', 'name' => 'Bayan', 'lat' => 14.5870, 'lng' => 121.1760],
    ['id' => 'san_jose', 'name' => 'San Jose', 'lat' => 14.5900, 'lng' => 121.1700],
    ['id' => 'mambugan', 'name' => 'Mambugan', 'lat' => 14.6370, 'lng' => 121.1200],
    ['id' => 'sumulong', 'name' => 'Sumulong Circle', 'lat' => 14.6002, 'lng' => 121.1750],
    ['id' => 'sumulong_highway', 'name' => 'Sumulong Highway', 'lat' => 14.6000, 'lng' => 121.1750],
    ['id' => 'cloud_9', 'name' => 'Cloud 9', 'lat' => 14.5900, 'lng' => 121.1400],
    ['id' => 'hinulugang_taktak', 'name' => 'Hinulugang Taktak', 'lat' => 14.5845, 'lng' => 121.1740],
    ['id' => 'pinto_art_museum', 'name' => 'Pinto Art Museum', 'lat' => 14.5780, 'lng' => 121.1760],
    ['id' => 'antipolo_junction', 'name' => 'Antipolo Junction', 'lat' => 14.5990, 'lng' => 121.1740],
    ['id' => 'cupang', 'name' => 'Cupang', 'lat' => 14.6030, 'lng' => 121.1000],
    ['id' => 'dalig', 'name' => 'Dalig', 'lat' => 14.5900, 'lng' => 121.1400],
    ['id' => 'delapaz', 'name' => 'Dela Paz', 'lat' => 14.6100, 'lng' => 121.1050],
    ['id' => 'inarawan', 'name' => 'Inarawan', 'lat' => 14.5550, 'lng' => 121.1900],
    ['id' => 'boso_boso', 'name' => 'Boso-Boso', 'lat' => 14.6100, 'lng' => 121.2400],
    ['id' => 'calawis', 'name' => 'Calawis', 'lat' => 14.5800, 'lng' => 121.2500],
    ['id' => 'pantay', 'name' => 'Pantay', 'lat' => 14.6300, 'lng' => 121.2000],
    ['id' => 'cubao', 'name' => 'Cubao', 'lat' => 14.6190, 'lng' => 121.0560],
    ['id' => 'marikina', 'name' => 'Marikina', 'lat' => 14.6500, 'lng' => 121.1020],
    ['id' => 'tanay', 'name' => 'Tanay', 'lat' => 14.4970, 'lng' => 121.2850],
    ];
}

return antipolo_point_seed();
