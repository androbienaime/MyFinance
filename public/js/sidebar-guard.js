(function () {
    try {
        var key = 'collapsedGroups';
        var raw = localStorage.getItem(key);
        var valid = false;
        if (raw !== null) {
            try { valid = Array.isArray(JSON.parse(raw)); } catch (e) {}
        }
        if (!valid) {
            localStorage.setItem(key, '[]');
        }
    } catch (e) {}
})();