<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport du {{ $formatted_date }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            margin: 20px; 
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #333; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            margin: 0; 
            color: #2c3e50;
            font-size: 24px;
        }
        .header p { 
            margin: 5px 0 0 0; 
            color: #7f8c8d;
        }
        .metrics { 
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .metric-card { 
            display: table-cell;
            border: 1px solid #ddd; 
            padding: 15px; 
            text-align: center; 
            vertical-align: middle;
        }
        .metric-card h3 { 
            margin: 0 0 8px 0; 
            color: #666; 
            font-size: 12px; 
            text-transform: uppercase;
        }
        .metric-value { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 0; 
        }
        .section { 
            margin-bottom: 20px; 
            page-break-inside: avoid;
        }
        .section-title { 
            background: #f8f9fa; 
            padding: 8px 12px; 
            border-left: 4px solid #007bff; 
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: bold;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
            font-size: 12px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f8f9fa; 
            font-weight: bold;
        }
        .revenue-breakdown {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .revenue-item {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .revenue-total {
            background-color: #e3f2fd;
            font-weight: bold;
        }
        .menu-item {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .category-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 8px;
        }
        .category-repas { background: #e3f2fd; color: #1976d2; }
        .category-boisson { background: #f3e5f5; color: #7b1fa2; }
        .hourly-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .hour-item {
            display: table-cell;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 10px;
        }
        .hour-item.active {
            background-color: #4caf50;
            color: white;
        }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            color: #666; 
            font-size: 10px; 
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-success { color: #28a745; }
        .text-primary { color: #007bff; }
        .text-center { text-align: center; }
        .mb-3 { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport du {{ $formatted_date }}</h1>
        <p>Généré le {{ $generated_at }} par {{ $admin->name }}</p>
    </div>

    <!-- Métriques principales -->
    <div class="metrics">
        <div class="metric-card">
            <h3>Commandes totales</h3>
            <p class="metric-value text-primary">{{ $total_orders }}</p>
        </div>
        <div class="metric-card">
            <h3>Chiffre d'affaires</h3>
            <p class="metric-value text-success">{{ number_format($total_revenue, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="metric-card">
            <h3>Panier moyen</h3>
            <p class="metric-value">{{ $total_orders > 0 ? number_format($total_revenue / $total_orders, 0, ',', ' ') : 0 }} FCFA</p>
        </div>
    </div>

    <!-- Analyse des revenus -->
    <div class="section">
        <div class="section-title">Analyse des Revenus</div>
        <div class="revenue-breakdown">
            <div class="revenue-item">
                <strong>Sur place</strong><br>
                <span class="text-success">{{ number_format($revenue_analysis['sur_place'], 0, ',', ' ') }} FCFA</span><br>
                <small>{{ $revenue_analysis['total'] > 0 ? round(($revenue_analysis['sur_place'] / $revenue_analysis['total']) * 100) : 0 }}%</small>
            </div>
            <div class="revenue-item">
                <strong>Livraison</strong><br>
                <span class="text-success">{{ number_format($revenue_analysis['livraison'], 0, ',', ' ') }} FCFA</span><br>
                <small>{{ $revenue_analysis['total'] > 0 ? round(($revenue_analysis['livraison'] / $revenue_analysis['total']) * 100) : 0 }}%</small>
            </div>
            <div class="revenue-item revenue-total">
                <strong>Total</strong><br>
                <span class="text-primary">{{ number_format($revenue_analysis['total'], 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
    </div>

    <!-- Performance du menu -->
    <div class="section">
        <div class="section-title">Performance du Menu</div>
        <table>
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Catégorie</th>
                    <th>Quantité</th>
                    <th>Revenus</th>
                    <th>Commandes</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($menu_performance, 0, 10) as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>
                        <span class="category-badge category-{{ $item['category'] }}">
                            {{ $item['category'] }}
                        </span>
                    </td>
                    <td>{{ $item['totalQuantity'] }}</td>
                    <td class="text-success">{{ number_format($item['totalRevenue'], 0, ',', ' ') }} FCFA</td>
                    <td>{{ $item['orders'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Statut des commandes -->
    <div class="section">
        <div class="section-title">Statut des Commandes</div>
        <table>
            <tr>
                <th>Statut</th>
                <th>Nombre</th>
            </tr>
            @foreach($order_status as $status => $count)
            <tr>
                <td>
                    @switch($status)
                        @case('terminé') ✅ Terminé @break
                        @case('livré') 🚚 Livré @break
                        @case('en_cours') ⏳ En cours @break
                        @case('prêt') ✅ Prêt @break
                        @case('commandé') 📝 Commandé @break
                        @default {{ ucfirst(str_replace('_', ' ', $status)) }}
                    @endswitch
                </td>
                <td class="text-center"><strong>{{ $count }}</strong></td>
            </tr>
            @endforeach
        </table>
    </div>

    <!-- Commandes par heure -->
    <div class="section">
        <div class="section-title">Commandes par Heure</div>
        <div class="hourly-grid">
            @foreach($orders_by_hour as $hour)
            <div class="hour-item {{ $hour['count'] > 0 ? 'active' : '' }}">
                <div><strong>{{ $hour['hour'] }}</strong></div>
                <div>{{ $hour['count'] }} cmd</div>
                @if($hour['revenue'] > 0)
                <div>{{ number_format($hour['revenue'], 0, ',', ' ') }} FCFA</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        <p>Rapport généré automatiquement par le système de gestion</p>
    </div>
</body>
</html>