(function () {
    // 1) Pré-remplir localStorage si la valeur est absente ou invalide
    try {
        var key = 'collapsedGroups';
        var raw = localStorage.getItem(key);
        var valid = false;
        if (raw !== null) {
            try { valid = Array.isArray(JSON.parse(raw)); } catch (e) {}
        }
        if (!valid) { localStorage.setItem(key, '[]'); }
    } catch (e) {}

    // 2) Corriger le store lui-même, qu'il existe déjà ou non au moment de alpine:init
    function patch(store) {
        if (store && (store.collapsedGroups === null || store.collapsedGroups === undefined)) {
            store.collapsedGroups = [];
        }
    }

    document.addEventListener('alpine:init', function () {
        var A = window.Alpine;
        if (!A || typeof A.store !== 'function') { return; }

        var original = A.store;
        A.store = function (name, value) {
            var result = original.apply(this, arguments);
            if (name === 'sidebar' && value !== undefined) {
                patch(original.call(A, 'sidebar'));
            }
            return result;
        };

        patch(original.call(A, 'sidebar')); // cas où le store est déjà enregistré
    });
})();