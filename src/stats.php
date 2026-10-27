<?php
require_once 'includes/db.php';
$title = 'Statistiken';

// Overall sales stats
$overall = $pdo->query("
    SELECT 
        COUNT(*) as total_sales,
        SUM(quantity) as total_cards_sold,
        SUM(sold_price * quantity) as total_revenue
    FROM sales
")->fetch(PDO::FETCH_ASSOC);

// Sales by card
$by_card = $pdo->query("
    SELECT 
        c.name,
        c.set_name,
        c.number,
        c.buy_price,
        SUM(s.quantity) as total_sold,
        SUM(s.sold_price * s.quantity) as total_revenue,
        SUM((s.sold_price - c.buy_price) * s.quantity) as total_profit
    FROM sales s
    JOIN cards c ON s.card_id = c.id
    GROUP BY s.card_id, c.name, c.set_name, c.number, c.buy_price
    ORDER BY total_profit DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Sales by platform
$by_platform = $pdo->query("
    SELECT 
        platform,
        COUNT(*) as total_sales,
        SUM(sold_price * quantity) as total_revenue
    FROM sales
    GROUP BY platform
    ORDER BY total_revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<h1>Statistiken</h1>

<!-- Overall stats -->
<div style="display:flex; gap:16px; margin-bottom:32px;">
    <div style="background:var(--color-surface); padding:24px; border-radius:8px; flex:1; text-align:center;">
        <div style="font-size:2rem; color:var(--color-gold); font-weight:bold;">
            <?= (int)($overall['total_cards_sold'] ?? 0) ?>
        </div>
        <div>Karten verkauft</div>
    </div>
    <div style="background:var(--color-surface); padding:24px; border-radius:8px; flex:1; text-align:center;">
        <div style="font-size:2rem; color:var(--color-gold); font-weight:bold;">
            <?= number_format($overall['total_revenue'] ?? 0, 2, ',', '.') ?> €
        </div>
        <div>Gesamtumsatz</div>
    </div>
</div>

<!-- Sales by card -->
<h2 style="margin-bottom:16px;">Verkäufe nach Karte</h2>
<table>
    <tr>
        <th>Name</th>
        <th>Set</th>
        <th>Nummer</th>
        <th>Verkauft</th>
        <th>Umsatz</th>
        <th>Einkauf gesamt</th>
        <th>Gewinn</th>
    </tr>
    <?php foreach ($by_card as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['set_name']) ?></td>
        <td><?= htmlspecialchars($row['number']) ?></td>
        <td><?= (int)$row['total_sold'] ?></td>
        <td><?= number_format($row['total_revenue'], 2, ',', '.') ?> €</td>
        <td><?= number_format($row['buy_price'] * $row['total_sold'], 2, ',', '.') ?> €</td>
        <td style="color:<?= $row['total_profit'] >= 0 ? '#5cb85c' : '#e74c3c' ?>">
            <?= number_format($row['total_profit'], 2, ',', '.') ?> €
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Sales by platform -->
<h2 style="margin:32px 0 16px;">Verkäufe nach Plattform</h2>
<table>
    <tr>
        <th>Plattform</th>
        <th>Anzahl Verkäufe</th>
        <th>Umsatz</th>
    </tr>
    <?php foreach ($by_platform as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['platform']) ?></td>
        <td><?= (int)$row['total_sales'] ?></td>
        <td><?= number_format($row['total_revenue'], 2, ',', '.') ?> €</td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once 'includes/footer.php'; ?>