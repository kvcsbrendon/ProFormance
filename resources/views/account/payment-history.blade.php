{{-- resources/views/account/payment-history.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-page">
    <div class="kb-account-container">

        {{-- Page header --}}
        <div class="kb-account-header">
            <h1 class="kb-account-heading">Subscription Payments</h1>
            <p class="kb-account-subheading">Your billing history for ProFormance Plus.</p>
        </div>

        {{-- Stats --}}
        <div class="kb-payments-stats">
            <div class="kb-payments-stat-card">
                <div class="kb-payments-stat-label">Total Paid</div>
                <div class="kb-payments-stat-value">{{ $symbol }}{{ number_format(($totalPaid ?? 0) / 100, 2) }}</div>
            </div>
            <div class="kb-payments-stat-card">
                <div class="kb-payments-stat-label">Payments</div>
                <div class="kb-payments-stat-value">{{ $paymentCount ?? 0 }}</div>
            </div>
        </div>

        {{-- Table --}}
        <div class="kb-payments-table-wrap">
            @if($payments->isEmpty())
                <div class="kb-payments-empty">
                    <i class="bi bi-receipt"></i>
                    <p>No subscription payments yet.</p>
                </div>
            @else
                <table class="kb-payments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Billing Period</th>
                            <th>Paid With</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>
                                    {{ $payment->created_at->format('d M Y') }}
                                    <div style="font-size:11px;color:var(--kb-secondary-font);">
                                        {{ $payment->created_at->format('H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $payment->plan->name ?? 'Subscription' }}</strong>
                                </td>
                                <td>
                                    @if($payment->period_start && $payment->period_end)
                                        {{ $payment->period_start->format('d M Y') }}
                                        <br>
                                        <span style="font-size:11px;color:var(--kb-secondary-font);">
                                            to {{ $payment->period_end->format('d M Y') }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($payment->savedCard)
                                        <span style="display:flex;align-items:center;gap:4px;">
                                            <i class="bi bi-credit-card"></i>
                                            {{ ucfirst($payment->savedCard->card_brand) }}
                                            •••• {{ $payment->savedCard->last_four }}
                                        </span>
                                    @else
                                        {{ ucfirst($payment->payment_method) }}
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size:12px;font-family:monospace;">
                                        {{ $payment->provider_ref }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($payment->status) {
                                            'Paid' => 'kb-badge-success',
                                            'Refunded' => 'kb-badge-warning',
                                            'Failed' => 'kb-badge-danger',
                                            default => 'kb-badge-secondary',
                                        };
                                    @endphp
                                    <span class="kb-badge {{ $statusClass }}">
                                        {{ $payment->status }}
                                    </span>
                                </td>
                                <td style="text-align:right;font-weight:600;">
                                    {{ $symbol }}{{ number_format($payment->amount_penny / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Pagination --}}
        @if($payments->hasPages())
            <div style="margin-top: 1rem;" class="kb-admin-pagination">{{ $payments->links() }}</div>
        @endif

        <div style="margin-top: 1rem;">
            <a href="{{ route('account.subscription') }}" class="kb-account-btn kb-account-btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Subscription
            </a>
        </div>
    </div>
</div>

<style>
    .kb-payments-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .kb-payments-stat-card {
        background: var(--kb-card-bg, var(--kb-primary-bg, #fff));
        border: 1px solid var(--kb-button-border, #e5e7eb);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .kb-payments-stat-label {
        font-size: 12px;
        color: var(--kb-secondary-font, #6b7280);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
    }
    .kb-payments-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--kb-primary-font, #111);
        margin-top: 2px;
    }
    .kb-payments-table-wrap {
        background: var(--kb-card-bg, var(--kb-primary-bg, #fff));
        border: 1px solid var(--kb-button-border, #e5e7eb);
        border-radius: 10px;
        overflow: hidden;
    }
    .kb-payments-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .kb-payments-table th {
        text-align: left;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: var(--kb-secondary-font, #6b7280);
        border-bottom: 1px solid var(--kb-button-border, #e5e7eb);
        background: var(--kb-grey-50, #f9fafb);
    }
    .kb-payments-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--kb-button-border, #e5e7eb);
        color: var(--kb-primary-font, #111);
    }
    .kb-payments-table tr:last-child td { border-bottom: none; }
    .kb-payments-table tr:hover td { background: var(--kb-grey-50, #f9fafb); }
    .kb-payments-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--kb-secondary-font, #6b7280);
    }
    .kb-payments-empty i { font-size: 2rem; margin-bottom: 0.5rem; display: block; }
    .kb-badge-success { background: #f0fdf4; color: #16a34a; }
    .kb-badge-warning { background: #fffbeb; color: #d97706; }
    .kb-badge-danger { background: #fef2f2; color: #dc2626; }
    .kb-badge-secondary { background: #f3f4f6; color: #6b7280; }

    @media (max-width: 768px) {
        .kb-payments-table th:nth-child(5),
        .kb-payments-table td:nth-child(5) { display: none; }
    }
    @media (max-width: 480px) {
        .kb-payments-table th:nth-child(3),
        .kb-payments-table td:nth-child(3),
        .kb-payments-table th:nth-child(4),
        .kb-payments-table td:nth-child(4) { display: none; }
        .kb-payments-table td { padding: 10px; font-size: 13px; }
    }
</style>
@endsection