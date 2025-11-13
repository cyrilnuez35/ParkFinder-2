<?php
require __DIR__ . '/util.php';
require __DIR__ . '/db.php';

cors();

try {
    $pdo = get_pdo();

    // Ensure table exists
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS parking_slots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            type ENUM('street','lot','garage','valet') NOT NULL DEFAULT 'street',
            access ENUM('public','private') NOT NULL DEFAULT 'public',
            address VARCHAR(255) NOT NULL,
            latitude DECIMAL(9,6) NOT NULL,
            longitude DECIMAL(9,6) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            duration VARCHAR(50) NOT NULL DEFAULT '2 hours',
            capacity INT NOT NULL DEFAULT 0,
            occupied INT NOT NULL DEFAULT 0,
            features TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
    // Add access column if table already exists without it
    try { $pdo->exec("ALTER TABLE parking_slots ADD COLUMN access ENUM('public','private') NOT NULL DEFAULT 'public'"); } catch (Throwable $_) {}

    // Build default slots list (public and malls as private). We'll insert missing ones only.

    $slots = [
        // Public defaults
        ['Bankerohan Street Parking','street','public','Bankerohan Public Market, Davao City',7.067632,125.603453,15.00,'2 hours',25,17,'Metered, Market Area, Public Transport'],
        ['Pitchon Street Parking','street','public','Pitchon Street, Davao City',7.064610,125.606807,12.00,'1 hour',15,10,'Metered, Residential Area'],
        ['Pelayo Street Parking','street','public','Pelayo Street, Davao City',7.066761,125.605295,18.00,'3 hours',30,18,'Metered, Commercial Area, Near City Center'],
        ['Anda Street Parking','street','public','Anda Street, Davao City',7.065656,125.606542,16.00,'2 hours',20,14,'Metered, Downtown Area'],
        ['C. M. Recto Street Parking','street','public','C. M. Recto Street, Davao City',7.070846,125.611728,20.00,'4 hours',35,20,'Metered, Main Street, Business District'],
        ['Bolton Street Parking','street','public','Bolton Street, Davao City',7.066880,125.609680,14.00,'2 hours',12,8,'Metered, Residential Area'],
        ['San Pedro Street Parking','street','public','San Pedro Street, Davao City',7.065595,125.607725,22.00,'3 hours',25,15,'Metered, Historic Area, Tourist Spot'],
        ['Illustre Street Parking','street','public','Illustre Street, Davao City',7.068760,125.605007,17.00,'2 hours',18,11,'Metered, Commercial Area'],
        ['Duterte Street Parking','street','public','Duterte Street, Davao City',7.068793,125.605838,19.00,'3 hours',22,13,'Metered, Government Area, Near City Hall'],
        ['Villa Abrille Street Parking','street','public','Villa Abrille Street, Davao City',7.074843,125.613956,21.00,'4 hours',28,17,'Metered, Upscale Area, Near Hotels'],
        ['Monteverde Street Parking','street','public','Monteverde Street, Davao City',7.075311,125.616710,13.00,'1 hour',10,7,'Metered, Residential Area'],
        ['Calinan Public Market Parking','lot','public','Calinan Public Market, Davao City',7.026900,125.409200,10.00,'2 hours',40,20,'Open Lot, Market Area, Public Transport'],
        ['Calinan Town Center Parking','street','public','Calinan Town Center, Davao City',7.030000,125.410000,12.00,'3 hours',30,15,'Metered, Town Center, Government Services'],
        ['Calinan Terminal Parking','lot','public','Calinan Terminal, Davao City',7.025000,125.408000,8.00,'1 hour',50,25,'Open Lot, Terminal Area, Public Transport'],
        ['Underground Garage','garage','public','Financial District, Davao City',7.070000,125.620000,12.00,'8 hours',60,38,'Underground, Security, Monthly rates'],
        // Mall private defaults
        ['Abreeza Mall Parking','garage','private','Abreeza Mall, Davao City',7.092300,125.613200,20.00,'2 hours',0,0,'Covered, Security, Mall'],
        ['SM Lanang Premier Parking','garage','private','SM Lanang Premier, Davao City',7.103900,125.629600,20.00,'2 hours',0,0,'Covered, Security, Mall'],
        ['SM City Davao Parking','garage','private','SM City Davao, Davao City',7.047900,125.586500,20.00,'2 hours',0,0,'Covered, Security, Mall'],
        ['Gaisano Mall Parking','garage','private','Gaisano Mall, Davao City',7.074400,125.611000,20.00,'2 hours',0,0,'Covered, Security, Mall'],
        ['Victoria Plaza Parking','lot','private','Victoria Plaza, Davao City',7.079200,125.612800,15.00,'2 hours',0,0,'Security, Mall'],
        ['NCCC Mall Parking','lot','private','NCCC Mall, Davao City',7.053000,125.581300,15.00,'2 hours',0,0,'Security, Mall'],
    ];

    // Insert only missing by name (case-insensitive)
    $existing = $pdo->query('SELECT name FROM parking_slots')->fetchAll(PDO::FETCH_COLUMN);
    $existingLower = array_map(function($n){ return strtolower(trim($n)); }, $existing ?: []);
    $toInsert = [];
    foreach ($slots as $s) {
        $nameLower = strtolower(trim($s[0]));
        if (!in_array($nameLower, $existingLower, true)) {
            $toInsert[] = $s;
        }
    }

    if (empty($toInsert)) {
        json_response(['message' => 'No new default slots to import', 'inserted' => 0], 200);
    }

    $ins = $pdo->prepare('INSERT INTO parking_slots (name, type, access, address, latitude, longitude, price, duration, capacity, occupied, features) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($toInsert as $s) {
        $ins->execute($s);
    }

    json_response(['message' => 'Seeded parking slots', 'inserted' => count($toInsert)], 201);
} catch (Throwable $e) {
    json_response(['message' => 'Server error', 'error' => $e->getMessage()], 500);
}