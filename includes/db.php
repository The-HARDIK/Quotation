<?php
/**
 * Quotation Studio - Database Connection Layer
 * Supports MySQL with automatic SQLite fallback for zero-friction setup.
 */

require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $mysqlAttempted = false;
    $useSqlite = false;

    // 1. Try MySQL Connection First
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2, // Fast timeout if MySQL server is offline
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $GLOBALS['DB_DRIVER'] = 'mysql';
        return $pdo;
    } catch (PDOException $e) {
        $mysqlAttempted = true;
        // Check if database doesn't exist yet on MySQL, try creating it
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $GLOBALS['DB_DRIVER'] = 'mysql';
                initialize_database($pdo, 'mysql');
                return $pdo;
            } catch (Exception $inner) {
                $useSqlite = true;
            }
        } else {
            // MySQL server is not running or credentials invalid -> fallback to SQLite
            $useSqlite = true;
        }
    }

    // 2. Fallback to SQLite
    if ($useSqlite) {
        $dbFile = SQLITE_PATH;
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $isNew = !file_exists($dbFile) || filesize($dbFile) === 0;

        $dsn = "sqlite:" . $dbFile;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, null, null, $options);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $GLOBALS['DB_DRIVER'] = 'sqlite';

        // Check if users table exists
        $tableExists = false;
        try {
            $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
            if ($check) {
                $tableExists = true;
            }
        } catch (Exception $e) {}

        if (!$tableExists) {
            initialize_database($pdo, 'sqlite');
        }

        return $pdo;
    }

    throw new RuntimeException("Could not establish database connection.");
}

