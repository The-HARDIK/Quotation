# Quotation Studio

**Quotation Studio** is a complete, production-grade dynamic quotation and commercial proposal management web application built with **HTML5, CSS3, Vanilla JavaScript (ES6+), PHP 8+, and MySQL**.

It features an end-to-end quotation lifecycle: customer management, products/services catalog, a split-screen **Two-Panel Quotation Builder** with live A4 preview (210mm × 297mm), centralized financial calculation engine with GST support, PDF export via Dompdf, client acceptance/rejection public portals, 1-click invoice conversion, payment settlement tracking, and sales analytics.

---

## 🏛️ Technology Stack

- **Frontend:** Pure HTML5, CSS3 (Custom SaaS Design System, CSS Variables, Flexbox/Grid), Vanilla JavaScript (ES6+).  
  *Zero React, Zero Vite, Zero TypeScript, Zero SPA frameworks.*
- **Backend:** PHP 8.3+ (Procedural / Modular OOP, PDO Prepared Statements, Session Authentication, CSRF protection).
- **Database:** MySQL 8+ with automated SQLite fallback (`database/quotation_studio.sqlite`) for plug-and-play local execution without database server prerequisites.
- **Libraries:**
  - **Dompdf** (PHP PDF rendering engine via Composer)
  - **Chart.js** (CDN for sales pipeline & trend charts)
  - **Font Awesome 6** (CDN for vector icons)
  - **Google Fonts** (Outfit & Inter)

---

## 📄 Reference Document Fidelity: 3-Page Commercial Proposal

The application faithfully reproduces the structure, layout, typography, and sections of the uploaded **`UiPrime-Quote-Format (2).docx`** reference document into a **100% dynamic quotation system**:

- **Page 1 (Cover Letter & Capabilities):**
  - Header branding & company contact block
  - Formal reference number (`PIPL/26-27/UiPrime/{seq}`), date, and recipient address block
  - Subject line and formal greeting
  - 31-year experience company narrative
  - 8 core competencies & capabilities bullets
  - Transition paragraph
- **Page 2 (Commercial Investment & Inclusions):**
  - Formal Particulars & Investment table with line item rates and totals
  - Inclusions & scope of work bullet list
  - Change request & customization process workflow
  - System and hardware requirements key-value specifications
  - Subtotal, GST (CGST/SGST/IGST), Grand Total, and Indian currency Amount in Words
