(function (window) {
    function formatDate(value) {
        if (!value) return 'Just now';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    async function responseJson(response) {
        const text = await response.text();

        try {
            return text ? JSON.parse(text) : {};
        } catch (error) {
            return {
                success: response.ok,
                message: text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
            };
        }
    }

    window.ContractUi = {
        formatDate: formatDate,
        escapeHtml: escapeHtml,
        responseJson: responseJson
    };
})(window);
