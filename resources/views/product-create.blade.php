@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
<div class="page">
    <div class="page-title">Add Product</div>
    <div class="page-sub">Add a new item to the menu</div>

    @if($errors->any())
        <div class="flash flash-error">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <form method="POST" action="/products" id="product-form">
            @csrf

            <div class="order-grid">

                {{-- LEFT: Product details --}}
                <div>
                    <div class="field-group">
                        <label class="field-label" for="product_name">Product name</label>
                        <input type="text" name="product_name" id="product_name"
                               class="field-select select-full"
                               value="{{ old('product_name') }}"
                               required
                               placeholder="e.g. Don Americano">
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="base_price">Base price (₱)</label>
                        <div class="price-wrap">
                            <!-- <span class="price-prefix">₱</span> -->
                            <input type="number" name="base_price" id="base_price"
                                   class="field-select price-input"
                                   value="{{ old('base_price') }}"
                                   required min="1" step="0.01"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="field-group field-group-0">
                        <label class="field-label" for="temperature">Temperature</label>
                        <select name="temperature" id="temperature" class="field-select select-full" required>
                            <option value="HOT"  {{ old('temperature') === 'HOT'  ? 'selected' : '' }}>☕ Hot</option>
                            <option value="COLD" {{ old('temperature') === 'COLD' ? 'selected' : '' }}>🧊 Cold</option>
                        </select>
                    </div>
                </div>

                {{-- RIGHT: Ingredients --}}
                <div>
                    <div class="summary-label">Ingredients</div>
                    <div class="ing-helper">
                        Tick the ingredients this product uses and set the quantity per serving.
                    </div>

                    <div id="ingredient-list">
                        @foreach($ingredients as $ingredient)
                        <div class="menu-item ing-row">
                            <div class="ing-check-wrap">
                                <input type="checkbox"
                                       name="ingredients[{{ $ingredient->ingredient_id }}][selected]"
                                       value="1"
                                       id="ing-{{ $ingredient->ingredient_id }}"
                                       onchange="toggleQty({{ $ingredient->ingredient_id }})"
                                       class="ing-checkbox">
                                <label for="ing-{{ $ingredient->ingredient_id }}" class="ing-label">
                                    {{ $ingredient->ingredient_name }}
                                    <span class="ing-unit-text">({{ $ingredient->unit }})</span>
                                </label>
                            </div>
                            <div id="qty-wrap-{{ $ingredient->ingredient_id }}" class="ing-qty-hidden">
                                <input type="number"
                                       name="ingredients[{{ $ingredient->ingredient_id }}][quantity_used]"
                                       id="qty-{{ $ingredient->ingredient_id }}"
                                       min="1" value="1"
                                       class="ing-qty-input">
                                <span class="ing-qty-unit">{{ $ingredient->unit }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Add new ingredient inline --}}
                    <div class="new-ing-section">
                        <div class="new-ing-heading">+ New ingredient</div>
                        <div class="new-ing-row">
                            <div class="new-ing-col-name">
                                <div class="new-ing-col-label">Name</div>
                                <input type="text" id="new-ing-name" placeholder="e.g. Chocolate Syrup"
                                       class="new-ing-input">
                            </div>
                            <div class="new-ing-col-unit">
                                <div class="new-ing-col-label">Unit</div>
                                <input type="text" id="new-ing-unit" placeholder="ml / grams"
                                       class="new-ing-input">
                            </div>
                            <div class="new-ing-col-stock">
                                <div class="new-ing-col-label">Stock</div>
                                <input type="number" id="new-ing-stock" placeholder="0" min="0"
                                       class="new-ing-input">
                            </div>
                            <button type="button" onclick="addIngredient()"
                                    class="btn btn-add-ing">
                                Add ↗
                            </button>
                        </div>
                        <div id="new-ing-error" class="new-ing-error"></div>
                    </div>

                    <div class="action-row action-row-mt">
                        <button type="submit" class="btn-place">Add product ↗</button>
                        <a href="/products" class="btn-clear btn-discard">Discard</a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Fix peso sign layout --}}
<style>
    .price-wrap {
        display: flex;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--bg);
        transition: box-shadow 0.15s, border-color 0.15s;
    }
    .price-wrap:focus-within {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(200,119,58,0.12);
    }
    .price-prefix {
        padding: 0 10px 0 13px;
        font-size: 14px;
        font-weight: 600;
        color: var(--muted);
        background: transparent;
        user-select: none;
        line-height: 1;
        flex-shrink: 0;
    }
    .price-input {
        flex: 1;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        outline: none !important;
        padding-left: 0 !important;
        background: transparent !important;
    }
    /* Remove the default focus ring from the input since price-wrap handles it */
    .price-input:focus {
        border: none !important;
        box-shadow: none !important;
    }
</style>

<script>
    function toggleQty(id) {
        const cb   = document.getElementById('ing-' + id);
        const wrap = document.getElementById('qty-wrap-' + id);
        wrap.style.display = cb.checked ? 'flex' : 'none';
        if (!cb.checked) document.getElementById('qty-' + id).value = 1;
    }

    async function addIngredient() {
        const name  = document.getElementById('new-ing-name').value.trim();
        const unit  = document.getElementById('new-ing-unit').value.trim();
        const stock = document.getElementById('new-ing-stock').value.trim();
        const errEl = document.getElementById('new-ing-error');

        errEl.style.display = 'none';

        if (!name || !unit) {
            errEl.textContent = 'Name and unit are required.';
            errEl.style.display = 'block';
            return;
        }

        const btn = document.querySelector('button[onclick="addIngredient()"]');
        btn.disabled    = true;
        btn.textContent = 'Adding…';

        try {
            const res  = await fetch('/ingredients', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ingredient_name: name, unit, stock_level: stock || 0 })
            });

            const data = await res.json();

            if (!res.ok) {
                errEl.textContent = data.message ?? 'Something went wrong.';
                errEl.style.display = 'block';
                return;
            }

            const list = document.getElementById('ingredient-list');
            const div  = document.createElement('div');
            div.className = 'menu-item ing-row';
            div.innerHTML = `
                <div class="ing-check-wrap">
                    <input type="checkbox"
                           name="ingredients[${data.ingredient_id}][selected]"
                           value="1"
                           id="ing-${data.ingredient_id}"
                           onchange="toggleQty(${data.ingredient_id})"
                           checked
                           class="ing-checkbox">
                    <label for="ing-${data.ingredient_id}" class="ing-label">
                        ${data.ingredient_name}
                        <span class="ing-unit-text">(${data.unit})</span>
                    </label>
                </div>
                <div id="qty-wrap-${data.ingredient_id}" class="ing-qty-wrap">
                    <input type="number"
                           name="ingredients[${data.ingredient_id}][quantity_used]"
                           id="qty-${data.ingredient_id}"
                           min="1" value="1"
                           class="ing-qty-input">
                    <span class="ing-qty-unit">${data.unit}</span>
                </div>
            `;
            list.appendChild(div);

            document.getElementById('new-ing-name').value  = '';
            document.getElementById('new-ing-unit').value  = '';
            document.getElementById('new-ing-stock').value = '';

        } catch (e) {
            errEl.textContent = 'Network error. Please try again.';
            errEl.style.display = 'block';
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Add ↗';
        }
    }
</script>
@endsection