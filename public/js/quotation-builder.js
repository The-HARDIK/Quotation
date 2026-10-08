/**
 * Quotation Studio - Two-Panel Interactive Quotation Builder & Live A4 Engine
 */

class QuotationBuilder {
    constructor(initialData = {}, config = {}) {
        this.config = config;
        this.state = {
            id: initialData.id || null,
            quotation_number: initialData.quotation_number || '',
            ref_number: initialData.ref_number || '',
            date: initialData.date || new Date().toISOString().split('T')[0],
            valid_until: initialData.valid_until || '',
            subject: initialData.subject || '',
            currency: initialData.currency || 'INR',
            status: initialData.status || 'Draft',
            template_id: initialData.template_id || 'classic',
            prepared_by: initialData.prepared_by || '',
            sales_person: initialData.sales_person || '',

            customer_id: initialData.customer_id || null,
            customer_snapshot: initialData.customer_snapshot || {
                name: '', company_name: '', contact_person: '',
                email: '', phone: '', billing_address: '', gstin: '', pan: ''
            },

            business_snapshot: initialData.business_snapshot || config.business || {},

            items: initialData.items && initialData.items.length > 0 ? initialData.items : [
                {
                    product_id: null,
                    description: 'UiPrime Automate Enterprise Edition – Dealer Management System (DMS) Automation Bot',
                    hsn_sac: '998313',
                    quantity: 1,
                    unit: 'Month',
                    unit_price: 15000,
                    discount_percent: 0,
                    discount_amount: 0,
                    tax_rate: 18,
                    billing_period: 'Per Month (Billed Quarterly)',
                    notes: ''
                }
            ],

            tax_type: initialData.tax_type || 'GST',
            tax_inclusive: Boolean(initialData.tax_inclusive),
            gst_rate: parseFloat(initialData.gst_rate !== undefined ? initialData.gst_rate : 18.0),
            overall_discount_type: initialData.overall_discount_type || 'fixed',
            overall_discount: parseFloat(initialData.overall_discount || 0),
            additional_charges: parseFloat(initialData.additional_charges || 0),
            advance_amount: parseFloat(initialData.advance_amount || 0),
            enable_round_off: initialData.enable_round_off !== undefined ? Boolean(initialData.enable_round_off) : true,

            intro_section: initialData.intro_section || {
                salutation: 'Dear Sir,',
                greeting: 'Greetings from Priyam!!',
                paragraph1: 'We are pleased to introduce ourselves as one of the leading commercial software application vendors in India. We have a rich experience of selling, supporting & implementation of application software for more than 31 years and having a hardcore technically strong team to serve & support our prestigious clientele. We have more than 19,000+ satisfied users of Tally & other solutions in India.',
                paragraph2: 'Apart from being an Authorized "5 Star Certified Partner and GVLA Partner" of Tally Solutions Pvt Ltd, we have expanded our capabilities to deliver comprehensive Tally Applications, Multi-Branch Accounting, ERPs, Cloud Services, RPA Automation, API Integration, and Industry-Specific Solutions.',
                relationship_heading: 'At the same time, we wish to introduce our relationship and core competencies as follows:',
                capabilities: [
                    'Tally Authorized "5 Star Certified Partner"',
                    'RPA Solutions Provider & Automation Specialist',
                    'Tally on Cloud & Cloud Backup Solutions',
                    'Automobile DMS to Tally Integrations through Excel, RPA & API Integration',
                    'Tally Integrator & Customization Partner',
                    'Centralized Branch Accounting Solutions',
                    'Workflow Management Solutions',
                    'UiPrime Automation System'
                ],
                transition: 'This has reference to our detailed discussion with you regarding UiPrime Enterprise Edition.'
            },

            scope_section: initialData.scope_section || {
                heading: 'Subscription Includes:',
                items: [
                    'BOT Installation, Implementation and one-time training to manage logs.',
                    'Portal Login Automation: The automation bot will securely log in to DMS portal using provided credentials.',
                    'Excel File Retrieval: Bot systematically navigates through designated portals and downloads designated files.',
                    'Data formulation & Processing: Formatting, filtering, and merging as per final SRS document.',
                    'Online support through chat, Email and Remote Access under subscription period.',
                    'Bug Fixing in Bot automation as per final confirmation on email of SRS.'
                ]
            },

            customization_section: initialData.customization_section || {
                heading: 'Additional Customization request:',
                description: 'Various components of the change request are as follows, and the time and effort for all these are chargeable and will be taken as phase two along with the below process:',
                steps: [
                    'Requirement study',
                    'Gap Analysis',
                    'Solution design',
                    'Approvals & discussions',
                    'Development',
                    'Testing',
                    'Deployment'
                ]
            },

            system_requirements: initialData.system_requirements || {
                heading: 'System Requirements for UiPrime:',
                specs: [
                    { key: 'OS', value: 'Windows 10/11 (64-bit)' },
                    { key: 'RAM', value: '4 GB (Minimum)' },
                    { key: 'Disk', value: '5 GB (Minimum free space)' },
                    { key: 'NIC', value: 'Stable Internet Connectivity' }
                ]
            },

            terms_conditions: initialData.terms_conditions || [
                'This proposal is to be accepted along with a Mandate / Purchase Order issued in favour of Priyam Infotech Solutions Pvt Ltd, Jaipur.',
                'Correspondence Address: 401-404, Neelkanth-1, Bhawani Singh Road, C-Scheme, Jaipur-302001.',
                'Validity period: This proposal is valid for 15 days.',
                'The payment terms are as follows: 100% in advance along with PO.',
                'Prices mentioned in the proposal are exclusive of taxes. All taxes shall be charged 18% extra as applicable.',
                'Certificate relating to Tax deducted at source, if any from payments made has to be issued before Financial Year end.',
                'Customer shall provide requisite approvals, sign-offs and certificate of deliverables on a timely basis.',
                'All user security settings have to be provided before starting implementation.',
                'Hand holding support applicable only on "UiPrime Application".',
                'The basic server infrastructure like Internet, user security and DMS permissions has to be provided by client.',
                'All Credentials security has to be taken care by your team only.',
                'Any further development and modifications will be charged extra.',
                'Any customization or process change will be treated as new requirement and is chargeable on case-to-case basis.'
            ],

            payment_terms_text: initialData.payment_terms_text || 'Payment terms: 100% in advance with Order.',

            notes_section: initialData.notes_section || [
                'UiPrime Workflow Automation is dependent on DMS Portal (If DMS not opening/working data will not download).',
                'UiPrime will generate excel reports as per work flow confirmation.',
                'UiPrime is a Robotic Technology. It requires similar environment to perform. Any change in environment or process will interrupt the routine working so user needs to follow DOs & DONTs strictly else error will occur.'
            ]
        };

        this.zoomLevel = 1.0;
        this.autosaveTimer = null;
        this.init();
    }

