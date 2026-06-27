<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 15px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .voucher-card {
            border: 2px dashed #000;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            min-height: 175px;
            page-break-inside: avoid;
        }

        .network-header {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            border-bottom: 2px solid;
            padding-bottom: 3px;
        }

        /* Network Color Accents */
        .network-mtn { border-color: #facc15; color: #a16207; }
        .network-airtel { border-color: #ef4444; color: #b91c1c; }
        .network-glo { border-color: #22c55e; color: #15803d; }
        .network-9mobile { border-color: #0f766e; color: #0f766e; }

        .value-badge {
            font-size: 20px;
            font-weight: 800;
            margin: 5px 0;
        }

        .pin-section {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            margin: 8px 0;
        }

        .pin-label {
            font-size: 8px;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .pin-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .serial-section {
            font-size: 10px;
            color: #374151;
            margin-bottom: 8px;
        }

        .instructions {
            font-size: 8px;
            color: #4b5563;
            line-height: 1.3;
            margin-top: 5px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        .business-footer {
            font-size: 8px;
            font-weight: bold;
            color: #9ca3af;
            text-align: center;
            margin-top: 5px;
            text-transform: uppercase;
        }

        /* Print styling rules */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .voucher-grid {
                gap: 10px;
            }
            .voucher-card {
                border-style: dashed;
                border-color: #000;
            }
        }
        
        .print-btn-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .print-btn {
            background-color: #4f46e5;
            color: white;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .print-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <div class="print-btn-container no-print">
        <button class="print-btn" onclick="window.print()">Send to Printer</button>
    </div>

    <div class="voucher-grid">
        @foreach($vouchers as $v)
        @php
            $netKey = strtolower($v->network);
            $netClass = 'network-' . $netKey;
        @endphp
        <div class="voucher-card">
            <div>
                <div class="network-header {{ $netClass }}">
                    {{ $v->network }} {{ $v->type === 'data' ? 'Data' : 'Airtime' }}
                </div>
                <div class="value-badge">
                    ₦{{ number_format($v->value) }}
                </div>
            </div>

            <div class="pin-section">
                <div class="pin-label">Recharge PIN</div>
                <div class="pin-code">{{ chunk_split($v->pin, 4, ' ') }}</div>
            </div>

            <div>
                <div class="serial-section">
                    <strong>S/N:</strong> {{ $v->serial_number }}
                    <span style="float: right;"><strong>Date:</strong> {{ $v->created_at->format('d/m/y') }}</span>
                </div>

                <div class="instructions">
                    @if($v->type === 'data')
                        Dial network-specific data balance code to verify allocation.
                    @else
                        @if($netKey === 'mtn') Dial *555*PIN# to load @elseif($netKey === 'airtel') Dial *126*PIN# to load @elseif($netKey === 'glo') Dial *123*PIN# to load @else Dial *222*PIN# to load @endif
                    @endif
                </div>

                @if($v->name_on_card)
                <div class="business-footer">
                    Powered by: {{ $v->name_on_card }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <script>
        // Trigger print dialog automatically when loaded
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
