<?php
require_once 'includes/db.php';
$title = 'Inventar';

$stmt = $pdo->query("
    SELECT ptcg_id,
    COALESCE(MAX(CASE WHEN `language` = 'Deutsch' THEN name END), MAX(name)) AS name,
    COALESCE(MAX(CASE WHEN `language` = 'Deutsch' THEN set_name END), MAX(set_name)) AS set_name,
    MAX(rarity) AS rarity,
    MAX(number) AS number,
    SUM(quantity) AS total_quantity,
    MIN(buy_price) AS buy_price,
    MIN(sell_price) AS sell_price,
    MIN(buying_time) AS buying_time,
    GROUP_CONCAT(DISTINCT `language` SEPARATOR ', ') as language
    FROM cards 
    WHERE quantity > 0
    GROUP BY ptcg_id
    ORDER BY buying_time DESC
");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php'
?>


<h1>Pokemon Shop Inventar</h1>

<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
        <th>Nummer</th>
        <th>Set</th>
        <th>Rarität</th>
        <th>Anzahl</th>
        <th>minimaler Einkaufspreis</th>
        <th>minimaler Verkaufspreis</th>
        <th>Sprache</th>
    </tr>

    <?php foreach ($cards as $card): ?>
    <tr onclick="window.location='card.php?ptcg_id=<?= urlencode($card['ptcg_id'])?>'" style="cursor:pointer;">
        <td class="center"><?=htmlspecialchars($card['name'])  ?></td>
        <td class="center"><?=htmlspecialchars($card['number'])  ?></td>
        <td class="center"><?=htmlspecialchars($card['set_name'])  ?></td>
        <td class="center"><?=htmlspecialchars($card['rarity'])  ?></td>
        <td class="center"><?=htmlspecialchars($card['total_quantity'])  ?></td>
        <td class="center"><?=number_format($card['buy_price'], 2, ',', '.') ?> €</td>
        <td class="center"><?=number_format($card['sell_price'], 2, ',', '.') ?> €</td>
        <td class="center"><?=htmlspecialchars($card['language']) ?></td>
    </tr> 
    <?php endforeach; ?>

</table>
<?php require_once 'includes/footer.php'; ?>