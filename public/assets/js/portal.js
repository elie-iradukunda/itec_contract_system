(function (window) {
    const config = window.contractPortalConfig || {};
    const pages = window.ContractPages || {};
    const context = {
        basePath: config.basePath || '/itec_contract_system',
        query: new URLSearchParams(window.location.search),
        ui: window.ContractUi,
        store: window.ContractDemoStore.create(config.basePath || '/itec_contract_system')
    };

    ['initContractsPage', 'initClientPortal', 'initSignaturePage'].forEach(function (method) {
        if (typeof pages[method] === 'function') {
            pages[method](context);
        }
    });
})(window);
