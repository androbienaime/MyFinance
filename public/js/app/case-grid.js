window.caseGrid = function ({ duration, price, paid, readonly = false }) {
    return {
        duration,
        price,
        paid,
        readonly,
        selected: [],
        targetAmount: null,
        manualInput: '',

        tagPalette: [
            'bg-indigo-500',
            'bg-emerald-500',
            'bg-amber-500',
            'bg-rose-500',
            'bg-sky-500',
            'bg-fuchsia-500',
            'bg-teal-500',
            'bg-orange-500',
        ],

        get months() {
            return Array.from({ length: this.duration }, (_, i) => i + 1);
        },

        casesInMonth(month) {
            const start = (month - 1) * 30 + 1;
            return Array.from({ length: 30 }, (_, i) => start + i);
        },

        get total() {
            return this.selected.reduce((sum, n) => sum + n * this.price, 0);
        },

        tagColor(index) {
            return this.tagPalette[index % this.tagPalette.length];
        },

        toggle(n) {
            // Verrou principal : meme si @click reste branche par erreur
            // sur une case, ou si toggle() est appele depuis la console,
            // aucune mutation de 'selected' n'est possible en lecture seule.
            if (this.readonly) return;
            if (this.paid.includes(n)) return;

            if (this.selected.includes(n)) {
                this.selected = this.selected.filter(x => x !== n);
            } else {
                this.selected.push(n);
            }
        },

        reset() {
            if (this.readonly) return;
            this.selected = [];
        },

        addManual() {
            if (this.readonly) return;

            const value = this.manualInput.trim();
            if (!value) return;

            if (value.includes('-')) {
                const [start, end] = value.split('-').map(v => parseInt(v.trim(), 10));
                if (!isNaN(start) && !isNaN(end) && start <= end) {
                    for (let n = start; n <= end; n++) this.addCase(n);
                }
            } else {
                const n = parseInt(value, 10);
                if (!isNaN(n)) this.addCase(n);
            }

            this.manualInput = '';
        },

        addCase(n) {
            if (this.readonly) return;
            const max = this.duration * 30;
            if (n < 1 || n > max) return;
            if (this.paid.includes(n)) return;
            if (this.selected.includes(n)) return;
            this.selected.push(n);
        },

        generate(externalTarget = null) {
            if (this.readonly) return;
            const target = parseInt(externalTarget ?? this.targetAmount, 10);
            if (!target || target <= 0) return;

            const max = this.duration * 30;
            const unavailable = new Set([...this.paid, ...this.selected]);
            const candidates = [];

            for (let n = 1; n <= max; n++) {
                if (!unavailable.has(n)) candidates.push(n);
            }

            candidates.sort((a, b) => b - a);

            const reachable = new Array(target + 1).fill(null);
            reachable[0] = [];

            for (const n of candidates) {
                const value = n * this.price;
                if (value > target) continue;

                for (let sum = target - value; sum >= 0; sum--) {
                    if (reachable[sum] !== null && reachable[sum + value] === null) {
                        reachable[sum + value] = [...reachable[sum], n];
                    }
                }
            }

            let bestSum = 0;
            for (let sum = target; sum >= 0; sum--) {
                if (reachable[sum] !== null) { bestSum = sum; break; }
            }

            for (const n of reachable[bestSum]) this.addCase(n);

            this.targetAmount = null;
        },
    };
};