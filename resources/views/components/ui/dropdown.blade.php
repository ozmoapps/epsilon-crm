@props([
    'align' => 'right',
    'width' => 'w-48',
])

<div
    x-data="{
        open: false,
        styles: '',
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.position());
                this.bind();
            } else {
                this.unbind();
            }
        },
        close() {
            this.open = false;
            this.unbind();
        },
        position() {
            const btn = this.$refs.button;
            const menu = this.$refs.menu;
            if (!btn || !menu) return;

            const b = btn.getBoundingClientRect();
            const m = menu.getBoundingClientRect();

            const padding = 8;
            const gap = 8;

            let top = b.bottom + gap;

            let left;
            if ('{{ $align }}' === 'left') {
                left = b.left;
            } else {
                left = b.right - m.width;
            }

            if (left < padding) left = padding;
            if (left + m.width > window.innerWidth - padding) {
                left = window.innerWidth - padding - m.width;
            }

            if (top + m.height > window.innerHeight - padding) {
                top = b.top - gap - m.height;
            }
            if (top < padding) top = padding;

            this.styles = `top:${top}px; left:${left}px;`;
        },
        bind() {
            this._onScroll = () => this.position();
            this._onResize = () => this.position();
            window.addEventListener('scroll', this._onScroll, true);
            window.addEventListener('resize', this._onResize);
        },
        unbind() {
            if (this._onScroll) window.removeEventListener('scroll', this._onScroll, true);
            if (this._onResize) window.removeEventListener('resize', this._onResize);
            this._onScroll = null;
            this._onResize = null;
        }
    }"
    class="relative inline-block text-left"
>
    <div x-ref="button" @click="toggle" x-bind:aria-expanded="open.toString()">
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            x-ref="menu"
            @click.outside="close"
            @keydown.escape.window="close"
            x-transition.opacity.duration.120ms
            x-bind:style="styles"
            class="fixed z-[9999]"
        >
            <div class="{{ $width }} overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft ring-1 ring-black/5">
                {{ $content }}
            </div>
        </div>
    </template>
</div>
