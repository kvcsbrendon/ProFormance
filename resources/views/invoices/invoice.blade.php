<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
            padding: 40px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .invoice-table thead th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #6b7280;
        }
        .invoice-table thead th:last-child,
        .invoice-table thead th:nth-child(3),
        .invoice-table thead th:nth-child(4) {
            text-align: right;
        }
        .invoice-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 12px;
        }
        .invoice-table tbody td:last-child,
        .invoice-table tbody td:nth-child(3),
        .invoice-table tbody td:nth-child(4) {
            text-align: right;
        }
        .invoice-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .item-sku {
            color: #9ca3af;
            font-size: 10px;
        }
        .item-gift-tag {
            color: #92400e;
            background: #fef3c7;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .invoice-payment-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .invoice-payment-info strong {
            color: #111827;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }
        .invoice-footer p {
            margin-bottom: 3px;
        }

        .flex-table { width: 100%; }
        .flex-table td { vertical-align: top; padding: 0; }

        .gift-note-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 11px;
            color: #92400e;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <table class="flex-table">
        <tr>
            <td style="width: 50%;">
                <h1 style="font-size: 28px; font-weight: 700; color: #EB7347; margin-bottom: 4px;">ProFormance</h1>
                <p style="color: #6b7280; font-size: 11px;">Performance-driven fitness gear &amp; nutrition</p>
            </td>
            <td style="width: 50%; text-align: right;">
                <h2 style="font-size: 22px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #111827;">Invoice</h2>
                <p style="font-size: 13px; color: #6b7280; margin-top: 4px;">{{ $order->order_number }}</p>
                <p style="font-size: 12px; color: #6b7280;">{{ $order->created_at->format('d F Y') }}</p>
            </td>
        </tr>
    </table>

    <hr style="border: none; border-top: 3px solid #EB7347; margin: 20px 0 30px;">

    {{-- ── Addresses ── --}}
    <table class="flex-table" style="margin-bottom: 30px;">
        <tr>
            <td style="width: 48%;">
                <h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #EB7347; margin-bottom: 8px; font-weight: 700;">Bill To</h3>
                <p style="font-size: 12px; line-height: 1.6; color: #374151;">
                    {{ $order->bill_recipient_name }}<br>
                    @if($order->bill_house_number){{ $order->bill_house_number }} @endif{{ $order->bill_address_line_one }}<br>
                    @if($order->bill_address_line_two){{ $order->bill_address_line_two }}<br>@endif
                    {{ $order->bill_city }}@if($order->bill_county), {{ $order->bill_county }}@endif<br>
                    {{ $order->bill_postcode }}<br>
                    {{ $order->bill_country_code }}
                </p>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #EB7347; margin-bottom: 8px; font-weight: 700;">Ship To</h3>

                @if($isPureGift)
                    {{-- Pure gift: hide recipient address --}}
                    <p style="font-size: 12px; line-height: 1.6; color: #374151;">
                        Gift Delivery<br>
                        Shipped to recipient's address<br>
                        {{ $order->ship_country_code }}
                    </p>
                    <div class="gift-note-box">Gift Order</div>

                @elseif($isMixedOrder)
                    {{-- Mixed: show buyer's address, note about gift items --}}
                    <p style="font-size: 12px; line-height: 1.6; color: #374151;">
                        {{ $order->ship_recipient_name }}<br>
                        @if($order->ship_house_number){{ $order->ship_house_number }} @endif{{ $order->ship_address_line_one }}<br>
                        @if($order->ship_address_line_two){{ $order->ship_address_line_two }}<br>@endif
                        {{ $order->ship_city }}@if($order->ship_county), {{ $order->ship_county }}@endif<br>
                        {{ $order->ship_postcode }}<br>
                        {{ $order->ship_country_code }}
                    </p>
                    <div class="gift-note-box">
                        Gift items delivered separately to recipient
                    </div>

                @else
                    {{-- Normal order --}}
                    <p style="font-size: 12px; line-height: 1.6; color: #374151;">
                        {{ $order->ship_recipient_name }}<br>
                        @if($order->ship_house_number){{ $order->ship_house_number }} @endif{{ $order->ship_address_line_one }}<br>
                        @if($order->ship_address_line_two){{ $order->ship_address_line_two }}<br>@endif
                        {{ $order->ship_city }}@if($order->ship_county), {{ $order->ship_county }}@endif<br>
                        {{ $order->ship_postcode }}<br>
                        {{ $order->ship_country_code }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Items Table ── --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: {{ $isMixedOrder ? '38%' : '45%' }};">Item</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%; text-align: right;">Unit Price</th>
                <th style="width: 12%; text-align: right;">Tax</th>
                <th style="width: 18%; text-align: right;">Line Total</th>
                @if($isMixedOrder)
                    <th style="width: 7%; text-align: center;">Gift</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $unitNet   = $item->unit_price_penny / 100;
                    $lineNet   = $unitNet * $item->quantity;
                    $lineTax   = $lineNet * $item->tax_rate;
                    $lineGross = $lineNet + $lineTax;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $item->title }}</strong><br>
                        <span class="item-sku">SKU: {{ $item->sku }}</span>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ $symbol }}{{ number_format($unitNet, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->tax_rate * 100, 0) }}%</td>
                    <td style="text-align: right;">{{ $symbol }}{{ number_format($lineGross, 2) }}</td>
                    @if($isMixedOrder)
                        <td style="text-align: center;">
                            @if($item->is_gift)
                                <span class="item-gift-tag">Gift</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    <table style="width: 280px; margin-left: auto; margin-bottom: 30px;">
        <tr>
            <td style="padding: 5px 0; font-size: 12px;">Subtotal (excl. VAT)</td>
            <td style="padding: 5px 0; font-size: 12px; text-align: right;">{{ $symbol }}{{ number_format($order->subtotal_penny / 100, 2) }}</td>
        </tr>

        @if($order->discount_penny > 0)
            <tr>
                <td style="padding: 5px 0; font-size: 12px; color: #16a34a;">Discount</td>
                <td style="padding: 5px 0; font-size: 12px; text-align: right; color: #16a34a;">
                    -{{ $symbol }}{{ number_format($order->discount_penny / 100, 2) }}
                </td>
            </tr>
        @endif

        <tr>
            <td style="padding: 5px 0; font-size: 12px;">Shipping</td>
            <td style="padding: 5px 0; font-size: 12px; text-align: right;">{{ $symbol }}{{ number_format($order->shipping_penny / 100, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0; font-size: 12px;">VAT</td>
            <td style="padding: 5px 0; font-size: 12px; text-align: right;">{{ $symbol }}{{ number_format($order->tax_penny / 100, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2"><hr style="border: none; border-top: 2px solid #111827; margin: 4px 0;"></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-size: 16px; font-weight: 700;">Total</td>
            <td style="padding: 8px 0; font-size: 16px; font-weight: 700; text-align: right;">{{ $symbol }}{{ number_format($order->total_penny / 100, 2) }}</td>
        </tr>
    </table>

    {{-- ── Payment / Status ── --}}
    <div class="invoice-payment-info">
        <strong>Order Status:</strong> {{ $order->order_status }} &nbsp;&bull;&nbsp;
        <strong>Currency:</strong> {{ strtoupper($order->currency_code) }} &nbsp;&bull;&nbsp;
        <strong>Order Date:</strong> {{ $order->created_at->format('d/m/Y H:i') }}
    </div>

    {{-- ── VAT Note ── --}}
    @if(strtoupper($order->ship_country_code) !== 'GB')
        <p style="font-size: 10px; color: #6b7280; margin-bottom: 20px;">
            VAT at 0% for export orders. Import duties and taxes may apply in the destination country.
        </p>
    @endif

    {{-- ── Gift note ── --}}
    @if($hasGiftItems)
        <p style="font-size: 10px; color: #6b7280; margin-bottom: 20px;">
            Gift items are delivered directly to the recipient. Recipient address details are not included on this invoice for privacy.
        </p>
    @endif

    {{-- ── Footer ── --}}
    <div class="invoice-footer">
        <p><strong>ProFormance</strong></p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>For queries, contact support@proformance.com</p>
        <p>&copy; {{ date('Y') }} ProFormance. All rights reserved.</p>
    </div>

</body>
</html>