- **Page 3 (Legal Terms, Banking & Signatures):**
  - 13 formal commercial terms and conditions clauses
  - Remittance and bank account settlement box (Bank, A/C #, IFSC, Branch, UPI)
  - Client acceptance sign-off block
  - Company authorized signatory with digital signature image
  - Multi-page A4 footer with corporate details and page indicators

*Note: All demo values are fictionalized and fully editable through the Quotation Builder.*

---

## 📁 Directory Structure

```
quotation-studio/
├── index.php                          # Entry router / splash
├── login.php                          # Secure session login
├── register.php                       # Business registration
├── logout.php                         # Session destruction
├── dashboard.php                      # Executive KPI dashboard & recent pipeline
│
├── quotations/
│   ├── index.php                      # Quotations list with search & status filters
│   ├── create.php                     # Two-panel split quotation builder
│   ├── edit.php                       # Live reactive editor for existing quotations
│   ├── view.php                       # 3-Page formal proposal rendered view
│   ├── duplicate.php                  # 1-click proposal cloning
│   ├── delete.php                     # CSRF-protected deletion
│   └── download.php                   # Dompdf server-side A4 PDF export
│
├── customers/
│   ├── index.php                      # Customer directory & search
│   ├── create.php                     # Add customer
│   ├── edit.php                       # Edit customer
│   ├── view.php                       # Customer profile & quotation history
│   └── delete.php                     # Delete customer
│
├── products/
│   ├── index.php                      # Product & service catalog
│   ├── create.php                     # Add product/service
│   ├── edit.php                       # Edit product/service
│   ├── duplicate.php                  # Duplicate SKU
│   └── delete.php                     # Delete item
│
├── invoices/
│   ├── index.php                      # Invoices list & status tracking
│   ├── create.php                     # Manual tax invoice creation
│   ├── view.php                       # Printable Tax Invoice & Record Payment modal
│   ├── convert-from-quotation.php     # 1-click conversion from accepted quotation
│   └── delete.php                     # Delete invoice
│
├── payments/
│   ├── index.php                      # Settlements & collections ledger
│   └── create.php                     # Record payment against open invoice
│
├── templates/
│   ├── index.php                      # Templates gallery
│   ├── classic.php                    # 3-Page enterprise proposal (uploaded doc replica)
│   ├── modern.php                     # Clean tech & SaaS template
│   ├── corporate.php                  # Executive formal tender layout
│   ├── minimal.php                    # Scandinavian monochrome layout
│   └── elegant.php                    # Boutique gold & slate template
│
├── reports/
│   └── index.php                      # Sales analytics, win-rates & Chart.js trends
│
├── settings/
│   ├── business.php                   # Company details, logo, signature & bank accounts
│   ├── numbering.php                  # Auto-numbering sequence & prefix formatting
│   ├── branding.php                   # Brand palette & typography configuration
│   └── terms.php                      # Legal terms & condition presets CRUD
│
├── public/
│   ├── quotation.php                  # Client-facing public proposal review & accept/reject portal
│   ├── css/
│   │   ├── style.css                  # Core SaaS design system
│   │   ├── dashboard.css              # KPI cards & metric grids
│   │   ├── quotation-builder.css      # Two-panel layout & live A4 preview canvas
│   │   └── quotation-print.css        # @page { size: A4; margin: 0; } print styles
│   ├── js/
│   │   ├── app.js                     # Global utilities, toast notifications & modals
│   │   ├── quotation-builder.js       # Live reactive preview engine & auto-save
│   │   └── calculations.js            # Centralized client-side financial engine
│   └── assets/                        # Brand logos, reference assets & signatures
│
├── includes/
│   ├── config.php                     # Global environment constants & paths
│   ├── db.php                         # PDO wrapper with MySQL & SQLite fallback
│   ├── auth.php                       # Session authentication & security helpers
│   ├── functions.php                  # Sanitization, currency formatters & CSRF tokens
│   ├── calculation-engine.php         # Centralized server-side financial engine
│   ├── header.php                     # Topbar & HTML shell
│   ├── sidebar.php                    # Navigation drawer
│   └── footer.php                     # Footer & global scripts
│
├── database/
│   ├── schema.sql                     # Standard MySQL schema (11 relational tables)
│   ├── seed.php                       # Initial demo data seeder
│   └── quotation_studio.sqlite        # Auto-created SQLite instance
│
└── composer.json                      # Dompdf dependency
```

---

## 🚀 Quick Start & Running Locally

### Prerequisites
- PHP 8.1+ with PDO and GD extensions enabled.
- (Optional) MySQL 8+ server. If MySQL is not running, the application automatically connects to the built-in SQLite database without any configuration!

### 1. Start the Built-in PHP Development Server
From the project root directory, run:
```bash
php -S 127.0.0.1:8000
```

### 2. Access the Application
Open your web browser and navigate to:
```
http://127.0.0.1:8000
```

### 3. Demo Credentials
The database comes pre-seeded with rich demo data:
- **Email:** `admin@quotationstudio.com`
- **Password:** `password123`

---

## 🧮 Centralized Financial Calculation Engine

All calculations are identical between `includes/calculation-engine.php` (backend) and `public/js/calculations.js` (frontend):
- `Line Total = (Quantity × Unit Price) - Line Discount`
- `Taxable Subtotal = Σ Line Totals - Overall Discount + Additional Charges`
- GST Modes:
  - **Intra-State:** CGST (50%) + SGST (50%)
  - **Inter-State:** IGST (100%)
  - **Tax Inclusive / Exclusive** pricing toggles
- Financial Round-Off to nearest rupee
- Automatic conversion of numbers to formal Indian currency words (*e.g., "One Lakh Fifteen Thousand Seven Hundred Rupees Only"*)

---

## 🔐 Security Standards
- **SQL Injection Prevention:** 100% of queries use PDO prepared statements with parameter binding.
- **XSS Mitigation:** All output rendered through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via `e()` helper.
- **CSRF Protection:** Synchronizer token pattern on all state-mutating POST requests (`verify_csrf()`).
- **Password Hashing:** Standard `password_hash($pwd, PASSWORD_DEFAULT)` and `password_verify()`.
- **Session Isolation:** All queries filter by `user_id = current_user_id()`.
