<?php
/**
 * PDO connection — auto-creates pastimes_db with tblUser, tblAdmin, tblClothes
 * on first run. No manual SQL import needed.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clothingstor');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `clothingstor` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `clothingstor`");

    // ── tbladmin ───────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbladmin (
            admin_id    INT AUTO_INCREMENT PRIMARY KEY,
            username    VARCHAR(50) NOT NULL UNIQUE,
            password    VARCHAR(255) NOT NULL,
            email       VARCHAR(100) NOT NULL,
            full_name   VARCHAR(100) NOT NULL,
            role        ENUM('super_admin','moderator') DEFAULT 'moderator',
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tbluser ───────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbluser (
            user_id             INT AUTO_INCREMENT PRIMARY KEY,
            first_name          VARCHAR(50) NOT NULL,
            last_name           VARCHAR(50) NOT NULL,
            email               VARCHAR(100) NOT NULL UNIQUE,
            username            VARCHAR(50) DEFAULT NULL UNIQUE,
            password            VARCHAR(255) NOT NULL,
            cellphone           VARCHAR(15) DEFAULT NULL,
            role                ENUM('buyer','seller','both') DEFAULT 'buyer',
            is_verified         TINYINT(1) DEFAULT 0,
            verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
            verified_by         INT DEFAULT NULL,
            verified_at         TIMESTAMP NULL DEFAULT NULL,
            rejection_reason    VARCHAR(255) DEFAULT NULL,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (verified_by) REFERENCES tbladmin(admin_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tblclothes ─────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblclothes (
            clothes_id      INT AUTO_INCREMENT PRIMARY KEY,
            seller_id       INT NOT NULL,
            title           VARCHAR(150) NOT NULL,
            description     TEXT DEFAULT NULL,
            price           DECIMAL(10,2) NOT NULL,
            category        VARCHAR(50) DEFAULT NULL,
            size            VARCHAR(10) DEFAULT NULL,
            clothesCondition ENUM('new','like new','good','fair') DEFAULT 'good',
            image_path      VARCHAR(255) DEFAULT NULL,
            status          ENUM('active','sold','flagged') DEFAULT 'active',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES tbluser(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Seed tbladmin ─────────────────────────────────────────
    if ((int)$pdo->query("SELECT COUNT(*) FROM tbladmin")->fetchColumn() === 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO tbladmin (username, password, email, full_name, role)
             VALUES (?, ?, ?, ?, 'super_admin')"
        );
        $stmt->execute(['admin', password_hash('admin12345', PASSWORD_DEFAULT), 'admin@pastimes.com', 'System Administrator']);
        
        // Add other admins with proper password hashes for 'password123'
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO tbladmin (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'moderator')");
        $stmt->execute(['moderator1', $passwordHash, 'mod1@pastimes.com', 'Jane Moderator']);
        $stmt->execute(['moderator2', $passwordHash, 'mod2@pastimes.com', 'Mike Reviewer']);
        $stmt->execute(['support_admin', $passwordHash, 'support@pastimes.com', 'Support Team Lead']);
        $stmt->execute(['head_admin', $passwordHash, 'head@pastimes.com', 'Head Administrator']);
    }

    // ── tblverification_documents ───────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblverification_documents (
            document_id      INT AUTO_INCREMENT PRIMARY KEY,
            user_id          INT NOT NULL,
            document_type    ENUM('id_document','proof_of_address','bank_statement','business_registration','other') NOT NULL,
            document_path    VARCHAR(255) NOT NULL,
            document_name    VARCHAR(255) NOT NULL,
            upload_date      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status           ENUM('pending','approved','rejected') DEFAULT 'pending',
            reviewed_by      INT DEFAULT NULL,
            reviewed_at      TIMESTAMP NULL DEFAULT NULL,
            rejection_reason VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES tbluser(user_id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES tbladmin(admin_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tblmessages ─────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblmessages (
            message_id   INT AUTO_INCREMENT PRIMARY KEY,
            sender_id    INT NOT NULL,
            receiver_id  INT NOT NULL,
            item_id      INT DEFAULT NULL,
            message_text TEXT NOT NULL,
            is_read      TINYINT(1) DEFAULT 0,
            read_at      TIMESTAMP NULL DEFAULT NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES tbluser(user_id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES tbluser(user_id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES tblclothes(clothes_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tblorder_items ──────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblorder_items (
            order_item_id     INT AUTO_INCREMENT PRIMARY KEY,
            order_id          INT NOT NULL,
            clothes_id        INT NOT NULL,
            quantity          INT DEFAULT 1,
            price_at_purchase DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES tblorder(order_id) ON DELETE CASCADE,
            FOREIGN KEY (clothes_id) REFERENCES tblclothes(clothes_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tblcart ────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tblcart (
            cart_id    INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            clothes_id INT NOT NULL,
            quantity   INT DEFAULT 1,
            added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cart_item (user_id, clothes_id),
            FOREIGN KEY (user_id) REFERENCES tbluser(user_id) ON DELETE CASCADE,
            FOREIGN KEY (clothes_id) REFERENCES tblclothes(clothes_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Seed tbluser ──────────────────────────────────────────
    // Bcrypt hash of the string "password" (standard Laravel test hash)
    if ((int)$pdo->query("SELECT COUNT(*) FROM tbluser")->fetchColumn() === 0) {
        $h = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // "password"
        $stmt = $pdo->prepare(
            "INSERT INTO tbluser (first_name, last_name, email, username, password, cellphone, role, is_verified, verification_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute(['John',    'Doe',   'john.doe@example.com',       'johndoe',     $h, '0821234567', 'both',   1, 'verified']);
        $stmt->execute(['Jane',    'Smith', 'jane.smith@email.com',        'janesmith',   $h, '0832345678', 'buyer',  1, 'verified']);
        $stmt->execute(['Michael', 'Brown', 'michael.b@webmail.co.za',     'mikeb',       $h, '0843456789', 'seller', 0, 'pending']);
        $stmt->execute(['Sarah',   'Johnson','sarah.j@fashionmail.com',    'sarahj',      $h, '0824567890', 'both',   1, 'verified']);
        $stmt->execute(['David',   'Williams','david.w@clothingstore.co.za', 'davidw',      $h, '0835678901', 'buyer',  1, 'verified']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    exit(
        '<p style="font-family:sans-serif;color:#b91c1c;padding:2rem;">'
        . '<strong>Database error:</strong> ' . htmlspecialchars($e->getMessage())
        . '<br><br>Make sure XAMPP MySQL is running (root / no password).</p>'
    );
}
