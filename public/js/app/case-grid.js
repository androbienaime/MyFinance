window.caseGrid = function ({ duration, price, paid, statePath }) {
    return {
        duration,
        price,
        paid,
        statePath,
        selected: [],
        targetAmount: null,
        manualInput: '',

        init() {
            this.selected = [];
        },

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

        sync() {
            this.$wire.set(this.statePath, this.selected);
        },

        toggle(n) {
            if (this.paid.includes(n)) return;
            if (this.selected.includes(n)) {
                this.selected = this.selected.filter(x => x !== n);
            } else {
                this.selected.push(n);
            }
            this.sync();
        },

        addManual() {
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
            this.sync();
        },

        addCase(n) {
            const max = this.duration * 30;
            if (n < 1 || n > max) return;
            if (this.paid.includes(n)) return;
            if (this.selected.includes(n)) return;
            this.selected.push(n);
        },

        generate() {
            const target = parseInt(this.targetAmount, 10);
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
            this.sync();
        },
    };
};


// alert(window.caseGrid(3, 50, 2500, ''));