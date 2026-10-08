/**
 * Quotation Studio - Product & Service Catalog JavaScript Helpers
 */

const Products = {
    /**
     * Search products by name, SKU or category
     * @param {string} query
     * @returns {Promise<Array>}
     */
    async search(query) {
        try {
            const url = query ? `${window.BASE_URL || ''}/api/product.php?search=${encodeURIComponent(query)}` : `${window.BASE_URL || ''}/api/product.php`;
            const res = await fetch(url);
            const data = await res.json();
            return data.success ? data.products : [];
        } catch (err) {
            console.error('Product search error:', err);
            return [];
        }
    },

    /**
     * Fetch single product by ID
     * @param {number|string} id
     * @returns {Promise<Object|null>}
     */
    async get(id) {
        if (!id) return null;
        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/product.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            return data.success ? data.product : null;
        } catch (err) {
            console.error('Error fetching product:', err);
            return null;
        }
    }
};

window.Products = Products;
