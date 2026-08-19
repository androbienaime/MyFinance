<div
    x-data="{
        output: '',
        running: true,
        init() {
            this.poll();
        },
        async poll() {
            try {
                const res = await fetch('{{ route('system-updates.log') }}');
                const data = await res.json();
                this.output = data.content;
                this.running = data.running;
            } catch (e) {}

            if (this.running) {
                setTimeout(() => this.poll(), 1500);
            }
        }
    }"
    class="rounded-md bg-gray-950 p-4"
>
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs text-gray-400" x-text="running ? 'En cours...' : 'Terminé'"></span>
        <span
            class="inline-block h-2 w-2 rounded-full"
            :class="running ? 'bg-yellow-400 animate-pulse' : 'bg-green-500'"
        ></span>
    </div>
    <pre
        x-text="output"
        class="text-xs text-green-400 font-mono whitespace-pre-wrap max-h-96 overflow-y-auto"
        x-init="$watch('output', () => $el.scrollTop = $el.scrollHeight)"
    ></pre>
</div>