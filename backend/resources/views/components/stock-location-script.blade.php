<script>
window.erpStockLocations = window.erpStockLocations || {
    data: null,
    promise: null,
    async load() {
        if (this.data) return this.data;
        if (!this.promise) {
            this.promise = fetch(`${window.APP_BASE_URL}/api/stock-locations`, {
                headers: { Accept: 'application/json' }
            }).then(async response => {
                if (!response.ok) throw new Error(`No se pudieron cargar las ubicaciones (HTTP ${response.status}).`);
                this.data = await response.json();
                return this.data;
            }).finally(() => { this.promise = null; });
        }
        return this.promise;
    },
    async bind(branchSelect) {
        if (!branchSelect || branchSelect._stockLocationBound) return;
        branchSelect._stockLocationBound = true;

        try {
            const branches = await this.load();
            const selectedBranch = branchSelect.dataset.selected ?? branchSelect.value;
            const allowEmpty = branchSelect.hasAttribute('data-stock-allow-empty');
            branchSelect.innerHTML = allowEmpty ? '<option value="">Todas las sucursales</option>' : '';
            branches.forEach(branch => branchSelect.add(new Option(branch.name, branch.name)));
            if (selectedBranch && [...branchSelect.options].some(option => option.value === selectedBranch)) {
                branchSelect.value = selectedBranch;
            }

            const warehouseId = branchSelect.dataset.warehouseTarget;
            const warehouseSelect = warehouseId ? document.getElementById(warehouseId) : null;
            if (!warehouseSelect) return;

            const selectedWarehouse = warehouseSelect.dataset.selected ?? warehouseSelect.value;
            const renderWarehouses = () => {
                const branch = branches.find(item => item.name === branchSelect.value);
                const warehouseAllowEmpty = warehouseSelect.hasAttribute('data-stock-allow-empty');
                warehouseSelect.innerHTML = warehouseAllowEmpty ? '<option value="">Todos los depósitos</option>' : '';
                (branch?.warehouses ?? []).forEach(warehouse =>
                    warehouseSelect.add(new Option(warehouse.name, warehouse.name))
                );
                if (selectedWarehouse && [...warehouseSelect.options].some(option => option.value === selectedWarehouse)) {
                    warehouseSelect.value = selectedWarehouse;
                }
            };

            branchSelect.addEventListener('change', renderWarehouses);
            renderWarehouses();
        } catch (error) {
            branchSelect.innerHTML = `<option value="">${error.message}</option>`;
        }
    },
    scan(root = document) {
        root.querySelectorAll?.('select[data-stock-branch]').forEach(select => this.bind(select));
    }
};

document.addEventListener('DOMContentLoaded', () => window.erpStockLocations.scan());
new MutationObserver(records => records.forEach(record =>
    record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.matches?.('select[data-stock-branch]')) window.erpStockLocations.bind(node);
            window.erpStockLocations.scan(node);
        }
    })
)).observe(document.documentElement, { childList: true, subtree: true });
</script>