    init() {
        this.bindTabs();
        this.bindZoom();
        this.bindEvents();
        this.renderItemRows();
        this.recalculate();
    }

    bindTabs() {
        document.querySelectorAll('.builder-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.builder-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const target = document.getElementById(btn.dataset.target);
                if (target) target.classList.add('active');
            });
        });
    }

    bindZoom() {
        document.getElementById('zoomInBtn')?.addEventListener('click', () => {
            this.zoomLevel = Math.min(1.5, this.zoomLevel + 0.1);
            this.applyZoom();
        });

        document.getElementById('zoomOutBtn')?.addEventListener('click', () => {
            this.zoomLevel = Math.max(0.4, this.zoomLevel - 0.1);
            this.applyZoom();
        });

        document.getElementById('zoomResetBtn')?.addEventListener('click', () => {
            this.zoomLevel = 1.0;
            this.applyZoom();
        });

        document.getElementById('zoomFitBtn')?.addEventListener('click', () => {
            const viewport = document.querySelector('.preview-viewport');
            if (viewport) {
                const availableWidth = viewport.clientWidth - 60;
                // A4 width in px is ~794px
                this.zoomLevel = Math.min(1.0, availableWidth / 800);
                this.applyZoom();
            }
        });

        document.getElementById('pageNavSelect')?.addEventListener('change', (e) => {
            const pageId = e.target.value;
            const el = document.getElementById(pageId);
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });
    }

    applyZoom() {
        const container = document.getElementById('a4PaperContainer');
        if (container) {
            container.style.transform = `scale(${this.zoomLevel})`;
            const label = document.getElementById('zoomLabel');
            if (label) label.textContent = `${Math.round(this.zoomLevel * 100)}%`;
        }
    }

    bindEvents() {
        // Customer Select
        document.getElementById('customerSelect')?.addEventListener('change', (e) => {
            const custId = parseInt(e.target.value);
            if (custId && this.config.customers) {
                const found = this.config.customers.find(c => c.id == custId);
                if (found) {
                    this.state.customer_id = found.id;
                    this.state.customer_snapshot = {
                        name: found.name,
                        company_name: found.company_name || found.name,
                        contact_person: found.contact_person || '',
                        email: found.email || '',
                        phone: found.phone || '',
                        billing_address: found.billing_address || '',
                        city: found.city || '',
                        state: found.state || '',
                        gstin: found.gstin || '',
                        pan: found.pan || ''
                    };
                    this.syncCustomerFields();
                    this.recalculate();
                }
            }
        });

        // Add Item Button
        document.getElementById('addItemBtn')?.addEventListener('click', () => {
            this.state.items.push({
                product_id: null,
                description: 'Custom Service / Product Item',
                hsn_sac: '998313',
                quantity: 1,
                unit: 'Nos',
                unit_price: 1000,
                discount_percent: 0,
                discount_amount: 0,
                tax_rate: this.state.gst_rate,
                billing_period: '',
                notes: ''
            });
            this.renderItemRows();
            this.recalculate();
        });

        // Form inputs binding
        document.getElementById('builderForm')?.addEventListener('input', (e) => {
            this.readFormValues();
            this.recalculate();
            this.triggerAutoSave();
        });

        // Template Selection Switcher
        document.querySelectorAll('input[name="template_id_radio"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.state.template_id = e.target.value;
                this.renderPreview();
                this.triggerAutoSave();
            });
        });

        // Terms Preset Select
        document.getElementById('termsPresetSelect')?.addEventListener('change', (e) => {
            const presetId = parseInt(e.target.value);
            if (presetId && this.config.terms_presets) {
                const found = this.config.terms_presets.find(p => p.id == presetId);
                if (found) {
                    try {
                        this.state.terms_conditions = JSON.parse(found.clauses);
                        document.getElementById('termsTextarea').value = this.state.terms_conditions.join('\n');
                        this.renderPreview();
                    } catch(err) {}
                }
            }
        });

        // Save Button Explicit Click
        document.getElementById('saveQuoteBtn')?.addEventListener('click', () => {
            this.saveQuotation(false);
        });
    }

    readFormValues() {
        this.state.quotation_number = document.getElementById('field_quote_number')?.value || this.state.quotation_number;
        this.state.ref_number = document.getElementById('field_ref_number')?.value || '';
        this.state.date = document.getElementById('field_date')?.value || this.state.date;
        this.state.valid_until = document.getElementById('field_valid_until')?.value || '';
        this.state.subject = document.getElementById('field_subject')?.value || '';
        this.state.prepared_by = document.getElementById('field_prepared_by')?.value || '';
        this.state.sales_person = document.getElementById('field_sales_person')?.value || '';
        this.state.status = document.getElementById('field_status')?.value || 'Draft';

        // Tax & summary options
        this.state.tax_type = document.getElementById('field_tax_type')?.value || 'GST';
        this.state.tax_inclusive = document.getElementById('field_tax_inclusive')?.checked || false;
        this.state.gst_rate = parseFloat(document.getElementById('field_gst_rate')?.value || 18.0);
        this.state.overall_discount = parseFloat(document.getElementById('field_overall_discount')?.value || 0);
        this.state.additional_charges = parseFloat(document.getElementById('field_additional_charges')?.value || 0);
        this.state.advance_amount = parseFloat(document.getElementById('field_advance_amount')?.value || 0);

        // Customer details
        this.state.customer_snapshot.company_name = document.getElementById('cust_company')?.value || '';
        this.state.customer_snapshot.name = document.getElementById('cust_name')?.value || '';
        this.state.customer_snapshot.contact_person = document.getElementById('cust_contact')?.value || '';
        this.state.customer_snapshot.billing_address = document.getElementById('cust_address')?.value || '';
        this.state.customer_snapshot.phone = document.getElementById('cust_phone')?.value || '';
        this.state.customer_snapshot.email = document.getElementById('cust_email')?.value || '';
        this.state.customer_snapshot.gstin = document.getElementById('cust_gstin')?.value || '';

        // Cover Letter
        this.state.intro_section.salutation = document.getElementById('intro_salutation')?.value || 'Dear Sir,';
        this.state.intro_section.greeting = document.getElementById('intro_greeting')?.value || 'Greetings from Priyam!!';
        this.state.intro_section.paragraph1 = document.getElementById('intro_p1')?.value || '';
        this.state.intro_section.paragraph2 = document.getElementById('intro_p2')?.value || '';
        this.state.intro_section.relationship_heading = document.getElementById('intro_rel_heading')?.value || '';
        this.state.intro_section.transition = document.getElementById('intro_transition')?.value || '';

        const caps = document.getElementById('intro_capabilities')?.value || '';
        this.state.intro_section.capabilities = caps.split('\n').map(s => s.trim()).filter(Boolean);

        // Scope & Inclusions
        this.state.scope_section.heading = document.getElementById('scope_heading')?.value || 'Subscription Includes:';
        const scopeItems = document.getElementById('scope_items')?.value || '';
        this.state.scope_section.items = scopeItems.split('\n').map(s => s.trim()).filter(Boolean);

        // Change Request
        this.state.customization_section.heading = document.getElementById('custom_heading')?.value || 'Additional Customization request:';
        this.state.customization_section.description = document.getElementById('custom_desc')?.value || '';
        const customSteps = document.getElementById('custom_steps')?.value || '';
        this.state.customization_section.steps = customSteps.split('\n').map(s => s.trim()).filter(Boolean);

        // System Requirements
        this.state.system_requirements.heading = document.getElementById('sysreq_heading')?.value || 'System Requirements:';
        const sysreqText = document.getElementById('sysreq_text')?.value || '';
        this.state.system_requirements.specs = sysreqText.split('\n').map(line => {
            const parts = line.split(':');
            return { key: (parts[0] || '').trim(), value: (parts.slice(1).join(':') || '').trim() };
        }).filter(s => s.key);

        // Terms
        const termsText = document.getElementById('termsTextarea')?.value || '';
        this.state.terms_conditions = termsText.split('\n').map(s => s.trim()).filter(Boolean);
        this.state.payment_terms_text = document.getElementById('field_payment_terms')?.value || '';

        // Notes
        const notesText = document.getElementById('notes_textarea')?.value || '';
        this.state.notes_section = notesText.split('\n').map(s => s.trim()).filter(Boolean);
    }

    syncCustomerFields() {
        const c = this.state.customer_snapshot;
        if (document.getElementById('cust_company')) document.getElementById('cust_company').value = c.company_name || '';
        if (document.getElementById('cust_name')) document.getElementById('cust_name').value = c.name || '';
        if (document.getElementById('cust_contact')) document.getElementById('cust_contact').value = c.contact_person || '';
        if (document.getElementById('cust_address')) document.getElementById('cust_address').value = c.billing_address || '';
        if (document.getElementById('cust_phone')) document.getElementById('cust_phone').value = c.phone || '';
        if (document.getElementById('cust_email')) document.getElementById('cust_email').value = c.email || '';
        if (document.getElementById('cust_gstin')) document.getElementById('cust_gstin').value = c.gstin || '';
    }

    renderItemRows() {
        const container = document.getElementById('itemsContainer');
        if (!container) return;

        container.innerHTML = '';
        this.state.items.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'item-row';
            card.dataset.index = idx;

            let productOptions = '<option value="">-- Choose from Catalog --</option>';
            if (this.config.products) {
                this.config.products.forEach(p => {
                    const sel = item.product_id == p.id ? 'selected' : '';
                    productOptions += `<option value="${p.id}" ${sel}>${p.name} (₹ ${p.unit_price})</option>`;
                });
            }

            card.innerHTML = `
                <div class="item-row-header">
                    <span class="item-row-number">Item #${idx + 1}</span>
                    <div class="item-row-actions">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="builder.duplicateItem(${idx})" title="Duplicate"><i class="fas fa-copy"></i></button>
                        ${this.state.items.length > 1 ? `<button type="button" class="btn btn-secondary btn-sm" style="color: var(--danger);" onclick="builder.removeItem(${idx})" title="Delete"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <select class="form-control" style="font-size: 12px; background: #f8fafc;" onchange="builder.onSelectProduct(${idx}, this.value)">
                        ${productOptions}
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <textarea class="form-control item-desc" rows="2" placeholder="Item description / particular name" oninput="builder.onItemFieldChange(${idx}, 'description', this.value)">${item.description || ''}</textarea>
                </div>

                <div class="item-grid-cols-4">
                    <div>
                        <label class="form-label" style="font-size: 11px;">Qty</label>
                        <input type="number" step="0.01" class="form-control" value="${item.quantity || 1}" oninput="builder.onItemFieldChange(${idx}, 'quantity', this.value)">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px;">Unit</label>
                        <input type="text" class="form-control" value="${item.unit || 'Nos'}" placeholder="Month/Nos" oninput="builder.onItemFieldChange(${idx}, 'unit', this.value)">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px;">Rate (₹)</label>
                        <input type="number" step="0.01" class="form-control" value="${item.unit_price || 0}" oninput="builder.onItemFieldChange(${idx}, 'unit_price', this.value)">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px;">Disc %</label>
                        <input type="number" step="0.1" class="form-control" value="${item.discount_percent || 0}" oninput="builder.onItemFieldChange(${idx}, 'discount_percent', this.value)">
                    </div>
                </div>

                <div class="item-grid-fields" style="margin-top: 8px;">
                    <div>
                        <label class="form-label" style="font-size: 11px;">Billing Period (e.g. Per Month)</label>
                        <input type="text" class="form-control" value="${item.billing_period || ''}" placeholder="Per Month (Billed Quarterly)" oninput="builder.onItemFieldChange(${idx}, 'billing_period', this.value)">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px;">HSN / SAC</label>
                        <input type="text" class="form-control" value="${item.hsn_sac || ''}" placeholder="998313" oninput="builder.onItemFieldChange(${idx}, 'hsn_sac', this.value)">
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    onSelectProduct(idx, prodId) {
        if (!prodId) return;
        const p = this.config.products.find(item => item.id == prodId);
        if (p) {
            this.state.items[idx].product_id = p.id;
            this.state.items[idx].description = p.name + (p.description ? ' – ' + p.description : '');
            this.state.items[idx].unit_price = parseFloat(p.unit_price) || 0;
            this.state.items[idx].unit = p.unit || 'Nos';
            this.state.items[idx].hsn_sac = p.hsn_sac || '';
            this.state.items[idx].discount_percent = parseFloat(p.default_discount) || 0;
            this.state.items[idx].tax_rate = parseFloat(p.default_tax_rate) || this.state.gst_rate;
            this.renderItemRows();
            this.recalculate();
            this.triggerAutoSave();
        }
    }

    onItemFieldChange(idx, field, val) {
        if (this.state.items[idx]) {
            this.state.items[idx][field] = val;
            this.recalculate();
            this.triggerAutoSave();
        }
    }

    duplicateItem(idx) {
        const copy = JSON.parse(JSON.stringify(this.state.items[idx]));
        this.state.items.splice(idx + 1, 0, copy);
        this.renderItemRows();
        this.recalculate();
        this.triggerAutoSave();
    }

    removeItem(idx) {
        if (this.state.items.length > 1) {
            this.state.items.splice(idx, 1);
            this.renderItemRows();
            this.recalculate();
            this.triggerAutoSave();
        }
    }

    recalculate() {
        const calc = CalculationEngine.calculate(this.state.items, {
            tax_type: this.state.tax_type,
            tax_inclusive: this.state.tax_inclusive,
            gst_rate: this.state.gst_rate,
            overall_discount_type: this.state.overall_discount_type,
            overall_discount: this.state.overall_discount,
            additional_charges: this.state.additional_charges,
            advance_amount: this.state.advance_amount,
            enable_round_off: this.state.enable_round_off
        });

        this.calc = calc;

        // Update Editor UI Summary
        if (document.getElementById('sum_subtotal')) document.getElementById('sum_subtotal').textContent = CalculationEngine.formatCurrency(calc.subtotal);
        if (document.getElementById('sum_discounts')) document.getElementById('sum_discounts').textContent = CalculationEngine.formatCurrency(calc.total_discount);
        if (document.getElementById('sum_taxable')) document.getElementById('sum_taxable').textContent = CalculationEngine.formatCurrency(calc.taxable_amount);

        const taxBreakdownEl = document.getElementById('sum_tax_breakdown');
        if (taxBreakdownEl) {
            if (calc.tax_type === 'GST') {
                taxBreakdownEl.innerHTML = `
                    <div style="display:flex; justify-content:space-between;"><span>CGST (${calc.cgst_rate}%):</span><span>${CalculationEngine.formatCurrency(calc.cgst_amount)}</span></div>
                    <div style="display:flex; justify-content:space-between;"><span>SGST (${calc.sgst_rate}%):</span><span>${CalculationEngine.formatCurrency(calc.sgst_amount)}</span></div>
                `;
            } else {
                taxBreakdownEl.innerHTML = `
                    <div style="display:flex; justify-content:space-between;"><span>IGST (${calc.igst_rate}%):</span><span>${CalculationEngine.formatCurrency(calc.igst_amount)}</span></div>
                `;
            }
        }

        if (document.getElementById('sum_grand_total')) document.getElementById('sum_grand_total').textContent = CalculationEngine.formatCurrency(calc.grand_total);
        if (document.getElementById('sum_round_off')) document.getElementById('sum_round_off').textContent = CalculationEngine.formatCurrency(calc.round_off);
        if (document.getElementById('sum_words')) document.getElementById('sum_words').textContent = calc.amount_in_words;

        // Render Live A4 Canvas
        this.renderPreview();
    }

    renderPreview() {
        const container = document.getElementById('a4PaperContainer');
        if (!container) return;

        const s = this.state;
        const b = s.business_snapshot;
        const c = s.customer_snapshot;
        const calc = this.calc;

        const primary = b.primary_color || '#0d5c75';
        const secondary = b.secondary_color || '#85a438';
        const font = b.font_preference || 'Inter';

        // Check template
        const isClassic = s.template_id === 'classic';

        // Generate Item Rows HTML
        let itemRowsHtml = '';
        calc.items.forEach(it => {
            const periodStr = it.billing_period ? ` <span style="color:#64748b; font-weight:normal;">(${it.billing_period})</span>` : '';
            itemRowsHtml += `
                <tr>
                    <td style="padding: 8px 10px; border: 1px solid #cbd5e1;">
                        <strong style="color: #0f172a;">${this.escape(it.description)}</strong>
                        ${it.notes ? `<div style="font-size: 11px; color: #64748b; margin-top: 4px;">${this.escape(it.notes)}</div>` : ''}
                    </td>
                    <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; white-space: nowrap; font-weight: 600;">
                        ${CalculationEngine.formatCurrency(it.line_total)} ${periodStr}
                    </td>
                </tr>
            `;
        });

        // Notes HTML
        let notesHtml = '';
        if (s.notes_section && s.notes_section.length > 0) {
            notesHtml = `
                <div style="margin-top: 10px; font-size: 11.5px; color: #334155; line-height: 1.5;">
                    <strong style="color: ${primary};">Note: -</strong>
                    <ul style="margin: 4px 0 10px 18px; list-style-type: disc;">
                        ${s.notes_section.map(n => `<li>${this.escape(n)}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        // Scope List HTML
        let scopeHtml = '';
        if (s.scope_section && s.scope_section.items && s.scope_section.items.length > 0) {
            scopeHtml = `
                <div class="scope-box" style="border-left: 3px solid ${primary};">
                    <h4 style="color: ${primary};">${this.escape(s.scope_section.heading || 'Subscription Includes:')}</h4>
                    <ul>
                        ${s.scope_section.items.map(sc => `<li>${this.escape(sc)}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        // Change Process HTML
        let changeReqHtml = '';
        if (s.customization_section && s.customization_section.steps && s.customization_section.steps.length > 0) {
            changeReqHtml = `
                <div style="margin-bottom: 14px; font-size: 11.5px;">
                    <strong style="color: ${primary}; font-size: 12px;">${this.escape(s.customization_section.heading || 'Additional Customization request:')}</strong>
                    <div style="margin-top: 3px; color: #475569;">${this.escape(s.customization_section.description || '')}</div>
                    <ul style="margin: 4px 0 0 18px; list-style-type: square; color: #334155;">
                        ${s.customization_section.steps.map(st => `<li>${this.escape(st)}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        // System Requirements HTML
        let sysReqHtml = '';
        if (s.system_requirements && s.system_requirements.specs && s.system_requirements.specs.length > 0) {
            sysReqHtml = `
                <div style="margin-bottom: 14px;">
                    <strong style="color: ${primary}; font-size: 12px;">${this.escape(s.system_requirements.heading || 'System Requirements:')}</strong>
                    <table class="sys-req-table" style="margin-top: 6px;">
                        <tbody>
                            ${s.system_requirements.specs.map(spec => `
                                <tr>
                                    <td class="key" style="color: ${primary}; font-weight: 700; width: 30%;">${this.escape(spec.key)}:</td>
                                    <td>${this.escape(spec.value)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        // Terms Clauses HTML
        let termsListHtml = '';
        if (s.terms_conditions && s.terms_conditions.length > 0) {
            termsListHtml = s.terms_conditions.map((term, i) => `<li>${this.escape(term)}</li>`).join('');
        }

        // Capabilities List HTML (Page 1)
        let capListHtml = '';
        if (s.intro_section && s.intro_section.capabilities && s.intro_section.capabilities.length > 0) {
            capListHtml = s.intro_section.capabilities.map(cap => `<li>${this.escape(cap)}</li>`).join('');
        }

        // Company Logo & Partner Logo
        const companyLogo = b.logo_url ? `<img src="${window.BASE_URL}/${b.logo_url}" class="proposal-logo-main" alt="Logo">` : `<div style="font-family: Outfit; font-size: 22px; font-weight: 700; color: ${primary};">${this.escape(b.company_name)}</div>`;
        const partnerLogo = b.secondary_logo_url ? `<img src="${window.BASE_URL}/${b.secondary_logo_url}" class="proposal-partner-logo" alt="Partner">` : '';

        // Digital Signature
        const signatureImg = b.signature_url ? `<img src="${window.BASE_URL}/${b.signature_url}" class="signature-image" alt="Signature">` : '';

        // Clean A4 Multi-Page Replica
        container.innerHTML = `
            <!-- PAGE 1: Introductory Letter & Credentials -->
            <div class="a4-page page-1" id="page-1" style="font-family: ${font}, sans-serif;">
                <span class="page-break-tag">Page 1 of 3</span>

                <!-- Header Branding -->
                <div class="proposal-header" style="border-bottom-color: ${primary};">
                    <div class="proposal-brand-left">
                        ${companyLogo}
                    </div>
                    <div class="proposal-brand-badges">
                        ${partnerLogo}
                    </div>
                </div>

                <!-- Reference & Date Grid -->
                <div class="proposal-meta-grid">
                    <div><strong style="color: ${primary};">Ref. No.:</strong> ${this.escape(s.quotation_number)}</div>
                    <div><strong style="color: ${primary};">Date:</strong> ${this.formatDate(s.date)}</div>
                </div>

                <!-- Recipient Address Box -->
                <div class="proposal-recipient-box" style="border-left-color: ${primary};">
                    <div style="font-weight: 700; color: ${primary};">To,</div>
                    <div style="font-weight: 700; font-size: 13px;">${this.escape(c.company_name || c.name || '{Company Name}')}</div>
                    ${c.contact_person ? `<div>Attn: ${this.escape(c.contact_person)}</div>` : ''}
                    <div>${this.escape(c.billing_address || '{Address}')}</div>
                    ${c.gstin ? `<div><strong>GSTIN:</strong> ${this.escape(c.gstin)}</div>` : ''}
                </div>

                <!-- Subject Line -->
                <div class="proposal-subject-line">
                    Subject: - ${this.escape(s.subject || 'Commercial Proposal & Quotation')}
                </div>

                <!-- Cover Letter -->
                <div class="proposal-intro-letter">
                    <p><strong>${this.escape(s.intro_section.salutation || 'Dear Sir,')}</strong></p>
                    <p style="font-weight: 600; color: ${primary};">${this.escape(s.intro_section.greeting || 'Greetings from Priyam!!')}</p>
                    <p>${this.escape(s.intro_section.paragraph1 || '')}</p>
                    <p>${this.escape(s.intro_section.paragraph2 || '')}</p>

                    <div style="margin-top: 10px; font-weight: 600; color: ${primary};">
                        ${this.escape(s.intro_section.relationship_heading || '')}
                    </div>
                    <ul class="capabilities-list" style="color: #334155;">
                        ${capListHtml}
                    </ul>

                    <p style="margin-top: 12px; font-style: italic; color: #475569;">
                        ${this.escape(s.intro_section.transition || '')}
                    </p>
                </div>

                <!-- Footer Bar -->
                <div class="proposal-footer-banner" style="border-top-color: ${secondary};">
                    <div>
                        <strong>${this.escape(b.company_name)}</strong> &bull; ${this.escape(b.phone || '')} &bull; ${this.escape(b.email || '')}
                    </div>
                    <div>Page 1</div>
                </div>
            </div>

            <!-- PAGE 2: Commercial Proposal Table & Technical Scope -->
            <div class="a4-page page-2" id="page-2" style="font-family: ${font}, sans-serif;">
                <span class="page-break-tag">Page 2 of 3</span>

                <!-- Header Branding -->
                <div class="proposal-header" style="border-bottom-color: ${primary};">
                    <div class="proposal-brand-left">
                        ${companyLogo}
                    </div>
                    <div style="font-size: 11px; text-align: right; color: #64748b;">
                        Ref: ${this.escape(s.quotation_number)}<br>
                        Date: ${this.formatDate(s.date)}
                    </div>
                </div>

                <div style="font-family: Outfit; font-size: 14px; font-weight: 700; color: ${primary}; margin-bottom: 8px;">
                    Commercial for ${this.escape(s.subject || 'UiPrime')}
                </div>

                <!-- Commercial Proposal Table -->
                <table class="commercial-table">
                    <thead>
                        <tr>
                            <th style="background: ${primary}; border-color: ${primary};">Particular</th>
                            <th style="background: ${primary}; border-color: ${primary}; width: 35%; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemRowsHtml}
                        <tr>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1;">
                                ${scopeHtml}
                            </td>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; font-weight: 600;">
                                Included
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; font-weight: 600;">
                                ${calc.tax_type === 'GST' ? `GST Extra as Applicable (${calc.gst_rate}%)` : `IGST (${calc.gst_rate}%)`}
                            </td>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; font-weight: 600;">
                                ${CalculationEngine.formatCurrency(calc.total_tax)}
                            </td>
                        </tr>
                        <tr style="background: #f1f5f9; font-weight: 700; font-size: 12.5px;">
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; color: ${primary};">Grand Total</td>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; color: ${primary};">
                                ${CalculationEngine.formatCurrency(calc.grand_total)}
                            </td>
                        </tr>
                    </tbody>
                </table>

                ${notesHtml}
                ${changeReqHtml}
                ${sysReqHtml}

                <!-- Footer Bar -->
                <div class="proposal-footer-banner" style="border-top-color: ${secondary};">
                    <div>
                        <strong>${this.escape(b.company_name)}</strong> &bull; ${this.escape(b.phone || '')} &bull; ${this.escape(b.email || '')}
                    </div>
                    <div>Page 2</div>
                </div>
            </div>

            <!-- PAGE 3: Terms, Banking, Authorization -->
            <div class="a4-page page-3" id="page-3" style="font-family: ${font}, sans-serif;">
                <span class="page-break-tag">Page 3 of 3</span>

                <!-- Header Branding -->
                <div class="proposal-header" style="border-bottom-color: ${primary};">
                    <div class="proposal-brand-left">
                        ${companyLogo}
                    </div>
                    <div style="font-size: 11px; text-align: right; color: #64748b;">
                        Ref: ${this.escape(s.quotation_number)}<br>
                        Date: ${this.formatDate(s.date)}
                    </div>
                </div>

                <div style="font-family: Outfit; font-size: 13.5px; font-weight: 700; color: ${primary}; margin-bottom: 8px;">
                    Terms and Condition: -
                </div>

                <!-- Terms Clauses List -->
                <ol class="terms-ordered-list">
                    ${termsListHtml}
                </ol>

                <!-- Payment Terms Box -->
                <div style="margin-bottom: 12px; font-size: 11.5px;">
                    <strong style="color: ${primary};">Payment Terms:</strong>
                    <div style="color: #334155; margin-top: 2px;">${this.escape(s.payment_terms_text)}</div>
                </div>

                <!-- Bank Info Box -->
                <div class="bank-info-box" style="border-color: ${primary};">
                    <div style="font-weight: 700; color: ${primary}; margin-bottom: 4px;">Payment should be made in favor of:</div>
                    <div style="font-size: 12px; font-weight: 600;">${this.escape(b.account_name || b.company_name)}</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 6px; font-size: 11px;">
                        <div><strong>Bank Name:</strong> ${this.escape(b.bank_name || 'HDFC Bank Ltd.')}</div>
                        <div><strong>Acc. No.:</strong> <code>${this.escape(b.account_number || '50200099887766')}</code></div>
                        <div><strong>IFSC Code:</strong> <code>${this.escape(b.ifsc_code || 'HDFC0001585')}</code></div>
                        <div><strong>Branch:</strong> ${this.escape(b.branch || 'Jaipur')}</div>
                        ${b.upi_id ? `<div><strong>UPI VPA:</strong> ${this.escape(b.upi_id)}</div>` : ''}
                    </div>
                </div>

                <!-- Closing & Signature -->
                <div style="margin-top: 14px; font-size: 11.5px; color: #475569;">
                    Thanking you & assuring you best of our attention & services at all the times. Waiting for your valued order.
                </div>

                <div class="signature-block">
                    <div class="signature-card">
                        <div style="font-size: 11px; color: #64748b;">Sincerely Yours,</div>
                        <div style="font-weight: 700; color: ${primary}; margin-bottom: 6px;">For ${this.escape(b.company_name)}</div>
                        ${signatureImg}
                        <div style="font-weight: 700; font-size: 12px;">${this.escape(b.signatory_name || '{Executive Name}')}</div>
                        <div style="font-size: 11px; color: #64748b;">${this.escape(b.signatory_designation || '{Designation}')}</div>
                        <div style="font-size: 11px; color: #64748b;">${this.escape(b.phone || '')}</div>
                        <div style="font-size: 11px; color: #64748b;">${this.escape(b.email || '')}</div>
                    </div>
                </div>

                <!-- Footer Banner with QR & Corporate Contact -->
                <div class="proposal-footer-banner" style="border-top-color: ${secondary};">
                    <div>
                        <div style="font-weight: 700; color: ${primary};">${this.escape(b.company_name)}</div>
                        <div>${this.escape(b.address || '')}, ${this.escape(b.city || '')} - ${this.escape(b.pincode || '')}</div>
                        <div>Phone: ${this.escape(b.phone || '')} &bull; Email: ${this.escape(b.email || '')}</div>
                    </div>
                    <div>Page 3</div>
                </div>
            </div>
        `;
    }

    triggerAutoSave() {
        const badge = document.getElementById('autosaveBadge');
        if (badge) {
            badge.className = 'autosave-status saving';
            badge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        clearTimeout(this.autosaveTimer);
        this.autosaveTimer = setTimeout(() => {
            this.saveQuotation(true);
        }, 1500);
    }

    async saveQuotation(isAuto = false) {
        this.readFormValues();
        const payload = {
            id: this.state.id,
            quotation_number: this.state.quotation_number,
            ref_number: this.state.ref_number,
            date: this.state.date,
            valid_until: this.state.valid_until,
            subject: this.state.subject,
            currency: this.state.currency,
            status: this.state.status,
            template_id: this.state.template_id,
            prepared_by: this.state.prepared_by,
            sales_person: this.state.sales_person,
            customer_id: this.state.customer_id,
            customer_snapshot: this.state.customer_snapshot,
            business_snapshot: this.state.business_snapshot,
            items: this.state.items,
            tax_type: this.state.tax_type,
            tax_inclusive: this.state.tax_inclusive,
            gst_rate: this.state.gst_rate,
            overall_discount_type: this.state.overall_discount_type,
            overall_discount: this.state.overall_discount,
            additional_charges: this.state.additional_charges,
            advance_amount: this.state.advance_amount,
            enable_round_off: this.state.enable_round_off,
            intro_section: this.state.intro_section,
            scope_section: this.state.scope_section,
            customization_section: this.state.customization_section,
            system_requirements: this.state.system_requirements,
            terms_conditions: this.state.terms_conditions,
            payment_terms_text: this.state.payment_terms_text,
            notes_section: this.state.notes_section
        };

        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/quotation.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.state.id = data.quotation_id;
                this.state.quotation_number = data.quotation_number;
                if (document.getElementById('field_quote_number')) {
                    document.getElementById('field_quote_number').value = data.quotation_number;
                }

                const badge = document.getElementById('autosaveBadge');
                if (badge) {
                    badge.className = 'autosave-status saved';
                    badge.innerHTML = `<i class="fas fa-check-circle"></i> Saved (${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})`;
                }

                if (!isAuto) {
                    showToast('Quotation saved successfully!', 'success');
                }
            } else {
                if (!isAuto) showToast(data.message || 'Error saving quotation', 'error');
            }
        } catch(err) {
            console.error('Save error', err);
            const badge = document.getElementById('autosaveBadge');
            if (badge) {
                badge.className = 'autosave-status';
                badge.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i> Offline';
            }
        }
    }

    escape(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
}

window.QuotationBuilder = QuotationBuilder;
