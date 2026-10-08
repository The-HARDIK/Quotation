/**
 * Quotation Studio - Customer Management JavaScript Helpers
 */

const Customers = {
    /**
     * Search customers asynchronously
     * @param {string} query
     * @returns {Promise<Array>}
     */
    async search(query) {
        if (!query || query.length < 2) return [];
        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/customer.php?search=${encodeURIComponent(query)}`);
            const data = await res.json();
            return data.success ? data.customers : [];
        } catch (err) {
            console.error('Customer search error:', err);
            return [];
        }
    },

    /**
     * Fetch full customer details by ID
     * @param {number|string} id
     * @returns {Promise<Object|null>}
     */
    async get(id) {
        if (!id) return null;
        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/customer.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            return data.success ? data.customer : null;
        } catch (err) {
            console.error('Error fetching customer:', err);
            return null;
        }
    },

    /**
     * Quick save a customer from builder modal
     * @param {Object} customerData
     * @returns {Promise<Object>}
     */
    async create(customerData) {
        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/customer.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(customerData)
            });
            return await res.json();
        } catch (err) {
            console.error('Error creating customer:', err);
            return { success: false, message: err.message };
        }
    }
};

window.Customers = Customers;
