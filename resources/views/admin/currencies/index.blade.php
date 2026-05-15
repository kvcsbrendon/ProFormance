{{-- resources/views/admin/currencies/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Currencies</h1>
    <p class="kb-admin-subtitle">Manage accepted currencies. Product prices are set per-variant on the product edit page.</p>
</div>

<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">Active Currencies</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Code</th><th>Name</th><th>Symbol</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @foreach($currencies as $c)
                <tr>
                    <form method="POST" action="{{ route('admin.currencies.update', $c->currency_code) }}">
                        @csrf @method('PUT')
                        <td class="kb-admin-mono"><strong>{{ $c->currency_code }}</strong></td>
                        <td><input type="text" name="currency_name" class="kb-form-input kb-form-input-sm" value="{{ $c->currency_name }}" required style="width:180px;"></td>
                        <td><input type="text" name="symbol" class="kb-form-input kb-form-input-sm" value="{{ $c->symbol }}" required style="width:60px;"></td>
                        <td>
                            <select name="is_active" class="kb-form-input kb-form-input-sm" style="width:70px;">
                                <option value="1" {{ $c->is_active ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$c->is_active ? 'selected' : '' }}>No</option>
                            </select>
                        </td>
                        <td><button type="submit" class="kb-admin-btn-sm">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-plus-circle"></i> Add Currency</h3>
    <form method="POST" action="{{ route('admin.currencies.store') }}">
        @csrf
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Code * (3 letters)</label>
                <input type="text" name="currency_code" class="kb-form-input" required maxlength="3" placeholder="e.g. EUR" style="text-transform:uppercase;">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Name *</label>
                <input type="text" name="currency_name" class="kb-form-input" required placeholder="e.g. Euro">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Symbol *</label>
                <input type="text" name="symbol" class="kb-form-input" required placeholder="e.g. €" maxlength="10">
            </div>
        </div>
        <div class="kb-admin-form-actions">
            <button type="submit" class="kb-admin-btn"><i class="bi bi-plus-circle"></i> Add Currency</button>
        </div>
    </form>
</div>
@endsection
