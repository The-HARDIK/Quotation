<?php
/**
 * Quotation Studio - Seed Realistic Demo Data
 */

function seed_demo_data(PDO $pdo): void {
    // Check if user already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) {
        return; // Already seeded
    }

    // 1. Create Default Admin User
    $passHash = password_hash('password123', PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $userStmt->execute(['Demo Administrator', 'admin@quotationstudio.com', $passHash, 'admin']);
    $userId = (int)$pdo->lastInsertId();

    // 2. Create Business Profile
    $bizStmt = $pdo->prepare("
        INSERT INTO business_profiles (
            user_id, company_name, tagline, logo_url, secondary_logo_url,
            address, city, state, country, pincode, phone, email, website,
            gstin, pan, registration_no, contact_person, designation,
            signatory_name, signatory_designation,
            bank_name, account_name, account_number, ifsc_code, branch, upi_id,
            primary_color, secondary_color, font_preference,
            numbering_prefix, numbering_fy, numbering_code, numbering_seq, numbering_digits
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ");

    $bizStmt->execute([
        $userId,
        'Priyam Infotech Solutions Pvt. Ltd.',
        'Adding Value Across Businesses',
        'public/assets/reference/image4.png',
        'public/assets/reference/image1.png',
        '401-404, Neelkanth-1, Bhawani Singh Road, C-Scheme',
        'Jaipur',
        'Rajasthan',
        'India',
        '302001',
        '+91 141 4005009, +91 85040 05009',
        'support@priyaminfotech.com',
        'https://www.priyaminfotech.com',
        '08AABCP1234F1Z5',
        'AABCP1234F',
        'U72200RJ2005PTC021234',
        'Virendra Sharma',
        'Executive Director',
        'Rajesh Sharma',
        'Director - Commercials',
        'HDFC Bank Ltd.',
        'Priyam Infotech Solutions Pvt Ltd',
        '50200099887766',
        'HDFC0001585',
        'C-Scheme, Jaipur',
        'priyaminfotech@hdfcbank',
        '#0d5c75',
        '#85a438',
        'Inter',
        'PIPL',
        '26-27',
        'UiPrime',
        4,
        3
    ]);

    // 3. Create Demo Customers
    $custStmt = $pdo->prepare("
        INSERT INTO customers (
            user_id, name, company_name, contact_person, email, phone,
            billing_address, city, state, country, pincode, gstin, pan, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $customers = [
        [
            $userId,
            'Apex Motors Pvt. Ltd.',
            'Apex Motors Private Limited',
            'Mr. Amit Agarwal (IT Head)',
            'amit.agarwal@apexmotors.in',
            '+91 98290 12345',
            'Plot No. 12, Transport Nagar, Tonk Road',
            'Jaipur',
            'Rajasthan',
            'India',
            '302015',
            '08AAACA1111A1Z1',
            'AAACA1111A',
            'Authorized Automobile Dealership network across 6 locations.'
        ],
        [
            $userId,
            'Horizon Logistics & Supply Chain',
            'Horizon Logistics Global LLP',
            'Ms. Neha Kulkarni (Finance VP)',
            'neha.k@horizonlogistics.com',
            '+91 99887 65432',
            'B-402, Trade World, Senapati Bapat Marg, Lower Parel',
            'Mumbai',
            'Maharashtra',
            'India',
            '400013',
            '27AABCH2222B1Z2',
            'AABCH2222B',
            'Inter-state transport and warehouse client.'
        ],
        [
            $userId,
            'Synergy Healthcare Products',
            'Synergy Lifecare Technologies Ltd',
            'Dr. Rohit Verma (Managing Director)',
            'rohit@synergyhealth.org',
            '+91 98111 22334',
            'Building 7, Okhla Industrial Area Phase-III',
            'New Delhi',
            'Delhi',
            'India',
            '110020',
            '07AAACS3333C1Z3',
            'AAACS3333C',
            'Medical equipment distribution & ERP sync requirements.'
        ],
        [
            $userId,
            'Skyline Infra & Developers',
            'Skyline Developers Private Limited',
            'Mr. Anand Rao (Chief Operating Officer)',
            'anand@skylineinfra.co.in',
            '+91 97400 55667',
            '10th Floor, Prestige Towers, Residency Road',
            'Bengaluru',
            'Karnataka',
            'India',
            '560025',
            '29AAACS4444D1Z4',
            'AAACS4444D',
            'Commercial real estate enterprise requiring multi-branch tally consolidation.'
        ]
    ];

    $custIds = [];
    foreach ($customers as $c) {
        $custStmt->execute($c);
        $custIds[] = (int)$pdo->lastInsertId();
    }

    // 4. Create Demo Products & Services
    $prodStmt = $pdo->prepare("
        INSERT INTO products_services (
            user_id, name, sku, description, hsn_sac, unit, unit_price, default_discount, default_tax_rate, category, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $products = [
        [
            $userId,
            'UiPrime Robotic Automation Bot',
            'UIP-BOT-01',
            'Robotic process automation bot for DMS portal automated download, data restructuring, validation and accounting synchronization.',
            '998313',
            'Month',
            15000.00,
            0.00,
            18.00,
            'Automation Software',
            'Requires dedicated VM or PC with Windows 10/11 and DMS credentials.'
        ],
        [
            $userId,
            'Automobile DMS to Tally API Bridge',
            'DMS-API-02',
            'Real-time automated sync bridge between OEM DMS portals and TallyPrime multi-branch accounting.',
            '998314',
            'License',
            35000.00,
            5.00,
            18.00,
            'Integration',
            'One-time setup with 12 months maintenance.'
        ],
        [
            $userId,
            'Tally on Cloud Enterprise',
            'TOC-ENT-03',
            'High-availability secure cloud hosting for TallyPrime with encrypted automated backups and remote access.',
            '998315',
            'User/Year',
            7200.00,
            0.00,
            18.00,
            'Cloud Services',
            'Includes daily snapshot backup.'
        ],
        [
            $userId,
            'Cloud Automated Backup & DR Solution',
            'BKP-DR-04',
            'End-to-end continuous encrypted backup solution with automated integrity testing and disaster recovery rollback.',
            '998316',
            'Year',
            12000.00,
            10.00,
            18.00,
            'Cloud Services',
            'Compliant with data residency guidelines.'
        ],
        [
            $userId,
            'Workflow Customization & SRS Development',
            'DEV-MAN-05',
            'Custom business workflow development, custom reporting, approval matrix design, and ERP enhancement.',
            '998313',
            'Man-Day',
            6500.00,
            0.00,
            18.00,
            'Professional Services',
            'Estimated as per confirmed Scope of Work.'
        ],
        [
            $userId,
            'Annual Maintenance Contract (AMC Support)',
            'AMC-SUP-06',
            'Comprehensive priority phone, remote access, chat, and email technical support with committed 4-hour SLA.',
            '998712',
            'Year',
            24000.00,
            0.00,
            18.00,
            'Support',
            'Includes regular version updates and bug-fixes.'
        ]
    ];

    $prodIds = [];
    foreach ($products as $p) {
        $prodStmt->execute($p);
        $prodIds[] = (int)$pdo->lastInsertId();
    }

    // 5. Create Terms Presets
    $termsStmt = $pdo->prepare("
        INSERT INTO terms_presets (user_id, title, category, clauses, is_default)
        VALUES (?, ?, ?, ?, ?)
    ");

    $botClauses = [
        "This proposal is to be accepted along with a Mandate / Purchase Order issued in favour of Priyam Infotech Solutions Pvt Ltd, Jaipur. Order / Mandate have to be accepted by the company in writing.",
        "Correspondence Address: Priyam Infotech Solutions Pvt. Ltd., 401-404, Neelkanth-1, Bhawani Singh Road, C-Scheme, Jaipur-302001.",
        "Validity period: This proposal is valid for 15 days from the date of issue.",
        "The payment terms are as follows: 100% in advance along with Purchase Order.",
        "Prices mentioned in the proposal are exclusive of taxes. All taxes shall be charged 18% extra as applicable.",
        "Certificate relating to Tax Deducted at Source (TDS), if any from payments made has to be issued before Financial Year end or execution of contract, whichever is earlier.",
        "Customer shall provide requisite approvals, sign-offs and certificate of deliverables on a timely basis.",
        "All user security settings have to be provided before starting the implementation.",
        "Hand holding support applicable only on 'UiPrime Application'.",
        "The basic server infrastructure like Internet, user security and DMS permissions from OEM has to be provided by client.",
        "All Credentials security has to be taken care by your team only.",
        "Any further development and modifications will be charged extra.",
        "Any customization or process change will be treated as new requirement and new requirement is chargeable on case-to-case basis."
    ];

    $saasClauses = [
        "Service Level Agreement guarantees 99.5% uptime during standard business hours.",
        "Subscription fees are billed quarterly or annually in advance.",
        "Data backup is executed daily with 30-day retention rollback.",
        "Either party may terminate the agreement with 30 days prior written notice.",
        "Taxes are applicable as per prevailing statutory Indian GST rates."
    ];

    $consultingClauses = [
        "Project delivery timeline commences post receipt of advance payment and sign-off on SRS.",
        "Scope changes beyond agreed SRS will require a Change Request Note with additional billing.",
        "Client will provide single-point contact for requirement clarifications and UAT sign-off.",
        "Payment milestone: 50% advance, 30% on UAT deployment, 20% on final go-live sign-off."
    ];

    $termsStmt->execute([$userId, 'BOT Automation & Software Terms', 'Automation', json_encode($botClauses), 1]);
    $termsStmt->execute([$userId, 'Standard SaaS Subscription Terms', 'SaaS', json_encode($saasClauses), 0]);
    $termsStmt->execute([$userId, 'Consulting & Custom Development Terms', 'Consulting', json_encode($consultingClauses), 0]);

    // 6. Create Seed Quotations
    // Quotation 1: Exact replication of 3-Page Reference Proposal!
    $q1Number = 'PIPL/26-27/UiPrime/001';
    $q1RefNumber = 'PIPL/26-27/UiPrime/REF-104';
    $q1Date = date('Y-m-d');
    $q1ValidUntil = date('Y-m-d', strtotime('+15 days'));
    $q1Subject = 'UiPrime Enterprise Automation Edition';

    $q1CustomerSnapshot = [
        'name' => 'Apex Motors Pvt. Ltd.',
        'company_name' => 'Apex Motors Private Limited',
        'contact_person' => 'Mr. Amit Agarwal (IT Head)',
        'email' => 'amit.agarwal@apexmotors.in',
        'phone' => '+91 98290 12345',
        'billing_address' => 'Plot No. 12, Transport Nagar, Tonk Road, Jaipur, Rajasthan 302015',
        'gstin' => '08AAACA1111A1Z1',
        'pan' => 'AAACA1111A'
    ];

    $q1BusinessSnapshot = [
        'company_name' => 'Priyam Infotech Solutions Pvt. Ltd.',
        'tagline' => 'Adding Value Across Businesses',
        'address' => '401-404, Neelkanth-1, Bhawani Singh Road, C-Scheme, Jaipur-302001',
        'phone' => '+91 141 4005009, +91 85040 05009',
        'email' => 'support@priyaminfotech.com',
        'website' => 'https://www.priyaminfotech.com',
        'gstin' => '08AABCP1234F1Z5',
        'pan' => 'AABCP1234F',
        'signatory_name' => 'Rajesh Sharma',
        'signatory_designation' => 'Director - Commercials',
        'bank_name' => 'HDFC Bank Ltd.',
        'account_name' => 'Priyam Infotech Solutions Pvt Ltd',
        'account_number' => '50200099887766',
        'ifsc_code' => 'HDFC0001585',
        'primary_color' => '#0d5c75',
        'secondary_color' => '#85a438'
    ];

    $q1Intro = [
        'salutation' => 'Dear Sir,',
        'greeting' => 'Greetings from Priyam!!',
        'paragraph1' => 'We are pleased to introduce ourselves as one of the leading commercial software application vendors in India. We have a rich experience of selling, supporting & implementation of application software for more than 31 years and having a hardcore technically strong team to serve & support our prestigious clientele. We have more than 19,000+ satisfied users of Tally & other solutions across India.',
        'paragraph2' => 'Apart from being an Authorized "5 Star Certified Partner and GVLA Partner" of Tally Solutions Pvt Ltd, we have expanded our capabilities to deliver comprehensive Tally Applications, Multi-Branch Accounting, ERPs, Cloud Services, RPA Automation, API Integration, and Industry-Specific Solutions that address the evolving needs of modern Small & Medium Enterprises (SMEs) and large Enterprises.',
        'relationship_heading' => 'At the same time, we wish to introduce our relationship and core competencies as follows:',
        'capabilities' => [
            'Tally Authorized "5 Star Certified Partner"',
            'RPA Solutions Provider & Process Automation Specialist',
            'Tally on Cloud, Cloud Backup & High Availability Solutions',
            'Automobile DMS to Tally Integrations through Excel, RPA & API Integration',
            'Tally Integrator & Enterprise Customization Partner',
            'Centralized Branch Accounting & Multi-Location Consolidation',
            'Workflow Management & Approval Solutions',
            'UiPrime Robotic Automation System'
        ],
        'transition' => 'This has reference to our detailed discussion with you regarding UiPrime Enterprise Edition.'
    ];

    $q1Scope = [
        'heading' => 'Subscription Includes:',
        'items' => [
            'BOT Installation, Implementation and one-time training to manage logs.',
            'Portal Login Automation: The automation bot will securely log in to DMS portal using provided credentials.',
            'Excel File Retrieval: Bot systematically navigates through designated portals and downloads required reports.',
            'Data formulation & Processing: Formatting, filtering, and merging as per final SRS document.',
            'Online support through Chat, Email and Remote Access under subscription period.',
            'Bug Fixing in Bot automation as per final confirmation on email of SRS.'
        ]
    ];

    $q1Customization = [
        'heading' => 'Additional Customization request:',
        'description' => 'Various components of the change request are as follows, and the time and effort for all these are chargeable and will be taken as phase two along with the below process:',
        'steps' => [
            'Requirement study',
            'Gap Analysis',
            'Solution design',
            'Approvals & discussions',
            'Development',
            'Testing',
            'Deployment'
        ]
    ];

    $q1SysReq = [
        'heading' => 'System Requirements for UiPrime:',
        'specs' => [
            ['key' => 'OS', 'value' => 'Windows 10/11 (64-bit)'],
            ['key' => 'RAM', 'value' => '4 GB (Minimum)'],
            ['key' => 'Disk', 'value' => '5 GB (Minimum free space)'],
            ['key' => 'NIC', 'value' => 'Stable Internet Connectivity']
        ]
    ];

    $q1Notes = [
        'UiPrime Workflow Automation is dependent on DMS Portal (If DMS not opening/working, data will not download).',
        'UiPrime will generate Excel reports as per workflow confirmation.',
        'UiPrime is a Robotic Technology. It requires a stable environment to perform. Any change in environment or process will interrupt routine working, so user needs to follow DOs & DONTs strictly else error will occur.'
    ];

    $qStmt = $pdo->prepare("
        INSERT INTO quotations (
            user_id, customer_id, quotation_number, ref_number, date, valid_until, subject,
            currency, status, template_id, prepared_by, sales_person,
            subtotal, total_discount, taxable_amount, tax_type, tax_inclusive, gst_rate,
            cgst_amount, sgst_amount, igst_amount, additional_charges, round_off,
            grand_total, advance_amount, balance_amount,
            customer_snapshot, business_snapshot,
            intro_section, scope_section, customization_section,
            system_requirements, terms_conditions, payment_terms_text, notes_section,
            public_share_id, customer_response
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?
        )
    ");

    $shareId1 = bin2hex(random_bytes(16));
    $qStmt->execute([
        $userId,
        $custIds[0],
        $q1Number,
        $q1RefNumber,
        $q1Date,
        $q1ValidUntil,
        $q1Subject,
        'INR',
        'Sent',
        'classic',
        'Rajesh Sharma',
        'Virendra Sharma',
        45000.00, // Subtotal (3 months @ 15,000)
        0.00,
        45000.00,
        'GST',
        0,
        18.00,
        4050.00, // CGST 9%
        4050.00, // SGST 9%
        0.00,
        0.00,
        0.00,
        53100.00, // Grand Total
        53100.00, // Advance
        0.00,
        json_encode($q1CustomerSnapshot),
        json_encode($q1BusinessSnapshot),
        json_encode($q1Intro),
        json_encode($q1Scope),
        json_encode($q1Customization),
        json_encode($q1SysReq),
        json_encode($botClauses),
        'Payment terms: 100% in advance with Order / Mandate.',
        json_encode($q1Notes),
        $shareId1,
        null
    ]);

    $quote1Id = (int)$pdo->lastInsertId();

    // Quotation 1 Items
    $qItemStmt = $pdo->prepare("
        INSERT INTO quotation_items (
            quotation_id, product_id, item_order, description, hsn_sac,
            quantity, unit, unit_price, discount_percent, discount_amount, tax_rate, line_total, billing_period, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $qItemStmt->execute([
        $quote1Id,
        $prodIds[0],
        1,
        'UiPrime Automate Enterprise Edition – Dealer Management System (DMS) Automation Bot',
        '998313',
        3.00,
        'Month',
        15000.00,
        0.00,
        0.00,
        18.00,
        45000.00,
        'Per Month (Billed Quarterly)',
        'Includes automated nightly run and daytime trigger.'
    ]);

    // Quotation 2: Accepted quotation (for Horizon Logistics)
    $shareId2 = bin2hex(random_bytes(16));
    $qStmt->execute([
        $userId,
        $custIds[1],
        'PIPL/26-27/UiPrime/002',
        'PIPL/26-27/UiPrime/REF-105',
        date('Y-m-d', strtotime('-5 days')),
        date('Y-m-d', strtotime('+10 days')),
        'Cloud Backup & Multi-Branch Tally Sync Proposal',
        'INR',
        'Accepted',
        'modern',
        'Rajesh Sharma',
        'Virendra Sharma',
        47000.00,
        2000.00,
        45000.00,
        'IGST',
        0,
        18.00,
        0.00,
        0.00,
        8100.00,
        0.00,
        0.00,
        53100.00,
        26550.00,
        26550.00,
        json_encode($customers[1]),
        json_encode($q1BusinessSnapshot),
        json_encode($q1Intro),
        json_encode($q1Scope),
        json_encode($q1Customization),
        json_encode($q1SysReq),
        json_encode($saasClauses),
        '50% advance along with PO, balance 50% upon deployment.',
        json_encode(['Cloud data residency in Mumbai data center.']),
        $shareId2,
        json_encode(['status' => 'Accepted', 'responded_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'comment' => 'Approved by VP Finance. Please raise invoice.'])
    ]);
    $quote2Id = (int)$pdo->lastInsertId();

    $qItemStmt->execute([
        $quote2Id,
        $prodIds[1],
        1,
        'Automobile DMS to Tally API Bridge License',
        '998314',
        1.00,
        'License',
        35000.00,
        0.00,
        0.00,
        18.00,
        35000.00,
        'One-time License',
        null
    ]);

    $qItemStmt->execute([
        $quote2Id,
        $prodIds[3],
        2,
        'Cloud Automated Backup & DR Solution (1 Year)',
        '998316',
        1.00,
        'Year',
        12000.00,
        16.67,
        2000.00,
        18.00,
        10000.00,
        'Annual Subscription',
        null
    ]);

    // Quotation 3: Draft quotation (for Synergy Healthcare)
    $shareId3 = bin2hex(random_bytes(16));
    $qStmt->execute([
        $userId,
        $custIds[2],
        'PIPL/26-27/UiPrime/003',
        'PIPL/26-27/UiPrime/REF-106',
        date('Y-m-d', strtotime('-1 day')),
        date('Y-m-d', strtotime('+14 days')),
        'Tally on Cloud Multi-User Enterprise Suite',
        'INR',
        'Draft',
        'corporate',
        'Rajesh Sharma',
        'Virendra Sharma',
        36000.00,
        0.00,
        36000.00,
        'GST',
        0,
        18.00,
        3240.00,
        3240.00,
        0.00,
        0.00,
        0.00,
        42480.00,
        0.00,
        42480.00,
        json_encode($customers[2]),
        json_encode($q1BusinessSnapshot),
        json_encode($q1Intro),
        json_encode($q1Scope),
        json_encode($q1Customization),
        json_encode($q1SysReq),
        json_encode($consultingClauses),
        '100% advance with Order.',
        json_encode(['5 concurrent users included.']),
        $shareId3,
        null
    ]);
    $quote3Id = (int)$pdo->lastInsertId();

    $qItemStmt->execute([
        $quote3Id,
        $prodIds[2],
        1,
        'Tally on Cloud Enterprise (5 Users Annual Pack)',
        '998315',
        5.00,
        'User/Year',
        7200.00,
        0.00,
        0.00,
        18.00,
        36000.00,
        'Annual',
        'High performance SSD cloud server.'
    ]);

    // 7. Seed One Converted Invoice from Quotation 2
    $invStmt = $pdo->prepare("
        INSERT INTO invoices (
            user_id, quotation_id, customer_id, invoice_number, issue_date, due_date, status,
            subtotal, total_discount, taxable_amount, cgst_amount, sgst_amount, igst_amount,
            round_off, grand_total, paid_amount, balance_due, payment_terms, notes,
            customer_snapshot, business_snapshot
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?
        )
    ");

    $invStmt->execute([
        $userId,
        $quote2Id,
        $custIds[1],
        'INV-2026-001',
        date('Y-m-d', strtotime('-1 day')),
        date('Y-m-d', strtotime('+14 days')),
        'Partially Paid',
        47000.00,
        2000.00,
        45000.00,
        0.00,
        0.00,
        8100.00,
        0.00,
        53100.00,
        26550.00, // 50% paid
        26550.00,
        '50% advance along with PO, balance 50% upon deployment.',
        'Converted from Quotation PIPL/26-27/UiPrime/002 upon customer acceptance.',
        json_encode($customers[1]),
        json_encode($q1BusinessSnapshot)
    ]);
    $invoiceId = (int)$pdo->lastInsertId();

    // Invoice items
    $invItemStmt = $pdo->prepare("
        INSERT INTO invoice_items (
            invoice_id, product_id, description, hsn_sac, quantity, unit, unit_price, discount_percent, tax_rate, line_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $invItemStmt->execute([
        $invoiceId,
        $prodIds[1],
        'Automobile DMS to Tally API Bridge License',
        '998314',
        1.00,
        'License',
        35000.00,
        0.00,
        18.00,
        35000.00
    ]);
    $invItemStmt->execute([
        $invoiceId,
        $prodIds[3],
        'Cloud Automated Backup & DR Solution (1 Year)',
        '998316',
        1.00,
        'Year',
        12000.00,
        16.67,
        18.00,
        10000.00
    ]);

    // 8. Seed One Payment against Invoice
    $payStmt = $pdo->prepare("
        INSERT INTO payments (
            user_id, invoice_id, payment_number, payment_date, amount, payment_method, reference_number, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $payStmt->execute([
        $userId,
        $invoiceId,
        'PAY-2026-001',
        date('Y-m-d', strtotime('-1 day')),
        26550.00,
        'NEFT/RTGS',
        'HDFC-NEFT-984321774',
        '50% advance received via HDFC bank transfer.'
    ]);
}
