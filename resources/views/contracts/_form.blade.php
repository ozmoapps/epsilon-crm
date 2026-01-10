<div class="space-y-8">
    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
        <h3 class="text-sm font-semibold tracking-wide text-slate-700">{{ __('Genel Bilgiler') }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="issued_at" :value="__('Düzenleme Tarihi')" />
                <x-input id="issued_at" name="issued_at" type="date" class="mt-1" :value="old('issued_at', optional($contract->issued_at)->toDateString())" required />
                <x-input-error :messages="$errors->get('issued_at')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="locale" :value="__('Dil')" />
                <x-select id="locale" name="locale" class="mt-1" required>
                    @foreach ($locales as $value => $label)
                        <option value="{{ $value }}" @selected(old('locale', $contract->locale ?? 'tr') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('locale')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="contract_template_id" :value="__('Şablon')" />
                <x-select id="contract_template_id" name="contract_template_id" class="mt-1">
                    <option value="">{{ __('Varsayılan (dile göre)') }}</option>
                    @foreach ($templates ?? [] as $template)
                        <option value="{{ $template->id }}" @selected((int) old('contract_template_id', $contract->contract_template_id) === $template->id)>
                            {{ $template->name }}
                            @if ($template->is_default)
                                ({{ __('Varsayılan') }})
                            @endif
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('contract_template_id')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-100 bg-white p-4">
        <h3 class="text-sm font-semibold tracking-wide text-slate-700">{{ __('Koşullar') }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="payment_terms" :value="__('Ödeme Şartları')" />
                <x-textarea id="payment_terms" name="payment_terms" rows="4" class="mt-1">{{ old('payment_terms', $contract->payment_terms ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="warranty_terms" :value="__('Garanti Şartları')" />
                <x-textarea id="warranty_terms" name="warranty_terms" rows="4" class="mt-1">{{ old('warranty_terms', $contract->warranty_terms ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('warranty_terms')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="scope_text" :value="__('Kapsam')" />
                <x-textarea id="scope_text" name="scope_text" rows="4" class="mt-1">{{ old('scope_text', $contract->scope_text ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('scope_text')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="exclusions_text" :value="__('Hariç Tutulanlar')" />
                <x-textarea id="exclusions_text" name="exclusions_text" rows="4" class="mt-1">{{ old('exclusions_text', $contract->exclusions_text ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('exclusions_text')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="delivery_terms" :value="__('Teslim Şartları')" />
                <x-textarea id="delivery_terms" name="delivery_terms" rows="4" class="mt-1">{{ old('delivery_terms', $contract->delivery_terms ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('delivery_terms')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