function initialize_database(PDO $pdo, string $driver = 'mysql'): void {
    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS business_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                company_name TEXT NOT NULL,
                tagline TEXT,
                logo_url TEXT,
                secondary_logo_url TEXT,
                address TEXT,
                city TEXT,
                state TEXT,
                country TEXT DEFAULT 'India',
                pincode TEXT,
                phone TEXT,
                email TEXT,
                website TEXT,
                gstin TEXT,
                pan TEXT,
                registration_no TEXT,
                contact_person TEXT,
                designation TEXT,
                signatory_name TEXT,
                signatory_designation TEXT,
                signature_url TEXT,
                bank_name TEXT,
                account_name TEXT,
                account_number TEXT,
                ifsc_code TEXT,
                branch TEXT,
                upi_id TEXT,
                primary_color TEXT DEFAULT '#0d5c75',
                secondary_color TEXT DEFAULT '#85a438',
                font_preference TEXT DEFAULT 'Inter',
                numbering_prefix TEXT DEFAULT 'PIPL',
                numbering_fy TEXT DEFAULT '26-27',
                numbering_code TEXT DEFAULT 'UiPrime',
                numbering_seq INTEGER DEFAULT 1,
                numbering_digits INTEGER DEFAULT 3,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                company_name TEXT,
                contact_person TEXT,
                email TEXT,
                phone TEXT,
                billing_address TEXT,
                shipping_address TEXT,
                city TEXT,
                state TEXT,
                country TEXT DEFAULT 'India',
                pincode TEXT,
                gstin TEXT,
                pan TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS products_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                sku TEXT,
                description TEXT,
                hsn_sac TEXT,
                unit TEXT DEFAULT 'Nos',
                unit_price REAL DEFAULT 0.00,
                default_discount REAL DEFAULT 0.00,
                default_tax_rate REAL DEFAULT 18.00,
                category TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS terms_presets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                category TEXT DEFAULT 'General',
                clauses TEXT NOT NULL,
                is_default INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS quotation_templates (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                thumbnail_class TEXT DEFAULT 'template-classic'
            );

            CREATE TABLE IF NOT EXISTS quotations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                customer_id INTEGER,
                quotation_number TEXT NOT NULL,
                ref_number TEXT,
                date DATE NOT NULL,
                valid_until DATE,
                subject TEXT,
                currency TEXT DEFAULT 'INR',
                status TEXT DEFAULT 'Draft',
                template_id TEXT DEFAULT 'classic',
                prepared_by TEXT,
                sales_person TEXT,
                subtotal REAL DEFAULT 0.00,
                total_discount REAL DEFAULT 0.00,
                taxable_amount REAL DEFAULT 0.00,
                tax_type TEXT DEFAULT 'GST',
                tax_inclusive INTEGER DEFAULT 0,
                gst_rate REAL DEFAULT 18.00,
                cgst_amount REAL DEFAULT 0.00,
                sgst_amount REAL DEFAULT 0.00,
                igst_amount REAL DEFAULT 0.00,
                additional_charges REAL DEFAULT 0.00,
                round_off REAL DEFAULT 0.00,
                grand_total REAL DEFAULT 0.00,
                advance_amount REAL DEFAULT 0.00,
                balance_amount REAL DEFAULT 0.00,
                customer_snapshot TEXT,
                business_snapshot TEXT,
                intro_section TEXT,
                scope_section TEXT,
                customization_section TEXT,
                system_requirements TEXT,
                terms_conditions TEXT,
                payment_terms_text TEXT,
                notes_section TEXT,
                public_share_id TEXT UNIQUE,
                customer_response TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS quotation_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quotation_id INTEGER NOT NULL,
                product_id INTEGER,
                item_order INTEGER DEFAULT 0,
                description TEXT NOT NULL,
                hsn_sac TEXT,
                quantity REAL DEFAULT 1.00,
                unit TEXT DEFAULT 'Nos',
                unit_price REAL DEFAULT 0.00,
                discount_percent REAL DEFAULT 0.00,
                discount_amount REAL DEFAULT 0.00,
                tax_rate REAL DEFAULT 18.00,
                line_total REAL DEFAULT 0.00,
                billing_period TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                quotation_id INTEGER,
                customer_id INTEGER NOT NULL,
                invoice_number TEXT NOT NULL UNIQUE,
                issue_date DATE NOT NULL,
                due_date DATE,
                status TEXT DEFAULT 'Unpaid',
                subtotal REAL DEFAULT 0.00,
                total_discount REAL DEFAULT 0.00,
                taxable_amount REAL DEFAULT 0.00,
                cgst_amount REAL DEFAULT 0.00,
                sgst_amount REAL DEFAULT 0.00,
                igst_amount REAL DEFAULT 0.00,
                round_off REAL DEFAULT 0.00,
                grand_total REAL DEFAULT 0.00,
                paid_amount REAL DEFAULT 0.00,
                balance_due REAL DEFAULT 0.00,
                payment_terms TEXT,
                notes TEXT,
                customer_snapshot TEXT,
                business_snapshot TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                product_id INTEGER,
                description TEXT NOT NULL,
                hsn_sac TEXT,
                quantity REAL DEFAULT 1.00,
                unit TEXT DEFAULT 'Nos',
                unit_price REAL DEFAULT 0.00,
                discount_percent REAL DEFAULT 0.00,
                tax_rate REAL DEFAULT 18.00,
                line_total REAL DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                invoice_id INTEGER NOT NULL,
                payment_number TEXT,
                payment_date DATE NOT NULL,
                amount REAL NOT NULL,
                payment_method TEXT DEFAULT 'Bank Transfer',
                reference_number TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ");
    }

    // Insert templates
    $templates = [
        ['classic', 'Commercial Proposal – Classic', 'High-fidelity 3-page corporate commercial proposal replicating the official format with formal cover letter, scope breakdown, hardware requirements, and comprehensive legal terms.', 'template-classic'],
        ['modern', 'Modern Clean', 'A crisp, modern tech layout with clean geometric headers, accent color bands, and compact line items.', 'template-modern'],
        ['corporate', 'Executive Corporate', 'Traditional formal enterprise proposal layout with distinguished serif headers, dark borders, and structured grids.', 'template-corporate'],
        ['minimal', 'Minimalist Slate', 'Sleek, black and white minimalist design focusing on content clarity, subtle dividers, and high legibility.', 'template-minimal'],
        ['elegant', 'Boutique Elegant', 'Refined aesthetic featuring warm jewel tones, stylish serif typography, and elegant quotation cards.', 'template-elegant']
    ];

    $stmt = $pdo->prepare("INSERT OR REPLACE INTO quotation_templates (id, name, description, thumbnail_class) VALUES (?, ?, ?, ?)");
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare("INSERT INTO quotation_templates (id, name, description, thumbnail_class) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
    }
    foreach ($templates as $t) {
        $stmt->execute($t);
    }

    // Auto seed demo data
    require_once __DIR__ . '/../database/seed.php';
    seed_demo_data($pdo);
}
